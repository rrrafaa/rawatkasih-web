<?php

use App\Models\Consent;
use App\Models\SupabaseUser;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    config([
        'supabase.url' => 'https://example.supabase.co',
        'supabase.publishable_key' => 'test-key',
        'session.encrypt' => false,
    ]);

    Storage::fake('local');
    Http::preventStrayRequests();
});

function loginCaregiver(string $id = '10000000-0000-4000-8000-000000000001'): SupabaseUser
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

    $request = app('request');
    $request->setLaravelSession(
        app('session')->driver()
    );

    return $user;
}

test('caregiver can view family updates index with consent status', function () {
    $caregiverId = '10000000-0000-4000-8000-000000000001';
    $residentId = '30000000-0000-4000-8000-000000000001';
    loginCaregiver($caregiverId);

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
        '*/rest/v1/consents*' => Http::response([
            [
                'id' => 'consent-1',
                'resident_id' => $residentId,
                'scope' => 'photo_share',
                'status' => 'granted',
            ],
        ], 200),
        '*/rest/v1/family_updates*' => Http::response([
            [
                'id' => '80000000-0000-4000-8000-000000000001',
                'resident_id' => $residentId,
                'author_id' => $caregiverId,
                'summary' => 'Eyang sedang menyiram tanaman bunga pagi ini.',
                'photo_path' => null,
                'status' => 'published',
                'published_at' => '2026-11-22T08:00:00Z',
                'created_at' => '2026-11-22T08:00:00Z',
            ],
        ], 200),
        '*/rest/v1/profiles*' => Http::response([
            [
                'id' => $caregiverId,
                'full_name' => 'Dewi Caregiver',
            ],
        ], 200),
    ]);

    $response = $this->get(route('care-updates.family-updates.index'));

    $response->assertOk()
        ->assertSee('Kabar Keluarga')
        ->assertSee('Eyang Hartono')
        ->assertSee('Eyang sedang menyiram tanaman bunga pagi ini.')
        ->assertSee('Terbit');
});

test('caregiver can save family update as draft with optional photo without consent', function () {
    $caregiverId = '10000000-0000-4000-8000-000000000001';
    $residentId = '30000000-0000-4000-8000-000000000001';
    loginCaregiver($caregiverId);

    Http::fake([
        '*/storage/v1/object/rawatkasih-private/*' => Http::response([], 201),
        '*/rest/v1/caregiver_assignments*' => Http::response([
            [
                'id' => '60000000-0000-4000-8000-000000000001',
                'user_id' => $caregiverId,
                'resident_id' => $residentId,
                'start_date' => '2026-11-01',
                'end_date' => null,
            ],
        ], 200),
        '*/rest/v1/family_updates*' => Http::response([
            ['id' => '80000000-0000-4000-8000-000000000002'],
        ], 201),
    ]);

    $file = UploadedFile::fake()->create('eyang-taman.jpg', 1500, 'image/jpeg');

    $response = $this->post(route('care-updates.family-updates.store'), [
        'resident_id' => $residentId,
        'summary' => 'Draft kabar pagi: Eyang sedang sarapan dan membaca koran.',
        'action' => 'draft',
        'photo' => $file,
    ]);

    $response->assertRedirect(route('care-updates.family-updates.index', ['resident' => $residentId]))
        ->assertSessionHas('status', 'Draft kabar keluarga berhasil disimpan.');

    Http::assertSent(function ($request) use ($residentId, $caregiverId) {
        if ($request->method() !== 'POST') {
            return false;
        }
        $data = $request->data();

        return ($data['resident_id'] ?? null) === $residentId
            && ($data['author_id'] ?? null) === $caregiverId
            && ($data['status'] ?? null) === 'draft'
            && ($data['published_at'] ?? null) === null
            && filled($data['photo_path'] ?? null);
    });
});

