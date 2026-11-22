<?php

namespace App\Http\Controllers;

use App\Models\CaregiverAssignment;
use App\Models\CareTaskOccurrence;
use App\Models\CareTaskTemplate;
use App\Models\FamilyVisits\FamilyVisitData;
use App\Models\Incident;
use App\Models\SupabaseUser;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminDashboardController extends Controller
{
    public function index(
        Request $request,
        CareTaskTemplate $templates,
        CareTaskOccurrence $occurrences,
        Incident $incidents,
        CaregiverAssignment $assignments,
        FamilyVisitData $visitData
    ): View {
        /** @var SupabaseUser $user */
        $user = $request->user();

        if ($user == null) {
            abort(401);
        }

        $today = now('Asia/Jakarta')->toDateString();

        // Statistics
        $stats = [
            'tasks' => $this->getTaskStats($templates, $occurrences, $today),
            'incidents' => $this->getIncidentStats($incidents),
            'visits' => $this->getVisitStats($visitData, $assignments, $today),
        ];

        return view('admin.dashboard', [
            'user' => $user,
            'stats' => $stats,
            'today' => $today,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    protected function getTaskStats(CareTaskTemplate $templates, CareTaskOccurrence $occurrences, string $today): array
    {
        $allTemplates = $templates->allForAdmin(['order' => 'category.asc,created_at.asc']);
        $templateIds = array_column($allTemplates, 'id');

        $todayOccurrences = $occurrences->forTemplatesOnDate($templateIds, $today);

        $total = count($todayOccurrences);
        $completed = count(array_filter($todayOccurrences, fn ($o) => ($o['status'] ?? '') === 'completed'));
        $pending = $total - $completed;

        $byCategory = [
            'care' => 0,
            'admin' => 0,
            'other' => 0,
        ];

        foreach ($todayOccurrences as $occurrence) {
            $category = $occurrence['category'] ?? 'other';
            if (isset($byCategory[$category])) {
                $byCategory[$category]++;
            }
        }

        return [
            'total' => $total,
            'completed' => $completed,
            'pending' => $pending,
            'byCategory' => $byCategory,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function getIncidentStats(Incident $incidents): array
    {
        $allIncidents = $incidents->all([], 100);

        $total = count($allIncidents);
        $open = count(array_filter($allIncidents, fn ($i) => ($i['status'] ?? '') === 'open'));
        $inProgress = count(array_filter($allIncidents, fn ($i) => ($i['status'] ?? '') === 'in_progress'));
        $closed = count(array_filter($allIncidents, fn ($i) => ($i['status'] ?? '') === 'closed'));

        $byCategory = [
            'medical' => 0,
            'security' => 0,
            'maintenance' => 0,
            'other' => 0,
        ];

        foreach ($allIncidents as $incident) {
            $category = $incident['category'] ?? 'other';
            if (isset($byCategory[$category])) {
                $byCategory[$category]++;
            } else {
                $byCategory['other']++;
            }
        }

        return [
            'total' => $total,
            'open' => $open,
            'in_progress' => $inProgress,
            'closed' => $closed,
            'byCategory' => $byCategory,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function getVisitStats(FamilyVisitData $visitData, CaregiverAssignment $assignments, string $today): array
    {
        $assignmentRows = $assignments->activeForCurrentCaregiver($today);
        $residentIds = array_column($assignmentRows, 'resident_id');

        $counts = $visitData->counts();

        return [
            'total' => $counts['pending'] ?? 0,
            'pending' => $counts['pending'] ?? 0,
            'approved' => $counts['approved'] ?? 0,
            'rejected' => $counts['rejected'] ?? 0,
        ];
    }
}
