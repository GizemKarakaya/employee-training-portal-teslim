<?php

use App\Http\Controllers\Api\EmployeeApiController;
use App\Http\Middleware\VerifyPersonnelApiToken;
use Illuminate\Support\Facades\Route;

Route::middleware(['throttle:120,1', VerifyPersonnelApiToken::class])->group(function (): void {
    Route::get('/employees', [EmployeeApiController::class, 'index']);
    Route::get('/employees/{employee}', [EmployeeApiController::class, 'show'])
        ->whereNumber('employee');
});
