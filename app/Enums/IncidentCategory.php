<?php

namespace App\Enums;

enum IncidentCategory: string
{
    case Medical = 'medical';
    case Security = 'security';
    case Maintenance = 'maintenance';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Medical => 'Medis',
            self::Security => 'Keamanan',
            self::Maintenance => 'Perawatan',
            self::Other => 'Lainnya',
        };
    }
}