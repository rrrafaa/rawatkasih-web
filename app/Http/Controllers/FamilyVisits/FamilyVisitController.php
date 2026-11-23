<?php

namespace App\Http\Controllers\FamilyVisits;

use App\Http\Controllers\Controller;
use App\Http\Requests\FamilyVisits\StoreVisitRequest;
use App\Models\FamilyVisits\FamilyVisitData;
use App\Models\FamilyVisits\VisitRequest;
use App\Services\FamilyVisits\PortalContext;
use App\Services\FamilyVisits\VisitHours;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\View\View;

class FamilyVisitController extends Controller
{
    public function index(Request $request, PortalContext $context, FamilyVisitData $data): View
    {
        $filters = $request->validate(['status' => ['nullable', 'in:pending,approved,rejected,cancelled'], 'page' => ['nullable', 'integer', 'min:1', 'max:100000']]);
        $vars = $context->for($request, withPortrait: true);
        $visits = $vars['resident'] ? $data->visits($vars['resident']->id, $filters['status'] ?? null, (int) ($filters['page'] ?? 1)) : null;

        return view('modules.family-visits.visits.index', [...$vars, 'visits' => $visits, 'activeNav' => 'visits']);
    }

    public function create(Request $request, PortalContext $context): View
    {
        $vars = $context->for($request, withPortrait: true);
        abort_unless($vars['resident'] !== null, 404);
        Gate::authorize('create', [VisitRequest::class, $vars['resident']]);

        return view('modules.family-visits.visits.create', [...$vars, 'submissionKey' => (string) Str::uuid(), 'visitSlots' => VisitHours::slots(), 'activeNav' => 'visits']);
    }

    public function store(StoreVisitRequest $request, FamilyVisitData $data): RedirectResponse
    {
        $input = $request->validated();
        $resident = $data->resident($input['resident_id']);
        Gate::authorize('create', [VisitRequest::class, $resident]);
        $start = CarbonImmutable::createFromFormat('!Y-m-d H:i', $input['visit_date'].' '.$input['start_time'], 'Asia/Jakarta');
        $end = CarbonImmutable::createFromFormat('!Y-m-d H:i', $input['visit_date'].' '.$input['end_time'], 'Asia/Jakarta');
        $data->submit(['p_resident_id' => $resident->id, 'p_submission_key' => $input['submission_key'],
            'p_requested_at' => $start->utc()->toIso8601String(), 'p_requested_end_at' => $end->utc()->toIso8601String(),
            'p_visitor_count' => (int) $input['visitor_count'], 'p_primary_visitor_name' => $input['primary_visitor_name'], 'p_note' => $input['note'] ?? null]);

        return redirect()->route('family.visits.index', ['resident' => $resident->id])->with('status', 'Pengajuan kunjungan tersimpan. Menunggu keputusan admin.');
    }

    public function show(Request $request, VisitRequest $visit, PortalContext $context): View
    {
        Gate::authorize('view', $visit);
        $request->query->set('resident', $visit->resident_id);

        return view('modules.family-visits.visits.show', [...$context->for($request, withPortrait: true), 'visit' => $visit, 'activeNav' => 'visits']);
    }

    public function cancel(VisitRequest $visit, FamilyVisitData $data): RedirectResponse
    {
        Gate::authorize('cancel', $visit);
        $data->cancel($visit);

        return redirect()->route('family.visits.show', $visit->id)->with('status', 'Pengajuan kunjungan dibatalkan.');
    }
}
