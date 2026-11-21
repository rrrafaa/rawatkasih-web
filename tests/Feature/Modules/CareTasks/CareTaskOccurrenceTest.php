
<?php

use App\Models\CareTaskOccurrence;
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

test('caregiver can read occurrences by ids', function () {
    $occurrenceId = '50000000-0000-4000-8000-000000000001';
    $templateId = '40000000-0000-4000-8000-000000000001';

    Http::fake([
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
                'completed_by' => '10000000-0000-4000-8000-000000000001',
                'completed_at' => '2026-11-20T00:42:00Z',
            ],
        ], 200),
    ]);

    $occurrences = app(CareTaskOccurrence::class)
        ->forIds([$occurrenceId]);

    expect($occurrences)->toHaveCount(1)
        ->and($occurrences[0]['id'])->toBe($occurrenceId)
        ->and($occurrences[0]['title'])->toBe('Makan pagi')
        ->and($occurrences[0]['task_date'])->toBe('2026-11-20');

    Http::assertSent(function ($request) use ($occurrenceId) {
        parse_str(
            (string) parse_url($request->url(), PHP_URL_QUERY),
            $query
        );

        return $request->method() === 'GET'
            && parse_url($request->url(), PHP_URL_PATH)
                === '/rest/v1/care_task_occurrences'
            && ($query['id'] ?? null) === 'in.('.$occurrenceId.')'
            && ($query['order'] ?? null)
                === 'task_date.desc,scheduled_time.asc'
            && $request->hasHeader(
                'Authorization',
                'Bearer caregiver-access'
            );
    });
});

test('empty occurrence ids do not send a supabase request', function () {
    Http::fake();

    $occurrences = app(CareTaskOccurrence::class)->forIds([]);

    expect($occurrences)->toBe([]);

    Http::assertNothingSent();
});

test('invalid occurrence ids do not send a supabase request', function () {
    Http::fake();

    $occurrences = app(CareTaskOccurrence::class)
        ->forIds(['not-a-uuid']);

    expect($occurrences)->toBe([]);

    Http::assertNothingSent();
});
