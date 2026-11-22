<?php

namespace App\Services\CareUpdates;

use App\Contracts\FamilyNewsProvider;
use App\Models\FamilyUpdate;

class SupabaseFamilyNewsProvider implements FamilyNewsProvider
{
    public function __construct(private FamilyUpdate $familyUpdates) {}

    /**
     * Mengambil kabar berstatus published untuk lansia tertentu,
     * serta menyembunyikan media foto jika consent foto tidak aktif.
     *
     * @return list<array{id: string, title: string, summary: string, category: string, published_at: string, author: string, media: string|null, is_demo: bool}>
     */
    public function publishedFor(string $residentId, bool $photoConsent): array
    {
        $rows = $this->familyUpdates->publishedForResident($residentId, 1, 50);

        $stories = [];
        foreach ($rows as $row) {
            $mediaUrl = null;
            if ($photoConsent && filled($row['photo_path'] ?? null)) {
                $mediaUrl = route('care-updates.family-updates.photo', ['updateId' => $row['id']]);
            }

            $summary = (string) ($row['summary'] ?? '');
            $title = mb_strimwidth($summary, 0, 45, '...');

            $stories[] = [
                'id' => (string) ($row['id'] ?? ''),
                'title' => $title ?: 'Kabar Harian Lansia',
                'summary' => $summary,
                'category' => 'aktivitas',
                'published_at' => (string) ($row['published_at'] ?? now()->toIso8601String()),
                'author' => 'Caregiver Panti',
                'media' => $mediaUrl,
                'is_demo' => false,
            ];
        }

        return $stories;
    }
}
