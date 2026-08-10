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

        $adminEmails = array_map(
            fn ($email) => Str::lower($email),
            config('hm.admin_emails', [])
        );

        $isVerifiedAdmin = $user->role === 'admin'
            && $user->email_verified_at !== null
            && in_array(Str::lower($user->email), $adminEmails, true);

        abort_unless($isVerifiedAdmin, 403);

        app(SubscriptionAccessSync::class)->syncExpired();

        return $next($request);
    }
}