<?php

use App\Models\SupabaseUser;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    config([
        'supabase.url' => 'https://example.supabase.co',
        'supabase.publishable_key' => 'test-key',
        'session.encrypt' => false,
    ]);

    Http::preventStrayRequests();

    $this->actingAs(new SupabaseUser([
        'id' => '10000000-0000-4000-8000-000000000001',
        'full_name' => 'Dewi',
        'role' => 'caregiver',
    ]))->withSession([
        'supabase' => [
            'access_token' => 'caregiver-access',
        ],
    ]);
});

test('caregiver page includes the name of the person who completed a task', function () {
    $residentId = '30000000-0000-4000-8000-000000000001';
    $templateId = '40000000-0000-4000-8000-000000000001';
    $occurrenceId = '50000000-0000-4000-8000-000000000001';
    $caregiverId = '10000000-0000-4000-8000-000000000001';

    $date = now('Asia/Jakarta')->toDateString();

    Http::fake([
        '*/rest/v1/rpc/generate_today_care_task_occurrences' => Http::response('0', 200),

        '*/rest/v1/caregiver_assignments*' => Http::response([
            [
                'id' => '60000000-0000-4000-8000-000000000001',
                'user_id' => $caregiverId,
                'resident_id' => $residentId,
                'start_date' => '2026-11-01',
                'end_date' => null,
            ],
        ], 200),

        '*/rest/v1/residents*' => Http::response([
            [
                'id' => $residentId,
                'full_name' => 'Ibu Sri',
                'room' => 'A01',
                'status' => 'active',
            ],
        ], 200),

        '*/rest/v1/care_task_templates*' => Http::response([
            [
                'id' => $templateId,
                'resident_id' => $residentId,
                'title' => 'Makan pagi',
                'category' => 'Makan',
                'scheduled_time' => '07:00:00',
                'instructions' => 'Bubur saring dan teh hangat',
                'is_active' => true,
            ],
        ], 200),

        '*/rest/v1/care_task_occurrences*' => Http::response([
            [
                'id' => $occurrenceId,
                'template_id' => $templateId,
                'task_date' => $date,
                'title' => 'Makan pagi',
                'category' => 'Makan',
                'scheduled_time' => '07:00:00',
                'instructions' => 'Bubur saring dan teh hangat',
                'status' => 'completed',
                'reason' => null,
                'completed_by' => $caregiverId,
                'completed_at' => '2026-11-21T00:42:00Z',
            ],
        ], 200),

        '*/rest/v1/profiles*' => Http::response([
            [
                'id' => $caregiverId,
                'full_name' => 'Dewi',
            ],
        ], 200),
    ]);

    $this->get(route('caregiver.dashboard'))
        ->assertOk()
        ->assertViewHas('residents', function ($residents) use ($caregiverId) {
            if (count($residents) !== 1) {
                return false;
            }

            $resident = $residents[0];
            $summary = $resident['task_summary'] ?? [];

            return $resident['full_name'] === 'Ibu Sri'
                && $resident['tasks'][0]['completed_by'] === $caregiverId
                && $resident['tasks'][0]['completed_by_name'] === 'Dewi'
                && ($summary['total'] ?? null) === 1
                && ($summary['completed'] ?? null) === 1
                && ($summary['pending'] ?? null) === 0
                && ($summary['postponed'] ?? null) === 0
                && ($summary['missed'] ?? null) === 0
                && ($summary['completion_percentage'] ?? null) === 100.0;
        });

    Http::assertSent(function ($request) use ($caregiverId) {
        parse_str(
            (string) parse_url($request->url(), PHP_URL_QUERY),
            $query
        );

        return $request->method() === 'GET'
            && parse_url($request->url(), PHP_URL_PATH) === '/rest/v1/profiles'
            && ($query['select'] ?? null) === 'id,full_name'
            && ($query['id'] ?? null) === 'in.('.$caregiverId.')'
            && $request->hasHeader(
                'Authorization',
                'Bearer caregiver-access'
            );
    });
});

