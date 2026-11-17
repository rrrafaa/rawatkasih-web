<?php

namespace App\Policies;

use App\Enums\Role;
use App\Enums\VisitStatus;
use App\Models\FamilyVisits\FamilyVisitData;
use App\Models\FamilyVisits\Resident;
use App\Models\FamilyVisits\VisitRequest;
use App\Models\SupabaseUser;

class FamilyVisitPolicy
{
    public function create(SupabaseUser $user, Resident $resident): bool
    {
        return $user->role() === Role::Family && $resident->status === 'active'
            && $resident->familyLink->status === 'verified' && $resident->familyLink->user_id === $user->id();
    }

    public function view(SupabaseUser $user, VisitRequest $visit): bool
    {
        if ($user->role() === Role::Admin) {
            return true;
        }
        if ($user->role() !== Role::Family || $visit->requester_id !== $user->id()) {
            return false;
        }

        // Fresh verified relation and consent, even when an old URL or form is reused.
        return app(FamilyVisitData::class)->resident($visit->resident_id)->id === $visit->resident_id;
    }

    public function cancel(SupabaseUser $user, VisitRequest $visit): bool
    {
        return $user->role() === Role::Family && $visit->status === VisitStatus::Pending && $this->view($user, $visit);
    }

    public function review(SupabaseUser $user, VisitRequest $visit): bool
    {
        return $user->role() === Role::Admin && $visit->status === VisitStatus::Pending;
    }
}
