<?php

namespace App\Models;

class FamilyLink extends SupabaseModel
{
    protected string $table = 'family_links';

    protected string $columns = 'id,user_id,resident_id,relationship,status,verified_at';

    public function isVerifiedForResident(string $residentId): bool
    {
        $user = auth()->user();

        if (! $user instanceof SupabaseUser) {
            return false;
        }

        if (! preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/iD', $residentId)) {
            return false;
        }

        $rows = $this->read([
            'user_id' => 'eq.'.$user->id(),
            'resident_id' => 'eq.'.$residentId,
            'status' => 'eq.verified',
            'limit' => 1,
        ]);

        return count($rows) > 0;
    }
}
