<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;

class JwtMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        try {
            \Log::info('JWT Middleware - Processing request:', [
                'method' => $request->method(),
                'path' => $request->path(),
                'has_token' => $request->hasHeader('Authorization'),
                'token_preview' => $request->hasHeader('Authorization') ? substr($request->header('Authorization'), 0, 50) . '...' : 'none'
            ]);
            
            $user = JWTAuth::parseToken()->authenticate();
            
            \Log::info('JWT Middleware - User authenticated:', ['user_id' => $user->id]);
            
        } catch (JWTException $e) {
            \Log::error('JWT Middleware - Token validation failed:', [
                'error' => $e->getMessage(),
                'path' => $request->path()
            ]);
            return response()->json(['error' => 'Token not valid'], 401);
        }

        return $next($request);
    }
}
