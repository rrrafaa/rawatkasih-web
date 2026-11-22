<?php

use App\Contracts\FamilyNewsProvider;
use App\Models\FamilyVisits\AccountProfile;
use App\Models\FamilyVisits\Resident;
use App\Models\FamilyVisits\VisitRequest;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Tests\Support\FamilyVisitFake;

beforeEach(function () {
    CarbonImmutable::setTestNow('2026-11-17T08:00:00+07:00');
    config(['supabase.publishable_key' => 'test-key', 'session.encrypt' => false]);
    $this->fake = new FamilyVisitFake;
    $this->fake->install();
    $this->withSession(['supabase' => ['access_token' => 'family-token', 'refresh_token' => 'refresh', 'expires_at' => time() + 3600]]);
});

afterEach(fn () => CarbonImmutable::setTestNow());

function validFamilyVisit(): array
{
    return ['resident_id' => FamilyVisitFake::RESIDENT, 'submission_key' => (string) Str::uuid(), 'visit_date' => '2026-11-19', 'start_time' => '09:00', 'end_time' => '10:30', 'visitor_count' => 2, 'primary_visitor_name' => 'Rina Wulandari', 'note' => 'Membawa buah'];
}

test('family dashboard uses related profile and clearly marks module two dummy data', function () {
    $this->get('/family')->assertOk()->assertSee('Rina')->assertSee('Eyang Hartono Soedirdjo')->assertSee('Data dummy sementara')->assertDontSee('family-token')->assertDontSee('internal_note');
    Http::assertSent(fn ($r) => str_contains($r->url(), '/family_links') && $r['user_id'] === 'eq.'.FamilyVisitFake::FAMILY && $r['status'] === 'eq.verified' && $r->hasHeader('Authorization', 'Bearer family-token'));
    Http::assertNotSent(fn ($r) => str_contains($r->url(), '/family_updates') || str_contains($r->url(), '/daily_notes'));
});

test('family can open v2 pages and empty visits', function (string $path, string $text) {
    $this->get($path)->assertOk()->assertSee($text);
})->with([['/family/kabar', 'Cerita hari-hari Eyang'], ['/family/profil', 'Simpan perubahan'], ['/family/kunjungan', 'Belum ada pengajuan kunjungan'], ['/family/kunjungan/create', 'Ajukan Kunjungan']]);

test('families can edit their own contact details without changing login identity or role', function () {
    $url = '/family/profil?resident='.FamilyVisitFake::RESIDENT;
    $this->patch($url, [
        'full_name' => '  Rina Utami  ', 'phone' => '+62 812-3456-7890', 'role' => 'admin',
        'email' => 'different@example.test', 'id' => FamilyVisitFake::OTHER,
    ])->assertRedirect($url)->assertSessionHas('status');
    expect($this->fake->calls)->toBe([['path' => '/rest/v1/rpc/update_my_profile',
        'data' => ['p_full_name' => 'Rina Utami', 'p_phone' => '+62 812-3456-7890']]]);
    Http::assertSent(fn ($request) => str_ends_with($request->url(), '/rpc/update_my_profile')
        && $request->hasHeader('Authorization', 'Bearer family-token'));
    Auth::forgetGuards();
    $this->get($url)->assertOk()->assertSee('Rina Utami')->assertSee('+62 812-3456-7890')
        ->assertSee('rina@example.test')->assertDontSee('different@example.test');
});

test('invalid profile data and other roles cannot update a family profile', function () {
    $this->patch('/family/profil', ['full_name' => ' ', 'phone' => 'example'])->assertSessionHasErrors(['full_name', 'phone']);
    $this->fake->role = 'admin';
    Auth::forgetGuards();
    $this->patch('/family/profil', ['full_name' => 'Admin'])->assertForbidden();
    expect($this->fake->calls)->toBeEmpty();
});

test('unlinked families see a safe empty state without dummy stories or a visit form', function () {
    $this->fake->linked = false;
    $this->get('/family')->assertOk()->assertSee('Belum ada orang tersayang')->assertDontSee('Eyang Hartono')->assertDontSee('Berkebun');
    Auth::forgetGuards();
    $this->get('/family/kunjungan/create')->assertNotFound();
});

