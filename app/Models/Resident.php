<?php

namespace App\Models;

class Resident extends SupabaseModel
{
    protected string $table = 'residents';

    protected string $columns = 'id,full_name,room,status';

    /**
     * @param  array<string, mixed>  $params
     * @return list<array<string, mixed>>
     */
    public function activeResidents(array $params = []): array
    {
        $token = request()->session()->get('supabase.access_token');

        $response = $this->client->request(
            'GET',
            '/rest/v1/residents',
            array_merge([
                'select' => $this->columns,
                'status' => 'eq.active',
                'order' => 'full_name.asc',
            ], $params),
            $token
        );

        return $response->json() ?? [];
    }

    /**
     * Mengambil seluruh data lansia untuk kebutuhan filter admin.
     *
     * @return list<array<string, mixed>>
     */
    public function allForAdmin(): array
    {
        return $this->read([
            'order' => 'full_name.asc',
        ]);
    }

    /**
     * @param  list<string>  $residentIds
     * @return list<array<string, mixed>>
     */
    public function forAssignedResidents(array $residentIds): array
    {
        if ($residentIds === []) {
            return [];
        }

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
            '/rest/v1/residents',
            [
                'select' => $this->columns,
                'id' => 'in.('.implode(',', $validIds).')',
                'order' => 'full_name.asc',
            ],
            $token
        );

        return $response->json() ?? [];
    }

    public function findForCurrentUser(string $id): ?array
    {
        if (! preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/iD', $id)) {
            return null;
        }

        $token = request()->session()->get('supabase.access_token');

        $response = $this->client->request(
            'GET',
            '/rest/v1/residents',
            [
                'select' => $this->columns,
                'id' => 'eq.'.$id,
                'limit' => 1,
            ],
            $token
        );

        $rows = $response->json() ?? [];

        return $rows[0] ?? null;
    }
}
