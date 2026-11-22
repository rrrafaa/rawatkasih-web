<?php

use App\Http\Controllers\CareUpdates\DailyNoteController;
use App\Http\Controllers\CareUpdates\FamilyUpdateController;
use Illuminate\Support\Facades\Route;

// Orang 2: Catatan Perawatan & Kabar Keluarga
// Included inside auth middleware group in routes/web.php.
Route::prefix('care-updates')->name('care-updates.')->group(function () {

    // Rute Caregiver
    Route::middleware('role:caregiver')->group(function () {
        // Catatan Harian Internal
        Route::get('/daily-notes', [DailyNoteController::class, 'index'])
            ->name('daily-notes.index');
        Route::post('/daily-notes', [DailyNoteController::class, 'store'])
            ->name('daily-notes.store');

        // Kabar Keluarga (Pengelolaan oleh Caregiver)
        Route::get('/family-updates', [FamilyUpdateController::class, 'index'])
            ->name('family-updates.index');
        Route::post('/family-updates', [FamilyUpdateController::class, 'store'])
            ->name('family-updates.store');
        Route::get('/family-updates/{updateId}/edit', [FamilyUpdateController::class, 'edit'])
            ->name('family-updates.edit')
            ->whereUuid('updateId');
        Route::patch('/family-updates/{updateId}', [FamilyUpdateController::class, 'update'])
            ->name('family-updates.update')
            ->whereUuid('updateId');
        Route::delete('/family-updates/{updateId}', [FamilyUpdateController::class, 'destroy'])
            ->name('family-updates.destroy')
            ->whereUuid('updateId');
    });

    // Rute Publikasi Foto Privat (Diautorisasi untuk Caregiver terkait, Admin, dan Keluarga berizin)
    Route::get('/family-updates/{updateId}/photo', [FamilyUpdateController::class, 'showPhoto'])
        ->name('family-updates.photo')
        ->whereUuid('updateId');
});
