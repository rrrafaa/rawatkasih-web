
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
});

test('caregiver can complete an assigned task occurrence', function () {
    $occurrenceId = '20000000-0000-4000-8000-000000000001';

    Http::fake(function ($request) use ($occurrenceId) {
        if ($request->method() === 'GET') {
            return Http::response([
                [
                    'id' => $occurrenceId,
                    'template_id' => '30000000-0000-4000-8000-000000000001',
                    'task_date' => '2026-11-21',
                    'title' => 'Makan pagi',
                    'category' => 'Makan',
                    'scheduled_time' => '07:00:00',
                    'instructions' => null,
                    'status' => 'pending',
                    'reason' => null,
                    'completed_by' => null,
                    'completed_at' => null,
                    'created_at' => '2026-11-21T00:00:00Z',
                    'updated_at' => '2026-11-21T00:00:00Z',
                ],
            ], 200);
        }

        return Http::response(null, 204);
    });

    $this->actingAs(new SupabaseUser([
        'id' => '10000000-0000-4000-8000-000000000001',
        'full_name' => 'Caregiver Uji',
        'role' => 'caregiver',
    ]))
        ->withSession([
            'supabase' => [
                'access_token' => 'caregiver-access',
            ],
        ])
        ->patch(route('care-tasks.occurrences.status', $occurrenceId), [
            'status' => 'completed',
        ])
        ->assertRedirect(route('caregiver.dashboard'))
        ->assertSessionHas('task_feedback', [
            'type' => 'success',
            'title' => 'Status berhasil diperbarui',
            'message' => 'Perubahan status tugas berhasil disimpan.',
        ]);

    Http::assertSent(function ($request) use ($occurrenceId) {
        return $request->method() === 'PATCH'
            && str_ends_with(
                $request->url(),
                '/rest/v1/care_task_occurrences?id=eq.'.$occurrenceId
            )
            && $request->hasHeader('Authorization', 'Bearer caregiver-access')
            && $request['status'] === 'completed'
            && $request['reason'] === null;
    });
});

test('caregiver must provide a reason when postponing a task', function () {
    $this->actingAs(new SupabaseUser([
        'id' => '10000000-0000-4000-8000-000000000001',
        'full_name' => 'Caregiver Uji',
        'role' => 'caregiver',
    ]))
        ->withSession([
            'supabase' => [
                'access_token' => 'caregiver-access',
            ],
        ])
        ->from('/caregiver')
        ->patch(route('care-tasks.occurrences.status', [
            'occurrenceId' => '20000000-0000-4000-8000-000000000001',
        ]), [
            'status' => 'postponed',
        ])
        ->assertSessionHasErrors('reason');

    Http::assertNothingSent();
});

test('caregiver must provide a reason when marking a task as missed', function () {
    $this->actingAs(new SupabaseUser([
        'id' => '10000000-0000-4000-8000-000000000001',
        'full_name' => 'Caregiver Uji',
        'role' => 'caregiver',
    ]))
        ->withSession([
            'supabase' => [
                'access_token' => 'caregiver-access',
            ],
        ])
        ->from('/caregiver')
        ->patch(route('care-tasks.occurrences.status', [
            'occurrenceId' => '20000000-0000-4000-8000-000000000001',
        ]), [
            'status' => 'missed',
        ])
        ->assertSessionHasErrors('reason');

    Http::assertNothingSent();
});

test('caregiver can postpone a task with a reason', function () {
    $occurrenceId = '20000000-0000-4000-8000-000000000001';

    Http::fake(function ($request) use ($occurrenceId) {
        if ($request->method() === 'GET') {
            return Http::response([
                [
                    'id' => $occurrenceId,
                    'template_id' => '30000000-0000-4000-8000-000000000001',
                    'task_date' => '2026-11-21',
                    'title' => 'Makan pagi',
                    'category' => 'Makan',
                    'scheduled_time' => '07:00:00',
                    'instructions' => null,
                    'status' => 'pending',
                    'reason' => null,
                    'completed_by' => null,
                    'completed_at' => null,
                    'created_at' => '2026-11-21T00:00:00Z',
                    'updated_at' => '2026-11-21T00:00:00Z',
                ],
            ], 200);
        }

        return Http::response(null, 204);
    });

    $this->actingAs(new SupabaseUser([
        'id' => '10000000-0000-4000-8000-000000000001',
        'full_name' => 'Caregiver Uji',
        'role' => 'caregiver',
    ]))
        ->withSession([
            'supabase' => [
                'access_token' => 'caregiver-access',
            ],
        ])
        ->patch(route('care-tasks.occurrences.status', $occurrenceId), [
            'status' => 'postponed',
            'reason' => 'Lansia sedang beristirahat.',
        ])
        ->assertRedirect(route('caregiver.dashboard'));

    Http::assertSent(function ($request) use ($occurrenceId) {
        return $request->method() === 'PATCH'
            && str_ends_with(
                $request->url(),
                '/rest/v1/care_task_occurrences?id=eq.'.$occurrenceId
            )
            && $request['status'] === 'postponed'
            && $request['reason'] === 'Lansia sedang beristirahat.';
    });
});

