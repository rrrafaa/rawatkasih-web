<?php

namespace App\Services\FamilyVisits;

use App\Models\FamilyVisits\FamilyVisitData;
use Illuminate\Http\Request;

class PortalContext
{
    public function __construct(private FamilyVisitData $data) {}

    /** @return array<string, mixed> */
    public function for(Request $request): array
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

        return ['user' => $request->user(), 'residents' => $residents, 'resident' => $resident];
    }
}
