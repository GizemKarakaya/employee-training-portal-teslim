<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\TrainingAssignment;
use Illuminate\Http\JsonResponse;

class TrainingAssignmentApiController extends Controller
{
    public function show(TrainingAssignment $assignment): JsonResponse
    {
        return response()->json([
            'data' => [
                'id' => $assignment->id,
                'training_id' => $assignment->training_id,
                'personnel_id' => $assignment->personnel_id,
                'status' => $assignment->status,
                'assigned_at' => $assignment->assigned_at->toISOString(),
                'completed_at' => $assignment->completed_at?->toISOString(),
            ],
        ]);
    }
}
