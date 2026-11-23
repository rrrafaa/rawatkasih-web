<?php

namespace App\Http\Controllers;

use App\Exceptions\SupabaseUnavailable;
use App\Services\SupabaseClient;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AdminMemberController extends Controller
{
    public function index(Request $request, SupabaseClient $client): View
    {
        $token = $this->token($request);
        $members = $client->request('GET', '/rest/v1/profiles', [
            'select' => 'id,full_name,email,role,is_active',
            'role' => 'in.(family,caregiver)',
            'order' => 'full_name,id',
        ], $token);
        $links = $client->request('GET', '/rest/v1/family_links', [
            'select' => 'id,user_id,resident_id,relationship,status',
        ], $token);
        $residents = $client->request('GET', '/rest/v1/residents', [
            'select' => 'id,full_name',
            'order' => 'full_name,id',
        ], $token);
        $memberRows = $members->json();
        $linkRows = $links->json();
        $residentRows = $residents->json();
        if ($members->failed() || $links->failed() || $residents->failed()
            || ! is_array($memberRows) || ! is_array($linkRows) || ! is_array($residentRows)) {
            throw new SupabaseUnavailable;
        }

        return view('admin.members', [
            'user' => $request->user(),
            'members' => $memberRows,
            'links' => collect($linkRows)->groupBy('user_id'),
            'residents' => collect($residentRows)->keyBy('id'),
            'creationConfigured' => filled(config('supabase.service_role_key')),
        ]);
    }

    public function store(Request $request, SupabaseClient $client): RedirectResponse
    {
        $token = $this->token($request);
        $input = $request->validate([
            'role' => ['required', Rule::in(['family', 'caregiver'])],
            'full_name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:254'],
            'password' => ['required', 'string', 'min:12', 'max:72', 'confirmed'],
            'resident_id' => ['exclude_unless:role,family', 'required', 'uuid'],
            'relationship' => ['exclude_unless:role,family', 'required', 'string', 'max:80'],
        ], attributes: [
            'full_name' => 'nama lengkap', 'password' => 'kata sandi',
            'resident_id' => 'lansia', 'relationship' => 'hubungan keluarga',
        ]);

        $metadata = ['rk_member_role' => $input['role']];
        if ($input['role'] === 'family') {
            $resident = $client->request('GET', '/rest/v1/residents', [
                'select' => 'id', 'id' => 'eq.'.$input['resident_id'], 'limit' => 1,
            ], $token);
            if ($resident->failed()) {
                throw new SupabaseUnavailable;
            }
            if ($resident->json('0.id') === null) {
                throw ValidationException::withMessages(['resident_id' => 'Lansia yang dipilih tidak ditemukan.']);
            }
            $metadata['rk_resident_id'] = $input['resident_id'];
            $metadata['rk_relationship'] = $input['relationship'];
        }

        $key = (string) config('supabase.service_role_key');
        $url = rtrim((string) config('supabase.url'), '/');
        if ($key === '' || ! str_starts_with($url, 'https://')) {
            throw new SupabaseUnavailable;
        }

        try {
            $response = Http::acceptJson()->withToken($key)->withHeaders(['apikey' => $key])
                ->timeout((int) config('supabase.timeout', 10))
                ->post($url.'/auth/v1/admin/users', [
                    'email' => strtolower($input['email']),
                    'password' => $input['password'],
                    'email_confirm' => true,
                    'app_metadata' => $metadata,
                    'user_metadata' => ['full_name' => $input['full_name']],
                ]);
        } catch (ConnectionException) {
            throw new SupabaseUnavailable;
        }

        if (in_array($response->json('error_code'), ['email_exists', 'user_already_exists'], true)) {
            throw ValidationException::withMessages(['email' => 'Email ini sudah terdaftar.']);
        }
        if ($response->failed() || ! is_string($response->json('id'))) {
            throw new SupabaseUnavailable;
        }

        $profile = $client->request('GET', '/rest/v1/profiles', [
            'select' => 'role,is_active', 'id' => 'eq.'.$response->json('id'), 'limit' => 1,
        ], $token);
        if ($profile->failed() || $profile->json('0.role') !== $input['role'] || $profile->json('0.is_active') !== true) {
            throw new SupabaseUnavailable;
        }

        return to_route('admin.members.index')->with('status', 'Akun anggota berhasil dibuat.');
    }

    public function verify(Request $request, string $link, SupabaseClient $client): RedirectResponse
    {
        $response = $client->request('PATCH', '/rest/v1/family_links?id=eq.'.$link.'&status=eq.pending', [
            'status' => 'verified', 'verified_at' => now()->toIso8601String(),
        ], $this->token($request), ['Prefer' => 'return=representation']);
        if ($response->failed()) {
            throw new SupabaseUnavailable;
        }

        abort_unless($response->json('0.id') === $link, 409, 'Relasi ini sudah diproses atau tidak tersedia.');

        return to_route('admin.members.index')->with('status', 'Relasi keluarga berhasil diverifikasi.');
    }

    private function token(Request $request): string
    {
        $token = $request->session()->get('supabase.access_token');
        abort_unless(is_string($token) && $token !== '', 401);

        return $token;
    }
}
