<?php

namespace App\Policies;

use App\Enums\Role;
use App\Models\Incident;
use App\Models\SupabaseUser;

class IncidentPolicy
{
    /**
     * Caregiver hanya bisa melaporkan insiden (create)
     * dan melihat (view) untuk lansia dalam penugasan aktifnya.
     */
    public function create(SupabaseUser $user): bool
    {
        return $user->role() === Role::Caregiver;
    }

    /**
     * View - hak berbeda untuk tiap role.
     *
     * - Caregiver: hanya lihat insiden yang dilaporkan untuk lansia
     *   yang dalam penugasan aktif (caregiver_assignments hari ini)
     * - Admin: lihat semua insiden
     * - Family: ditolak (false)
     */
    public function view(SupabaseUser $user, Incident $incident): bool
    {
        return match ($user->role()) {
            Role::Admin => true,
            Role::Caregiver => $this->canViewResidentIncident($user, $incident),
            Role::Family => false,
        };
    }

    /**
     * Admin bisa mereview/menutup semua insiden.
     */
    public function review(SupabaseUser $user): bool
    {
        return $user->role() === Role::Admin;
    }

    /**
     * Update status insiden - hanya untuk role yang boleh.
     */
    public function update(SupabaseUser $user, Incident $incident): bool
    {
        return match ($user->role()) {
            Role::Admin => true,
            Role::Caregiver => $this->canViewResidentIncident($user, $incident),
            Role::Family => false,
        };
    }

    /**
     * Cek apakah caregiver memiliki lansia yang ditugaskan hari ini.
     */
    protected function canViewResidentIncident(SupabaseUser $user, Incident $incident): bool
    {
        // Jika incident belum memiliki resident_id, tolak
        if (! isset($incident->resident_id)) {
            return false;
        }

        // Cek caregiver_assignments - penugasan hari ini di Asia/Jakarta
        $today = now('Asia/Jakarta')->format('Y-m-d');

        $assignment = app(\App\Models\CaregiverAssignment::class)->read([
            'user_id' => 'eq.'.$user->id(),
            'resident_id' => 'eq.'.$incident->resident_id,
            'start_date' => 'lte.'.$today,
            'or' => '(end_date.is.null,end_date.gte.'.$today.')',
            'limit' => 1,
        ]);

        return count($assignment) > 0;
    }
}