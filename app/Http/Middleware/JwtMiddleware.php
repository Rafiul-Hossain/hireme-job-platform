<?php

namespace App\Http\Middleware;

use Closure;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Http\Middleware\BaseMiddleware;
use Tymon\JWTAuth\Exceptions\TokenExpiredException;
use Tymon\JWTAuth\Exceptions\TokenInvalidException;
use Tymon\JWTAuth\Exceptions\JWTException;
use Illuminate\Support\Facades\Log;

class JwtMiddleware extends BaseMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        try {
            $user = JWTAuth::parseToken()->authenticate();
            
            // Log successful authentication
            Log::info('User authenticated', [
                'user_id' => $user->id,
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent()
            ]);
            
            return $next($request);
            
        } catch (TokenExpiredException $e) {
            try {
                // Try to refresh the token
                $newToken = JWTAuth::refresh(JWTAuth::getToken());
                
                // Log the token refresh
                Log::info('Token refreshed', [
                    'user_id' => JWTAuth::getPayload()->get('sub'),
                    'ip' => $request->ip()
                ]);
                
                // Set the new token in the response header
                $response = $next($request);
                return $this->setAuthenticationHeader($response, $newToken);
                
            } catch (JWTException $refreshException) {
                // Log the refresh failure
                Log::warning('Token refresh failed', [
                    'error' => $refreshException->getMessage(),
                    'ip' => $request->ip(),
                    'user_agent' => $request->userAgent()
                ]);
                
                return response()->json([
                    'status' => 'error',
                    'message' => 'Token has expired and could not be refreshed',
                    'code' => 'token_expired'
                ], 401);
            }
            
        } catch (TokenInvalidException $e) {
            Log::warning('Invalid token provided', [
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'token' => $request->bearerToken()
            ]);
            
            return response()->json([
                'status' => 'error',
                'message' => 'Token is invalid',
                'code' => 'token_invalid'
            ], 401);
            
        } catch (JWTException $e) {
            Log::warning('Token not provided or malformed', [
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'status' => 'error',
                'message' => 'Authorization token not found',
                'code' => 'token_absent'
            ], 401);
            
        } catch (\Exception $e) {
            // Log any other unexpected errors
            Log::error('Authentication error', [
                'error' => $e->getMessage(),
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'status' => 'error',
                'message' => 'Could not authenticate user',
                'code' => 'authentication_error'
            ], 500);
        }
    }
}
