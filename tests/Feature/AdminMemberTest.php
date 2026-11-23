<?php

use App\Models\SupabaseUser;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    config(['supabase.publishable_key' => 'test-public', 'supabase.service_role_key' => 'test-secret']);
    Http::preventStrayRequests();
    $this->actingAs(new SupabaseUser(['id' => 'admin', 'full_name' => 'Admin', 'role' => 'admin']))
        ->withSession(['supabase.access_token' => 'admin-token']);
});

test('admin provisions family and caregiver accounts with trusted role metadata', function (string $role) {
    $resident = '11111111-1111-4111-8111-111111111111';
    $id = '89e58fa6-a43c-4f11-b8a2-f0e176b7aa4a';
    Http::fake([
        '*/rest/v1/residents*' => Http::response([['id' => $resident]]),
        '*/auth/v1/admin/users' => Http::response(['id' => $id]),
        '*/rest/v1/profiles*' => Http::response([['role' => $role, 'is_active' => true]]),
    ]);

    $this->post('/admin/anggota', [
        'role' => $role, 'full_name' => 'Anggota Demo', 'email' => 'member@example.test',
        'password' => 'test-password', 'password_confirmation' => 'test-password',
        'resident_id' => $resident, 'relationship' => 'Anak',
    ])->assertRedirect('/admin/anggota')->assertSessionHas('status');

    Http::assertSent(fn ($request) => str_ends_with($request->url(), '/auth/v1/admin/users')
        && $request->hasHeader('Authorization', 'Bearer test-secret')
        && $request['app_metadata'] === ($role === 'family'
            ? ['rk_member_role' => 'family', 'rk_resident_id' => $resident, 'rk_relationship' => 'Anak']
            : ['rk_member_role' => 'caregiver']));
})->with(['family', 'caregiver']);

test('admin sees pending links and verifies only a pending link with their own token', function () {
    $id = '89e58fa6-a43c-4f11-b8a2-f0e176b7aa4a';
    Http::fake([
        '*/rest/v1/profiles*' => Http::response([['id' => $id, 'full_name' => 'Rina', 'email' => 'family@example.test', 'role' => 'family', 'is_active' => true]]),
        '*/rest/v1/residents*' => Http::response([]),
        '*/rest/v1/family_links*' => Http::response([['id' => $id, 'user_id' => $id, 'resident_id' => $id, 'relationship' => 'Anak', 'status' => 'pending']]),
    ]);

    $this->get('/admin/anggota')->assertOk()->assertSee('Tambah Keluarga')->assertSee('Rina')->assertSee('Verifikasi relasi');
    $this->patch('/admin/anggota/relasi/'.$id)->assertRedirect('/admin/anggota');

    Http::assertSent(fn ($request) => $request->method() === 'PATCH'
        && str_contains($request->url(), 'status=eq.pending')
        && $request['status'] === 'verified'
        && $request->hasHeader('Authorization', 'Bearer admin-token'));
});

test('non-admin cannot manage members and admin cannot provision an admin role', function () {
    $this->post('/admin/anggota', ['role' => 'admin'])->assertSessionHasErrors('role');
    $this->actingAs(new SupabaseUser(['id' => 'family', 'full_name' => 'Rina', 'role' => 'family']));
    $this->get('/admin/anggota')->assertForbidden();
    $this->post('/admin/anggota')->assertForbidden();
    $this->patch('/admin/anggota/relasi/89e58fa6-a43c-4f11-b8a2-f0e176b7aa4a')->assertForbidden();

    Http::assertNothingSent();
});
