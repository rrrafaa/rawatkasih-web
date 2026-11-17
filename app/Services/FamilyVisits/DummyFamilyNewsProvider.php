<?php

namespace App\Services\FamilyVisits;

use App\Contracts\FamilyNewsProvider;

/** Temporary read-only module-2 contract. Never writes family_updates or internal notes. */
class DummyFamilyNewsProvider implements FamilyNewsProvider
{
    public function publishedFor(string $residentId, bool $photoConsent): array
    {
        if (! config('family_visits.demo_news')) {
            return [];
        }
        $stories = [
            ['garden', 'Berkebun bersama di Taman Melati', 'Eyang menikmati udara sore, menyiram tanaman, dan berbincang dengan pendamping.', 'aktivitas', '2026-11-14T15:20:00+07:00', 'Sr. Siti Aminah'],
            ['lunch', 'Makan siang bersama teman', 'Suasana makan bersama berlangsung hangat dengan pendamping.', 'kebersamaan', '2026-11-14T12:35:00+07:00', 'Sr. Dewi Lestari'],
            ['morning', 'Cerita dan kenangan pagi', 'Eyang menikmati waktu membaca dan saling bercerita di ruang keluarga.', 'kebersamaan', '2026-11-14T10:10:00+07:00', 'Sr. Siti Aminah'],
        ];

        return array_map(fn ($s) => ['id' => $s[0], 'title' => $s[1], 'summary' => $s[2], 'category' => $s[3],
            'published_at' => $s[4], 'author' => $s[5], 'media' => $photoConsent ? $s[0] : null, 'is_demo' => true], $stories);
    }
}
