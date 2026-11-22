
<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\PasswordController;
use App\Http\Controllers\CareTasks\CaregiverDashboardController;
use App\Http\Controllers\DashboardController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/login')->name('home');

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

    Route::get('/dashboard', [DashboardController::class, 'redirect'])
        ->name('dashboard')
        ->block(10, 10);

    // Shared dashboards for admin and family.
    foreach (['admin', 'family'] as $role) {
        Route::prefix($role)
            ->name($role.'.')
            ->middleware('role:'.$role)
            ->group(function () {
                Route::get('/', [DashboardController::class, 'show'])
                    ->name('dashboard')
                    ->block(10, 10);
            });
    }

    // Dedicated dashboard for caregiver.
    Route::prefix('caregiver')
        ->name('caregiver.')
        ->middleware('role:caregiver')
        ->group(function () {
            Route::get('/', [CaregiverDashboardController::class, 'index'])
                ->name('dashboard')
                ->block(10, 10);
        });

    // Each module owns a separate route file,
    // keeping shared auth routes stable.
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
