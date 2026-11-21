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


    /**
     * @param list<string> $ids
     * @return array<string, string>
     */
    public function namesByIds(array $ids, string $token): array
    {
        $profileIds = array_values(array_unique(array_filter(
            $ids,
            fn ($id) => is_string($id)
                && preg_match(
                    '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/iD',
                    $id
                )
        )));

        if ($profileIds === []) {
            return [];
        }

        $response = $this->client->request('GET', '/rest/v1/profiles', [
            'select' => 'id,full_name',
            'id' => 'in.('.implode(',', $profileIds).')',
        ], $token);

        abort_if(in_array($response->status(), [401, 403], true), 403);

        if (! $response->successful()) {
            throw new SupabaseUnavailable;
        }

        $names = [];

        foreach ($response->json() as $profile) {
            if (
                is_array($profile)
                && is_string($profile['id'] ?? null)
                && is_string($profile['full_name'] ?? null)
            ) {
                $names[$profile['id']] = $profile['full_name'];
            }
        }

        return $names;
    }
}
