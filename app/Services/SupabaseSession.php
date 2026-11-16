<?php

namespace App\Services;

use App\Exceptions\InvalidCredentials;
use App\Exceptions\SupabaseUnavailable;
use App\Models\Profile;
use App\Models\SupabaseUser;
use Illuminate\Http\Request;

class SupabaseSession
{
    public function __construct(private SupabaseClient $client, private Profile $profiles) {}

    public function login(Request $request, string $email, string $password): SupabaseUser
    {
        $tokens = $this->client->auth('POST', 'token?grant_type=password', compact('email', 'password'));
        $id = $tokens['user']['id'] ?? null;
        if (! is_string($id) || ! isset($tokens['access_token'], $tokens['refresh_token'])) {
            throw new InvalidCredentials;
        }
        try {
            $user = $this->profiles->activeUser($id, $tokens['access_token']);
        } catch (InvalidCredentials $exception) {
            $this->revoke($tokens['access_token']);
            throw $exception;
        }
        $request->session()->regenerate();
        $request->session()->forget('supabase_recovery');
        $this->store($request, $tokens);

        return $user;
    }

    public function user(Request $request): ?SupabaseUser
    {
        $tokens = $request->session()->get('supabase');
        if (! is_array($tokens) || ! isset($tokens['access_token'], $tokens['refresh_token'])) {
            return null;
        }
        try {
            if (($tokens['expires_at'] ?? 0) <= time() + 60) {
                $tokens = $this->client->auth('POST', 'token?grant_type=refresh_token', [
                    'refresh_token' => $tokens['refresh_token'],
                ]);
                $this->store($request, $tokens);
            }
            // Validate with the Auth server; never trust a decoded JWT or cached role.
            $identity = $this->client->auth('GET', 'user', [], $tokens['access_token']);
            if (! isset($identity['id']) || ! is_string($identity['id'])) {
                throw new InvalidCredentials;
            }

            return $this->profiles->activeUser($identity['id'], $tokens['access_token']);
        } catch (InvalidCredentials) {
            $this->revoke($tokens['access_token']);
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return null;
        }
    }

    public function logout(Request $request): void
    {
        $token = $request->session()->get('supabase.access_token');
        if (is_string($token)) {
            $this->revoke($token);
        }
        $request->session()->invalidate();
        $request->session()->regenerateToken();
    }

    public function revoke(string $token): void
    {
        try {
            $this->client->auth('POST', 'logout?scope=local', [], $token);
        } catch (InvalidCredentials|SupabaseUnavailable) {
            // Local session is always destroyed, even if remote logout is unavailable.
        }
    }

    /** @param array<string, mixed> $tokens */
    private function store(Request $request, array $tokens): void
    {
        if (! is_string($tokens['access_token'] ?? null) || ! is_string($tokens['refresh_token'] ?? null)) {
            throw new InvalidCredentials;
        }
        $request->session()->put('supabase', [
            'access_token' => $tokens['access_token'],
            'refresh_token' => $tokens['refresh_token'],
            'expires_at' => time() + (int) ($tokens['expires_in'] ?? 3600),
        ]);
    }
}
