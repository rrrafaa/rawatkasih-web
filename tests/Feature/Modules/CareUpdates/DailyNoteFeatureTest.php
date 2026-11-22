<?php

use App\Models\CaregiverAssignment;
use App\Models\DailyNote;
use App\Models\Resident;
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

function loginAsCaregiver(string $id = '10000000-0000-4000-8000-000000000001'): SupabaseUser
{
    $user = new SupabaseUser([
        'id' => $id,
        'full_name' => 'Dewi Caregiver',
        'role' => 'caregiver',
    ]);

    test()->actingAs($user)->withSession([
        'supabase' => [
            'access_token' => 'caregiver-token',
        ],
    ]);

    $request = test()->app->make('request');
    $request->setLaravelSession(
        test()->app->make('session')->driver()
    );

    return $user;
}

test('caregiver can view daily notes index for assigned residents', function () {
    $caregiverId = '10000000-0000-4000-8000-000000000001';
    $residentId = '30000000-0000-4000-8000-000000000001';
    $user = loginAsCaregiver($caregiverId);

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
                'full_name' => 'Eyang Hartono',
                'room' => 'Melati 01',
                'status' => 'active',
            ],
        ], 200),
        '*/rest/v1/daily_notes*' => Http::response([
            [
                'id' => '70000000-0000-4000-8000-000000000001',
                'resident_id' => $residentId,
                'caregiver_id' => $caregiverId,
                'mood' => 'Ceria',
                'meal_note' => 'Habis satu porsi bubur',
                'internal_note' => 'Tidur siang 2 jam, tensi darah normal 120/80',
                'recorded_at' => '2026-11-22T10:00:00+07:00',
            ],
        ], 200),
        '*/rest/v1/profiles*' => Http::response([
            [
                'id' => $caregiverId,
                'full_name' => 'Dewi Caregiver',
            ],
        ], 200),
    ]);

    $response = $this->get(route('care-updates.daily-notes.index'));

    $response->assertOk()
        ->assertSee('Catatan Harian Internal')
        ->assertSee('Eyang Hartono')
        ->assertSee('Ceria')
        ->assertSee('Habis satu porsi bubur')
        ->assertSee('Tidur siang 2 jam, tensi darah normal 120/80')
        ->assertSee('Dewi Caregiver');
});

test('caregiver can store a valid internal daily note for assigned resident', function () {
    $caregiverId = '10000000-0000-4000-8000-000000000001';
    $residentId = '30000000-0000-4000-8000-000000000001';
    loginAsCaregiver($caregiverId);

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
        '*/rest/v1/daily_notes*' => Http::response([], 201),
    ]);

    $response = $this->post(route('care-updates.daily-notes.store'), [
        'resident_id' => $residentId,
        'mood' => 'Tenang',
        'meal_note' => 'Makan sedikit',
        'internal_note' => 'Keluhan pusing ringan sudah dilaporkan ke dokter jaga.',
    ]);

    $response->assertRedirect(route('care-updates.daily-notes.index', ['resident' => $residentId]))
        ->assertSessionHas('status', 'Catatan harian internal berhasil ditambahkan.');

    Http::assertSent(function ($request) use ($residentId, $caregiverId) {
        if ($request->method() !== 'POST') {
            return false;
        }

        $data = $request->data();

        return ($data['resident_id'] ?? null) === $residentId
            && ($data['caregiver_id'] ?? null) === $caregiverId
            && ($data['mood'] ?? null) === 'Tenang'
            && ($data['meal_note'] ?? null) === 'Makan sedikit'
            && ($data['internal_note'] ?? null) === 'Keluhan pusing ringan sudah dilaporkan ke dokter jaga.';
    });
});

test('caregiver cannot store daily note for unassigned resident', function () {
    $caregiverId = '10000000-0000-4000-8000-000000000001';
    $unassignedResidentId = '30000000-0000-4000-8000-000000000099';
    loginAsCaregiver($caregiverId);

    Http::fake([
        '*/rest/v1/caregiver_assignments*' => Http::response([], 200),
    ]);

    $response = $this->post(route('care-updates.daily-notes.store'), [
        'resident_id' => $unassignedResidentId,
        'mood' => 'Gelisah',
        'internal_note' => 'Mencoba membuat catatan untuk lansia di luar penugasan.',
    ]);

    $response->assertForbidden();
});

test('family user cannot access daily notes routes', function () {
    $familyUser = new SupabaseUser([
        'id' => '20000000-0000-4000-8000-000000000001',
        'full_name' => 'Keluarga',
        'role' => 'family',
    ]);

    $this->actingAs($familyUser)->withSession([
        'supabase' => [
            'access_token' => 'family-token',
        ],
    ]);

    $response = $this->get(route('care-updates.daily-notes.index'));
    $response->assertForbidden();
});
