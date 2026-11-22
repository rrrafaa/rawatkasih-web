<?php

namespace App\Policies;

use App\Enums\Role;
use App\Models\CareTaskOccurrence;
use App\Models\SupabaseUser;

class CareTaskOccurrencePolicy
{
    public function update(
        SupabaseUser $user,
        string $occurrenceId
    ): bool {
        if ($user->role() !== Role::Caregiver) {
            return false;
        }

        return app(CareTaskOccurrence::class)
            ->findForCurrentUser($occurrenceId) !== null;
    }
}
