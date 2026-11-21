<?php

namespace App\Services;

use App\Enums\Role;
use App\Exceptions\SupabaseUnavailable;
use App\Models\SupabaseUser;

class CareTaskOccurrenceGenerator
{
    public function __construct(private SupabaseClient $client) {}

    public function generateForCurrentCaregiver(): int
    {
        $user = auth()->user();

        abort_unless($user instanceof SupabaseUser, 401);
        abort_unless($user->role() === Role::Caregiver, 403);

        $token = request()->session()->get('supabase.access_token');

        abort_unless(is_string($token) && $token !== '', 401);

        $response = $this->client->request(
            'POST',
            '/rest/v1/rpc/generate_today_care_task_occurrences',
            (object) [],
            $token
        );

        abort_if(in_array($response->status(), [401, 403], true), 403);

        if (! $response->successful()) {
            throw new SupabaseUnavailable;
        }

        $count = $response->json();

        if (! is_int($count) || $count < 0) {
            throw new SupabaseUnavailable;
        }

        return $count;
    }
}
