<?php

namespace App\Services\FamilyVisits;

use App\Contracts\FamilyNewsProvider;
use App\Exceptions\SupabaseUnavailable;
use App\Services\SupabaseClient;

/**
 * Read-only published updates using the signed-in user's JWT and Supabase RLS.
 * Dummy stories only appear if there are no actual published records and demo is enabled.
 */
class PublishedFamilyNewsProvider implements FamilyNewsProvider
{
    public function __construct(private SupabaseClient $client, private DummyFamilyNewsProvider $dummy) {}

    public function publishedFor(string $residentId, bool $photoConsent): array
    {
        $token = request()->session()->get('supabase.access_token');
        abort_unless(is_string($token) && $token !== '', 401);
        $response = $this->client->request('GET', '/rest/v1/family_updates', [
            'select' => 'id,resident_id,title,category,summary,photo_path,photo_caption,status,published_at,created_at',
            'resident_id' => 'eq.'.$residentId,
            'status' => 'eq.published',
            'order' => 'published_at.desc.nullslast,created_at.desc',
            'limit' => 50,
        ], $token);
        abort_if(in_array($response->status(), [401, 403], true), 403);
        if (! $response->successful()) {
            throw new SupabaseUnavailable;
        }
        $rows = $response->json();
        if (! is_array($rows) || $rows === []) {
            return $this->dummy->publishedFor($residentId, $photoConsent);
        }
        $stories = [];
        foreach ($rows as $row) {
            if (! is_array($row) || empty($row['id'])) {
                continue;
            }
            $media = $photoConsent && is_string($row['photo_path'] ?? null) && $row['photo_path'] !== ''
                ? route('family.update-photo', ['update' => $row['id'], 'resident' => $residentId], absolute: false)
                : null;
            $stories[] = [
                'id' => (string) $row['id'],
                'title' => trim((string) ($row['title'] ?? '')) ?: 'Kabar dari panti',
                'summary' => (string) ($row['summary'] ?? ''),
                'category' => in_array($row['category'] ?? null, ['aktivitas', 'kebersamaan'], true)
                    ? $row['category'] : 'lainnya',
                'published_at' => $row['published_at'] ?: $row['created_at'],
                'author' => 'Tim pendamping RawatKasih',
                'media' => $media,
                'is_demo' => false,
                'photo_caption' => $row['photo_caption'] ?? null,
            ];
        }

        return $stories;
    }
}
