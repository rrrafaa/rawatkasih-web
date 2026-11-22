<?php

namespace App\Services\FamilyVisits;

final class VisitHours
{
    /** @return list<string> */
    public static function slots(): array
    {
        $slots = [];
        for ($minute = 8 * 60; $minute <= 19 * 60; $minute += 15) {
            $slots[] = sprintf('%02d:%02d', intdiv($minute, 60), $minute % 60);
        }

        return $slots;
    }
}
