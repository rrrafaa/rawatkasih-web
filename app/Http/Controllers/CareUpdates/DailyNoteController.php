<?php

namespace App\Http\Controllers\CareUpdates;

use App\Http\Controllers\Controller;
use App\Http\Requests\CareUpdates\StoreDailyNoteRequest;
use App\Models\CaregiverAssignment;
use App\Models\DailyNote;
use App\Models\Profile;
use App\Models\Resident;
use App\Models\SupabaseUser;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DailyNoteController extends Controller
{
    public function index(
        Request $request,
        CaregiverAssignment $assignments,
        Resident $residents,
        DailyNote $dailyNotes,
        Profile $profiles
    ): View {
        /** @var SupabaseUser $user */
        $user = $request->user();

        $date = now('Asia/Jakarta')->toDateString();

        // Ambil penugasan aktif caregiver yang sedang login
        $assignmentRows = $assignments->activeForCurrentCaregiver($date);

        $assignedResidentIds = array_values(array_unique(
            array_column($assignmentRows, 'resident_id')
        ));

        $residentRows = $residents->forAssignedResidents($assignedResidentIds);
        $residentsById = collect($residentRows)->keyBy('id');

        // Filter opsional berdasarkan resident yang dipilih di query
        $selectedResidentId = $request->query('resident');
        if (
            ! is_string($selectedResidentId) ||
            ! $residentsById->has($selectedResidentId)
        ) {
            $selectedResidentId = null;
        }

        $queryResidentIds = $selectedResidentId !== null
            ? [$selectedResidentId]
            : array_column($residentRows, 'id');

        $page = max(1, min(1000, $request->integer('page', 1)));
        $perPage = 25;

        $noteRows = [];
        if ($queryResidentIds !== []) {
            $noteRows = $dailyNotes->forResidents($queryResidentIds, $page, $perPage);
        }

        // Ambil nama pencatat (caregiver)
        $caregiverIds = array_values(array_unique(array_filter(
            array_column($noteRows, 'caregiver_id'),
            fn ($id) => is_string($id) && $id !== ''
        )));

        $caregiverNames = [];
        if ($caregiverIds !== []) {
            $token = $request->session()->get('supabase.access_token');
            if (is_string($token) && $token !== '') {
                $caregiverNames = $profiles->namesByIds($caregiverIds, $token);
            }
        }

        $notesList = [];
        foreach ($noteRows as $row) {
            $rId = $row['resident_id'] ?? null;
            $cgId = $row['caregiver_id'] ?? null;

            $notesList[] = array_merge($row, [
                'resident_name' => is_string($rId) && $residentsById->has($rId)
                    ? $residentsById->get($rId)['full_name']
                    : 'Lansia',
                'resident_room' => is_string($rId) && $residentsById->has($rId)
                    ? ($residentsById->get($rId)['room'] ?? '-')
                    : '-',
                'caregiver_name' => is_string($cgId) && isset($caregiverNames[$cgId])
                    ? $caregiverNames[$cgId]
                    : ($cgId === $user->id() ? $user->name() : 'Caregiver'),
            ]);
        }

        return view('modules.care-updates.daily-notes.index', [
            'user' => $user,
            'date' => $date,
            'residents' => $residentRows,
            'selectedResidentId' => $selectedResidentId,
            'notes' => $notesList,
            'page' => $page,
            'hasMore' => count($noteRows) === $perPage,
        ]);
    }

    public function store(
        StoreDailyNoteRequest $request,
        DailyNote $dailyNotes
    ): RedirectResponse {
        /** @var SupabaseUser $user */
        $user = $request->user();

        $data = $request->validated();

        $dailyNotes->createForCurrentCaregiver([
            'resident_id' => $data['resident_id'],
            'caregiver_id' => $user->id(),
            'mood' => filled($data['mood'] ?? null) ? trim($data['mood']) : null,
            'meal_note' => filled($data['meal_note'] ?? null) ? trim($data['meal_note']) : null,
            'internal_note' => trim($data['internal_note']),
            'recorded_at' => now('Asia/Jakarta')->toIso8601String(),
        ]);

        return redirect()
            ->route('care-updates.daily-notes.index', ['resident' => $data['resident_id']])
            ->with('status', 'Catatan harian internal berhasil ditambahkan.');
    }
}
