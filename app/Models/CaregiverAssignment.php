<?php

namespace App\Models;

class CaregiverAssignment extends SupabaseModel
{
    protected string $table = 'caregiver_assignments';

    protected string $columns = 'id,user_id,resident_id,start_date,end_date';

    /**
     * @param array<string, mixed> $params
     * @return list<array<string, mixed>>
     */
    public function activeForCurrentCaregiver(string $date, array $params = []): array
    {
        $user = auth()->user();

        abort_unless($user instanceof SupabaseUser, 401);

        $token = request()->session()->get('supabase.access_token');

        $response = $this->client->request(
            'GET',
            '/rest/v1/caregiver_assignments',
            array_merge([
                'select' => $this->columns,
                'user_id' => 'eq.'.$user->id(),
                'start_date' => 'lte.'.$date,
                'or' => '(end_date.is.null,end_date.gte.'.$date.')',
                'order' => 'start_date.desc',
            ], $params),
            $token
        );

        return $response->json() ?? [];
    }

    public function isAssignedToResident(string $residentId, ?string $date = null): bool
    {
        $user = auth()->user();

        if (! $user instanceof SupabaseUser) {
            return false;
        }

        if (! preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/iD', $residentId)) {
            return false;
        }

        $date ??= now('Asia/Jakarta')->toDateString();

        $token = request()->session()->get('supabase.access_token');

        $response = $this->client->request(
            'GET',
            '/rest/v1/caregiver_assignments',
            [
                'select' => $this->columns,
                'user_id' => 'eq.'.$user->id(),
                'resident_id' => 'eq.'.$residentId,
                'start_date' => 'lte.'.$date,
                'or' => '(end_date.is.null,end_date.gte.'.$date.')',
                'limit' => 1,
            ],
            $token
        );

        $rows = $response->json() ?? [];
        return count($rows) > 0;
    }
}