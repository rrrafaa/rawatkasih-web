<?php

namespace App\Services\FamilyVisits;

use App\Exceptions\SupabaseUnavailable;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

class FamilyMediaStorage
{
    /** @return array{string, string, string} */
    private function credentials(): array
    {
        $token = request()->session()->get('supabase.access_token');
        $key = config('supabase.publishable_key');
        $url = rtrim((string) config('supabase.url'), '/');
        abort_unless(is_string($token) && $token !== '', 401);
        if (! is_string($key) || $key === '' || ! str_starts_with($url, 'https://')) {
            throw new SupabaseUnavailable;
        }

        return [$url, $key, $token];
    }

    public function upload(UploadedFile $file, string $namespace): string
    {
        // Namespace is chosen by the controller, not supplied by a browser.
        abort_unless(
            preg_match('~\\A(?:[0-9a-f-]{36}/(?:profile|updates)|profiles/[0-9a-f-]{36}/avatar)\\z~D', $namespace) === 1,
            422
        );
        $mime = $file->getMimeType();
        $extension = match ($mime) {
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp',
            default => null,
        };
        abort_unless($extension !== null && $file->getSize() <= 3145728, 422);
        $path = $namespace.'/'.Str::uuid().'.'.$extension;
        [$url, $key, $token] = $this->credentials();
        try {
            $response = Http::withHeaders(['apikey' => $key, 'x-upsert' => 'false'])
                ->withToken($token)
                ->withBody((string) file_get_contents($file->getRealPath()), $mime)
                ->timeout((int) config('supabase.timeout', 10))
                ->post($url.'/storage/v1/object/'.config('supabase.storage_bucket').'/'.$path);
        } catch (ConnectionException) {
            throw new SupabaseUnavailable;
        }
        abort_if(in_array($response->status(), [401, 403], true), 403);
        if (! $response->successful()) {
            throw new SupabaseUnavailable;
        }

        return $path;
    }

    public function download(string $path): SymfonyResponse
    {
        // Never accept an arbitrary user URL; only validated private object keys.
        abort_unless(
            preg_match('~\\A(?:[0-9a-f-]{36}/(?:profile|updates)|profiles/[0-9a-f-]{36}/avatar)/[0-9a-f-]{36}\\.(?:jpg|png|webp)\\z~D', $path) === 1,
            404
        );
        [$url, $key, $token] = $this->credentials();
        try {
            $response = Http::withHeaders(['apikey' => $key])
                ->withToken($token)
                ->timeout((int) config('supabase.timeout', 10))
                ->get($url.'/storage/v1/object/authenticated/'.config('supabase.storage_bucket').'/'.$path);
        } catch (ConnectionException) {
            throw new SupabaseUnavailable;
        }
        abort_if(in_array($response->status(), [401, 403], true), 403);
        abort_if($response->status() === 404, 404);
        if (! $response->successful()) {
            throw new SupabaseUnavailable;
        }
        $mime = strtolower(trim(explode(';', $response->header('Content-Type'), 2)[0]));
        abort_unless(in_array($mime, ['image/jpeg', 'image/png', 'image/webp'], true), 502);

        return response($response->body(), 200, [
            'Content-Type' => $mime,
            'Cache-Control' => 'private, no-store',
            'X-Content-Type-Options' => 'nosniff',
            'Content-Security-Policy' => "default-src 'none'; sandbox",
        ]);
    }
}
