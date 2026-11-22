<?php

namespace App\Policies;

use App\Enums\Role;
use App\Models\CaregiverAssignment;
use App\Models\Consent;
use App\Models\FamilyLink;
use App\Models\FamilyUpdate;
use App\Models\SupabaseUser;

class FamilyUpdatePolicy
{
    public function viewAny(SupabaseUser $user): bool
    {
        return in_array($user->role(), [Role::Admin, Role::Caregiver, Role::Family], true);
    }

    /**
     * @param  array<string, mixed>|string  $update
     */
    public function view(SupabaseUser $user, array|string $update): bool
    {
        $updateData = is_array($update)
            ? $update
            : app(FamilyUpdate::class)->findForCurrentUser($update);

        if ($updateData === null) {
            return false;
        }

        $residentId = $updateData['resident_id'] ?? null;
        if (! is_string($residentId) || $residentId === '') {
            return false;
        }

        if ($user->role() === Role::Admin) {
            return true;
        }

        if ($user->role() === Role::Caregiver) {
            return app(CaregiverAssignment::class)->isAssignedToResident($residentId);
        }

        if ($user->role() === Role::Family) {
            // Matriks akses PRD: keluarga hanya bisa melihat FamilyUpdate
            // berstatus 'published' untuk lansia dengan family_links terverifikasi.
            if (($updateData['status'] ?? null) !== 'published') {
                return false;
            }

            return app(FamilyLink::class)->isVerifiedForResident($residentId);
        }

        return false;
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
     * @param  array<string, mixed>|string  $update
     */
    public function update(SupabaseUser $user, array|string $update): bool
    {
        $updateData = is_array($update)
            ? $update
            : app(FamilyUpdate::class)->findForCurrentUser($update);

        if ($updateData === null) {
            return false;
        }

        if ($user->role() === Role::Admin) {
            return true;
        }

        if ($user->role() === Role::Caregiver) {
            $residentId = $updateData['resident_id'] ?? null;
            if (! is_string($residentId) || $residentId === '') {
                return false;
            }

            if (! app(CaregiverAssignment::class)->isAssignedToResident($residentId)) {
                return false;
            }

            $authorId = $updateData['author_id'] ?? null;
            if (is_string($authorId) && $authorId !== '' && $authorId !== $user->id()) {
                return false;
            }

            return true;
        }

        return false;
    }

    /**
     * @param  array<string, mixed>|string  $update
     */
    public function publish(SupabaseUser $user, array|string $update): bool
    {
        $updateData = is_array($update)
            ? $update
            : app(FamilyUpdate::class)->findForCurrentUser($update);

        if ($updateData === null) {
            return false;
        }

        if ($user->role() === Role::Admin) {
            return true;
        }

        if ($user->role() === Role::Caregiver) {
            $residentId = $updateData['resident_id'] ?? null;
            if (! is_string($residentId) || $residentId === '') {
                return false;
            }

            if (! app(CaregiverAssignment::class)->isAssignedToResident($residentId)) {
                return false;
            }

            $photoPath = $updateData['photo_path'] ?? null;
            if (is_string($photoPath) && trim($photoPath) !== '') {
                if (! app(Consent::class)->hasPhotoConsent($residentId)) {
                    return false;
                }
            }

            if (! app(Consent::class)->hasVisibilityConsent($residentId)) {
                return false;
            }

            return true;
        }

        return false;
    }

    /**
     * @param  array<string, mixed>|string  $update
     */
    public function delete(SupabaseUser $user, array|string $update): bool
    {
        $updateData = is_array($update)
            ? $update
            : app(FamilyUpdate::class)->findForCurrentUser($update);

        if ($updateData === null) {
            return false;
        }

        if ($user->role() === Role::Admin) {
            return true;
        }

        if ($user->role() === Role::Caregiver) {
            $residentId = $updateData['resident_id'] ?? null;
            if (! is_string($residentId) || $residentId === '') {
                return false;
            }

            if (! app(CaregiverAssignment::class)->isAssignedToResident($residentId)) {
                return false;
            }

            $authorId = $updateData['author_id'] ?? null;
            if (is_string($authorId) && $authorId !== '' && $authorId !== $user->id()) {
                return false;
            }

            return true;
        }

        return false;
    }
}
