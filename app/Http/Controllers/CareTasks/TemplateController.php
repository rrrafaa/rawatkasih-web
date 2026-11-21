<?php

namespace App\Http\Controllers\CareTasks;

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
        return view('admin.care-tasks.templates.index', [
            'user' => $request->user(),
            'residents' => $residents->activeResidents(),
            'templates' => $templates->allForAdmin(),
        ]);
    }

    public function store(
        StoreCareTaskTemplateRequest $request,
        CareTaskTemplate $templates,
        Resident $residents
    ): RedirectResponse {
        $data = $request->validated();

        $resident = $residents->findForCurrentUser($data['resident_id']);

        abort_unless(
            $resident !== null && $resident['status'] === 'active',
            422,
            'Lansia tidak ditemukan atau tidak aktif.'
        );

        $frequency = $data['frequency'];

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

        return redirect()
            ->route('care-tasks.templates.index')
            ->with('status', 'Template tugas berhasil ditambahkan.');
    }
    public function update(
        StoreCareTaskTemplateRequest $request,
        string $templateId,
        CareTaskTemplate $templates,
        Resident $residents
    ): RedirectResponse {
        $data = $request->validated();

        $resident = $residents->findForCurrentUser($data['resident_id']);

        abort_unless(
            $resident !== null && $resident['status'] === 'active',
            422,
            'Lansia tidak ditemukan atau tidak aktif.'
        );

        $frequency = $data['frequency'];

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

        return redirect()
            ->route('care-tasks.templates.index')
            ->with('status', 'Template tugas berhasil diperbarui.');
    }
    public function setStatus(
        Request $request,
        string $templateId,
        CareTaskTemplate $templates
    ): RedirectResponse {
        $data = $request->validate([
            'is_active' => ['required', 'boolean'],
        ]);

        $isActive = (bool) $data['is_active'];

        $templates->updateForAdmin($templateId, [
            'is_active' => $isActive,
        ]);

        return redirect()
            ->route('care-tasks.templates.index')
            ->with(
                'status',
                $isActive
                    ? 'Template tugas berhasil diaktifkan.'
                    : 'Template tugas berhasil dinonaktifkan.'
            );
    }
}