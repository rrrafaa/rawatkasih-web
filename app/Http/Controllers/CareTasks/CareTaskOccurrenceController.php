<?php

namespace App\Http\Controllers\CareTasks;

use App\Exceptions\SupabaseUnavailable;
use App\Http\Controllers\Controller;
use App\Http\Requests\CareTasks\UpdateCareTaskOccurrenceRequest;
use App\Models\CareTaskOccurrence;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

class CareTaskOccurrenceController extends Controller
{
    public function updateStatus(
        UpdateCareTaskOccurrenceRequest $request,
        string $occurrenceId,
        CareTaskOccurrence $occurrences
    ): RedirectResponse {
        Gate::authorize(
            'update',
            [CareTaskOccurrence::class, $occurrenceId]
        );

        $data = $request->validated();

        try {
            $occurrences->updateStatus($occurrenceId, [
                'status' => $data['status'],
                'reason' => $data['reason'] ?? null,
            ]);

        } catch (SupabaseUnavailable) {
            return back()
                ->withInput()
                ->with('task_feedback', [
                    'type' => 'error',
                    'title' => 'Gagal mengubah status',
                    'message' => 'Status tugas belum berhasil diperbarui. Silakan coba kembali.',
                ]);
        }

        return redirect()
            ->route('caregiver.dashboard')
            ->with('task_feedback', [
                'type' => 'success',
                'title' => 'Status berhasil diperbarui',
                'message' => 'Perubahan status tugas berhasil disimpan.',
            ]);
    }
}