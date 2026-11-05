<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\TrainingAssignmentController;
use App\Http\Controllers\TrainingController;
use Illuminate\Support\Facades\Route;

Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
Route::post('/login', [AuthenticatedSessionController::class, 'store'])->middleware('throttle:5,1');

Route::middleware('auth')->group(function (): void {
    Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');
    Route::get('/', DashboardController::class)->name('dashboard');
    Route::resource('trainings', TrainingController::class)->only(['index', 'create', 'store', 'show']);
    Route::post('/trainings/{training}/assignments', [TrainingAssignmentController::class, 'store'])
        ->name('trainings.assignments.store');
    Route::patch('/assignments/{assignment}/complete', [TrainingAssignmentController::class, 'complete'])
        ->name('assignments.complete');
});
