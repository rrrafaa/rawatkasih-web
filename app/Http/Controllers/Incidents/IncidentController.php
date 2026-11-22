<?php

namespace App\Http\Controllers\Incidents;

use App\Enums\IncidentCategory;
use App\Http\Controllers\Controller;
use App\Models\CaregiverAssignment;
use App\Models\Incident;
use App\Models\Resident;
use App\Models\SupabaseUser;
use App\Services\AuditLogger;
use App\Services\SupabaseClient;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class IncidentController extends Controller
{
    /**
     * Daftar semua insiden (untuk admin).
     */
    public function adminIndex(
        Request $request,
        Incident $incidents
    ): View {
        /** @var SupabaseUser $user */
        $user = $request->user();

        if (! $user instanceof SupabaseUser) {
            abort(401);
        }

        // Dapatkan semua insiden dengan joins
        $incidentsData = $incidents->all();

        // Proses data untuk view: ekstrak nama resident & reporter
        $processedIncidents = $this->flattenIncidents($incidentsData);

        return view('incidents.admin-index', [
            'user' => $user,
            'incidents' => $processedIncidents,
            'statusLabels' => $this->statusLabels(),
            'statusColors' => $this->statusColors(),
        ]);
    }

    /**
     * Daftar insiden untuk caregiver.
     */
    public function index(
        Request $request,
        Incident $incidents,
        CaregiverAssignment $assignments,
        Resident $residents
    ): View|RedirectResponse {
        /** @var SupabaseUser $user */
        $user = $request->user();

        if (! $user instanceof SupabaseUser) {
            abort(401);
        }

        if ($user->role()->value === 'admin') {
            return redirect()->route('admin.incidents.index');
        }

        $date = now('Asia/Jakarta')->toDateString();

        // Dapatkan penugasan aktif caregiver
        $assignmentRows = $assignments->activeForCurrentCaregiver($date);
        $assignedResidentIds = array_values(array_unique(
            array_column($assignmentRows, 'resident_id')
        ));

        $residentRows = $residents->forAssignedResidents($assignedResidentIds);

        // Dapatkan insiden untuk caregiver
        $incidentsData = $incidents->forCaregiver($user->id(), $date);

        // Proses data untuk view
        $processedIncidents = $this->flattenIncidents($incidentsData);

        return view('incidents.index', [
            'user' => $user,
            'incidents' => $processedIncidents,
            'statusLabels' => $this->statusLabels(),
            'statusColors' => $this->statusColors(),
        ]);
    }

    /**
     * Form pelaporan insiden untuk caregiver.
     */
    public function create(
        Request $request,
        Incident $incidents,
        Resident $residents
    ): View {
        /** @var SupabaseUser $user */
        $user = $request->user();

        if (! $user instanceof SupabaseUser) {
            abort(401);
        }

        // Dapatkan SEMUA lansia aktif (bukan hanya yang ditugaskan hari ini)
        $residentRows = $residents->activeResidents();

        // Flatten resident data menjadi array untuk view
        $residentsList = [];
        foreach ($residentRows as $resident) {
            $residentsList[] = (object) $resident;
        }

        return view('incidents.create', [
            'residents' => $residentsList,
            'categories' => IncidentCategory::cases(),
            'user' => $user,
            'statusLabels' => $this->statusLabels(),
        ]);
    }

    /**
     * Simpan pelaporan insiden baru.
     */
    public function store(
        Request $request,
        Incident $incidents
    ): RedirectResponse {
        /** @var SupabaseUser $user */
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
            'occurred_at.before_or_equal' => 'Waktu kejadian tidak boleh masa depan.',
            'description.required' => 'Kronologi kejadian harus diisi.',
            'description.max' => 'Kronologi maksimal 2000 karakter.',
        ]);

        // Pastikan waktu tidak masa depan
        if (Carbon::parse($validated['occurred_at'])->greaterThan(now('Asia/Jakarta'))) {
            return back()->withErrors(['occurred_at' => 'Waktu kejadian tidak boleh masa depan.']);
        }

        $now = now('Asia/Jakarta')->toIso8601String();

        $incidentData = [
            'resident_id' => $validated['resident_id'],
            'reporter_id' => $user->id(),
            'category' => $validated['category'],
            'occurred_at' => $validated['occurred_at'],
            'description' => $validated['description'],
            'action_taken' => $validated['initial_action'] ?? null,
            'status' => 'open',
            'created_at' => $now,
            'updated_at' => $now,
        ];

        $created = $incidents->create($incidentData);

        $incidentId = $created['id'] ?? null;

        if (! $incidentId) {
            return back()->withErrors(['resident_id' => 'Gagal menyimpan insiden. Silakan coba lagi.']);
        }

        // Catat audit log
        $auditLogger = new AuditLogger(app(SupabaseClient::class));
        $auditLogger->log('create', 'Incident', $incidentId);

        return redirect()
            ->route('incidents.index')
            ->with('status', 'Pelaporan insiden berhasil dikirim dan menunggu tinjauan admin.');
    }

    /**
     * Detail insiden untuk caregiver.
     */
    public function show(
        Request $request,
        string $incidentId,
        Incident $incidents
    ): View {
        /** @var SupabaseUser $user */
        $user = $request->user();

        if (! $user instanceof SupabaseUser) {
            abort(401);
        }

        $incident = $incidents->find($incidentId);

        if (! $incident) {
            abort(404);
        }

        // $this->authorize('view', $incident);

        // Flatten incident data untuk view
        $incident = $this->flattenIncidents([$incident])[0] ?? $incident;

        return view('incidents.show', [
            'incident' => $incident,
            'user' => $user,
            'auditLogs' => $incidents->auditLogFor($incidentId),
            'statusLabels' => $this->statusLabels(),
            'statusColors' => $this->statusColors(),
        ]);
    }

    /**
     * Detail admin insiden.
     */
    public function showAdmin(
        Request $request,
        string $incidentId,
        Incident $incidents
    ): View {
        /** @var SupabaseUser $user */
        $user = $request->user();

        if (! $user instanceof SupabaseUser || $user->role()->value !== 'admin') {
            abort(403);
        }

        $incident = $incidents->find($incidentId);

        if (! $incident) {
            abort(404);
        }

        // Flatten incident data untuk view
        $incident = $this->flattenIncidents([$incident])[0] ?? $incident;

        return view('incidents.admin-show', [
            'incident' => $incident,
            'user' => $user,
            'auditLogs' => $incidents->auditLogFor($incidentId),
            'statusLabels' => $this->statusLabels(),
            'statusColors' => $this->statusColors(),
        ]);
    }

    /**
     * Update incident status (admin only).
     */
    public function update(
        Request $request,
        string $incidentId,
        Incident $incidents
    ): RedirectResponse {
        /** @var SupabaseUser $user */
        $user = $request->user();

        if (! $user instanceof SupabaseUser) {
            abort(401);
        }

        $incident = $incidents->find($incidentId);

        if (! $incident) {
            abort(404);
        }

        $status = $request->input('status');
        $allowedStatuses = ['open', 'in_progress', 'closed'];

        if (! in_array($status, $allowedStatuses, true)) {
            return back()->withErrors(['status' => 'Status tidak valid.']);
        }

        $updated = $incidents->update($incidentId, [
            'status' => $status,
            'updated_at' => now('Asia/Jakarta')->toIso8601String(),
        ]);

        // Catat audit log
        $auditLogger = new AuditLogger(app(SupabaseClient::class));
        $auditLogger->log('update', 'Incident', $incidentId);

        return redirect()
            ->route('admin.incidents.show', $incidentId)
            ->with('status', 'Status insiden telah diperbarui.');
    }

    /**
     * Audit log untuk insiden (admin only).
     */
    public function auditLog(
        Request $request,
        string $incidentId,
        Incident $incidents
    ): View {
        /** @var SupabaseUser $user */
        $user = $request->user();

        if (! $user instanceof SupabaseUser || $user->role()->value !== 'admin') {
            abort(403);
        }

        $incident = $incidents->find($incidentId);

        if (! $incident) {
            abort(404);
        }

        // Flatten incident data untuk view
        $incident = $this->flattenIncidents([$incident])[0] ?? $incident;

        return view('incidents.audit-log', [
            'incident' => $incident,
            'auditLogs' => $incidents->auditLogFor($incidentId),
            'statusLabels' => $this->statusLabels(),
        ]);
    }

    /**
     * Status labels untuk view.
     * Maps both enum values and DB values to display labels.
     */
    protected function statusLabels(): array
    {
        return [
            'open' => 'Terbuka',
            'in_progress' => 'Sedang ditindaklanjuti', // DB value
            'handling' => 'Sedang ditindaklanjuti',  // Enum value
            'closed' => 'Ditutup',
            // Category labels
            'medical' => 'Medis',
            'security' => 'Keamanan',
            'maintenance' => 'Perawatan',
            'other' => 'Lainnya',
        ];
    }

    /**
     * Status colors untuk view.
     */
    protected function statusColors(): array
    {
        return [
            'open' => 'bg-amber-50 text-amber-700',
            'in_progress' => 'bg-blue-50 text-blue-700',
            'handling' => 'bg-blue-50 text-blue-700',
            'closed' => 'bg-emerald-50 text-emerald-700',
            // Category colors
            'medical' => 'bg-red-100 text-red-800',
            'security' => 'bg-red-100 text-red-800',
            'maintenance' => 'bg-blue-100 text-blue-800',
            'other' => 'bg-gray-100 text-gray-800',
        ];
    }

    /**
     * Flatten incident data untuk view.
     * Mengganti array resident/reporter dengan nama lengkap string.
     * Menyediakan alias initial_action untuk action_taken.
     *
     * @param  array<int, array<string, mixed>>  $incidents
     * @return array<int, array<string, mixed>>
     */
    protected function flattenIncidents(array $incidents): array
    {
        $result = [];

        foreach ($incidents as $incident) {
            $flattened = $incident;

            // Ekstrak resident name dari join data
            // Jika resident adalah array (dari join), ambil full_name
            // Jika sudah string, gunakan langsung
            // Jika tidak ada, fallback ke resident_id atau 'Lansia'
            $residentValue = $incident['resident'] ?? null;
            if (is_array($residentValue)) {
                $flattened['resident'] = $residentValue['full_name'] ?? ($incident['resident_id'] ?? 'Lansia');
            } elseif (is_string($residentValue)) {
                // Sudah string, tidak perlu diubah
            } else {
                $flattened['resident'] = ($incident['resident_id'] ?? 'Lansia');
            }

            // Ekstrak reporter name dari join data
            if (is_array($incident['reporter'] ?? null)) {
                $flattened['reporter_name'] = $incident['reporter']['full_name'] ?? null;
            }

            // Alias: initial_action = action_taken (untuk kompatibilitas view)
            if (! isset($flattened['initial_action']) && isset($flattened['action_taken'])) {
                $flattened['initial_action'] = $flattened['action_taken'];
            }

            $result[] = $flattened;
        }

        return $result;
    }
}
