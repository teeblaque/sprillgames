<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RedirectIfAuthenticated
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // $guards = empty($guards) ? [null] : $guards;

        // foreach ($guards as $guard) {

        //     if (Auth::guard($guard)->check()) {

        //         return redirect(RouteServiceProvider::HOME);

        //     }

        // }

        if (Auth::guard('web')->check()) {

            return redirect()->route('/auth/sign-in');

        }

        return $next($request);
    }
}
