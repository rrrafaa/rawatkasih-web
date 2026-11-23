<?php

namespace App\Http\Controllers\CareTasks;

use App\Http\Controllers\Controller;
use App\Models\CaregiverAssignment;
use App\Models\CareTaskOccurrence;
use App\Models\CareTaskStatusHistory;
use App\Models\CareTaskTemplate;
use App\Models\Profile;
use App\Models\Resident;
use App\Models\SupabaseUser;
use App\Services\CareTaskOccurrenceGenerator;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CaregiverDashboardController extends Controller
{
    public function index(
        Request $request,
        CareTaskOccurrenceGenerator $generator,
        CaregiverAssignment $assignments,
        Resident $residents,
        CareTaskTemplate $templates,
        CareTaskOccurrence $occurrences,
        CareTaskStatusHistory $history,
        Profile $profiles
    ): View {
        /** @var SupabaseUser $user */
        $user = $request->user();

        $date = now('Asia/Jakarta')->toDateString();

        $viewMode = $request->query('view') === 'history'
            ? 'history'
            : 'tasks';

        $page = max(1, min(1000, $request->integer('page', 1)));

        // Generator hanya berjalan ketika caregiver membuka Tugas Hari Ini.
        if ($viewMode === 'tasks') {
            $generator->generateForCurrentCaregiver();
        }

        $assignmentRows = $assignments->activeForCurrentCaregiver($date);

        $residentIds = array_values(array_unique(
            array_column($assignmentRows, 'resident_id')
        ));

        $residentRows = $residents->forAssignedResidents($residentIds);

        $templateRows = $templates->allForResidents(
            array_column($residentRows, 'id')
        );

        $templatesById = collect($templateRows)->keyBy('id');

        /*
         * Mode Riwayat
         * Mengambil perubahan status lintas tanggal yang diizinkan RLS.
         */
        if ($viewMode === 'history') {
            $perPage = 50;
            $statusFilter = $request->query('status');

            $allowedStatuses = [
                'pending',
                'completed',
                'postponed',
                'missed',
            ];

            if (!in_array($statusFilter, $allowedStatuses, true)) {
                $statusFilter = null;
            }

            $historyRows = [];

            if ($residentRows !== [] && $templateRows !== []) {
                $historyRows = $history->latestForCurrentCaregiver(
                    $page,
                    $perPage,
                    $statusFilter
                );
            }

            $historyOccurrenceIds = array_values(
                collect($historyRows)
                    ->pluck('occurrence_id')
                    ->filter(fn ($id) => is_string($id) && $id !== '')
                    ->unique()
                    ->values()
                    ->all()
            );

            $historyOccurrenceRows = $occurrences->forIds(
                $historyOccurrenceIds
            );

            $occurrencesById = collect($historyOccurrenceRows)->keyBy('id');

            $residentsById = collect($residentRows)->keyBy('id');

            $changedByIds = array_values(
                collect($historyRows)
                    ->pluck('changed_by')
                    ->filter(fn ($id) => is_string($id) && $id !== '')
                    ->unique()
                    ->values()
                    ->all()
            );

            $changedByNames = [];

            if ($changedByIds !== []) {
                $token = $request->session()->get('supabase.access_token');

                abort_unless(is_string($token) && $token !== '', 401);

                $changedByNames = $profiles->namesByIds(
                    $changedByIds,
                    $token
                );
            }

            $historyList = [];

            foreach ($historyRows as $historyRow) {
                $occurrence = $occurrencesById->get(
                    $historyRow['occurrence_id']
                );

                if ($occurrence === null) {
                    continue;
                }

                $template = $templatesById->get(
                    $occurrence['template_id']
                );

                if ($template === null) {
                    continue;
                }

                $resident = $residentsById->get(
                    $template['resident_id']
                );

                if ($resident === null) {
                    continue;
                }

                $changedById = $historyRow['changed_by'] ?? null;

                $historyList[] = array_merge($historyRow, [
                    'task_title' => $occurrence['title'],
                    'task_date' => $occurrence['task_date'],
                    'scheduled_time' => $occurrence['scheduled_time'],
                    'resident_name' => $resident['full_name'],
                    'changed_by_name' => is_string($changedById)
                        ? ($changedByNames[$changedById] ?? null)
                        : null,
                ]);
            }

            return view('caregiver.dashboard', [
                'user' => $user,
                'date' => $date,
                'residents' => $residentRows,
                'viewMode' => 'history',
                'historyRows' => $historyList,
                'historyPage' => $page,
                'hasMoreHistory' => count($historyRows) === $perPage,
                'historyStatusFilter' => $statusFilter,
            ]);
        }

        /*
         * Mode Tugas Hari Ini
         */
        $occurrenceRows = $occurrences->forTemplatesOnDate(
            array_column($templateRows, 'id'),
            $date
        );

        // Ambil nama caregiver yang menyelesaikan tugas.
        $completedByIds = array_values(
            collect($occurrenceRows)
                ->pluck('completed_by')
                ->filter(fn ($id) => is_string($id) && $id !== '')
                ->unique()
                ->values()
                ->all()
        );

        $completedByNames = [];

        if ($completedByIds !== []) {
            $token = $request->session()->get('supabase.access_token');

            abort_unless(is_string($token) && $token !== '', 401);

            $completedByNames = $profiles->namesByIds(
                $completedByIds,
                $token
            );
        }

        foreach ($occurrenceRows as &$occurrence) {
            $completedById = $occurrence['completed_by'] ?? null;

            $occurrence['completed_by_name'] = is_string($completedById)
                ? ($completedByNames[$completedById] ?? null)
                : null;
        }

        unset($occurrence);

        $tasksByResident = [];

        foreach ($occurrenceRows as $occurrence) {
            $template = $templatesById->get($occurrence['template_id']);

            if ($template === null) {
                continue;
            }

            $tasksByResident[$template['resident_id']][] = $occurrence;
        }

        $residentList = collect($residentRows)->map(
            function (array $resident) use ($tasksByResident) {
                $residentTasks = $tasksByResident[$resident['id']] ?? [];
                $tasks = collect($residentTasks);

                $total = $tasks->count();
                $completed = $tasks->where('status', 'completed')->count();

                $resident['tasks'] = $residentTasks;

                $resident['task_summary'] = [
                    'total' => $total,
                    'completed' => $completed,
                    'pending' => $tasks->where('status', 'pending')->count(),
                    'postponed' => $tasks->where('status', 'postponed')->count(),
                    'missed' => $tasks->where('status', 'missed')->count(),
                    'completion_percentage' => $total > 0
                        ? round(($completed / $total) * 100, 1)
                        : 0,
                ];

                return $resident;
            }
        )->all();

        $allTasks = collect($residentList)
            ->flatMap(fn (array $resident) => $resident['tasks']);

        return view('caregiver.dashboard', [
            'user' => $user,
            'date' => $date,
            'residents' => $residentList,
            'viewMode' => 'tasks',
            'taskCount' => $allTasks->count(),
            'completedCount' => $allTasks->where('status', 'completed')->count(),
            'pendingCount' => $allTasks->where('status', 'pending')->count(),
            'postponedCount' => $allTasks->where('status', 'postponed')->count(),
            'missedCount' => $allTasks->where('status', 'missed')->count(),
        ]);
    }
}
