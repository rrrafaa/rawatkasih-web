<?php

namespace App\Models;

class CaregiverAssignment extends SupabaseModel
{
    protected string $table = 'caregiver_assignments';

    protected string $columns = 'id,user_id,resident_id,start_date,end_date';

    /** @return list<array<string, mixed>> */
    public function activeForCurrentCaregiver(string $date): array
    {
        $user = auth()->user();

        abort_unless($user instanceof SupabaseUser, 401);

        return $this->read([
            'user_id' => 'eq.'.$user->id(),
            'start_date' => 'lte.'.$date,
            'or' => '(end_date.is.null,end_date.gte.'.$date.')',
            'order' => 'start_date.desc',
        ]);
    }
}
