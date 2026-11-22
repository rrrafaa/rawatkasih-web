<?php

namespace App\Models;

use App\Exceptions\SupabaseUnavailable;

class CareTaskTemplate extends SupabaseModel
{
    protected string $table = 'care_task_templates';

    protected string $columns = 'id,resident_id,title,category,scheduled_time,instructions,is_active,frequency,days_of_week,created_at,updated_at';

    /**
     * @param  array<string, mixed>  $params
     * @return list<array<string, mixed>>
     */
    public function allForAdmin(array $params = []): array
    {
        $token = request()->session()->get('supabase.access_token');

        $response = $this->client->request(
            'GET',
            '/rest/v1/care_task_templates',
            array_merge([
                'select' => $this->columns,
                'order' => 'created_at.desc',
            ], $params),
            $token
        );

        return $response->json() ?? [];
    }

    /**
     * @param  list<string>  $residentIds
     * @return list<array<string, mixed>>
     */
    public function activeForResidents(array $residentIds): array
    {
        $ids = $this->validUuidList($residentIds);

        if ($ids === []) {
            return [];
        }

        $token = request()->session()->get('supabase.access_token');

        $response = $this->client->request(
            'GET',
            '/rest/v1/care_task_templates',
            [
                'select' => $this->columns,
                'resident_id' => 'in.('.implode(',', $ids).')',
                'is_active' => 'eq.true',
                'order' => 'scheduled_time.asc',
            ],
            $token
        );

        return $response->json() ?? [];
    }

    /**
     * @param  list<string>  $residentIds
     * @return list<array<string, mixed>>
     */
    public function allForResidents(array $residentIds): array
    {
        $ids = $this->validUuidList($residentIds);

        if ($ids === []) {
            return [];
        }

        $token = request()->session()->get('supabase.access_token');

        $response = $this->client->request(
            'GET',
            '/rest/v1/care_task_templates',
            [
                'select' => $this->columns,
                'resident_id' => 'in.('.implode(',', $ids).')',
                'order' => 'scheduled_time.asc',
            ],
            $token
        );

        return $response->json() ?? [];
    }

    /**
     * @param  list<string>  $ids
     * @return list<string>
     */
    private function validUuidList(array $ids): array
    {
        return array_values(array_unique(array_filter(
            $ids,
            fn ($id) => preg_match(
                '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/iD',
                $id
            ) === 1
        )));
    }

    /** @param array<string, mixed> $data */
    public function createForAdmin(array $data): void
    {
        abort_unless(auth()->user() instanceof SupabaseUser, 401);

        $token = request()->session()->get('supabase.access_token');
        abort_unless(is_string($token) && $token !== '', 401);

        $response = $this->client->request(
            'POST',
            '/rest/v1/care_task_templates',
            $data,
            $token
        );

        abort_if(in_array($response->status(), [401, 403], true), 403);

        if (! $response->successful()) {
            throw new SupabaseUnavailable;
        }
    }

    /** @param array<string, mixed> $data */
    public function updateForAdmin(string $id, array $data): void
    {
        abort_unless(
            preg_match(
                '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/iD',
                $id
            ) === 1,
            404
        );

        abort_unless(auth()->user() instanceof SupabaseUser, 401);

        $token = request()->session()->get('supabase.access_token');
        abort_unless(is_string($token) && $token !== '', 401);

        $response = $this->client->request(
            'PATCH',
            '/rest/v1/care_task_templates?id=eq.'.$id,
            $data,
            $token
        );

        abort_if(in_array($response->status(), [401, 403], true), 403);

        if (! $response->successful()) {
            throw new SupabaseUnavailable;
        }
    }
}
