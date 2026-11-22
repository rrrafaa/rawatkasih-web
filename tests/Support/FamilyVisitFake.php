<?php

namespace Tests\Support;

use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class FamilyVisitFake
{
    public string $role = 'family';

    public bool $linked = true;

    public bool $photoConsent = true;

    public bool $outage = false;

    public bool $conflict = false;

    public string $residentName = 'Eyang Hartono Soedirdjo';

    public string $familyName = 'Rina Wulandari';

    public ?string $familyPhone = '0800000000';

    public array $rows = [];

    public array $updates = [];

    public ?string $residentPhotoPath = null;

    public ?string $avatarPath = null;

    public array $calls = [];

    public const FAMILY = '20000000-0000-4000-8000-000000000001';

    public const RESIDENT = '30000000-0000-4000-8000-000000000001';

    public const OTHER = '30000000-0000-4000-8000-000000000099';

    public const VISIT = '40000000-0000-4000-8000-000000000001';

    public function install(): void
    {
        Http::preventStrayRequests();
        Http::fake(fn (Request $request) => $this->respond($request));
    }

    public function profile(): array
    {
        return ['id' => self::FAMILY, 'full_name' => $this->familyName, 'email' => 'rina@example.test', 'phone' => $this->familyPhone, 'role' => $this->role, 'is_active' => true, 'avatar_path' => $this->avatarPath];
    }

    public function resident(): array
    {
        return ['id' => self::RESIDENT, 'full_name' => $this->residentName, 'birth_date' => '1947-05-10', 'room' => 'Melati 04', 'status' => 'active', 'profile_photo_path' => $this->residentPhotoPath];
    }

    public function row(array $overrides = []): array
    {
        return [...['id' => self::VISIT, 'resident_id' => self::RESIDENT, 'requester_id' => self::FAMILY, 'requested_at' => '2026-11-19T02:00:00+00:00', 'requested_end_at' => '2026-11-19T03:30:00+00:00', 'visitor_count' => 2, 'primary_visitor_name' => 'Rina Wulandari', 'note' => 'Membawa buah', 'status' => 'pending', 'review_note' => null, 'reviewed_by' => null, 'reviewed_at' => null, 'created_at' => '2026-11-17T01:00:00+00:00', 'resident' => $this->resident(), 'requester' => $this->profile()], ...$overrides];
    }

    private function respond(Request $request)
    {
        $path = parse_url($request->url(), PHP_URL_PATH);
        if ($path === '/auth/v1/logout') {
            return Http::response([], 204);
        }
        if ($path === '/auth/v1/user') {
            return Http::response(['id' => self::FAMILY]);
        }
        if ($path === '/rest/v1/profiles') {
            return Http::response([$this->profile()]);
        }
        if ($path === '/rest/v1/family_links') {
            return Http::response($this->linked ? [['id' => '50000000-0000-4000-8000-000000000001', 'user_id' => self::FAMILY, 'resident_id' => self::RESIDENT, 'relationship' => 'Putri / anggota keluarga', 'status' => 'verified', 'verified_at' => '2026-11-01T01:00:00Z', 'resident' => $this->resident()]] : []);
        }
        if ($path === '/rest/v1/consents') {
            return Http::response($this->photoConsent ? [['id' => 'consent']] : []);
        }
        if ($path === '/rest/v1/family_updates') {
            $rows = $this->updates;
            foreach (['id','resident_id','status'] as $field) {
                if (isset($request[$field])) {
                    $rows = array_values(array_filter($rows, fn ($r) => ($request[$field] === 'eq.'.($r[$field] ?? ''))));
                }
            }
            if (isset($request['select']) && $request['select'] === 'photo_path') {
                $rows = array_map(fn ($r) => ['photo_path' => $r['photo_path'] ?? null], $rows);
            }
            return Http::response($rows);
        }
        if (str_starts_with($path, '/storage/v1/object/authenticated/rawatkasih-private/')) {
            return Http::response('binary-photo-bytes', 200, ['Content-Type' => 'image/jpeg']);
        }
        if ($path === '/rest/v1/visit_requests') {
            $rows = array_values(array_filter($this->rows, fn ($r) => ($this->role === 'admin' || ($this->linked && $r['requester_id'] === self::FAMILY))
                && (! isset($request['id']) || $request['id'] === 'eq.'.$r['id'])
                && (! isset($request['resident_id']) || $request['resident_id'] === 'eq.'.$r['resident_id'])
                && (! isset($request['status']) || $request['status'] === 'eq.'.$r['status'])));
            if (! str_contains((string) ($request['select'] ?? ''), 'reviewer:')) {
                $rows = array_map(function (array $row): array {
                    unset($row['reviewer']);

                    return $row;
                }, $rows);
            }
            $total = count($rows);
            $rows = array_slice($rows, (int) ($request['offset'] ?? 0), (int) ($request['limit'] ?? 25));

            return Http::response($rows, 200, ['Content-Range' => '0-24/'.$total]);
        }
        if (str_starts_with($path, '/rest/v1/rpc/')) {
            $this->calls[] = ['path' => $path, 'data' => $request->data()];
            if ($this->outage) {
                return Http::response([], 503);
            }
            if ($this->conflict) {
                return Http::response(['message' => 'changed'], 409);
            }
            if ($path === '/rest/v1/rpc/update_my_profile') {
                $this->familyName = $request['p_full_name'];
                $this->familyPhone = $request['p_phone'];

                return Http::response(null, 204);
            }
            if ($path === '/rest/v1/rpc/family_visit_counts') {
                return Http::response(array_map(fn ($status) => ['status' => $status, 'total' => count(array_filter($this->rows, fn ($r) => $r['status'] === $status))], ['pending', 'approved', 'rejected', 'cancelled']));
            }
            if ($path === '/rest/v1/rpc/submit_family_visit') {
                $data = $request->data();
                foreach ($this->rows as $row) {
                    if (($row['submission_key'] ?? null) === $data['p_submission_key']) {
                        return Http::response($row);
                    }
                }
                $row = $this->row(['id' => (string) Str::uuid(), 'submission_key' => $data['p_submission_key'], 'resident_id' => $data['p_resident_id'], 'requested_at' => $data['p_requested_at'], 'requested_end_at' => $data['p_requested_end_at'], 'visitor_count' => $data['p_visitor_count'], 'primary_visitor_name' => $data['p_primary_visitor_name'], 'note' => $data['p_note']]);
                $this->rows[] = $row;

                return Http::response($row);
            }
            $id = $request['p_visit_id'];
            foreach ($this->rows as &$row) {
                if ($row['id'] === $id) {
                    $row['status'] = $path === '/rest/v1/rpc/cancel_family_visit' ? 'cancelled' : $request['p_decision'];
                    $row['review_note'] = $request['p_review_note'] ?? null;
                    $row['reviewed_at'] = $row['status'] === 'cancelled' ? null : '2026-11-17T01:00:00Z';
                    if ($row['reviewed_at'] !== null) {
                        $row['reviewed_by'] = self::FAMILY;
                        $row['reviewer'] = ['id' => self::FAMILY, 'full_name' => 'Sari Admin'];
                    }

                    return Http::response($row);
                }
            }

            return Http::response([], 404);
        }
        throw new \RuntimeException('Unexpected HTTP request: '.$request->url());
    }
}
