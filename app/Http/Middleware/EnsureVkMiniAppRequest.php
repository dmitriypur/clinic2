<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureVkMiniAppRequest
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->has(['vk_platform', 'vk_app_id', 'sign'])) {
            abort(404);
        }

        return $next($request);
    }
}

