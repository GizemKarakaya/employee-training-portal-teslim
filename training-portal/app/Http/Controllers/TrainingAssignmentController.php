<?php

namespace App\Http\Controllers;

use App\Exceptions\PersonnelApiException;
use App\Models\Training;
use App\Models\TrainingAssignment;
use App\Services\PersonnelApiService;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class TrainingAssignmentController extends Controller
{
    public function store(Request $request, Training $training, PersonnelApiService $personnelApi): RedirectResponse
    {
        $validated = $request->validate([
            'personnel_id' => ['required', 'integer', 'min:1'],
        ], [
            'personnel_id.required' => 'Atama yapmak için personel seçin.',
            'personnel_id.integer' => 'Geçerli bir personel seçin.',
            'personnel_id.min' => 'Geçerli bir personel seçin.',
        ]);
        $personnelId = (int) $validated['personnel_id'];

        if ($training->assignments()->where('personnel_id', $personnelId)->exists()) {
            return back()->withErrors(['personnel_id' => 'Bu eğitim seçilen personele daha önce atanmış.'])->withInput();
        }

        try {
            $employee = $personnelApi->employee($personnelId);
        } catch (PersonnelApiException $exception) {
            return back()->withErrors(['personnel_id' => $exception->getMessage()])->withInput();
        }

        if ($employee === null) {
            return back()->withErrors(['personnel_id' => 'Seçilen personel Personel Servisi içinde bulunamadı.'])->withInput();
        }

        try {
            TrainingAssignment::create([
                'training_id' => $training->id,
                'personnel_id' => $personnelId,
                'status' => 'pending',
                'assigned_at' => now(),
            ]);
        } catch (QueryException $exception) {
            if (($exception->errorInfo[1] ?? null) === 19 || $exception->getCode() === '23000') {
                return back()->withErrors(['personnel_id' => 'Bu eğitim seçilen personele daha önce atanmış.'])->withInput();
            }

            throw $exception;
        }

        return to_route('trainings.show', $training)->with('success', $employee['first_name'].' '.$employee['last_name'].' için eğitim atandı.');
    }

    public function complete(TrainingAssignment $assignment): RedirectResponse
    {
        if ($assignment->status !== 'completed') {
            $assignment->update([
                'status' => 'completed',
                'completed_at' => now(),
            ]);
        }

        return to_route('trainings.show', $assignment->training_id)->with('success', 'Eğitim tamamlandı olarak işaretlendi.');
    }
}
