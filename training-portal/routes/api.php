<?php

use App\Http\Controllers\Api\TrainingAssignmentApiController;
use Illuminate\Support\Facades\Route;

Route::get('/assignments/{assignment}', [TrainingAssignmentApiController::class, 'show'])
    ->whereNumber('assignment')
    ->middleware(['web', 'auth']);
