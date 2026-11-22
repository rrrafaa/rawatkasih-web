<?php

namespace App\Models;

use App\Exceptions\SupabaseUnavailable;

class CareTaskOccurrence extends SupabaseModel
{
    protected string $table = 'care_task_occurrences';

    protected string $columns = 'id,template_id,task_date,title,category,scheduled_time,instructions,status,reason,completed_by,completed_at,created_at,updated_at';

    /**
     * @param  list<string>  $templateIds
     * @return list<array<string, mixed>>
     */
    public function forTemplatesOnDate(array $templateIds, string $date): array
    {
        $ids = array_values(array_unique(array_filter(
            $templateIds,
            fn ($id) => preg_match('/^[0-9a-f-]{36}$/iD', $id) === 1
        )));

        if ($ids === []) {
            return [];
        }

        $token = request()->session()->get('supabase.access_token');

        $response = $this->client->request(
            'GET',
            '/rest/v1/care_task_occurrences',
            [
                'select' => $this->columns,
                'template_id' => 'in.('.implode(',', $ids).')',
                'task_date' => 'eq.'.$date,
                'order' => 'scheduled_time.asc',
            ],
            $token
        );

        return $response->json() ?? [];
    }

    /** @param array{status: string, reason: ?string} $data */
    public function updateStatus(string $id, array $data): void
    {
        abort_unless(auth()->user() instanceof SupabaseUser, 401);

        $token = request()->session()->get('supabase.access_token');
        abort_unless(is_string($token) && $token !== '', 401);

        if (! preg_match(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/iD',
            $id
        )) {
            abort(404);
        }

        $response = $this->client->request(
            'PATCH',
            '/rest/v1/'.$this->table.'?id=eq.'.$id,
            $data,
            $token
        );

        abort_if(in_array($response->status(), [401, 403], true), 403);

        if (! $response->successful()) {
            throw new SupabaseUnavailable;
        }
    }

    /**
     * @param  list<string>  $occurrenceIds
     * @return list<array<string, mixed>>
     */
    public function forIds(array $occurrenceIds): array
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

        $token = request()->session()->get('supabase.access_token');

        $response = $this->client->request(
            'GET',
            '/rest/v1/care_task_occurrences',
            [
                'select' => $this->columns,
                'id' => 'in.('.implode(',', $ids).')',
                'order' => 'task_date.desc,scheduled_time.asc',
            ],
            $token
        );

        return $response->json() ?? [];
    }
}
