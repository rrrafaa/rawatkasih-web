<?php

namespace App\Http\Controllers\Auth;

use App\Exceptions\InvalidCredentials;
use App\Exceptions\SupabaseUnavailable;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ForgotPasswordRequest;
use App\Http\Requests\Auth\ResetPasswordRequest;
use App\Services\SupabaseClient;
use App\Services\SupabaseSession;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class PasswordController extends Controller
{
    public function request(): View
    {
        return view('auth.forgot-password');
    }

    public function email(ForgotPasswordRequest $request, SupabaseClient $client): RedirectResponse
    {
        $data = $request->validated();
        try {
            $client->auth('POST', 'recover', ['email' => strtolower(trim($data['email']))]);
        } catch (InvalidCredentials) {
            // Same message whether or not the email exists.
        }

        return back()->with('status', 'Jika akun terdaftar, tautan pemulihan akan dikirim ke email Anda.');
    }

    public function edit(Request $request): View
    {
        $tokenHash = $request->query('token_hash', '');
        abort_unless(is_string($tokenHash) && strlen($tokenHash) <= 2048, 400);

        return view('auth.reset-password', compact('tokenHash'));
    }

    public function update(ResetPasswordRequest $request, SupabaseClient $client, SupabaseSession $session): RedirectResponse
    {
        $data = $request->validated();
        try {
            $recovery = $request->session()->get('supabase_recovery');
            if (! is_array($recovery) || ($recovery['hash'] ?? '') !== hash('sha256', $data['token_hash'])) {
                $tokens = $client->auth('POST', 'verify', ['token_hash' => $data['token_hash'], 'type' => 'recovery']);
                if (! is_string($tokens['access_token'] ?? null)) {
                    throw new InvalidCredentials;
                }
                $request->session()->regenerate();
                $recovery = ['hash' => hash('sha256', $data['token_hash']), 'token' => $tokens['access_token']];
                $request->session()->put('supabase_recovery', $recovery);
            }
            $client->auth('PUT', 'user', ['password' => $data['password']], $recovery['token']);
        } catch (InvalidCredentials) {
            $request->session()->forget('supabase_recovery');
            throw ValidationException::withMessages(['token_hash' => 'Tautan tidak valid, sudah digunakan, atau kedaluwarsa. Minta tautan baru.']);
        } catch (SupabaseUnavailable $exception) {
            // Keep the verified recovery session so a temporary network failure is retryable.
            throw $exception;
        }
        $session->revoke($recovery['token']);
        $session->logout($request);

        return redirect()->route('login')->with('status', 'Kata sandi diperbarui. Silakan masuk kembali.');
    }
}
