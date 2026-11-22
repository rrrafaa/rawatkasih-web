<?php

namespace App\Services;

use App\Exceptions\InvalidCredentials;
use App\Exceptions\SupabaseUnavailable;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SupabaseClient
{
    /**
     * @param  array<string, mixed>|object  $data
     * @param  array<string, string>  $headers
     */
    public function request(
        string $method,
        string $path,
        array|object $data = [],
        ?string $token = null,
        array $headers = []
    ): Response {
        $url = rtrim((string) config('supabase.url'), '/');
        $key = (string) config('supabase.publishable_key');

        if (! str_starts_with($url, 'https://') || $key === '') {
            throw new SupabaseUnavailable;
        }

        try {
            $request = Http::baseUrl($url)
                ->acceptJson()
                ->asJson()
                ->withHeaders([
                    ...$headers,
                    'apikey' => $key,
                ])
                ->timeout((int) config('supabase.timeout', 10));

            if ($token !== null) {
                $request = $request->withToken($token);
            }

            // Jangan ulangi pergantian token, perubahan kata sandi, atau operasi tulis secara otomatis.
            $response = $request->send($method, $path, [
                strtoupper($method) === 'GET' ? 'query' : 'json' => $data,
            ]);
        } catch (ConnectionException $e) {
            Log::error('Supabase ConnectionException: '.$e->getMessage());
            throw new SupabaseUnavailable;
        }

        if ($response->serverError() || $response->status() === 429) {
            Log::error('Supabase ServerError '.$response->status().': '.$response->body());
            throw new SupabaseUnavailable;
        }

        return $response;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function auth(
        string $method,
        string $path,
        array $data = [],
        ?string $token = null
    ): array {
        $response = $this->request(
            $method,
            '/auth/v1/'.$path,
            $data,
            $token
        );

        if (! $response->successful()) {
            if (in_array($response->status(), [400, 401, 403, 422], true)) {
                throw new InvalidCredentials;
            }

            throw new SupabaseUnavailable;
        }

        return $response->json() ?? [];
    }
}
