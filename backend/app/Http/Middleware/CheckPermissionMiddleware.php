<?php

namespace App\Http\Middleware;

use App\Services\ResponsesService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class CheckPermissionMiddleware
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
    public function handle(Request $request, Closure $next, $permission): Response
    {
        if (Auth::user()->roles()->whereHas('permissions', function ($query) use ($permission) {
            $query->where('key_code', $permission);
        })->exists()) {
            return $next($request);
        }
        return $this->responsesService->error(403, __('message.forbidden'));
    }
}