test('an unlinked resident ID cannot be selected or submitted', function () {
    $this->get('/family?resident='.FamilyVisitFake::OTHER)->assertNotFound();
    Auth::forgetGuards();
    $this->post('/family/kunjungan', [...validFamilyVisit(), 'resident_id' => FamilyVisitFake::OTHER])->assertNotFound();
    expect($this->fake->calls)->toBeEmpty();
});

test('a valid visit is persisted with current actor and times converted from WIB', function () {
    $this->post('/family/kunjungan', [...validFamilyVisit(), 'requester_id' => 'attacker', 'status' => 'approved', 'reviewed_by' => 'attacker'])->assertRedirect()->assertSessionHas('status');
    expect($this->fake->rows)->toHaveCount(1);
    $call = $this->fake->calls[0]['data'];
    expect($call['p_requested_at'])->toBe('2026-11-19T02:00:00+00:00')
        ->and($call['p_requested_end_at'])->toBe('2026-11-19T03:30:00+00:00')
        ->and($call)->not->toHaveKeys(['requester_id', 'status', 'reviewed_by'])
        ->and($this->fake->rows[0]['status'])->toBe('pending');
});

test('repeated submit preserves submission key for database idempotency', function () {
    $data = validFamilyVisit();
    $this->post('/family/kunjungan', $data)->assertRedirect();
    Auth::forgetGuards();
    $this->post('/family/kunjungan', $data)->assertRedirect();
    expect($this->fake->rows)->toHaveCount(1)
        ->and($this->fake->calls[0]['data']['p_submission_key'])->toBe($this->fake->calls[1]['data']['p_submission_key']);
});

test('invalid visit data never reaches a write RPC', function (array $invalid, string $field) {
    $this->from('/family/kunjungan/create')->post('/family/kunjungan', [...validFamilyVisit(), ...$invalid])->assertSessionHasErrors($field);
    expect($this->fake->calls)->toBeEmpty();
})->with([
    [['visit_date' => '2026-11-16'], 'visit_date'], [['visit_date' => '2026-11-17', 'start_time' => '08:00'], 'visit_date'],
    [['end_time' => '08:00'], 'end_time'], [['end_time' => '09:00'], 'end_time'], [['visitor_count' => 0], 'visitor_count'],
    [['visitor_count' => 21], 'visitor_count'], [['visitor_count' => '2.5'], 'visitor_count'], [['primary_visitor_name' => '   '], 'primary_visitor_name'],
    [['primary_visitor_name' => 'a'], 'primary_visitor_name'], [['note' => str_repeat('a', 1001)], 'note'], [['resident_id' => 'not-uuid'], 'resident_id'],
    [['submission_key' => ''], 'submission_key'], [['visit_date' => '2026-02-31'], 'visit_date'], [['start_time' => '24:00'], 'start_time'],
    [['start_time' => '07:45'], 'start_time'], [['start_time' => '19:00'], 'start_time'],
    [['start_time' => '09:10'], 'start_time'], [['end_time' => '19:15'], 'end_time'],
    [['end_time' => '10:10'], 'end_time'],
]);

test('the last available visit ends at 19.00 WIB', function () {
    $this->post('/family/kunjungan', [...validFamilyVisit(), 'start_time' => '18:45', 'end_time' => '19:00'])
        ->assertRedirect()->assertSessionHas('status');
    expect($this->fake->calls[0]['data']['p_requested_end_at'])->toBe('2026-11-19T12:00:00+00:00');
});

test('own pending visit can be viewed and cancelled', function () {
    $this->fake->rows[] = $this->fake->row();
    $this->get('/family/kunjungan/'.FamilyVisitFake::VISIT)->assertOk()->assertSee('Batalkan pengajuan')->assertSee('09.00–10.30');
    Auth::forgetGuards();
    $this->patch('/family/kunjungan/'.FamilyVisitFake::VISIT.'/batalkan')->assertRedirect()->assertSessionHas('status');
    expect($this->fake->rows[0]['status'])->toBe('cancelled');
});

test('another family request stays inaccessible even for the same resident', function () {
    $this->fake->rows[] = $this->fake->row(['requester_id' => '20000000-0000-4000-8000-000000000099', 'note' => 'private-other-family']);
    $this->get('/family/kunjungan/'.FamilyVisitFake::VISIT)->assertNotFound()->assertDontSee('private-other-family');
    Auth::forgetGuards();
    $this->patch('/family/kunjungan/'.FamilyVisitFake::VISIT.'/batalkan')->assertNotFound();
    expect($this->fake->calls)->toBeEmpty();
});

