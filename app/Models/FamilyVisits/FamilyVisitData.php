<?php

namespace App\Models\FamilyVisits;

use App\Enums\Role;
use App\Enums\VisitStatus;
use App\Exceptions\SupabaseUnavailable;
use App\Exceptions\VisitConflict;
use App\Models\SupabaseUser;
use App\Services\SupabaseClient;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Client\Response;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/** Batas akses data: muat relasi aman melalui PostgREST dengan JWT pengguna dan RLS. */
class FamilyVisitData
{
    private const VISIT_COLUMNS = 'id,resident_id,requester_id,requested_at,requested_end_at,visitor_count,primary_visitor_name,note,status,reviewed_by,reviewed_at,review_note,created_at,resident:residents!visit_requests_resident_id_fkey(id,full_name,room,status),requester:profiles!visit_requests_requester_id_fkey(id,full_name)';

    public function __construct(private SupabaseClient $client) {}

    private function user(): SupabaseUser
    {
        $user = auth()->user();
        abort_unless($user instanceof SupabaseUser, 401);

        return $user;
    }

    /** @param array<string, mixed> $data */
    private function send(string $method, string $path, array $data = []): Response
    {
        $this->user();
        $token = request()->session()->get('supabase.access_token');
        abort_unless(is_string($token) && $token !== '', 401);
        $response = $this->client->request($method, '/rest/v1/'.$path, $data, $token, $method === 'GET' && $path === 'visit_requests' ? ['Prefer' => 'count=exact'] : []);
        if ($response->status() === 409) {
            throw new VisitConflict;
        }
        abort_if(in_array($response->status(), [401, 403], true), 403);
        abort_if($response->status() === 404, 404);
        if ($response->status() === 422) {
            if ($path === 'rpc/update_my_profile') {
                throw ValidationException::withMessages(['full_name' => 'Profil belum dapat disimpan. Periksa kembali data akun.']);
            }
            throw ValidationException::withMessages(['visit' => 'Detail kunjungan tidak valid. Periksa waktu, jumlah, dan status pengajuan.']);
        }
        if (! $response->successful()) {
            throw new SupabaseUnavailable;
        }

        return $response;
    }

    /** @return list<Resident> */
    public function residents(): array
    {
        $rows = $this->send('GET', 'family_links', [
            'select' => 'id,user_id,resident_id,relationship,status,verified_at,resident:residents!family_links_resident_id_fkey(id,full_name,birth_date,room,status,profile_photo_path)',
            'user_id' => 'eq.'.$this->user()->id(), 'status' => 'eq.verified', 'order' => 'verified_at.desc', 'limit' => 25,
        ])->json();
        $residents = [];
        foreach ($rows as $row) {
            // Data penghuni kosong jika RLS atau persetujuan tidak lagi mengizinkan akses.
            if (! is_array($row['resident'] ?? null) || $row['resident']['status'] !== 'active') {
                continue;
            }
            $resident = $this->hydrate(Resident::class, $row['resident']);
            unset($row['resident']);
            $resident->setRelation('familyLink', $this->hydrate(FamilyLink::class, $row));
            $residents[] = $resident;
        }

        return $residents;
    }

    public function resident(string $id): Resident
    {
        foreach ($this->residents() as $resident) {
            if ($resident->id === $id) {
                return $resident;
            }
        }
        abort(404);
    }

    public function findVisit(string $id): VisitRequest
    {
        abort_unless(Str::isUuid($id), 404);
        $query = ['select' => self::VISIT_COLUMNS, 'id' => 'eq.'.$id, 'limit' => 1];
        if ($this->user()->role() === Role::Family) {
            $query['requester_id'] = 'eq.'.$this->user()->id();
        } elseif ($this->user()->role() === Role::Admin) {
            $query['select'] .= ',reviewer:profiles!visit_requests_reviewed_by_fkey(id,full_name)';
        }
        $row = $this->send('GET', 'visit_requests', $query)->json('0');
        abort_unless(is_array($row), 404);

        return $this->visit($row);
    }

