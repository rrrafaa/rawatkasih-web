<?php

namespace App\Models;

use App\Exceptions\SupabaseUnavailable;
use Illuminate\Support\Facades\Log;

class FamilyUpdate extends SupabaseModel
{
    protected string $table = 'family_updates';

    protected string $columns = 'id,resident_id,author_id,summary,photo_path,status,published_at,created_at,updated_at';

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function createForCurrentCaregiver(array $data): array
    {
        abort_unless(auth()->user() instanceof SupabaseUser, 401);

        $token = request()->session()->get('supabase.access_token');
        abort_unless(is_string($token) && $token !== '', 401);

        $response = $this->client->request(
            'POST',
            '/rest/v1/'.$this->table,
            $data,
            $token,
            ['Prefer' => 'return=representation']
        );

        abort_if(in_array($response->status(), [401, 403], true), 403);

        if (! $response->successful()) {
            Log::error('Supabase family_updates error: '.$response->status().' - '.$response->body());
            throw new SupabaseUnavailable;
        }

        $result = $response->json();

        return is_array($result) && isset($result[0]) ? $result[0] : (array) $result;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function updateForCurrentCaregiver(string $id, array $data): array
    {
        abort_unless(auth()->user() instanceof SupabaseUser, 401);

        if (! preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/iD', $id)) {
            abort(404);
        }

        $token = request()->session()->get('supabase.access_token');
        abort_unless(is_string($token) && $token !== '', 401);

        $response = $this->client->request(
            'PATCH',
            '/rest/v1/'.$this->table.'?id=eq.'.$id,
            $data,
            $token,
            ['Prefer' => 'return=representation']
        );

        abort_if(in_array($response->status(), [401, 403], true), 403);

        if (! $response->successful()) {
            Log::error('Supabase family_updates update error: '.$response->status().' - '.$response->body());
            throw new SupabaseUnavailable;
        }

        $result = $response->json();

        return is_array($result) && isset($result[0]) ? $result[0] : (array) $result;
    }

    public function deleteForCurrentCaregiver(string $id): void
    {
        abort_unless(auth()->user() instanceof SupabaseUser, 401);

        if (! preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/iD', $id)) {
            abort(404);
        }

        $token = request()->session()->get('supabase.access_token');
        abort_unless(is_string($token) && $token !== '', 401);

        // Cobalah HARD DELETE terlebih dahulu
        $response = $this->client->request(
            'DELETE',
            '/rest/v1/'.$this->table.'?id=eq.'.$id,
            [],
            $token
        );

        // Jika Supabase RLS melarang DELETE (HTTP 401/403/404), lakukan SOFT DELETE (archived) sesuai PRD 8.5
        if (! $response->successful()) {
            Log::info("Supabase DELETE family_updates returned {$response->status()}, falling back to soft-delete (status='archived')");
            $response = $this->client->request(
                'PATCH',
                '/rest/v1/'.$this->table.'?id=eq.'.$id,
                [
                    'status' => 'archived',
                    'updated_at' => now('Asia/Jakarta')->toIso8601String(),
                ],
                $token
            );
        }

        abort_if(in_array($response->status(), [401, 403], true), 403);

        if (! $response->successful()) {
            Log::error('Supabase family_updates delete error: '.$response->status().' - '.$response->body());
            throw new SupabaseUnavailable;
        }
    }

    /**
     * @param  list<string>  $residentIds
     * @return list<array<string, mixed>>
     */
    public function forResidents(array $residentIds, ?string $status = null, int $page = 1, int $perPage = 50): array
    {
        $validIds = array_values(array_unique(array_filter(
            $residentIds,
            fn ($id) => preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/iD', $id) === 1
        )));

        if ($validIds === []) {
            return [];
        }

        $query = [
            'resident_id' => 'in.('.implode(',', $validIds).')',
            'order' => 'created_at.desc',
            'limit' => $perPage,
            'offset' => (max(1, $page) - 1) * $perPage,
        ];

        if ($status !== null) {
            $query['status'] = 'eq.'.$status;
        } else {
            // Jangan tampilkan item yang sudah di-arsipkan (soft-deleted) secara default
            $query['status'] = 'neq.archived';
        }

        return $this->read($query);
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function publishedForResident(string $residentId, int $page = 1, int $perPage = 25): array
    {
        if (! preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/iD', $residentId)) {
            return [];
        }

        return $this->read([
            'resident_id' => 'eq.'.$residentId,
            'status' => 'eq.published',
            'order' => 'published_at.desc',
            'limit' => $perPage,
            'offset' => (max(1, $page) - 1) * $perPage,
        ]);
    }
}
