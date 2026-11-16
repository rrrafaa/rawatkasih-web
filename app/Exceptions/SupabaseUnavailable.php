<?php

namespace App\Exceptions;

use RuntimeException;

class SupabaseUnavailable extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('Layanan belum dapat dihubungi. Silakan coba kembali.');
    }
}