test('caregiver page does not request profiles when no task has been completed', function () {
    $residentId = '30000000-0000-4000-8000-000000000001';
    $templateId = '40000000-0000-4000-8000-000000000001';

    $date = now('Asia/Jakarta')->toDateString();

    Http::fake([
        '*/rest/v1/rpc/generate_today_care_task_occurrences' => Http::response('0', 200),

        '*/rest/v1/caregiver_assignments*' => Http::response([
            [
                'id' => '60000000-0000-4000-8000-000000000001',
                'user_id' => '10000000-0000-4000-8000-000000000001',
                'resident_id' => $residentId,
                'start_date' => '2026-11-01',
                'end_date' => null,
            ],
        ], 200),

        '*/rest/v1/residents*' => Http::response([
            [
                'id' => $residentId,
                'full_name' => 'Ibu Sri',
                'room' => 'A01',
                'status' => 'active',
            ],
        ], 200),

        '*/rest/v1/care_task_templates*' => Http::response([
            [
                'id' => $templateId,
                'resident_id' => $residentId,
                'title' => 'Makan pagi',
                'category' => 'Makan',
                'scheduled_time' => '07:00:00',
                'instructions' => 'Bubur saring dan teh hangat',
                'is_active' => true,
            ],
        ], 200),

        '*/rest/v1/care_task_occurrences*' => Http::response([
            [
                'id' => '50000000-0000-4000-8000-000000000001',
                'template_id' => $templateId,
                'task_date' => $date,
                'title' => 'Makan pagi',
                'category' => 'Makan',
                'scheduled_time' => '07:00:00',
                'instructions' => 'Bubur saring dan teh hangat',
                'status' => 'pending',
                'reason' => null,
                'completed_by' => null,
                'completed_at' => null,
            ],
        ], 200),
    ]);

    $this->get(route('caregiver.dashboard'))
        ->assertOk()
        ->assertViewHas('residents', function ($residents) {
            return count($residents) === 1
                && $residents[0]['tasks'][0]['status'] === 'pending'
                && $residents[0]['tasks'][0]['completed_by_name'] === null;
        });

    Http::assertSentCount(5);
});

test('caregiver can view history with task resident and actor details', function () {
    $residentId = '30000000-0000-4000-8000-000000000001';
    $templateId = '40000000-0000-4000-8000-000000000001';
    $occurrenceId = '50000000-0000-4000-8000-000000000001';
    $caregiverId = '10000000-0000-4000-8000-000000000001';

    Http::fake([
        '*/rest/v1/caregiver_assignments*' => Http::response([
            [
                'id' => '60000000-0000-4000-8000-000000000001',
                'user_id' => $caregiverId,
                'resident_id' => $residentId,
                'start_date' => '2026-11-01',
                'end_date' => null,
            ],
        ], 200),

        '*/rest/v1/residents*' => Http::response([
            [
                'id' => $residentId,
                'full_name' => 'Ibu Sri',
                'room' => 'A01',
                'status' => 'active',
            ],
        ], 200),

        '*/rest/v1/care_task_templates*' => Http::response([
            [
                'id' => $templateId,
                'resident_id' => $residentId,
                'title' => 'Makan pagi',
                'category' => 'Makan',
                'scheduled_time' => '07:00:00',
                'instructions' => 'Bubur saring',
                'is_active' => false,
            ],
        ], 200),

        '*/rest/v1/care_task_status_history*' => Http::response([
            [
                'id' => 10,
                'occurrence_id' => $occurrenceId,
                'old_status' => 'pending',
                'new_status' => 'completed',
                'reason' => null,
                'changed_by' => $caregiverId,
                'changed_at' => '2026-11-20T01:00:00Z',
            ],
        ], 200),

        '*/rest/v1/care_task_occurrences*' => Http::response([
            [
                'id' => $occurrenceId,
                'template_id' => $templateId,
                'task_date' => '2026-11-20',
                'title' => 'Makan pagi',
                'category' => 'Makan',
                'scheduled_time' => '07:00:00',
                'instructions' => 'Bubur saring',
                'status' => 'completed',
                'reason' => null,
                'completed_by' => $caregiverId,
                'completed_at' => '2026-11-20T01:00:00Z',
            ],
        ], 200),

        '*/rest/v1/profiles*' => Http::response([
            [
                'id' => $caregiverId,
                'full_name' => 'Dewi',
            ],
        ], 200),
    ]);

    $this->get('/caregiver?view=history')
        ->assertOk()
        ->assertViewHas('viewMode', 'history')
        ->assertViewHas('historyPage', 1)
        ->assertViewHas('historyRows', function ($rows) {
            return count($rows) === 1
                && $rows[0]['task_title'] === 'Makan pagi'
                && $rows[0]['resident_name'] === 'Ibu Sri'
                && $rows[0]['changed_by_name'] === 'Dewi'
                && $rows[0]['old_status'] === 'pending'
                && $rows[0]['new_status'] === 'completed'
                && $rows[0]['task_date'] === '2026-11-20';
        });

    Http::assertNotSent(function ($request) {
        return $request->method() === 'POST'
            && parse_url($request->url(), PHP_URL_PATH)
                === '/rest/v1/rpc/generate_today_care_task_occurrences';
    });
});
