<?php

namespace App\Models;

class CareTaskStatusHistory extends SupabaseModel
{
    protected string $table = 'care_task_status_history';

    protected string $keyType = 'int';

    protected string $columns = 'id,occurrence_id,old_status,new_status,reason,changed_by,changed_at';

    /**
     * @param  list<string>  $occurrenceIds
     * @return list<array<string, mixed>>
     */
    public function forOccurrences(array $occurrenceIds): array
    {
        $ids = array_values(array_unique(array_filter(
            $occurrenceIds,
            fn ($id) => preg_match(
                '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/iD',
                $id
            ) === 1
        )));

        if ($ids === []) {
            return [];
        }

        return $this->read([
            'occurrence_id' => 'in.('.implode(',', $ids).')',
            'order' => 'changed_at.desc',
        ]);
    }

    /**
     * Mengambil riwayat terbaru yang boleh dilihat caregiver.
     *
     * RLS Supabase tetap menjadi pembatas akses data.
     *
     * @return list<array<string, mixed>>
     */
    public function latestForCurrentCaregiver(
        int $page = 1,
        int $perPage = 50
    ): array {
        $page = max(1, $page);
        $perPage = max(1, min(100, $perPage));

        return $this->read([
            'order' => 'changed_at.desc',
            'limit' => $perPage,
            'offset' => ($page - 1) * $perPage,
        ]);
    }
}
