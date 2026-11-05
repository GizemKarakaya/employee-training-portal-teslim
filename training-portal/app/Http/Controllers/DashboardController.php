<?php

namespace App\Http\Controllers;

use App\Models\Training;
use App\Models\TrainingAssignment;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        return view('dashboard', [
            'trainingCount' => Training::query()->count(),
            'assignmentCount' => TrainingAssignment::query()->count(),
            'completedCount' => TrainingAssignment::query()->where('status', 'completed')->count(),
            'pendingCount' => TrainingAssignment::query()->where('status', 'pending')->count(),
            'recentTrainings' => Training::query()
                ->withCount('assignments')
                ->latest()
                ->limit(5)
                ->get(),
        ]);
    }
}
