<?php

namespace App\Models;

use App\Enums\Role;
use App\Exceptions\InvalidCredentials;
use App\Exceptions\SupabaseUnavailable;
use App\Services\SupabaseClient;

class Profile
{
    public function __construct(private SupabaseClient $client) {}

    public function activeUser(string $id, string $token): SupabaseUser
    {
        $response = $this->client->request('GET', '/rest/v1/profiles', [
            'select' => 'id,full_name,email,role,is_active', 'id' => 'eq.'.$id, 'limit' => 1,
        ], $token);
        if (in_array($response->status(), [401, 403], true)) {
            throw new InvalidCredentials;
        }
        if (! $response->successful()) {
            throw new SupabaseUnavailable;
        }
        $profile = $response->json('0');
        if (! is_array($profile) || ($profile['id'] ?? null) !== $id
            || ($profile['is_active'] ?? false) !== true
            || Role::tryFrom($profile['role'] ?? '') === null) {
            throw new InvalidCredentials;
        }

        return new SupabaseUser($profile);
    }
}
