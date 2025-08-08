<?php

namespace App\Http\Middleware;

use Closure;
use Exception;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Exceptions\TokenExpiredException;
use Tymon\JWTAuth\Exceptions\TokenInvalidException;
use Tymon\JWTAuth\Exceptions\JWTException;

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
                return $this->unauthorizedResponse('Token non fourni');
            }

            JWTAuth::setToken($token);
            $user = JWTAuth::authenticate();

            if (!$user) {
                return $this->unauthorizedResponse('Utilisateur non trouvé');
            }

            if (!$user->status) {
                return $this->unauthorizedResponse('Compte désactivé');
            }
        } catch (TokenExpiredException $e) {
            return $this->unauthorizedResponse('Token expiré', true);
        } catch (TokenInvalidException $e) {
            return $this->unauthorizedResponse('Token invalide', true);
        } catch (JWTException $e) {
            return $this->unauthorizedResponse('Erreur JWT', true);
        }

        return $next($request);
    }

    private function unauthorizedResponse($message, $clearCookie = false)
    {
        $response = response()->json([
            'status' => 'error',
            'code' => 401,
            'message' => $message,
            'requires_login' => true
        ], 401);

        if ($clearCookie) {
            $response = $response->cookie(
                'auth_token',
                '',
                -1,
                '/',
                null,
                true,
                true,
                false,
                'None'
            );
        }

        return $response->header('Access-Control-Allow-Credentials', 'true')
            ->header('Access-Control-Allow-Origin', 'http://localhost:5173');
    }
}
