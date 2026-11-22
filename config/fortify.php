<?php

use Laravel\Fortify\Features;

return [

    // Guard yang digunakan Fortify untuk autentikasi.

    'guard' => 'web',

    // Broker yang digunakan Fortify untuk mengatur ulang kata sandi.

    'passwords' => 'users',

    // Kolom email digunakan sebagai identitas pengguna dan pemulihan kata sandi.

    'username' => 'email',

    'email' => 'email',

    // Ubah identitas pengguna menjadi huruf kecil sebelum disimpan.

    'lowercase_usernames' => true,

    // Alamat tujuan setelah autentikasi berhasil.

    'home' => '/dashboard',

    // Awalan rute dan subdomain yang digunakan Fortify.

    'prefix' => '',

    'domain' => null,

    // Middleware yang diterapkan pada rute Fortify.

    'middleware' => ['web'],

    // Batasi frekuensi percobaan login agar tidak disalahgunakan.

    'limiters' => [
        'login' => 'login',
    ],

    // Aktifkan rute tampilan yang disediakan Fortify.

    'views' => true,

    // Daftar fitur Fortify yang diaktifkan.

    'features' => [
        // Public registration is deliberately disabled.

    ],

];
