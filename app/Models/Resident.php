<?php

namespace App\Models;

class Resident extends SupabaseModel
{
    protected string $table = 'residents';

    protected string $columns = 'id,full_name,room,status';

    /** @return list<array<string, mixed>> */
    public function activeResidents(): array
    {
        return $this->read([
            'status' => 'eq.active',
            'order' => 'full_name.asc',
        ]);
    }

    /** @param list<string> $residentIds
     * @return list<array<string, mixed>>
     */
    public function forAssignedResidents(array $residentIds): array
    {
        if ($residentIds === []) {
            return [];
        }

        return $this->read([
            'id' => 'in.('.implode(',', $residentIds).')',
            'order' => 'full_name.asc',
        ]);
    }

    public function findForCurrentUser(string $id): ?array
    {
        $residents = $this->read([
            'id' => 'eq.'.$id,
            'limit' => 1,
        ]);

        return $residents[0] ?? null;
    }
}