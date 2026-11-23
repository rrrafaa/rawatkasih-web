<?php

namespace App\Http\Controllers\CareTasks;

use App\Exceptions\SupabaseUnavailable;
use App\Http\Controllers\Controller;
use App\Http\Requests\CareTasks\StoreCareTaskTemplateRequest;
use App\Models\CareTaskTemplate;
use App\Models\Resident;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TemplateController extends Controller
{
    public function index(
        Request $request,
        Resident $residents,
        CareTaskTemplate $templates
    ): View {
        $filters = $request->validate([
            'status' => ['nullable', 'in:active,inactive'],
            'resident_id' => ['nullable', 'uuid'],
            'page' => ['nullable', 'integer', 'min:1', 'max:100000'],
        ]);

        // Default tampilan adalah template aktif.
        $statusFilter = $filters['status'] ?? 'active';
        $selectedResidentId = $filters['resident_id'] ?? '';
        $requestedPage = (int) ($filters['page'] ?? 1);

        // Lansia aktif untuk form tambah/edit.
        $activeResidents = $residents->activeResidents();

        // Semua lansia untuk dropdown filter.
        $residentOptions = $residents->allForAdmin();

        // Ambil seluruh template dari Supabase.
        $allTemplates = $templates->allForAdmin();

        // Terapkan filter lansia terlebih dahulu.
        $residentScopedTemplates = array_values(
            array_filter(
                $allTemplates,
                function ($template) use ($selectedResidentId) {
                    return $selectedResidentId === ''
                        || ($template['resident_id'] ?? null) === $selectedResidentId;
                }
            )
        );

        // Pisahkan template aktif dan nonaktif.
        $activeTemplates = array_values(
            array_filter(
                $residentScopedTemplates,
                fn ($template) => filter_var(
                    $template['is_active'] ?? false,
                    FILTER_VALIDATE_BOOLEAN
                )
            )
        );

        $inactiveTemplates = array_values(
            array_filter(
                $residentScopedTemplates,
                fn ($template) => ! filter_var(
                    $template['is_active'] ?? false,
                    FILTER_VALIDATE_BOOLEAN
                )
            )
        );

        // Hitung jumlah berdasarkan hasil filter lansia.
        $activeTemplateCount = count($activeTemplates);
        $inactiveTemplateCount = count($inactiveTemplates);

        // Tentukan data yang akan ditampilkan.
        $filteredTemplates = $statusFilter === 'active'
            ? $activeTemplates
            : $inactiveTemplates;

        // Pagination.
        $perPage = 10;
        $totalFilteredTemplates = count($filteredTemplates);

        $totalPages = max(
            1,
            (int) ceil($totalFilteredTemplates / $perPage)
        );

        $currentPage = min($requestedPage, $totalPages);

        $paginatedTemplates = array_slice(
            $filteredTemplates,
            ($currentPage - 1) * $perPage,
            $perPage
        );

        return view('admin.care-tasks.templates.index', [
            'user' => $request->user(),

            'residents' => $activeResidents,
            'residentOptions' => $residentOptions,
            'selectedResidentId' => $selectedResidentId,

            'templates' => $paginatedTemplates,

            'templateStatusFilter' => $statusFilter,

            'activeTemplateCount' => $activeTemplateCount,
            'inactiveTemplateCount' => $inactiveTemplateCount,

            'totalFilteredTemplates' => $totalFilteredTemplates,
            'templatePage' => $currentPage,
            'templatePerPage' => $perPage,
            'templateTotalPages' => $totalPages,
        ]);
    }

    public function store(
        StoreCareTaskTemplateRequest $request,
        CareTaskTemplate $templates,
        Resident $residents
    ): RedirectResponse {
        $data = $request->validated();

        $resident = $residents->findForCurrentUser(
            $data['resident_id']
        );

        if ($resident === null || $resident['status'] !== 'active') {
            return back()
                ->withInput()
                ->withErrors([
                    'resident_id' => 'Lansia tidak ditemukan atau tidak aktif.',
                ]);
        }

        $frequency = $data['frequency'];

        try {
            $templates->createForAdmin([
                'resident_id' => $data['resident_id'],
                'title' => $data['title'],
                'category' => $data['category'],
                'scheduled_time' => $data['scheduled_time'],
                'instructions' => $data['instructions'] ?? null,
                'frequency' => $frequency,
                'days_of_week' => $frequency === 'daily'
                    ? []
                    : array_map('intval', $data['days_of_week']),
            ]);
        } catch (SupabaseUnavailable) {
            return back()
                ->withInput()
                ->with('task_feedback', [
                    'type' => 'error',
                    'title' => 'Gagal menyimpan template',
                    'message' => 'Template belum berhasil disimpan. Silakan coba kembali.',
                ]);
        }

        return redirect()
            ->route('care-tasks.templates.index', [
                'status' => 'active',
            ])
            ->with('task_feedback', [
                'type' => 'success',
                'title' => 'Template berhasil ditambahkan',
                'message' => 'Template tugas baru sudah berhasil disimpan.',
            ]);
    }

    public function update(
        StoreCareTaskTemplateRequest $request,
        string $templateId,
        CareTaskTemplate $templates,
        Resident $residents
    ): RedirectResponse {
        $data = $request->validated();

        $resident = $residents->findForCurrentUser(
            $data['resident_id']
        );

        if ($resident === null || $resident['status'] !== 'active') {
            return back()
                ->withInput()
                ->withErrors([
                    'resident_id' => 'Lansia tidak ditemukan atau tidak aktif.',
                ]);
        }

        $frequency = $data['frequency'];

        try {
            $templates->updateForAdmin($templateId, [
                'resident_id' => $data['resident_id'],
                'title' => $data['title'],
                'category' => $data['category'],
                'scheduled_time' => $data['scheduled_time'],
                'instructions' => $data['instructions'] ?? null,
                'frequency' => $frequency,
                'days_of_week' => $frequency === 'daily'
                    ? []
                    : array_map('intval', $data['days_of_week']),
            ]);
        } catch (SupabaseUnavailable) {
            return back()
                ->withInput()
                ->with('task_feedback', [
                    'type' => 'error',
                    'title' => 'Gagal memperbarui template',
                    'message' => 'Perubahan template belum berhasil disimpan.',
                ]);
        }

        return redirect()
            ->route('care-tasks.templates.index', [
                'status' => 'active',
            ])
            ->with('task_feedback', [
                'type' => 'success',
                'title' => 'Template berhasil diperbarui',
                'message' => 'Perubahan template tugas berhasil disimpan.',
            ]);
    }

    public function setStatus(
        Request $request,
        string $templateId,
        CareTaskTemplate $templates
    ): RedirectResponse {
        $data = $request->validate([
            'is_active' => ['required', 'boolean'],
            'resident_id' => ['nullable', 'uuid'],
        ]);

        $isActive = (bool) $data['is_active'];

        try {
            $templates->updateForAdmin($templateId, [
                'is_active' => $isActive,
            ]);
        } catch (SupabaseUnavailable) {
            return back()->with('task_feedback', [
                'type' => 'error',
                'title' => 'Gagal mengubah status',
                'message' => 'Status template belum berhasil diperbarui. Silakan coba kembali.',
            ]);
        }

        $redirectParams = [
            'status' => $isActive ? 'active' : 'inactive',
        ];

        if (! empty($data['resident_id'])) {
            $redirectParams['resident_id'] = $data['resident_id'];
        }

        return redirect()
            ->route('care-tasks.templates.index', $redirectParams)
            ->with('task_feedback', [
                'type' => 'success',
                'title' => $isActive
                    ? 'Template berhasil diaktifkan'
                    : 'Template berhasil dinonaktifkan',
                'message' => $isActive
                    ? 'Status template sekarang Aktif.'
                    : 'Status template sekarang Nonaktif.',
            ]);
    }
}