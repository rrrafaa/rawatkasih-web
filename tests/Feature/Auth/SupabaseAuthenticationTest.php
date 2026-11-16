<?php

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    config(['supabase.publishable_key' => 'test-key', 'session.encrypt' => false]);
    Http::preventStrayRequests();
});

function sessionTokens(): array
{
    return ['access_token' => 'user-access', 'refresh_token' => 'user-refresh', 'expires_at' => time() + 3600];
}

function fakeIdentity(string $role = 'admin', bool $active = true): void
{
    Http::fake([
        '*/auth/v1/token*' => Http::response(['access_token' => 'user-access', 'refresh_token' => 'user-refresh', 'expires_in' => 3600, 'user' => ['id' => '10000000-0000-4000-8000-000000000001']]),
        '*/auth/v1/user' => Http::response(['id' => '10000000-0000-4000-8000-000000000001']),
        '*/rest/v1/profiles*' => Http::response([['id' => '10000000-0000-4000-8000-000000000001', 'full_name' => 'Pengguna Uji', 'email' => 'uji@example.test', 'role' => $role, 'is_active' => $active]]),
        '*/auth/v1/logout*' => Http::response([], 204),
    ]);
}

test('login is Indonesian and has no public signup', function () {
    $this->get('/login')->assertOk()->assertSee('Masuk ke akun')->assertDontSee('Sign up')->assertHeader('Cache-Control', 'no-store, private');
    $this->get('/register')->assertNotFound();
    $this->post('/register', [])->assertNotFound();
});

test('all roles land on their own dashboard and cannot access the other two', function (string $role) {
    fakeIdentity($role);
    $this->post('/login', ['email' => 'uji@example.test', 'password' => 'not-a-real-password', 'role' => 'admin'])
        ->assertRedirect('/'.$role)->assertSessionHas('supabase.access_token', 'user-access');
    Auth::forgetGuards();
    $this->get('/'.$role)->assertOk()->assertSee('Pengguna Uji')->assertDontSee('user-access');
    foreach (array_diff(['admin', 'caregiver', 'family'], [$role]) as $other) {
        Auth::forgetGuards();
        $this->get('/'.$other)->assertForbidden();
    }
})->with(['admin', 'caregiver', 'family']);

test('inactive and unknown-role accounts are rejected without storing tokens', function (string $role, bool $active) {
    fakeIdentity($role, $active);
    $this->post('/login', ['email' => 'uji@example.test', 'password' => 'not-a-real-password'])->assertSessionHasErrors('email')->assertSessionMissing('supabase');
})->with([['admin', false], ['superadmin', true]]);

test('invalid credentials produce a generic error', function () {
    Http::fake(['*/auth/v1/token*' => Http::response(['error' => 'invalid_grant'], 400)]);
    $this->post('/login', ['email' => 'missing@example.test', 'password' => 'bad'])->assertSessionHasErrors(['email' => 'Tidak dapat masuk. Periksa kredensial atau hubungi admin.'])->assertSessionMissing('supabase');
});

test('login is rate limited by email and IP with HTTP 429 and Retry-After', function () {
    Http::fake(['*/auth/v1/token*' => Http::response([], 400)]);
    for ($i = 0; $i < 5; $i++) {
        $this->post('/login', ['email' => 'locked@example.test', 'password' => 'bad'])->assertSessionHasErrors('email');
    }
    $this->post('/login', ['email' => 'locked@example.test', 'password' => 'bad'])
        ->assertStatus(429)->assertHeader('Retry-After');
    Http::assertSentCount(5);

    // Throttle is keyed per normalized email + IP, not globally for all accounts.
    $this->post('/login', ['email' => 'other@example.test', 'password' => 'bad'])
        ->assertSessionHasErrors('email');
    Http::assertSentCount(6);
});

test('throttled JSON login requests get a JSON 429 response', function () {
    Http::fake(['*/auth/v1/token*' => Http::response([], 400)]);
    for ($i = 0; $i < 5; $i++) {
        $this->postJson('/login', ['email' => 'json-locked@example.test', 'password' => 'bad'])
            ->assertUnprocessable();
    }
    $this->postJson('/login', ['email' => 'json-locked@example.test', 'password' => 'bad'])
        ->assertStatus(429)
        ->assertHeader('Retry-After')
        ->assertJsonPath('message', 'Terlalu banyak percobaan. Coba lagi dalam satu menit.');
    Http::assertSentCount(5);
});

test('protected URLs redirect guests', function () {
    foreach (['admin', 'caregiver', 'family', 'dashboard'] as $path) {
        $this->get('/'.$path)->assertRedirect('/login');
    }
});

test('deactivated account loses existing session on next request', function () {
    fakeIdentity('caregiver', false);
    $this->withSession(['supabase' => sessionTokens()])->get('/caregiver')->assertRedirect('/login')->assertSessionMissing('supabase');
});

test('a role change is read from the database on the next request', function () {
    fakeIdentity('family');
    $this->withSession(['supabase' => sessionTokens()])->get('/admin')->assertForbidden();
});

test('expired sessions rotate refresh tokens and validate the identity', function () {
    fakeIdentity('family');
    $this->withSession(['supabase' => [...sessionTokens(), 'expires_at' => 0]])->get('/family')->assertOk();
    Http::assertSent(fn ($request) => str_contains($request->url(), 'grant_type=refresh_token') && $request['refresh_token'] === 'user-refresh');
});

test('revoked refresh tokens clear local session', function () {
    Http::fake(['*/auth/v1/token*' => Http::response([], 400), '*/auth/v1/logout*' => Http::response([], 401)]);
    $this->withSession(['supabase' => [...sessionTokens(), 'expires_at' => 0]])->get('/admin')->assertRedirect('/login')->assertSessionMissing('supabase');
});

test('logout destroys session even during a Supabase outage', function () {
    Http::fake(['*/auth/v1/logout*' => Http::response([], 503)]);
    $this->withSession(['supabase' => sessionTokens()])->post('/logout')->assertRedirect('/login')->assertSessionMissing('supabase');
    Auth::forgetGuards();
    $this->get('/admin')->assertRedirect('/login');
});

test('an upstream outage never reports login success or leaks credentials', function () {
    Http::fake(['*/auth/v1/token*' => Http::response([], 503)]);
    $this->post('/login', ['email' => 'uji@example.test', 'password' => 'secret-test'])->assertStatus(503)->assertDontSee('secret-test')->assertSessionMissing('supabase');
});

test('role comes from profile and not editable auth metadata', function () {
    fakeIdentity('family');
    $this->post('/login', ['email' => 'uji@example.test', 'password' => 'test', 'role' => 'admin'])->assertRedirect('/family');
    Http::assertSent(fn ($request) => str_contains($request->url(), '/rest/v1/profiles') && $request->hasHeader('Authorization', 'Bearer user-access') && $request['select'] === 'id,full_name,email,role,is_active');
});