    /** @return LengthAwarePaginator<int, VisitRequest> */
    public function visits(?string $residentId, ?string $status, int $page): LengthAwarePaginator
    {
        $query = ['select' => self::VISIT_COLUMNS, 'order' => 'requested_at.desc,id.asc', 'limit' => 25, 'offset' => ($page - 1) * 25];
        if ($this->user()->role() === Role::Family) {
            $query['requester_id'] = 'eq.'.$this->user()->id();
        }
        if ($residentId !== null) {
            $query['resident_id'] = 'eq.'.$residentId;
        }
        if ($status !== null) {
            $query['status'] = 'eq.'.$status;
        }
        $response = $this->send('GET', 'visit_requests', $query);
        // Jumlah data persis diminta lewat header klien PostgREST, bukan parameter kueri.
        $total = (int) substr(strrchr($response->header('Content-Range') ?: '0/0', '/') ?: '/0', 1);
        $rows = $response->json();
        if ($response->header('Content-Range') === '') {
            $total = count($rows) + (($page - 1) * 25);
        }

        return new LengthAwarePaginator(array_map(fn ($row) => $this->visit($row), $rows), $total, 25, $page,
            ['path' => request()->url(), 'query' => request()->query()]);
    }

    /** @return array<string, int> */
    public function counts(): array
    {
        $rows = $this->send('POST', 'rpc/family_visit_counts')->json();
        $counts = array_fill_keys(array_column(VisitStatus::cases(), 'value'), 0);
        foreach ($rows as $row) {
            if (array_key_exists($row['status'], $counts)) {
                $counts[$row['status']] = (int) $row['total'];
            }
        }

        return $counts;
    }

    /** @return array<string, mixed> */
    public function account(): array
    {
        return $this->send('GET', 'profiles', ['select' => 'id,full_name,email,phone,avatar_path', 'id' => 'eq.'.$this->user()->id(), 'limit' => 1])->json('0') ?? [];
    }

    public function avatarPath(string $profileId): ?string
    {
        abort_unless(Str::isUuid($profileId), 404);
        $row = $this->send('GET', 'profiles', [
            'select' => 'avatar_path', 'id' => 'eq.'.$profileId, 'limit' => 1,
        ])->json('0');

        return is_string($row['avatar_path'] ?? null) ? $row['avatar_path'] : null;
    }

    public function updateAccount(string $name, ?string $phone): void
    {
        $this->send('POST', 'rpc/update_my_profile', ['p_full_name' => $name, 'p_phone' => $phone]);
    }

    public function photoConsent(string $residentId): bool
    {
        $rows = $this->send('GET', 'consents', ['select' => 'id', 'resident_id' => 'eq.'.$residentId, 'scope' => 'eq.photo_share', 'status' => 'eq.granted', 'limit' => 1])->json();

        return count($rows) > 0;
    }

    /** @param array<string, mixed> $data */
    public function submit(array $data): VisitRequest
    {
        return $this->rpc('submit_family_visit', $data);
    }

    public function cancel(VisitRequest $visit): VisitRequest
    {
        return $this->rpc('cancel_family_visit', ['p_visit_id' => $visit->id]);
    }

    public function review(VisitRequest $visit, string $decision, ?string $note): VisitRequest
    {
        return $this->rpc('review_family_visit', ['p_visit_id' => $visit->id, 'p_decision' => $decision, 'p_review_note' => $note]);
    }

    /** @param array<string, mixed> $data */
    private function rpc(string $name, array $data): VisitRequest
    {
        $row = $this->send('POST', 'rpc/'.$name, $data)->json();
        $row = isset($row[0]) ? $row[0] : $row;
        if (! is_array($row) || ! is_string($row['id'] ?? null)) {
            throw new SupabaseUnavailable;
        }

        return $this->visit($row);
    }

    /** @param array<string, mixed> $row */
    private function visit(array $row): VisitRequest
    {
        $resident = $row['resident'] ?? null;
        $requester = $row['requester'] ?? null;
        $reviewer = $row['reviewer'] ?? null;
        unset($row['resident'], $row['requester'], $row['reviewer']);
        $visit = $this->hydrate(VisitRequest::class, $row);
        $visit->setRelation('resident', is_array($resident) ? $this->hydrate(Resident::class, $resident) : null);
        $visit->setRelation('requester', is_array($requester) ? $this->hydrate(AccountProfile::class, $requester) : null);

        $visit->setRelation('reviewer', is_array($reviewer) ? $this->hydrate(AccountProfile::class, $reviewer) : null);

        return $visit;
    }

    /** @template T of Model
     * @param  class-string<T>  $class
     * @param  array<string, mixed>  $data
     * @return T
     */
    private function hydrate(string $class, array $data): Model
    {
        $model = new $class;
        $model->setRawAttributes($data, true);
        $model->exists = true;
        $model->preventsLazyLoading = true;

        return $model;
    }
}
