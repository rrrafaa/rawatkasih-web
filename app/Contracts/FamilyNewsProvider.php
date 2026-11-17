<?php

namespace App\Contracts;

interface FamilyNewsProvider
{
    // Newest first. Demo media is an asset ID; production media is a protected URL.
    /** @return list<array{id: string, title: string, summary: string, category: string, published_at: string, author: string, media: string|null, is_demo: bool}> */
    public function publishedFor(string $residentId, bool $photoConsent): array;
}
