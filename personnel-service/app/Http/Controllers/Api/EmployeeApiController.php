<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use Illuminate\Http\JsonResponse;

class EmployeeApiController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json([
            'data' => Employee::query()
                ->orderBy('last_name')
                ->orderBy('first_name')
                ->get(['id', 'first_name', 'last_name', 'email', 'department']),
        ]);
    }

    public function show(Employee $employee): JsonResponse
    {
        return response()->json([
            'data' => $employee->only([
                'id',
                'first_name',
                'last_name',
                'email',
                'department',
            ]),
        ]);
    }
}