test('caregiver can mark a task as missed with a reason', function () {
    $occurrenceId = '20000000-0000-4000-8000-000000000001';

    Http::fake(function ($request) use ($occurrenceId) {
        if ($request->method() === 'GET') {
            return Http::response([
                [
                    'id' => $occurrenceId,
                    'template_id' => '30000000-0000-4000-8000-000000000001',
                    'task_date' => '2026-11-21',
                    'title' => 'Makan pagi',
                    'category' => 'Makan',
                    'scheduled_time' => '07:00:00',
                    'instructions' => null,
                    'status' => 'pending',
                    'reason' => null,
                    'completed_by' => null,
                    'completed_at' => null,
                    'created_at' => '2026-11-21T00:00:00Z',
                    'updated_at' => '2026-11-21T00:00:00Z',
                ],
            ], 200);
        }

        return Http::response(null, 204);
    });

    $this->actingAs(new SupabaseUser([
        'id' => '10000000-0000-4000-8000-000000000001',
        'full_name' => 'Caregiver Uji',
        'role' => 'caregiver',
    ]))
        ->withSession([
            'supabase' => [
                'access_token' => 'caregiver-access',
            ],
        ])
        ->patch(route('care-tasks.occurrences.status', $occurrenceId), [
            'status' => 'missed',
            'reason' => 'Lansia menolak melakukan aktivitas.',
        ])
        ->assertRedirect(route('caregiver.dashboard'));

    Http::assertSent(function ($request) use ($occurrenceId) {
        return $request->method() === 'PATCH'
            && str_ends_with(
                $request->url(),
                '/rest/v1/care_task_occurrences?id=eq.'.$occurrenceId
            )
            && $request['status'] === 'missed'
            && $request['reason'] === 'Lansia menolak melakukan aktivitas.';
    });
});

test('caregiver cannot update an occurrence they cannot access', function () {
    $occurrenceId = '20000000-0000-4000-8000-000000000099';

    Http::fake([
        '*care_task_occurrences*' => Http::response([], 200),
    ]);

    $this->actingAs(new SupabaseUser([
        'id' => '10000000-0000-4000-8000-000000000001',
        'full_name' => 'Caregiver Uji',
        'role' => 'caregiver',
    ]))
        ->withSession([
            'supabase' => [
                'access_token' => 'caregiver-access',
            ],
        ])
        ->patch(route('care-tasks.occurrences.status', $occurrenceId), [
            'status' => 'completed',
        ])
        ->assertForbidden();

    Http::assertSentCount(1);

    Http::assertSent(function ($request) use ($occurrenceId) {
        return $request->method() === 'GET'
            && parse_url($request->url(), PHP_URL_PATH)
                === '/rest/v1/care_task_occurrences'
            && str_contains($request->url(), 'id=eq.'.$occurrenceId)
            && $request->hasHeader(
                'Authorization',
                'Bearer caregiver-access'
            );
    });
});

test('admin cannot update a care task occurrence status', function () {
    $this->actingAs(new SupabaseUser([
        'id' => '10000000-0000-4000-8000-000000000002',
        'full_name' => 'Admin Uji',
        'role' => 'admin',
    ]))
        ->withSession([
            'supabase' => [
                'access_token' => 'admin-access',
            ],
        ])
        ->patch(route('care-tasks.occurrences.status', [
            'occurrenceId' => '20000000-0000-4000-8000-000000000099',
        ]), [
            'status' => 'completed',
        ])
        ->assertForbidden();

    Http::assertNothingSent();
});

test('caregiver receives forbidden when supabase rejects a status update', function () {
    $occurrenceId = '20000000-0000-4000-8000-000000000099';

    Http::fake([
        '*care_task_occurrences*' => Http::sequence()
            ->push([
                [
                    'id' => $occurrenceId,
                    'template_id' => '30000000-0000-4000-8000-000000000099',
                    'status' => 'pending',
                ],
            ], 200)
            ->push(['message' => 'Forbidden'], 403),
    ]);

    $this->actingAs(new SupabaseUser([
        'id' => '10000000-0000-4000-8000-000000000001',
        'full_name' => 'Caregiver Uji',
        'role' => 'caregiver',
    ]))
        ->withSession([
            'supabase' => [
                'access_token' => 'caregiver-access',
            ],
        ])
        ->patch(route('care-tasks.occurrences.status', $occurrenceId), [
            'status' => 'completed',
        ])
        ->assertForbidden();

    Http::assertSentCount(2);
});