test('caregiver cannot publish family update with photo if photo consent is not granted', function () {
    $caregiverId = '10000000-0000-4000-8000-000000000001';
    $residentId = '30000000-0000-4000-8000-000000000001';
    loginCaregiver($caregiverId);

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
        '*/rest/v1/consents*scope=eq.family_visibility*' => Http::response([
            [
                'id' => '70000000-0000-4000-8000-000000000001',
                'resident_id' => $residentId,
                'scope' => 'family_visibility',
                'status' => 'granted',
            ],
        ], 200),
        '*/rest/v1/consents*scope=eq.photo_share*' => Http::response([], 200), // No photo consent
        '*/rest/v1/consents*' => Http::response([], 200),
    ]);

    $file = UploadedFile::fake()->create('eyang-senam.jpg', 1200, 'image/jpeg');

    $response = $this->from(route('care-updates.family-updates.index'))->post(route('care-updates.family-updates.store'), [
        'resident_id' => $residentId,
        'summary' => 'Mencoba memublikasikan kabar dengan foto tanpa izin.',
        'action' => 'publish',
        'photo' => $file,
    ]);

    $response->assertSessionHasErrors('photo');
    Http::assertNotSent(fn ($r) => str_contains($r->url(), '/family_updates') && $r->method() === 'POST');
});

test('caregiver can publish family update with photo when photo consent is granted', function () {
    $caregiverId = '10000000-0000-4000-8000-000000000001';
    $residentId = '30000000-0000-4000-8000-000000000001';
    loginCaregiver($caregiverId);

    Http::fake([
        '*/storage/v1/object/rawatkasih-private/*' => Http::response([], 201),
        '*/rest/v1/caregiver_assignments*' => Http::response([
            [
                'id' => '60000000-0000-4000-8000-000000000001',
                'user_id' => $caregiverId,
                'resident_id' => $residentId,
                'start_date' => '2026-11-01',
                'end_date' => null,
            ],
        ], 200),
        '*/rest/v1/consents*' => Http::response([
            [
                'id' => 'consent-photo-1',
                'resident_id' => $residentId,
                'scope' => 'photo_share',
                'status' => 'granted',
            ],
        ], 200),
        '*/rest/v1/family_updates*' => Http::response([
            ['id' => '80000000-0000-4000-8000-000000000003'],
        ], 201),
    ]);

    $file = UploadedFile::fake()->create('eyang-senam.jpg', 1200, 'image/jpeg');

    $response = $this->post(route('care-updates.family-updates.store'), [
        'resident_id' => $residentId,
        'summary' => 'Eyang bersemangat mengikuti senam pagi bersama.',
        'action' => 'publish',
        'photo' => $file,
    ]);

    $response->assertRedirect(route('care-updates.family-updates.index', ['resident' => $residentId]))
        ->assertSessionHas('status', 'Kabar keluarga berhasil dipublikasikan.');

    Http::assertSent(function ($request) use ($residentId) {
        if ($request->method() !== 'POST') {
            return false;
        }
        $data = $request->data();

        return ($data['resident_id'] ?? null) === $residentId
            && ($data['status'] ?? null) === 'published'
            && filled($data['published_at'] ?? null)
            && filled($data['photo_path'] ?? null);
    });
});

test('photo upload rejects files larger than 3 MB or invalid mimes', function () {
    $caregiverId = '10000000-0000-4000-8000-000000000001';
    $residentId = '30000000-0000-4000-8000-000000000001';
    loginCaregiver($caregiverId);

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

    // File terlalu besar (> 3072 KB)
    $largeFile = UploadedFile::fake()->create('large.jpg', 3500, 'image/jpeg');

    $response = $this->from(route('care-updates.family-updates.index'))->post(route('care-updates.family-updates.store'), [
        'resident_id' => $residentId,
        'summary' => 'Tes ukuran file besar.',
        'action' => 'draft',
        'photo' => $largeFile,
    ]);

    $response->assertSessionHasErrors('photo');
});

