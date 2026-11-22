<?php

namespace App\Http\Controllers\CareTasks;

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
        Gate::authorize('update', [CareTaskOccurrence::class, $occurrenceId]);

        $data = $request->validated();

        $occurrences->updateStatus($occurrenceId, [
            'status' => $data['status'],
            'reason' => $data['reason'] ?? null,
        ]);

        return redirect()
            ->route('caregiver.dashboard')
            ->with('status', 'Status tugas berhasil diperbarui.');
    }
}
