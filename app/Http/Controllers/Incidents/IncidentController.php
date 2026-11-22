<?php

namespace App\Http\Controllers\Incidents;

use App\Enums\IncidentCategory;
use App\Enums\IncidentStatus;
use App\Models\CaregiverAssignment;
use App\Models\Incident;
use App\Models\Resident;
use App\Models\SupabaseUser;
use App\Services\AuditLogger;
use App\Services\SupabaseClient;
use Carbon\Carbon;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class IncidentController
{
    use AuthorizesRequests;

    public function __construct(
        private Incident $incidents,
        private CaregiverAssignment $assignments,
        private Resident $residents,
    ) {}

    /**
     * Helper untuk status labels & colors agar tidak duplikat di banyak method.
     */
    private function statusViewData(): array
    {
        return [
            'statusLabels' => array_combine(
                array_map(fn ($s) => $s->value, IncidentStatus::cases()),
                array_map(fn ($s) => $s->label(), IncidentStatus::cases())
            ),
            'statusColors' => [
                'open' => 'bg-amber-50 text-amber-700',
                'handling' => 'bg-blue-50 text-blue-700',
                'closed' => 'bg-emerald-50 text-emerald-700',
            ],
        ];
    }

    /**
     * Daftar semua insiden (untuk admin).
     */
    public function adminIndex(Request $request): View
    {
        $user = $request->user();

        if (! $user instanceof SupabaseUser) {
            abort(401);
        }

        return view('incidents.admin-index', [
            'incidents' => $this->incidents->all(50),
            ...$this->statusViewData(),
        ]);
    }

    /**
     * Daftar insiden untuk caregiver (hanya yang dilaporkan untuk lansia dalam penugasan).
     */
    public function index(Request $request): View|RedirectResponse
    {
        $user = $request->user();

        if (! $user instanceof SupabaseUser) {
            abort(401);
        }

        if ($user->role()->value === 'admin') {
            return redirect()->route('admin.incidents.index');
        }

        $date = now('Asia/Jakarta')->toDateString();

        return view('incidents.index', [
            'incidents' => $this->incidents->forCaregiver($user->id(), $date),
            ...$this->statusViewData(),
        ]);
    }

    /**
     * Form pelaporan insiden untuk caregiver.
     */
    public function create(Request $request): View
    {
        $this->authorize('create', Incident::class);

        $user = $request->user();

        if (! $user instanceof SupabaseUser) {
            abort(401);
        }

        $date = now('Asia/Jakarta')->toDateString();
        $activeAssignments = $this->assignments->activeForCaregiver($user->id(), $date);
        $residentIds = array_column($activeAssignments, 'resident_id');

        // Kelompokkan assignment berdasarkan resident_id untuk menghindari N+1 query ke Supabase
        $assignmentsByResident = [];
        foreach ($activeAssignments as $assignment) {
            $assignmentsByResident[$assignment['resident_id']][] = $assignment;
        }

        $residents = [];
        if (! empty($residentIds)) {
            // Sesuaikan pemanggilan ini dengan method yang ada di model Resident kamu
            $residentData = $this->residents->read([
                'id' => 'in.('.implode(',', $residentIds).')',
                'limit' => 100,
            ]);

            foreach ($residentData as $r) {
                $r['assignments'] = $assignmentsByResident[$r['id']] ?? [];
                $residents[] = $r;
            }
        }

        return view('incidents.create', [
            'residents' => $residents,
            'categories' => IncidentCategory::cases(),
            'statusLabels' => $this->statusViewData()['statusLabels'],
        ]);
    }

    /**
     * Simpan pelaporan insiden baru.
     */
    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', Incident::class);

        $user = $request->user();

        if (! $user instanceof SupabaseUser) {
            abort(401);
        }

        $validated = $request->validate([
            'resident_id' => 'required|uuid',
            'category' => ['required', 'in:'.implode(',', array_map(fn ($c) => $c->value, IncidentCategory::cases()))],
            'occurred_at' => 'required|date',
            'description' => 'required|string|max:2000',
            'initial_action' => 'nullable|string|max:500',
        ], [
            'resident_id.required' => 'Lansia harus dipilih.',
            'occurred_at.required' => 'Waktu kejadian harus ditentukan.',
            'occurred_at.date' => 'Format waktu tidak valid.',
            'description.required' => 'Kronologi kejadian harus diisi.',
            'description.max' => 'Kronologi maksimal 2000 karakter.',
        ]);

        if (Carbon::parse($validated['occurred_at'])->greaterThan(now('Asia/Jakarta'))) {
            return back()->withErrors(['occurred_at' => 'Waktu kejadian tidak boleh masa depan.']);
        }

        $now = now('Asia/Jakarta')->toIso8601String();

        $created = $this->incidents->create([
            'resident_id' => $validated['resident_id'],
            'reporter_id' => $user->id(),
            'category' => $validated['category'],
            'occurred_at' => $validated['occurred_at'],
            'description' => $validated['description'],
            'initial_action' => $validated['initial_action'] ?? null, // Ubah ke 'action_taken' jika kolom DB-nya itu
            'status' => 'open',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $incidentId = $created['id'] ?? null;

        if (! $incidentId) {
            Log::error('Failed to create incident', ['response' => $created]);

            return back()->withErrors(['resident_id' => 'Gagal menyimpan insiden. Silakan coba lagi.']);
        }

        $auditLogger = new AuditLogger(app(SupabaseClient::class), $user->accessToken());
        $auditLogger->log('create', 'Incident', $incidentId);

        return redirect()
            ->route('incidents.index')
            ->with('status', 'Pelaporan insiden berhasil dikirim dan menunggu tinjauan admin.');
    }

    /**
     * Detail insiden untuk caregiver.
     */
    public function show(Request $request, string $incidentId): View
    {
        $user = $request->user();

        if (! $user instanceof SupabaseUser) {
            abort(401);
        }

        $incident = $this->incidents->find($incidentId);

        if (! $incident) {
            abort(404);
        }

        $this->authorize('view', $incident);

        return view('incidents.show', [
            'incident' => $incident,
            'auditLogs' => $this->incidents->auditLogFor($incidentId),
            ...$this->statusViewData(),
        ]);
    }

    /**
     * Detail admin insiden.
     */
    public function showAdmin(Request $request, string $incidentId): View
    {
        $user = $request->user();

        if (! $user instanceof SupabaseUser || $user->role()->value !== 'admin') {
            abort(403);
        }

        $incident = $this->incidents->find($incidentId);

        if (! $incident) {
            abort(404);
        }

        return view('incidents.admin-show', [
            'incident' => $incident,
            'auditLogs' => $this->incidents->auditLogFor($incidentId),
            ...$this->statusViewData(),
        ]);
    }
}