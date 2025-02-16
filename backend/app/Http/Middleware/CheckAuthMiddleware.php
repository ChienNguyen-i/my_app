<?php

namespace App\Http\Middleware;

use App\Services\ResponsesService;
use Closure;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class CheckAuthMiddleware
{
    protected $responsesService;

    public function __construct(ResponsesService $responsesService)
    {
        $this->responsesService = $responsesService;
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::check()) {
            return $next($request);
        }
        $token = $request->cookie('token');
        if (!$token) {
            return $this->responsesService->error(401, __('message.token_not_found'));
        }
        try {
            $user = Auth::setToken($token)->authenticate();
            if (!$user) {
                return $this->responsesService->error(401, __('message.invalid_expired_token'));
            }
            $request->merge(['user' => $user]);
        } catch (Exception $e) {
            return $this->responsesService->error(401, __('message.invalid_token'), $e->getMessage());
        }
        return $next($request);
    }
}