test('private photo route serves image only to authorized users with valid consent', function () {
    $familyId = '20000000-0000-4000-8000-000000000001';
    $residentId = '30000000-0000-4000-8000-000000000001';
    $updateId = '80000000-0000-4000-8000-000000000001';

    $familyUser = new SupabaseUser([
        'id' => $familyId,
        'full_name' => 'Rina Keluarga',
        'role' => 'family',
    ]);

    $this->actingAs($familyUser)->withSession([
        'supabase' => [
            'access_token' => 'family-token',
        ],
    ]);

    $request = $this->app->make('request');
    $request->setLaravelSession($this->app->make('session')->driver());

    // Taruh file palsu di storage private disk 'local'
    $path = 'family_updates/dummy.jpg';
    Storage::disk('local')->put($path, file_get_contents(resource_path('resident/sulastri/profil.jpg')));

    Http::fake([
        '*/rest/v1/family_updates*' => Http::response([
            [
                'id' => $updateId,
                'resident_id' => $residentId,
                'status' => 'published',
                'photo_path' => $path,
            ],
        ], 200),
        '*/rest/v1/family_links*' => Http::response([
            [
                'id' => 'link-1',
                'user_id' => $familyId,
                'resident_id' => $residentId,
                'status' => 'verified',
            ],
        ], 200),
        '*/rest/v1/consents*' => Http::response([
            [
                'id' => 'consent-1',
                'resident_id' => $residentId,
                'scope' => 'photo_share',
                'status' => 'granted',
            ],
        ], 200),
    ]);

    $response = $this->get(route('care-updates.family-updates.photo', ['updateId' => $updateId]));

    $response->assertOk()
        ->assertHeader('Cache-Control', 'no-store, private');
});

test('private photo route returns 403 when photo consent is revoked for family', function () {
    $familyId = '20000000-0000-4000-8000-000000000001';
    $residentId = '30000000-0000-4000-8000-000000000001';
    $updateId = '80000000-0000-4000-8000-000000000001';

    $familyUser = new SupabaseUser([
        'id' => $familyId,
        'full_name' => 'Rina Keluarga',
        'role' => 'family',
    ]);

    $this->actingAs($familyUser)->withSession([
        'supabase' => [
            'access_token' => 'family-token',
        ],
    ]);

    $request = $this->app->make('request');
    $request->setLaravelSession($this->app->make('session')->driver());

    $path = 'family_updates/dummy.jpg';
    Storage::disk('local')->put($path, file_get_contents(resource_path('resident/sulastri/profil.jpg')));

    Http::fake([
        '*/rest/v1/family_updates*' => Http::response([
            [
                'id' => $updateId,
                'resident_id' => $residentId,
                'status' => 'published',
                'photo_path' => $path,
            ],
        ], 200),
        '*/rest/v1/family_links*' => Http::response([
            [
                'id' => 'link-1',
                'user_id' => $familyId,
                'resident_id' => $residentId,
                'status' => 'verified',
            ],
        ], 200),
        '*/rest/v1/consents*' => Http::response([], 200), // Revoked/no consent
    ]);

    $response = $this->get(route('care-updates.family-updates.photo', ['updateId' => $updateId]));
    $response->assertForbidden();
});

test('caregiver cannot create a draft without a documentation photo', function () {
    loginCaregiver();
    Http::fake(['*/rest/v1/caregiver_assignments*' => Http::response([['id' => 'assigned']])]);

    $this->from(route('care-updates.family-updates.index'))
        ->post(route('care-updates.family-updates.store'), [
            'resident_id' => '30000000-0000-4000-8000-000000000001',
            'summary' => 'Kabar kegiatan pagi Eyang.',
            'action' => 'draft',
        ])->assertSessionHasErrors('photo');

    Http::assertNotSent(fn ($request) => $request->method() === 'POST');
});
