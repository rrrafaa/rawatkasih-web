<?php

namespace App\Services\FamilyVisits;

use App\Models\FamilyVisits\FamilyVisitData;
use App\Models\FamilyVisits\Resident;
use Illuminate\Http\Request;

class PortalContext
{
    public function __construct(private FamilyVisitData $data) {}

    /** @return array<string, mixed> */
    public function for(Request $request, bool $withPortrait = false): array
    {
        $request->validate(['resident' => ['nullable', 'uuid']]);
        $residents = $this->data->residents();
        $selected = $request->query('resident');
        $resident = $selected === null ? ($residents[0] ?? null) : null;
        foreach ($residents as $candidate) {
            if ($candidate->id === $selected) {
                $resident = $candidate;
            }
        }
        abort_if($selected !== null && $resident === null, 404);

        $vars = ['user' => $request->user(), 'residents' => $residents, 'resident' => $resident];
        if (! $withPortrait) {
            return $vars;
        }

        $photoConsent = $resident !== null && $this->data->photoConsent($resident->id);

        return [...$vars, 'photoConsent' => $photoConsent,
            'realPortrait' => $photoConsent && filled($resident->profile_photo_path),
            'demoPortrait' => $photoConsent && $this->hasDemoPortrait($resident)];
    }

    public function hasDemoPortrait(Resident $resident): bool
    {
        return config('family_visits.demo_news')
            && $resident->id === config('family_visits.demo_portrait_resident_id')
            && is_file(resource_path('resident/sulastri/profil.jpg'));
    }
}
