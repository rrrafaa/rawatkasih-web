<?php

namespace App\Enums;

enum IncidentStatus: string
{
    case Open = 'open';
    case Handling = 'handling';
    case Closed = 'closed';

    public function label(): string
    {
        return match ($this) {
            self::Open => 'Terbuka',
            self::Handling => 'Sedang ditindaklanjuti',
            self::Closed => 'Ditutup',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Open => 'bg-amber-50 text-amber-700',
            self::Handling => 'bg-blue-50 text-blue-700',
            self::Closed => 'bg-emerald-50 text-emerald-700',
        };
    }
}
