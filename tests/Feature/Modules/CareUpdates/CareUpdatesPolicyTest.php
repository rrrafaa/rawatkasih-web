<?php

use App\Models\DailyNote;
use App\Models\FamilyUpdate;
use App\Models\SupabaseUser;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    config([
        'supabase.url' => 'https://example.supabase.co',
        'supabase.publishable_key' => 'test-key',
        'session.encrypt' => false,
    ]);

    Http::preventStrayRequests();
});

function actingAsUser(string $id, string $role, string $token): SupabaseUser
{
    $user = new SupabaseUser([
        'id' => $id,
        'full_name' => 'User '.$role,
        'role' => $role,
    ]);

    test()->actingAs($user)->withSession([
        'supabase' => [
            'access_token' => $token,
        ],
    ]);

    $request = app('request');
    $request->setLaravelSession(
        app('session')->driver()
    );

    return $user;
}

test('caregiver with active assignment can view daily note', function () {
    $caregiverId = '10000000-0000-4000-8000-000000000001';
    $residentId = '30000000-0000-4000-8000-000000000001';
    $user = actingAsUser($caregiverId, 'caregiver', 'caregiver-token');

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
    ]);

    $note = [
        'id' => '70000000-0000-4000-8000-000000000001',
        'resident_id' => $residentId,
        'caregiver_id' => $caregiverId,
        'mood' => 'Senang',
        'meal_note' => 'Habis satu porsi bubur',
        'internal_note' => 'Tidur siang 2 jam, tensi stabil',
    ];

    expect(Gate::forUser($user)->allows('view', [DailyNote::class, $note]))->toBeTrue();
});

test('caregiver without active assignment cannot view daily note', function () {
    $caregiverId = '10000000-0000-4000-8000-000000000001';
    $residentId = '30000000-0000-4000-8000-000000000001';
    $user = actingAsUser($caregiverId, 'caregiver', 'caregiver-token');

    Http::fake([
        '*/rest/v1/caregiver_assignments*' => Http::response([], 200),
    ]);

    $note = [
        'id' => '70000000-0000-4000-8000-000000000001',
        'resident_id' => $residentId,
        'caregiver_id' => '10000000-0000-4000-8000-000000000099',
        'mood' => 'Biasa',
        'meal_note' => 'Habis',
        'internal_note' => 'Catatan internal staf',
    ];

    expect(Gate::forUser($user)->allows('view', [DailyNote::class, $note]))->toBeFalse();
});

test('family user cannot view daily note under any circumstance', function () {
    $familyId = '20000000-0000-4000-8000-000000000001';
    $residentId = '30000000-0000-4000-8000-000000000001';
    $user = actingAsUser($familyId, 'family', 'family-token');

    Http::fake();

    $note = [
        'id' => '70000000-0000-4000-8000-000000000001',
        'resident_id' => $residentId,
        'caregiver_id' => '10000000-0000-4000-8000-000000000001',
        'mood' => 'Senang',
        'meal_note' => 'Habis',
        'internal_note' => 'Catatan medis dan observasi internal',
    ];

    expect(Gate::forUser($user)->allows('viewAny', DailyNote::class))->toBeFalse()
        ->and(Gate::forUser($user)->allows('view', [DailyNote::class, $note]))->toBeFalse();

    Http::assertNothingSent();
});

test('caregiver with active assignment can create daily note for resident', function () {
    $caregiverId = '10000000-0000-4000-8000-000000000001';
    $residentId = '30000000-0000-4000-8000-000000000001';
    $user = actingAsUser($caregiverId, 'caregiver', 'caregiver-token');

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
    ]);

    expect(Gate::forUser($user)->allows('create', [DailyNote::class, $residentId]))->toBeTrue();
});

test('caregiver without active assignment cannot create daily note for resident', function () {
    $caregiverId = '10000000-0000-4000-8000-000000000001';
    $residentId = '30000000-0000-4000-8000-000000000001';
    $user = actingAsUser($caregiverId, 'caregiver', 'caregiver-token');

    Http::fake([
        '*/rest/v1/caregiver_assignments*' => Http::response([], 200),
    ]);

    expect(Gate::forUser($user)->allows('create', [DailyNote::class, $residentId]))->toBeFalse();
});

test('family user cannot create daily note', function () {
    $familyId = '20000000-0000-4000-8000-000000000001';
    $residentId = '30000000-0000-4000-8000-000000000001';
    $user = actingAsUser($familyId, 'family', 'family-token');

    Http::fake();

    expect(Gate::forUser($user)->allows('create', [DailyNote::class, $residentId]))->toBeFalse();
    Http::assertNothingSent();
});

