<?php

namespace App\Contracts;

interface FamilyNewsProvider
{
    // Urutkan dari terbaru; media demo memakai ID aset, sedangkan media asli memakai URL terlindungi.
    /** @return list<array{id: string, title: string, summary: string, category: string, published_at: string, author: string, media: string|null, is_demo: bool}> */
    public function publishedFor(string $residentId, bool $photoConsent): array;
}
