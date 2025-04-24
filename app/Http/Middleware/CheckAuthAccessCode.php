<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckAuthAccessCode
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $accessCode = $request->header('auth-access-code');

        // Check if the header is present and valid
        if (!$accessCode || $accessCode !== config('app.access_code')) {
            return response()->json([
                'message' => 'Unauthorized access. Invalid or missing access code.',
            ], 401);
        }

        return $next($request);
    }
}