test('decided visits cannot be cancelled', function (string $status) {
    $this->fake->rows[] = $this->fake->row(['status' => $status]);
    $this->patch('/family/kunjungan/'.FamilyVisitFake::VISIT.'/batalkan')->assertForbidden();
    expect($this->fake->calls)->toBeEmpty();
})->with(['approved', 'rejected', 'cancelled']);

test('role middleware protects all family and admin visit endpoints', function (string $role, string $url) {
    $this->fake->role = $role;
    $this->get($url)->assertForbidden();
})->with([['caregiver', '/family'], ['caregiver', '/family/kunjungan'], ['admin', '/family/kunjungan'], ['family', '/admin/kunjungan'], ['caregiver', '/admin/kunjungan']]);

test('admin sees real counts and can decide a pending visit', function (string $decision) {
    $this->fake->role = 'admin';
    $this->fake->rows[] = $this->fake->row();
    $this->get('/admin/kunjungan?status=pending')->assertOk()->assertSee('Manajemen Kunjungan')->assertSee('Rina Wulandari');
    Auth::forgetGuards();
    $url = '/admin/kunjungan/'.FamilyVisitFake::VISIT;
    $this->get($url)->assertOk()->assertSee('Simpan keputusan');
    Auth::forgetGuards();
    $this->patch('/admin/kunjungan/'.FamilyVisitFake::VISIT, ['decision' => $decision, 'review_note' => $decision === 'rejected' ? 'Kegiatan panti berlangsung.' : null])->assertRedirect()->assertSessionHas('status');
    expect($this->fake->rows[0]['status'])->toBe($decision);

    Auth::forgetGuards();
    $this->get($url)->assertOk()->assertSee('Sari Admin')->assertSee('17 November 2026, 08.00')
        ->assertDontSee('Simpan keputusan');

    $this->fake->role = 'family';
    Auth::forgetGuards();
    $response = $this->get('/family/kunjungan/'.FamilyVisitFake::VISIT)->assertOk()->assertDontSee('Sari Admin');
    $response->assertSee($decision === 'approved' ? 'Disetujui' : 'Kegiatan panti berlangsung.');
})->with(['approved', 'rejected']);

test('admin rejection requires a nonblank note', function () {
    $this->fake->role = 'admin';
    $this->fake->rows[] = $this->fake->row();
    $this->patch('/admin/kunjungan/'.FamilyVisitFake::VISIT, ['decision' => 'rejected', 'review_note' => '   '])->assertSessionHasErrors('review_note');
    expect($this->fake->calls)->toBeEmpty();
});

test('admin cannot decide an already decided visit', function () {
    $this->fake->role = 'admin';
    $this->fake->rows[] = $this->fake->row(['status' => 'approved']);
    $this->patch('/admin/kunjungan/'.FamilyVisitFake::VISIT, ['decision' => 'rejected', 'review_note' => 'late change'])->assertForbidden();
});

test('database race conflicts return HTTP 409 without a success message', function () {
    $this->fake->conflict = true;
    $this->post('/family/kunjungan', validFamilyVisit())->assertStatus(409)->assertSessionMissing('status')->assertSee('Pengajuan sudah berubah');
});

test('an outage during submit never reports a successful save', function () {
    $this->fake->outage = true;
    $this->post('/family/kunjungan', validFamilyVisit())->assertStatus(503)->assertSessionMissing('status');
    expect($this->fake->rows)->toBeEmpty();
});

test('published dummy text remains visible when photo permission is revoked', function () {
    $this->fake->photoConsent = false;
    $this->get('/family/kabar')->assertOk()->assertSee('Berkebun bersama di Taman Melati')->assertDontSee('/family/contoh-media/garden');
    Auth::forgetGuards();
    $this->get('/family/contoh-media/garden?resident='.FamilyVisitFake::RESIDENT)->assertForbidden();
});

test('family images use the page origin and serve JPEG files', function () {
    $avatar = '/family/foto-profil';
    $story = '/family/contoh-media/garden?resident='.FamilyVisitFake::RESIDENT;

    $this->get('/family')->assertOk()->assertSee('src="'.$avatar.'"', false)
        ->assertSee('src="'.$story.'"', false);
    foreach ([$avatar, $story] as $image) {
        Auth::forgetGuards();
        $this->get($image)->assertOk()->assertHeader('Content-Type', 'image/jpeg');
    }
});

