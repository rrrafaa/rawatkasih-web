<?php

use App\Http\Controllers\CareTasks\CareTaskOccurrenceController;
use App\Http\Controllers\CareTasks\TemplateController;
use Illuminate\Support\Facades\Route;

// Orang 1: Perawatan & Manajemen Tugas
Route::prefix('care-tasks')->name('care-tasks.')->group(function () {
    Route::get('/templates', [TemplateController::class, 'index'])
        ->middleware('role:admin')
        ->name('templates.index');
    Route::post('/templates', [TemplateController::class, 'store'])
        ->middleware('role:admin')
        ->name('templates.store');
    Route::patch('/templates/{templateId}', [TemplateController::class, 'update'])
        ->middleware('role:admin')
        ->name('templates.update');
    Route::patch('/templates/{templateId}/status', [TemplateController::class, 'setStatus'])
        ->middleware('role:admin')
        ->name('templates.status');
    Route::patch('/occurrences/{occurrenceId}/status', [
        CareTaskOccurrenceController::class,
        'updateStatus',
    ])
        ->middleware('role:caregiver')
        ->name('occurrences.status');
});
