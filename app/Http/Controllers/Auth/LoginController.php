<?php

namespace App\Http\Controllers\Auth;

use App\Exceptions\InvalidCredentials;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Services\SupabaseSession;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class LoginController extends Controller
{
    public function create(): View
    {
        return view('auth.login');
    }

    public function store(LoginRequest $request, SupabaseSession $session): RedirectResponse
    {
        $data = $request->validated();
        $email = Str::lower(trim($data['email']));
        try {
            $user = $session->login($request, $email, $data['password']);
        } catch (InvalidCredentials) {
            throw ValidationException::withMessages(['email' => 'Tidak dapat masuk. Periksa kredensial atau hubungi admin.']);
        }

        // Arahkan setiap peran ke dasbornya sendiri; abaikan URL tujuan lain.
        return redirect()->route($user->role()->value.'.dashboard');
    }

    public function destroy(Request $request, SupabaseSession $session): RedirectResponse
    {
        $session->logout($request);

        return redirect()->route('login')->with('status', 'Anda telah keluar.');
    }
}
