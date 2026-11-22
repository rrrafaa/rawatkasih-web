<?php

namespace App\Policies;

use App\Enums\Role;
use App\Models\CaregiverAssignment;
use App\Models\DailyNote;
use App\Models\SupabaseUser;

class DailyNotePolicy
{
    public function viewAny(SupabaseUser $user): bool
    {
        if ($user->role() === Role::Family) {
            return false;
        }

        return in_array($user->role(), [Role::Admin, Role::Caregiver], true);
    }

    /**
     * @param  array<string, mixed>|string  $note
     */
    public function view(SupabaseUser $user, array|string $note): bool
    {
        if ($user->role() === Role::Family) {
            return false;
        }

        if ($user->role() === Role::Admin) {
            return true;
        }

        $noteData = is_array($note)
            ? $note
            : app(DailyNote::class)->findForCurrentUser($note);

        if ($noteData === null) {
            return false;
        }

        $residentId = $noteData['resident_id'] ?? null;
        if (! is_string($residentId) || $residentId === '') {
            return false;
        }

        return app(CaregiverAssignment::class)->isAssignedToResident($residentId);
    }

    /**
     * @param  array<string, mixed>|string|null  $resident
     */
    public function create(SupabaseUser $user, array|string|null $resident = null): bool
    {
        if ($user->role() !== Role::Caregiver) {
            return false;
        }

        if ($resident === null) {
            return true;
        }

        $residentId = is_array($resident)
            ? ($resident['resident_id'] ?? $resident['id'] ?? null)
            : $resident;

        if (! is_string($residentId) || $residentId === '') {
            return false;
        }

        return app(CaregiverAssignment::class)->isAssignedToResident($residentId);
    }

    /**
     * @param  array<string, mixed>|string  $note
     */
    public function update(SupabaseUser $user, array|string $note): bool
    {
        if ($user->role() === Role::Admin) {
            return true;
        }

        if ($user->role() !== Role::Caregiver) {
            return false;
        }

        $noteData = is_array($note)
            ? $note
            : app(DailyNote::class)->findForCurrentUser($note);

        if ($noteData === null) {
            return false;
        }

        $residentId = $noteData['resident_id'] ?? null;
        if (! is_string($residentId) || $residentId === '') {
            return false;
        }

        return app(CaregiverAssignment::class)->isAssignedToResident($residentId);
    }

    /**
     * @param  array<string, mixed>|string  $note
     */
    public function delete(SupabaseUser $user, array|string $note): bool
    {
        // PRD Section 8.5: Daily notes are append-only; hard deletion is prohibited.
        return false;
    }
}
