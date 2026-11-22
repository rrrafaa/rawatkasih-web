<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class Incident extends SupabaseModel
{
    protected string $table = 'incidents';

    protected string $columns = 'id,resident_id,reporter_id,category,severity,description,action_taken,occurred_at,status,follow_up,resolved_at,created_at,updated_at';

    /**
     * Kolom untuk query dengan joins (resident & reporter data).
     */
    private const JOINED_COLUMNS = 'id,resident_id,reporter_id,category,severity,description,action_taken,occurred_at,status,follow_up,resolved_at,created_at,updated_at,resident:residents!incidents_resident_id_fkey(id,full_name,room,status),reporter:profiles!incidents_reporter_id_fkey(id,full_name)';

    /**
     * Ambil semua insiden (untuk admin) dengan data resident.
     *
     * @param array<string, mixed> $params
     * @return list<array<string, mixed>>
     */
    public function all(array $params = [], int $limit = 50): array
    {
        $token = request()->session()->get('supabase.access_token');

        $response = $this->client->request(
            'GET',
            '/rest/v1/incidents',
            array_merge([
                'select' => self::JOINED_COLUMNS,
                'order' => 'occurred_at.desc,id.asc',
                'limit' => $limit,
            ], $params),
            $token
        );

        return $response->json() ?? [];
    }

    /**
     * @param array<string> $residentIds
     * @return list<array<string, mixed>>
     */
    public function forResidents(array $residentIds, int $page = 1, int $perPage = 50): array
    {
        $validIds = array_values(array_filter(
            $residentIds,
            fn ($id) => preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/iD', $id) === 1
        ));

        if ($validIds === []) {
            return [];
        }

        $token = request()->session()->get('supabase.access_token');

        $response = $this->client->request(
            'GET',
            '/rest/v1/incidents',
            [
                'select' => self::JOINED_COLUMNS,
                'resident_id' => 'in.('.implode(',', $validIds).')',
                'order' => 'occurred_at.desc,id.asc',
                'limit' => $perPage,
                'offset' => (max(1, $page) - 1) * $perPage,
            ],
            $token
        );

        return $response->json() ?? [];
    }

    /**
     * Dapatkan insiden untuk caregiver berdasarkan penugasan mereka hari ini.
     *
     * @return list<array<string, mixed>>
     */
    public function forCaregiver(string $caregiverId, string $date, int $page = 1, int $perPage = 50): array
    {
        $assignments = app(CaregiverAssignment::class)->activeForCurrentCaregiver($date);

        $residentIds = array_column($assignments, 'resident_id');

        return $this->forResidents($residentIds, $page, $perPage);
    }

    /**
     * Dapatkan lansia yang ditugaskan ke caregiver pada tanggal tertentu.
     *
     * @return list<array<string, mixed>>
     */
    public function residentsForCaregiver(string $caregiverId, string $date): array
    {
        $response = $this->client->request(
            'GET',
            '/rest/v1/caregiver_assignments',
            [
                'select' => 'resident:residents!caregiver_assignments_resident_id_fkey(id,full_name,room,status)',
                'user_id' => 'eq.'.$caregiverId,
                'start_date' => 'lte.'.$date,
                'or' => '(end_date.is.null,end_date.gte.'.$date.')',
                'limit' => 100,
            ],
            request()->session()->get('supabase.access_token')
        );

        return $response->json() ?? [];
    }

    /**
     * Temukan insiden berdasarkan ID.
     */
    public function find(string $id): ?array
    {
        $token = request()->session()->get('supabase.access_token');

        $response = $this->client->request(
            'GET',
            '/rest/v1/incidents',
            [
                'select' => self::JOINED_COLUMNS,
                'id' => 'eq.'.$id,
                'limit' => 1,
            ],
            $token
        );

        $rows = $response->json() ?? [];
        return $rows[0] ?? null;
    }

    /**
     * Buat insiden baru.
     *
     * @param array<string, mixed> $data
     */
    public function create(array $data): array
    {
        $token = request()->session()->get('supabase.access_token');

        $response = $this->client->request(
            'POST',
            '/rest/v1/incidents',
            $data,
            $token,
            ['Prefer' => 'return=representation']
        );

        if (! $response->successful()) {
            Log::error('Failed to create incident', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            abort(500, 'Gagal menyimpan insiden ke database.');
        }

        $result = $response->json();
        return is_array($result) && isset($result[0]) ? $result[0] : (array) $result;
    }

    /**
     * Update incident (untuk admin).
     *
     * @param array<string, mixed> $data
     */
    public function update(string $id, array $data): array
    {
        $token = request()->session()->get('supabase.access_token');

        $response = $this->client->request(
            'PATCH',
            '/rest/v1/incidents?id=eq.'.$id,
            $data,
            $token,
            ['Prefer' => 'return=representation']
        );

        if (! $response->successful()) {
            Log::error('Failed to update incident', [
                'status' => $response->status(),
                'body' => $response->body(),
                'incident_id' => $id,
            ]);

            abort(500, 'Gagal memperbarui insiden.');
        }

        $result = $response->json();
        return is_array($result) && isset($result[0]) ? $result[0] : (array) $result;
    }

    /**
     * Dapatkan audit log untuk insiden tertentu.
     *
     * @return list<array<string, mixed>>
     */
    public function auditLogFor(string $incidentId, int $limit = 100): array
    {
        $response = $this->client->request(
            'GET',
            '/rest/v1/audit_logs',
            [
                'target_table' => 'eq.Incident',
                'target_id' => 'eq.'.$incidentId,
                'order' => 'occurred_at.desc',
                'limit' => $limit,
            ],
            request()->session()->get('supabase.access_token')
        );

        return $response->json() ?? [];
    }
}