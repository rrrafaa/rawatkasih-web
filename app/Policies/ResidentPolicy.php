<?php

namespace App\Policies;

use App\Enums\Role;
use App\Models\Resident;
use App\Models\SupabaseUser;

class ResidentPolicy
{
    public function view(SupabaseUser $user, string $residentId): bool
    {
        // RLS mewajibkan penugasan pendamping aktif atau relasi keluarga terverifikasi beserta persetujuan.
        return app(Resident::class)->findForCurrentUser($residentId) !== null;
    }

    public function create(SupabaseUser $user): bool
    {
        return $user->role() === Role::Admin;
    }

    public function update(SupabaseUser $user, string $residentId): bool
    {
        return $user->role() === Role::Admin && $this->view($user, $residentId);
    }
}
