<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class RequireCustomerLogin
{
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::guest()) {
            return redirect()
                ->guest(route('login'))
                ->with(
                    'auth_notice',
                    'Silakan masuk untuk melanjutkan. Fitur ini tersedia bagi customer HM Music yang telah memiliki akun.',
                );
        }

        return $next($request);
    }
}
