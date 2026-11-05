<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class EmployeeController extends Controller
{
    public function index(Request $request): View
    {
        $department = $request->query('department');
        $employees = Employee::query()
            ->when($department, fn ($query) => $query->where('department', $department))
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->get();

        return view('employees.index', [
            'employees' => $employees,
            'departments' => Employee::query()->distinct()->orderBy('department')->pluck('department'),
            'selectedDepartment' => $department,
            'totalEmployees' => Employee::query()->count(),
        ]);
    }

    public function create(): View
    {
        return view('employees.create');
    }

    public function store(Request $request): RedirectResponse
    {
        Employee::create($this->validatedData($request));

        return to_route('employees.index')->with('success', 'Personel başarıyla eklendi.');
    }

    public function edit(Employee $employee): View
    {
        return view('employees.edit', compact('employee'));
    }

    public function update(Request $request, Employee $employee): RedirectResponse
    {
        $employee->update($this->validatedData($request, $employee));

        return to_route('employees.index')->with('success', 'Personel bilgileri güncellendi.');
    }

    public function destroy(Employee $employee): RedirectResponse
    {
        $employee->delete();

        return to_route('employees.index')->with('success', 'Personel kaydı silindi.');
    }

    private function validatedData(Request $request, ?Employee $employee = null): array
    {
        return $request->validate([
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('employees', 'email')->ignore($employee),
            ],
            'department' => ['required', 'string', 'max:120'],
        ], [
            'first_name.required' => 'Ad alanı zorunludur.',
            'first_name.string' => 'Ad metin biçiminde olmalıdır.',
            'first_name.max' => 'Ad en fazla 100 karakter olabilir.',
            'last_name.required' => 'Soyad alanı zorunludur.',
            'last_name.string' => 'Soyad metin biçiminde olmalıdır.',
            'last_name.max' => 'Soyad en fazla 100 karakter olabilir.',
            'email.required' => 'E-posta alanı zorunludur.',
            'email.email' => 'Geçerli bir e-posta adresi girin.',
            'email.max' => 'E-posta en fazla 255 karakter olabilir.',
            'email.unique' => 'Bu e-posta adresi zaten kayıtlı.',
            'department.required' => 'Departman alanı zorunludur.',
            'department.string' => 'Departman metin biçiminde olmalıdır.',
            'department.max' => 'Departman en fazla 120 karakter olabilir.',
        ]);
    }
}
