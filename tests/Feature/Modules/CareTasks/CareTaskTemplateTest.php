<?php

use App\Models\CareTaskTemplate;
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

test('caregiver can read active and inactive templates for assigned residents', function () {
    $residentId = '30000000-0000-4000-8000-000000000001';

    Http::fake([
        '*/rest/v1/care_task_templates*' => Http::response([
            [
                'id' => '40000000-0000-4000-8000-000000000001',
                'resident_id' => $residentId,
                'title' => 'Minum Obat',
                'is_active' => true,
            ],
            [
                'id' => '40000000-0000-4000-8000-000000000002',
                'resident_id' => $residentId,
                'title' => 'Cek Tekanan Darah',
                'is_active' => false,
            ],
        ], 200),
    ]);

    $templates = app(CareTaskTemplate::class)
        ->allForResidents([$residentId]);

    expect($templates)->toHaveCount(2)
        ->and($templates[0]['is_active'])->toBeTrue()
        ->and($templates[1]['is_active'])->toBeFalse();

    Http::assertSent(function ($request) use ($residentId) {
        parse_str(
            (string) parse_url($request->url(), PHP_URL_QUERY),
            $query
        );

        return $request->method() === 'GET'
            && parse_url($request->url(), PHP_URL_PATH)
                === '/rest/v1/care_task_templates'
            && ($query['resident_id'] ?? null)
                === 'in.('.$residentId.')'
            && ! array_key_exists('is_active', $query)
            && $request->hasHeader(
                'Authorization',
                'Bearer caregiver-access'
            );
    });
});

test('empty resident ids do not send a supabase request', function () {
    Http::fake();

    $templates = app(CareTaskTemplate::class)
        ->allForResidents([]);

    expect($templates)->toBe([]);

    Http::assertNothingSent();
});

test('invalid resident ids do not send a supabase request', function () {
    Http::fake();

    $templates = app(CareTaskTemplate::class)
        ->allForResidents(['not-a-uuid']);

    expect($templates)->toBe([]);

    Http::assertNothingSent();
});


test('admin can create a care task template through supabase', function () {
    $this->actingAs(new SupabaseUser([
        'id' => '10000000-0000-4000-8000-000000000002',
        'full_name' => 'Admin Uji',
        'role' => 'admin',
    ]))->withSession([
        'supabase' => [
            'access_token' => 'admin-access',
        ],
    ]);

    $data = [
        'resident_id' => '30000000-0000-4000-8000-000000000001',
        'title' => 'Minum Obat',
        'category' => 'Obat',
        'scheduled_time' => '08:30',
        'instructions' => 'Berikan setelah sarapan.',
        'frequency' => 'daily',
        'days_of_week' => [],
    ];

    Http::fake([
        '*/rest/v1/care_task_templates' => Http::response(null, 201),
    ]);

    app(CareTaskTemplate::class)->createForAdmin($data);

    Http::assertSent(function ($request) use ($data) {
        return $request->method() === 'POST'
            && parse_url($request->url(), PHP_URL_PATH)
                === '/rest/v1/care_task_templates'
            && $request->hasHeader(
                'Authorization',
                'Bearer admin-access'
            )
            && $request->data() === $data;
    });
});