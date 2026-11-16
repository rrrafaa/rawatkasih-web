<?php

namespace App\Enums;

enum Role: string
{
    case Admin = 'admin';
    case Caregiver = 'caregiver';
    case Family = 'family';

    public function label(): string
    {
        return match ($this) {
            self::Admin => 'Admin',
            self::Caregiver => 'Caregiver',
            self::Family => 'Keluarga',
        };
    }
}
