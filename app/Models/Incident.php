<?php

namespace App\Models;

use Carbon\Carbon;

class Incident extends SupabaseModel
{
    protected string $table = 'incidents';

    protected string $columns = 'id,resident_id,reporter_id,category,severity,description,action_taken,occurred_at,status,follow_up,resolved_at,created_at,updated_at';

    /**
     * @param array<string> $residentIds
     * @return list<array<string, mixed>>
     */
    public function forResidents(array $residentIds): array
    {
        if ($residentIds === []) {
            return [];
        }

        return $this->read([
            'resident_id' => 'in.('.implode(',', $residentIds).')',
            'order' => 'occurred_at.desc',
            'limit' => 100,
        ]);
    }

    /**
     * Ambil semua insiden (untuk admin).
     *
     * @return list<array<string, mixed>>
     */
    public function all(int $limit = 50): array
    {
        return $this->read([
            'order' => 'occurred_at.desc',
            'limit' => $limit,
        ]);
    }

    /**
     * Get incidents for a specific caregiver (filtered by their assignments).
     *
     * @return list<array<string, mixed>>
     */
    public function forCaregiver(string $caregiverId, string $date): array
    {
        $assignments = app(CaregiverAssignment::class)->activeForCaregiver($caregiverId, $date);

        $residentIds = array_column($assignments, 'resident_id');

        return $this->forResidents($residentIds);
    }

    /**
     * Find incident by ID.
     */
    public function find(string $id): ?array
    {
        return $this->read([
            'id' => 'eq.'.$id,
            'limit' => 1,
        ])[0] ?? null;
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function auditLogFor(string $incidentId): array
    {
        $response = $this->client->request(
            'GET',
            '/rest/v1/audit_logs',
            [
                'target_table' => 'eq.Incident',
                'target_id' => 'eq.'.$incidentId,
                'order' => 'occurred_at.desc',
                'limit' => 100,
            ],
            request()->session()->get('supabase.access_token')
        );

        return $response->json() ?? [];
    }

    /**
     * @param array<string> $data
     */
    public function create(array $data): array
    {
        $response = $this->client->request(
            'POST',
            '/rest/v1/incidents',
            $data,
            request()->session()->get('supabase.access_token')
        );

        return $response->json() ?? [];
    }
}

