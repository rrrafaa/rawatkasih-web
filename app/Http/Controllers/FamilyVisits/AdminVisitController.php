<?php

namespace App\Http\Controllers\FamilyVisits;

use App\Http\Controllers\Controller;
use App\Http\Requests\FamilyVisits\ReviewVisitRequest;
use App\Models\FamilyVisits\FamilyVisitData;
use App\Models\FamilyVisits\VisitRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class AdminVisitController extends Controller
{
    public function index(Request $request, FamilyVisitData $data): View
    {
        $filters = $request->validate(['status' => ['nullable', 'in:pending,approved,rejected,cancelled'], 'page' => ['nullable', 'integer', 'min:1', 'max:100000']]);
        $status = $filters['status'] ?? null;

        return view('modules.family-visits.admin.index', ['user' => $request->user(), 'visits' => $data->visits(null, $status, (int) ($filters['page'] ?? 1)), 'counts' => $data->counts()]);
    }

    public function show(Request $request, VisitRequest $visit): View
    {
        Gate::authorize('view', $visit);

        return view('modules.family-visits.admin.show', ['user' => $request->user(), 'visit' => $visit]);
    }

    public function update(ReviewVisitRequest $request, VisitRequest $visit, FamilyVisitData $data): RedirectResponse
    {
        Gate::authorize('review', $visit);
        $input = $request->validated();
        $data->review($visit, $input['decision'], $input['review_note'] ?? null);

        return redirect()->route('admin.visits.show', $visit->id)->with('status', 'Keputusan kunjungan berhasil disimpan.');
    }
}
