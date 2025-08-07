<?php

namespace App\Http\Middleware;

use Closure;
use Exception;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Support\Facades\Log;
use Tymon\JWTAuth\Exceptions\TokenExpiredException;
use Tymon\JWTAuth\Exceptions\TokenInvalidException;
use Tymon\JWTAuth\Exceptions\JWTException;
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
                return $this->responder('Token non fourni');
            }

            JWTAuth::setToken($token);
            $user = JWTAuth::authenticate();

            if (!$user) {
                return $this->responder('Utilisateur non autorisé');
            }

            if (!$user->status) {
                return $this->responder('Compte désactivé');
            }
        } catch (TokenExpiredException $e) {
            Log::info('Token expiré pour l\'utilisateur');
            return $this->responder('Token expiré');
        } catch (TokenInvalidException $e) {
            Log::warning('Token invalide');
            return $this->responder('Token invalide');
        } catch (JWTException $e) {
            Log::error('Erreur JWT: ' . $e->getMessage());
            return $this->responder('Erreur de token');
        } catch (Exception $e) {
            Log::error('Erreur d\'authentification: ' . $e->getMessage());
            return $this->responder('Erreur d\'authentification');
        }

        return $next($request);
    }

    private function responder($message)
    {
        return response()->json([
            'status' => 'error',
            'code' => 401,
            'message' => $message
        ], 401);
    }
}