test('demo media requires auth, a verified relation and fresh photo permission', function () {
    $this->get('/family/contoh-media/garden?resident='.FamilyVisitFake::RESIDENT)->assertOk()->assertHeader('Content-Type', 'image/jpeg')->assertHeader('Cache-Control', 'no-store, private');
    $this->fake->linked = false;
    Auth::forgetGuards();
    $this->get('/family/contoh-media/garden?resident='.FamilyVisitFake::RESIDENT)->assertNotFound();
});

test('news filters use the temporary provider without querying module two', function () {
    $this->get('/family/kabar?category=aktivitas')->assertOk()->assertSee('Berkebun bersama')->assertDontSee('Makan siang bersama teman');
    Auth::forgetGuards();
    $this->get('/family/kabar?date=2026-11-13')->assertOk()->assertSee('Belum ada kabar untuk pilihan ini');
    Auth::forgetGuards();
    $this->get('/family/kabar?date=2026-11-12&category=kebersamaan')->assertOk()
        ->assertSee('Cerita dan kenangan pagi')->assertDontSee('Makan siang bersama teman');
});

test('the fictional Sulastri portrait belongs to the selected dummy resident and requires photo consent', function () {
    $portrait = '/family/contoh-media/sulastri?resident='.FamilyVisitFake::RESIDENT;
    $this->get($portrait)->assertNotFound();
    $this->fake->residentName = 'Ibu Sulastri (Dummy)';
    Auth::forgetGuards();
    $this->get('/family/kabar')->assertOk()->assertSee('Foto ilustrasi Ibu Sulastri')->assertSee($portrait);
    Auth::forgetGuards();
    $this->get($portrait)->assertOk()->assertHeader('Content-Type', 'image/jpeg');
    $this->fake->photoConsent = false;
    Auth::forgetGuards();
    $this->get('/family/kabar')->assertOk()->assertDontSee($portrait);
    Auth::forgetGuards();
    $this->get($portrait)->assertForbidden();
});

test('Eloquent relationships match the Supabase foreign keys and protected fields', function () {
    $visit = new VisitRequest;
    expect($visit->resident()->getForeignKeyName())->toBe('resident_id')
        ->and($visit->requester()->getForeignKeyName())->toBe('requester_id')
        ->and($visit->reviewer()->getForeignKeyName())->toBe('reviewed_by')
        ->and((new Resident)->visits()->getForeignKeyName())->toBe('resident_id')
        ->and((new AccountProfile)->requestedVisits()->getForeignKeyName())->toBe('requester_id')
        ->and($visit->getFillable())->not->toContain('status', 'requester_id', 'reviewed_by');
});

test('temporary stories can be disabled without affecting the real family portal', function () {
    config(['family_visits.demo_news' => false]);
    $this->get('/family')->assertOk()->assertSee('Eyang Hartono')->assertSee('Menunggu kabar keluarga')->assertDontSee('Data dummy sementara');
    Auth::forgetGuards();
    $this->get('/family/contoh-media/garden?resident='.FamilyVisitFake::RESIDENT)->assertNotFound();
});

test('the module two provider can supply real published news without dummy labels or media routes', function () {
    app()->instance(FamilyNewsProvider::class, new class implements FamilyNewsProvider
    {
        public function publishedFor(string $residentId, bool $photoConsent): array
        {
            return [['id' => 'published-fixture', 'title' => 'Kabar terpublikasi dari provider', 'summary' => 'Ringkasan fixture integrasi.',
                'category' => 'aktivitas', 'published_at' => '2026-11-17T02:00:00Z', 'author' => 'Caregiver Uji',
                'media' => $photoConsent ? '/media/private/published-fixture' : null, 'is_demo' => false]];
        }
    });
    $this->get('/family')->assertOk()->assertSee('Kabar terpublikasi dari provider')->assertSee('/media/private/published-fixture')->assertSee('09.00')->assertDontSee('KABAR CONTOH')->assertDontSee('Data dummy sementara');
    Auth::forgetGuards();
    $this->get('/family/kabar')->assertOk()->assertSee('Penerbit: Caregiver Uji')->assertDontSee('Contoh penerbit')->assertDontSee('/family/contoh-media/');
});
