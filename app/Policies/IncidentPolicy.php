<?php

namespace App\Policies;

use App\Enums\Role;
use App\Models\CaregiverAssignment;
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
     *
     * @param array<string, mixed>|string $incident
     */
    public function view(SupabaseUser $user, array|string $incident): bool
    {
        $incidentData = is_array($incident) ? $incident : app(Incident::class)->find($incident);

        if ($incidentData === null) {
            return false;
        }

        return match ($user->role()) {
            Role::Admin => true,
            Role::Caregiver => $this->canViewResidentIncident($user, $incidentData),
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
     *
     * @param array<string, mixed>|string $incident
     */
    public function update(SupabaseUser $user, array|string $incident): bool
    {
        $incidentData = is_array($incident) ? $incident : app(Incident::class)->find($incident);

        if ($incidentData === null) {
            return false;
        }

        return match ($user->role()) {
            Role::Admin => true,
            Role::Caregiver => $this->canViewResidentIncident($user, $incidentData),
            Role::Family => false,
        };
    }

    /**
     * Cek apakah caregiver memiliki lansia yang ditugaskan hari ini.
     *
     * @param array<string, mixed> $incidentData
     */
    protected function canViewResidentIncident(SupabaseUser $user, array $incidentData): bool
    {
        // Jika incident belum memiliki resident_id, tolak
        $residentId = $incidentData['resident_id'] ?? null;
        if (! is_string($residentId) || $residentId === '') {
            return false;
        }

        // Cek caregiver_assignments - penugasan hari ini di Asia/Jakarta
        $today = now('Asia/Jakarta')->toDateString();

        $assignments = app(CaregiverAssignment::class)->isAssignedToResident($residentId, $today);

        return $assignments;
    }

    /**
     * Admin bisa melihat semua insiden.
     */
    public function viewAny(SupabaseUser $user): bool
    {
        return in_array($user->role(), [Role::Admin, Role::Caregiver], true);
    }
}