<?php

use Illuminate\Support\Facades\Http;

beforeEach(function () {
    config(['supabase.publishable_key' => 'test-key', 'session.encrypt' => false]);
    Http::preventStrayRequests();
});

test('recovery request has a generic response for registered and missing emails', function (int $status) {
    Http::fake(['*/auth/v1/recover' => Http::response([], $status)]);
    $this->from('/forgot-password')->post('/forgot-password', ['email' => 'uji@example.test'])->assertRedirect('/forgot-password')->assertSessionHas('status', 'Jika akun terdaftar, tautan pemulihan akan dikirim ke email Anda.');
})->with([200, 400]);

test('network failure does not claim recovery email was sent', function () {
    Http::fake(['*/auth/v1/recover' => Http::response([], 503)]);
    $this->post('/forgot-password', ['email' => 'uji@example.test'])->assertStatus(503)->assertSessionMissing('status');
});

test('GET renders recovery form without consuming the token', function () {
    $this->get('/reset-password?token_hash=one-use-token')->assertOk()->assertSee('one-use-token');
    Http::assertNothingSent();
});

test('password confirmation is checked before consuming the token', function () {
    $this->post('/reset-password', ['token_hash' => 'one-use-token', 'password' => 'long-enough-password', 'password_confirmation' => 'different-password'])->assertSessionHasErrors('password');
    Http::assertNothingSent();
});

test('expired recovery links are rejected', function () {
    Http::fake(['*/auth/v1/verify' => Http::response([], 403)]);
    $this->post('/reset-password', ['token_hash' => 'expired', 'password' => 'long-enough-password', 'password_confirmation' => 'long-enough-password'])->assertSessionHasErrors('token_hash')->assertSessionMissing('supabase_recovery');
});

test('verified recovery changes the password and clears all local tokens', function () {
    Http::fake([
        '*/auth/v1/verify' => Http::response(['access_token' => 'recovery-access']),
        '*/auth/v1/user' => Http::response(['id' => 'user']),
        '*/auth/v1/logout*' => Http::response([], 204),
    ]);
    $this->post('/reset-password', ['token_hash' => 'valid', 'password' => 'long-enough-password', 'password_confirmation' => 'long-enough-password'])->assertRedirect('/login')->assertSessionMissing('supabase_recovery')->assertSessionMissing('supabase');
    Http::assertSent(fn ($request) => str_ends_with($request->url(), '/verify') && $request['type'] === 'recovery');
    Http::assertSent(fn ($request) => $request->method() === 'PUT' && $request->hasHeader('Authorization', 'Bearer recovery-access'));
});

test('temporary update failure retains verified recovery for retry', function () {
    Http::fake(['*/auth/v1/verify' => Http::response(['access_token' => 'recovery-access']), '*/auth/v1/user' => Http::response([], 503)]);
    $this->post('/reset-password', ['token_hash' => 'valid', 'password' => 'long-enough-password', 'password_confirmation' => 'long-enough-password'])->assertStatus(503)->assertSessionHas('supabase_recovery.token', 'recovery-access');
});

test('recovery requests are throttled per email and IP after five attempts', function () {
    Http::fake(['*/auth/v1/recover' => Http::response([], 200)]);
    for ($i = 0; $i < 5; $i++) {
        $this->from('/forgot-password')->post('/forgot-password', ['email' => 'limit-recovery@example.test'])
            ->assertRedirect('/forgot-password');
    }
    $this->post('/forgot-password', ['email' => 'limit-recovery@example.test'])
        ->assertStatus(429)
        ->assertHeader('Retry-After');
    Http::assertSentCount(5);
});