test('daily notes cannot be deleted by anyone', function () {
    $caregiverId = '10000000-0000-4000-8000-000000000001';
    $adminId = '00000000-0000-4000-8000-000000000001';
    $residentId = '30000000-0000-4000-8000-000000000001';

    $note = [
        'id' => '70000000-0000-4000-8000-000000000001',
        'resident_id' => $residentId,
    ];

    $caregiver = actingAsUser($caregiverId, 'caregiver', 'cg-token');
    expect(Gate::forUser($caregiver)->allows('delete', [DailyNote::class, $note]))->toBeFalse();

    $admin = actingAsUser($adminId, 'admin', 'adm-token');
    expect(Gate::forUser($admin)->allows('delete', [DailyNote::class, $note]))->toBeFalse();
});

test('caregiver with active assignment can create family update', function () {
    $caregiverId = '10000000-0000-4000-8000-000000000001';
    $residentId = '30000000-0000-4000-8000-000000000001';
    $user = actingAsUser($caregiverId, 'caregiver', 'caregiver-token');

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
    ]);

    expect(Gate::forUser($user)->allows('create', [FamilyUpdate::class, $residentId]))->toBeTrue();
});

test('caregiver without active assignment cannot create family update', function () {
    $caregiverId = '10000000-0000-4000-8000-000000000001';
    $residentId = '30000000-0000-4000-8000-000000000001';
    $user = actingAsUser($caregiverId, 'caregiver', 'caregiver-token');

    Http::fake([
        '*/rest/v1/caregiver_assignments*' => Http::response([], 200),
    ]);

    expect(Gate::forUser($user)->allows('create', [FamilyUpdate::class, $residentId]))->toBeFalse();
});

test('caregiver with active assignment can view draft family update', function () {
    $caregiverId = '10000000-0000-4000-8000-000000000001';
    $residentId = '30000000-0000-4000-8000-000000000001';
    $user = actingAsUser($caregiverId, 'caregiver', 'caregiver-token');

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
    ]);

    $draftUpdate = [
        'id' => '80000000-0000-4000-8000-000000000001',
        'resident_id' => $residentId,
        'author_id' => $caregiverId,
        'summary' => 'Draft kabar pagi',
        'status' => 'draft',
    ];

    expect(Gate::forUser($user)->allows('view', [FamilyUpdate::class, $draftUpdate]))->toBeTrue();
});

test('family user can view published family update for verified resident', function () {
    $familyId = '20000000-0000-4000-8000-000000000001';
    $residentId = '30000000-0000-4000-8000-000000000001';
    $user = actingAsUser($familyId, 'family', 'family-token');

    Http::fake([
        '*/rest/v1/family_links*' => Http::response([
            [
                'id' => '50000000-0000-4000-8000-000000000001',
                'user_id' => $familyId,
                'resident_id' => $residentId,
                'relationship' => 'Anak',
                'status' => 'verified',
            ],
        ], 200),
    ]);

    $publishedUpdate = [
        'id' => '80000000-0000-4000-8000-000000000001',
        'resident_id' => $residentId,
        'summary' => 'Eyang sedang menyiram tanaman bunga pagi ini.',
        'status' => 'published',
        'published_at' => '2026-11-22T08:00:00Z',
    ];

    expect(Gate::forUser($user)->allows('view', [FamilyUpdate::class, $publishedUpdate]))->toBeTrue();
});

test('family user cannot view draft family update even if link is verified', function () {
    $familyId = '20000000-0000-4000-8000-000000000001';
    $residentId = '30000000-0000-4000-8000-000000000001';
    $user = actingAsUser($familyId, 'family', 'family-token');

    Http::fake();

    $draftUpdate = [
        'id' => '80000000-0000-4000-8000-000000000001',
        'resident_id' => $residentId,
        'summary' => 'Masih draft kabar.',
        'status' => 'draft',
    ];

    expect(Gate::forUser($user)->allows('view', [FamilyUpdate::class, $draftUpdate]))->toBeFalse();
    Http::assertNothingSent();
});

test('family user cannot view published family update if family link is unverified or missing', function () {
    $familyId = '20000000-0000-4000-8000-000000000001';
    $residentId = '30000000-0000-4000-8000-000000000001';
    $user = actingAsUser($familyId, 'family', 'family-token');

    Http::fake([
        '*/rest/v1/family_links*' => Http::response([], 200),
    ]);

    $publishedUpdate = [
        'id' => '80000000-0000-4000-8000-000000000001',
        'resident_id' => $residentId,
        'summary' => 'Kabar lansia lain.',
        'status' => 'published',
        'published_at' => '2026-11-22T08:00:00Z',
    ];

    expect(Gate::forUser($user)->allows('view', [FamilyUpdate::class, $publishedUpdate]))->toBeFalse();
});

test('admin can view all family updates', function () {
    $adminId = '00000000-0000-4000-8000-000000000001';
    $residentId = '30000000-0000-4000-8000-000000000001';
    $user = actingAsUser($adminId, 'admin', 'admin-token');

    Http::fake();

    $draftUpdate = [
        'id' => '80000000-0000-4000-8000-000000000001',
        'resident_id' => $residentId,
        'status' => 'draft',
    ];

    expect(Gate::forUser($user)->allows('view', [FamilyUpdate::class, $draftUpdate]))->toBeTrue();
    Http::assertNothingSent();
});
