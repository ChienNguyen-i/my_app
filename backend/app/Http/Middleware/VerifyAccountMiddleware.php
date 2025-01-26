<?php

namespace App\Http\Middleware;

use App\Services\ResponsesService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class VerifyAccountMiddleware
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
        if (Auth::user()->email_verified_at === null) {
            return $this->responsesService->error(400, __('messages.verify_email_before_continue'));
        }

        return $next($request);
    }
}
