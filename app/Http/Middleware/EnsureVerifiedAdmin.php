<?php

namespace App\Http\Middleware;

use App\Support\SubscriptionAccessSync;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class EnsureVerifiedAdmin
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

        abort_unless($isVerifiedAdmin, 403);

        app(SubscriptionAccessSync::class)->syncExpired();

        return $next($request);
    }
}
