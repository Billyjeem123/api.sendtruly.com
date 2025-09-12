<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateMerchant
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next)
    {
        $apiKey = $request->bearerToken();
        $user = User::where('api_key', $apiKey)->first();
        if (!$user) {
            return response()->json([
                'message' => 'Unauthorized: Invalid API key',
            ], 401);
        }
        Auth::setUser($user);
        return $next($request);
    }
}
