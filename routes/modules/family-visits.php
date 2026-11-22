<?php

use App\Http\Controllers\FamilyVisits\AdminVisitController;
use App\Http\Controllers\FamilyVisits\FamilyPortalController;
use App\Http\Controllers\FamilyVisits\FamilyMediaController;
use App\Http\Controllers\FamilyVisits\FamilyVisitController;
use Illuminate\Support\Facades\Route;

// PAWL02/04: named prefix groups + resource actions + implicit model binding.
// This module is included inside the shared auth group.
Route::prefix('family')->name('family.')->middleware('role:family')->group(function () {
    Route::get('/', [FamilyPortalController::class, 'index'])->name('dashboard')->block(10, 10);
    Route::get('kabar', [FamilyPortalController::class, 'news'])->name('news');
    Route::get('foto-profil', [FamilyPortalController::class, 'avatar'])->name('avatar');
    Route::post('foto-profil', [FamilyMediaController::class, 'uploadAvatar'])->name('avatar.upload');
    Route::get('resident/{resident}/foto', [FamilyMediaController::class, 'resident'])->whereUuid('resident')->name('resident-photo');
    Route::get('kabar/{update}/foto', [FamilyMediaController::class, 'update'])->whereUuid('update')->name('update-photo');
    Route::get('profil', [FamilyPortalController::class, 'profile'])->name('profile');
    Route::patch('profil', [FamilyPortalController::class, 'updateProfile'])->name('profile.update');
    Route::get('contoh-media/{asset}', [FamilyPortalController::class, 'media'])->whereIn('asset', ['garden', 'lunch', 'morning', 'sulastri'])->name('demo-media');
    Route::resource('kunjungan', FamilyVisitController::class)->only(['index', 'create', 'store', 'show'])
        ->parameters(['kunjungan' => 'visit'])->names('visits');
    Route::patch('kunjungan/{visit}/batalkan', [FamilyVisitController::class, 'cancel'])->whereUuid('visit')->name('visits.cancel')->block(10, 10);
});

Route::prefix('admin')->name('admin.')->middleware('role:admin')->group(function () {
    Route::get('foto-keluarga', [FamilyMediaController::class, 'admin'])->name('family-media');
    Route::post('foto-keluarga/resident', [FamilyMediaController::class, 'uploadResident'])->name('family-media.resident');
    Route::post('foto-keluarga/kabar', [FamilyMediaController::class, 'uploadUpdate'])->name('family-media.update');
    Route::get('kunjungan/{visit}/foto-pengaju', [FamilyPortalController::class, 'requesterAvatar'])->name('visits.avatar');
    Route::resource('kunjungan', AdminVisitController::class)->only(['index', 'show', 'update'])
        ->parameters(['kunjungan' => 'visit'])->names('visits');
});
