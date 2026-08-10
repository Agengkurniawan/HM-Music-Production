<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\View\View;

class AuthController extends Controller
{
    public function showLogin(): View|RedirectResponse
    {
        if (! Auth::check()) {
            return view('auth.login');
        }

        return $this->redirectAfterLogin(Auth::user());
    }

    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            return back()
                ->withErrors(['email' => 'The email or password is incorrect.'])
                ->onlyInput('email');
        }

        if ($this->isSuspendedCustomer(Auth::user())) {
            Auth::logout();

            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()
                ->route('login')
                ->withErrors(['email' => 'This account is suspended. Please contact admin.'])
                ->withInput($request->only('email'));
        }

        $request->session()->regenerate();

        return $this->redirectAfterLogin(Auth::user());
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }

    private function redirectAfterLogin(User $user): RedirectResponse
    {
        if ($this->isVerifiedAdmin($user)) {
            return redirect()->route('admin.dashboard');
        }

        if ($user->role === 'admin') {
            Auth::logout();

            return redirect()
                ->route('login')
                ->withErrors(['email' => 'This admin account is not verified for admin access.']);
        }

        return redirect()->route('dashboard');
    }

    private function isVerifiedAdmin(User $user): bool
    {
        $adminEmails = array_map(
            fn ($email) => Str::lower($email),
            config('hm.admin_emails', [])
        );

        return $user->role === 'admin'
            && $user->email_verified_at !== null
            && in_array(Str::lower($user->email), $adminEmails, true);
    }

    private function isSuspendedCustomer(?User $user): bool
    {
        return $user?->role === 'customer' && $user->status === 'Suspended';
    }
}