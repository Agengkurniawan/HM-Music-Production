<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password as PasswordRule;
use Illuminate\View\View;

class PasswordResetController extends Controller
{
    private const RESET_LINK_SENT_MESSAGE = 'Jika email tersebut terdaftar sebagai customer, link verifikasi reset password sudah kami kirim. Silakan cek inbox atau folder spam.';

    public function showLinkRequestForm(): RedirectResponse
    {
        return redirect()
            ->route('login')
            ->with('password_reset_modal', true);
    }

    public function sendResetLinkEmail(Request $request): RedirectResponse
    {
        $validated = $request->validateWithBag('passwordReset', [
            'email' => ['required', 'email'],
        ]);

        $status = Password::sendResetLink([
            'email' => $validated['email'],
            'role' => 'customer',
        ]);

        if ($status === Password::RESET_THROTTLED) {
            return back()
                ->withErrors(['email' => 'Tunggu sebentar sebelum meminta link reset password lagi.'], 'passwordReset')
                ->with('password_reset_modal', true)
                ->onlyInput('email');
        }

        return back()
            ->with('success', self::RESET_LINK_SENT_MESSAGE)
            ->onlyInput('email');
    }

    public function showResetForm(Request $request, string $token): View
    {
        return view('auth.reset-password', [
            'email' => $request->query('email'),
            'token' => $token,
        ]);
    }

    public function reset(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'token' => ['required', 'string'],
            'email' => ['required', 'email'],
            'password' => ['required', 'confirmed', PasswordRule::min(8)->letters()->numbers()],
        ], [
            'password.letters' => 'Password harus berisi minimal satu huruf.',
            'password.numbers' => 'Password harus berisi minimal satu angka.',
        ]);

        $status = Password::reset([
            'email' => $validated['email'],
            'role' => 'customer',
            'token' => $validated['token'],
            'password' => $validated['password'],
            'password_confirmation' => $request->input('password_confirmation'),
        ], function (User $user, string $password): void {
            $user->forceFill([
                'password' => Hash::make($password),
                'remember_token' => Str::random(60),
                'email_verified_at' => $user->email_verified_at ?: now(),
                'last_activity' => 'Password reset by customer verification link',
            ])->save();

            event(new PasswordReset($user));
        });

        if ($status !== Password::PASSWORD_RESET) {
            return back()
                ->withErrors(['email' => 'Link reset password tidak valid atau sudah kedaluwarsa. Minta link baru untuk melanjutkan.'])
                ->withInput($request->only('email'));
        }

        return redirect()
            ->route('login')
            ->with('success', 'Password berhasil diganti. Silakan login memakai password baru.');
    }
}
