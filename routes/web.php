
<?php

use App\Http\Controllers\AdminDashboardController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\PasswordController;
use App\Http\Controllers\CareTasks\CaregiverDashboardController;
use App\Http\Controllers\DashboardController;
use App\Models\SupabaseUser;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/login')->name('home');

Route::get('/images/rawatkasih-mark.svg', fn () => response()->file(resource_path('asset/rawatkasih-mark.svg'), [
    'Content-Type' => 'image/svg+xml',
    'Cache-Control' => 'public, max-age=3600',
]));

Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'create'])
        ->name('login');

    Route::post('/login', [LoginController::class, 'store'])
        ->middleware('throttle:login')
        ->name('login.store')
        ->block(10, 10);
});

Route::post('/logout', [LoginController::class, 'destroy'])
    ->name('logout')
    ->block(10, 10);

Route::get('/forgot-password', [PasswordController::class, 'request'])
    ->name('password.request');

Route::post('/forgot-password', [PasswordController::class, 'email'])
    ->middleware('throttle:password-recovery')
    ->name('password.email');

Route::get('/reset-password', [PasswordController::class, 'edit'])
    ->name('password.reset');

Route::post('/reset-password', [PasswordController::class, 'update'])
    ->middleware('throttle:5,1')
    ->name('password.update')
    ->block(10, 10);

Route::middleware('auth')->group(function () {

    // Arahkan pengguna ke dasbor sesuai perannya.
    Route::get('/dashboard', [DashboardController::class, 'redirect'])
        ->name('dashboard')
        ->block(10, 10);

    // Dasbor admin dengan statistik khusus.
    Route::prefix('admin')
        ->name('admin.')
        ->middleware('role:admin')
        ->group(function () {
            Route::get('/', [AdminDashboardController::class, 'index'])
                ->name('dashboard')
                ->block(10, 10);
        });

    // Dasbor keluarga untuk memantau penghuni.
    Route::prefix('family')
        ->name('family.')
        ->middleware('role:family')
        ->group(function () {
            Route::get('/', [DashboardController::class, 'show'])
                ->name('dashboard')
                ->block(10, 10);
        });

    // Dasbor khusus pendamping.
    Route::prefix('caregiver')
        ->name('caregiver.')
        ->middleware('role:caregiver')
        ->group(function () {
            Route::get('/', [CaregiverDashboardController::class, 'index'])
                ->name('dashboard')
                ->block(10, 10);
        });

    // Muat rute setiap modul secara terpisah.
    foreach ([
        'care-tasks',
        'care-updates',
        'family-visits',
        'incidents',
        'operations',
    ] as $module) {
        require __DIR__.'/modules/'.$module.'.php';
    }
});

// Pratinjau catatan harian tanpa autentikasi Supabase.
Route::get('/preview/daily-notes', function () {
    abort_unless(app()->environment('local'), 404);

    $dummyUser = new SupabaseUser([
        'id' => '10000000-0000-4000-8000-000000000001',
        'full_name' => 'Dewi Caregiver (Demo)',
        'role' => 'caregiver',
    ]);

    auth()->setUser($dummyUser);

    $dummyResidents = [
        [
            'id' => '30000000-0000-4000-8000-000000000001',
            'full_name' => 'Eyang Hartono Soedirdjo',
            'room' => 'Melati 01',
            'photo_consent' => true,
        ],
        [
            'id' => '30000000-0000-4000-8000-000000000002',
            'full_name' => 'Ibu Sulastri',
            'room' => 'Mawar 02',
            'photo_consent' => false,
        ],
    ];

    $dummyNotes = [
        [
            'id' => '1',
            'resident_id' => '30000000-0000-4000-8000-000000000001',
            'resident_name' => 'Eyang Hartono Soedirdjo',
            'resident_room' => 'Melati 01',
            'caregiver_name' => 'Dewi Caregiver',
            'mood' => 'Ceria',
            'meal_note' => 'Habis satu porsi bubur ayam & teh hangat',
            'internal_note' => 'Tidur siang 2 jam nyenyak, tensi darah stabil 120/80, tidak ada keluhan pusing.',
            'recorded_at' => now()->toIso8601String(),
        ],
    ];

    return view('modules.care-updates.daily-notes.index', [
        'user' => $dummyUser,
        'date' => now('Asia/Jakarta')->toDateString(),
        'residents' => $dummyResidents,
        'selectedResidentId' => null,
        'notes' => $dummyNotes,
        'page' => 1,
        'hasMore' => false,
    ]);
});

// Pratinjau kabar keluarga tanpa autentikasi Supabase.
Route::get('/preview/family-updates', function () {
    abort_unless(app()->environment('local'), 404);

    $dummyUser = new SupabaseUser([
        'id' => '10000000-0000-4000-8000-000000000001',
        'full_name' => 'Dewi Caregiver (Demo)',
        'role' => 'caregiver',
    ]);

    auth()->setUser($dummyUser);

    $dummyResidents = [
        [
            'id' => '30000000-0000-4000-8000-000000000001',
            'full_name' => 'Eyang Hartono Soedirdjo',
            'room' => 'Melati 01',
            'photo_consent' => true,
        ],
        [
            'id' => '30000000-0000-4000-8000-000000000002',
            'full_name' => 'Ibu Sulastri',
            'room' => 'Mawar 02',
            'photo_consent' => false,
        ],
    ];

    $dummyUpdates = [
        [
            'id' => '1',
            'resident_id' => '30000000-0000-4000-8000-000000000001',
            'resident_name' => 'Eyang Hartono Soedirdjo',
            'resident_room' => 'Melati 01',
            'author_name' => 'Dewi Caregiver',
            'summary' => 'Pagi ini Eyang sangat bersemangat mengikuti senam lansia di taman bersama teman-teman.',
            'status' => 'published',
            'published_at' => now()->toIso8601String(),
            'created_at' => now()->toIso8601String(),
            'photo_url' => null,
        ],
        [
            'id' => '2',
            'resident_id' => '30000000-0000-4000-8000-000000000002',
            'resident_name' => 'Ibu Sulastri',
            'resident_room' => 'Mawar 02',
            'author_name' => 'Dewi Caregiver',
            'summary' => 'Sedang istirahat dan merajut di ruang tengah (draft).',
            'status' => 'draft',
            'published_at' => null,
            'created_at' => now()->toIso8601String(),
            'photo_url' => null,
        ],
    ];

    return view('modules.care-updates.family-updates.index', [
        'user' => $dummyUser,
        'date' => now('Asia/Jakarta')->toDateString(),
        'residents' => $dummyResidents,
        'selectedResidentId' => null,
        'statusFilter' => null,
        'updates' => $dummyUpdates,
        'page' => 1,
        'hasMore' => false,
    ]);
});
