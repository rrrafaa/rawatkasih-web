<?php

namespace App\Models;

use App\Exceptions\SupabaseUnavailable;

class DailyNote extends SupabaseModel
{
    protected string $table = 'daily_notes';

    protected string $columns = 'id,resident_id,caregiver_id,mood,meal_note,internal_note,recorded_at,created_at,updated_at';

    /**
     * @param  array<string, mixed>  $data
     */
    public function createForCurrentCaregiver(array $data): void
    {
        abort_unless(auth()->user() instanceof SupabaseUser, 401);

        $token = request()->session()->get('supabase.access_token');
        abort_unless(is_string($token) && $token !== '', 401);

        $response = $this->client->request(
            'POST',
            '/rest/v1/'.$this->table,
            $data,
            $token
        );

        abort_if(in_array($response->status(), [401, 403], true), 403);

        if (! $response->successful()) {
            throw new SupabaseUnavailable;
        }
    }

    /**
     * @param  list<string>  $residentIds
     * @return list<array<string, mixed>>
     */
    public function forResidents(array $residentIds, int $page = 1, int $perPage = 50): array
    {
        $validIds = array_values(array_unique(array_filter(
            $residentIds,
            fn ($id) => preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/iD', $id) === 1
        )));

        if ($validIds === []) {
            return [];
        }

        return $this->read([
            'resident_id' => 'in.('.implode(',', $validIds).')',
            'order' => 'recorded_at.desc',
            'limit' => $perPage,
            'offset' => (max(1, $page) - 1) * $perPage,
        ]);
    }
}
