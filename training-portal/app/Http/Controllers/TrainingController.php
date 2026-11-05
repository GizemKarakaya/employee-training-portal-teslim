<?php

namespace App\Http\Controllers;

use App\Exceptions\PersonnelApiException;
use App\Models\Training;
use App\Services\PersonnelApiService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TrainingController extends Controller
{
    public function index(): View
    {
        return view('trainings.index', [
            'trainings' => Training::query()->withCount('assignments')->latest()->get(),
        ]);
    }

    public function create(): View
    {
        return view('trainings.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:160'],
            'description' => ['required', 'string', 'max:5000'],
        ], [
            'title.required' => 'Eğitim adı zorunludur.',
            'title.string' => 'Eğitim adı metin biçiminde olmalıdır.',
            'title.max' => 'Eğitim adı en fazla 160 karakter olabilir.',
            'description.required' => 'Açıklama alanı zorunludur.',
            'description.string' => 'Açıklama metin biçiminde olmalıdır.',
            'description.max' => 'Açıklama en fazla 5000 karakter olabilir.',
        ]);

        $training = Training::create($validated);

        return to_route('trainings.show', $training)->with('success', 'Eğitim başarıyla oluşturuldu.');
    }

    public function show(Training $training, PersonnelApiService $personnelApi): View
    {
        $training->load(['assignments' => fn ($query) => $query->orderByDesc('assigned_at')]);
        $employees = [];
        $personnelError = null;

        try {
            $employees = $personnelApi->employees();
        } catch (PersonnelApiException $exception) {
            $personnelError = $exception->getMessage();
        }

        return view('trainings.show', compact('training', 'employees', 'personnelError'));
    }
}
