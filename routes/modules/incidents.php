<?php

use App\Http\Controllers\Incidents\IncidentController;
use Illuminate\Support\Facades\Route;

// Ringkasan:
// - Admin: CRUD semua insiden
// - Caregiver: laporkan, lihat untuk lansia dalam penugasan

// Caregiver routes - pelaporan & lihat sendiri (diletakkan di /caregiver/incidents)
Route::prefix('caregiver/incidents')->name('incidents.')->group(function () {
    Route::middleware('role:caregiver')->group(function () {
        Route::get('/', [IncidentController::class, 'index'])->name('index');
        Route::get('/create', [IncidentController::class, 'create'])->name('create');
        Route::post('/', [IncidentController::class, 'store'])->name('store');
        Route::get('/{incident}', [IncidentController::class, 'show'])->name('show');
    });
});

// Admin routes (diletakkan di /admin/incidents)
// Kita pakai prefix terpisah untuk menghindari konflik nama route
Route::prefix('admin/incidents')->name('admin.incidents.')->group(function () {
    Route::middleware('role:admin')->group(function () {
        Route::get('/', [IncidentController::class, 'adminIndex'])->name('index');
        Route::get('/{incident}', [IncidentController::class, 'showAdmin'])->name('show');
        Route::patch('/{incident}', [IncidentController::class, 'update'])->name('update');
        Route::get('/{incident}/audit-log', [IncidentController::class, 'auditLog'])->name('audit-log');
    });
});