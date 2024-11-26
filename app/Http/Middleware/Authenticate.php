<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class Authenticate
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {

        if (!Auth::check()) {
            return redirect('/auth/sign-in');
        }

        return $next($request);
    }

    protected function redirectTo($request): ?string
    {
        return $request->expectsJson() ? null : redirect('/auth/sign-in');
    }


    //  Determine if the user is logged in with User guards.

//     protected function authenticate($request, array $guards)

//     {

//         if ($this->auth->guard('user')->check()) {

//             return $this->auth->shouldUse('user');  

//         }

//         $this->unauthenticated($request, ['user']);

//     }
}
