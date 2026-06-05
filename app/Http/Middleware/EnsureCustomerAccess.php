<?php

namespace App\Http\Middleware;

use App\Support\SubscriptionAccessSync;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class EnsureCustomerAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            return redirect()->route('login');
        }

        $isVerifiedAdmin = $user->role === 'admin'
            && $user->email_verified_at !== null
            && Str::lower($user->email) === Str::lower((string) config('hm.admin_email'));

        if ($isVerifiedAdmin) {
            return redirect()->route('admin.dashboard');
        }

        abort_unless($user->role === 'customer', 403);

        if ($user->status === 'Suspended') {
            Auth::logout();

            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()
                ->route('login')
                ->withErrors(['email' => 'This account is suspended. Please contact admin.']);
        }

        app(SubscriptionAccessSync::class)->syncExpired();

        return $next($request);
    }
}
