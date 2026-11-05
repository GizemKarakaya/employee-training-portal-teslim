<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    public function create(): View|RedirectResponse
    {
        if (Auth::check()) {
            return to_route('employees.index');
        }

        return view('auth.login');
    }

    public function store(Request $request): RedirectResponse
    {
        $validator = Validator::make($request->all(), [
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ], [
            'email.required' => 'E-posta alanı zorunludur.',
            'email.email' => 'Geçerli bir e-posta adresi girin.',
            'password.required' => 'Parola alanı zorunludur.',
        ]);

        if ($validator->fails()) {
            return to_route('login')->withErrors($validator)->withInput($request->only('email'));
        }

        if (! Auth::attempt($validator->validated())) {
            return to_route('login')
                ->withErrors(['email' => 'E-posta veya parola hatalı.'])
                ->withInput($request->only('email'));
        }

        $request->session()->regenerate();

        return to_route('employees.index');
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return to_route('login');
    }
}
