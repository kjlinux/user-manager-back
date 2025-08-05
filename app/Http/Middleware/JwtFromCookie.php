<?php

namespace App\Http\Middleware;

use Closure;
use Exception;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Facades\JWTAuth;
use Symfony\Component\HttpFoundation\Response;

class JwtFromCookie
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next)
    {
        try {
            $token = $request->cookie('auth_token');

            if (!$token) {
                return response()->json([
                    'status' => 'error',
                    'code' => 401,
                    'message' => 'Token non fourni'
                ], 401);
            }

            JWTAuth::setToken($token);

            $user = JWTAuth::parseToken()->authenticate();

            if (!$user) {
                return response()->json([
                    'status' => 'error',
                    'code' => 401,
                    'message' => 'Utilisateur non autorisé'
                ], 401);
            }
        } catch (Exception $e) {
            return response()->json([
                'status' => 'error',
                'code' => 401,
                'message' => $e->getMessage()
            ], 401);
        }

        return $next($request);
    }
}
