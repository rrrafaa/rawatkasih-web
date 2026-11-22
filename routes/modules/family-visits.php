<?php

use App\Http\Controllers\FamilyVisits\AdminVisitController;
use App\Http\Controllers\FamilyVisits\FamilyPortalController;
use App\Http\Controllers\FamilyVisits\FamilyVisitController;
use Illuminate\Support\Facades\Route;

// PAWL02/04: named prefix groups + resource actions + implicit model binding.
// This module is included inside the shared auth group.
Route::prefix('family')->name('family.')->middleware('role:family')->group(function () {
    Route::get('/', [FamilyPortalController::class, 'index'])->name('dashboard')->block(10, 10);
    Route::get('kabar', [FamilyPortalController::class, 'news'])->name('news');
    Route::get('profil', [FamilyPortalController::class, 'profile'])->name('profile');
    Route::patch('profil', [FamilyPortalController::class, 'updateProfile'])->name('profile.update');
    Route::get('contoh-media/{asset}', [FamilyPortalController::class, 'media'])->whereIn('asset', ['garden', 'lunch', 'morning', 'sulastri'])->name('demo-media');
    Route::resource('kunjungan', FamilyVisitController::class)->only(['index', 'create', 'store', 'show'])
        ->parameters(['kunjungan' => 'visit'])->names('visits');
    Route::patch('kunjungan/{visit}/batalkan', [FamilyVisitController::class, 'cancel'])->whereUuid('visit')->name('visits.cancel')->block(10, 10);
});

Route::prefix('admin')->name('admin.')->middleware('role:admin')->group(function () {
    Route::resource('kunjungan', AdminVisitController::class)->only(['index', 'show', 'update'])
        ->parameters(['kunjungan' => 'visit'])->names('visits');
});
