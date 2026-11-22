<?php

use App\Models\CareTaskStatusHistory;
use App\Models\Profile;
use App\Models\SupabaseUser;
use Illuminate\Support\Facades\Http;
use Symfony\Component\HttpKernel\Exception\HttpException;

beforeEach(function () {
    config([
        'supabase.url' => 'https://example.supabase.co',
        'supabase.publishable_key' => 'test-key',
        'session.encrypt' => false,
    ]);

    Http::preventStrayRequests();

    $this->actingAs(new SupabaseUser([
        'id' => '10000000-0000-4000-8000-000000000001',
        'full_name' => 'Caregiver Uji',
        'role' => 'caregiver',
    ]))->withSession([
        'supabase' => [
            'access_token' => 'caregiver-access',
        ],
    ]);

    $request = $this->app->make('request');
    $request->setLaravelSession(
        $this->app->make('session')->driver()
    );
});

test('caregiver can read status history for assigned occurrences', function () {
    $occurrenceId = '20000000-0000-4000-8000-000000000001';

    Http::fake([
        '*/rest/v1/care_task_status_history*' => Http::response([
            [
                'id' => 1,
                'occurrence_id' => $occurrenceId,
                'old_status' => 'pending',
                'new_status' => 'completed',
                'reason' => null,
                'changed_by' => '10000000-0000-4000-8000-000000000001',
                'changed_at' => '2026-11-21T01:00:00Z',
            ],
        ], 200),
    ]);

    $history = app(CareTaskStatusHistory::class)
        ->forOccurrences([$occurrenceId]);

    expect($history)->toHaveCount(1)
        ->and($history[0]['new_status'])->toBe('completed');

    Http::assertSent(function ($request) use ($occurrenceId) {
        parse_str(
            (string) parse_url($request->url(), PHP_URL_QUERY),
            $query
        );

        return $request->method() === 'GET'
            && parse_url($request->url(), PHP_URL_PATH)
                === '/rest/v1/care_task_status_history'
            && ($query['occurrence_id'] ?? null)
                === 'in.('.$occurrenceId.')'
            && $request->hasHeader(
                'Authorization',
                'Bearer caregiver-access'
            );
    });
});

test('empty occurrence ids do not send a supabase request', function () {
    Http::fake();

    $history = app(CareTaskStatusHistory::class)
        ->forOccurrences([]);

    expect($history)->toBe([]);

    Http::assertNothingSent();
});

test('caregiver can read names of profiles recorded in task history', function () {
    $profileId = '10000000-0000-4000-8000-000000000002';

    Http::fake([
        '*/rest/v1/profiles*' => Http::response([
            [
                'id' => $profileId,
                'full_name' => 'Caregiver Lain',
            ],
        ], 200),
    ]);

    $names = app(Profile::class)->namesByIds(
        [$profileId],
        'caregiver-access'
    );

    expect($names)->toBe([
        $profileId => 'Caregiver Lain',
    ]);

    Http::assertSent(function ($request) use ($profileId) {
        parse_str(
            (string) parse_url($request->url(), PHP_URL_QUERY),
            $query
        );

        return $request->method() === 'GET'
            && parse_url($request->url(), PHP_URL_PATH)
                === '/rest/v1/profiles'
            && ($query['select'] ?? null) === 'id,full_name'
            && ($query['id'] ?? null) === 'in.('.$profileId.')'
            && $request->hasHeader(
                'Authorization',
                'Bearer caregiver-access'
            );
    });
});

test('empty profile ids do not send a supabase request', function () {
    Http::fake();

    $names = app(Profile::class)->namesByIds(
        [],
        'caregiver-access'
    );

    expect($names)->toBe([]);

    Http::assertNothingSent();
});

test('status history read fails when supabase rejects access', function () {
    $occurrenceId = '20000000-0000-4000-8000-000000000001';

    Http::fake([
        '*/rest/v1/care_task_status_history*' => Http::response(['message' => 'Forbidden'], 403),
    ]);

    expect(fn () => app(CareTaskStatusHistory::class)
        ->forOccurrences([$occurrenceId]))
        ->toThrow(HttpException::class);
});

test('caregiver can read latest status history with pagination', function () {
    Http::fake([
        '*/rest/v1/care_task_status_history*' => Http::response([
            [
                'id' => 10,
                'occurrence_id' => '20000000-0000-4000-8000-000000000001',
                'old_status' => 'pending',
                'new_status' => 'completed',
                'reason' => null,
                'changed_by' => '10000000-0000-4000-8000-000000000001',
                'changed_at' => '2026-11-20T01:00:00Z',
            ],
        ], 200),
    ]);

    $history = app(CareTaskStatusHistory::class)
        ->latestForCurrentCaregiver(page: 2, perPage: 50);

    expect($history)->toHaveCount(1)
        ->and($history[0]['new_status'])->toBe('completed');

    Http::assertSent(function ($request) {
        parse_str(
            (string) parse_url($request->url(), PHP_URL_QUERY),
            $query
        );

        return $request->method() === 'GET'
            && parse_url($request->url(), PHP_URL_PATH)
                === '/rest/v1/care_task_status_history'
            && ($query['order'] ?? null) === 'changed_at.desc'
            && ($query['limit'] ?? null) === '50'
            && ($query['offset'] ?? null) === '50'
            && $request->hasHeader(
                'Authorization',
                'Bearer caregiver-access'
            );
    });
});

test('status history pagination enforces a maximum of 100 records', function () {
    Http::fake([
        '*/rest/v1/care_task_status_history*' => Http::response([], 200),
    ]);

    $history = app(CareTaskStatusHistory::class)
        ->latestForCurrentCaregiver(page: 1, perPage: 200);

    expect($history)->toBe([]);

    Http::assertSent(function ($request) {
        parse_str(
            (string) parse_url($request->url(), PHP_URL_QUERY),
            $query
        );

        return ($query['limit'] ?? null) === '100'
            && ($query['offset'] ?? null) === '0';
    });
});
