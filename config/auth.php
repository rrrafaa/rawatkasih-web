<?php

use App\Models\SupabaseUser;
use App\Models\User;

return [

    // Pengaturan autentikasi bawaan, termasuk guard dan broker pemulihan kata sandi.

    'defaults' => [
        'guard' => env('AUTH_GUARD', 'web'),
        'passwords' => env('AUTH_PASSWORD_BROKER', 'users'),
    ],

    // Guard autentikasi menentukan mekanisme pemeriksaan identitas pengguna.

    'guards' => [
        'web' => [
            'driver' => 'supabase',
            'provider' => 'users',
        ],
    ],

    // Provider menentukan sumber data pengguna yang digunakan guard.

    'providers' => [
        'users' => [
            'driver' => 'eloquent',
            'model' => SupabaseUser::class,
        ],

        // 'users' => [
        //     'driver' => 'database',
        //     'table' => 'users',
        // ],
    ],

    // Broker mengelola token, masa berlaku, dan jeda permintaan pemulihan kata sandi.

    'passwords' => [
        'users' => [
            'provider' => 'users',
            'table' => env('AUTH_PASSWORD_RESET_TOKEN_TABLE', 'password_reset_tokens'),
            'expire' => 60,
            'throttle' => 60,
        ],
    ],

    // Batas waktu konfirmasi ulang kata sandi dalam satuan detik.

    'password_timeout' => env('AUTH_PASSWORD_TIMEOUT', 10800),

];
