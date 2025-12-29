<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class CachePageResponse
{
    public function handle(Request $request, Closure $next)
    {
        if ($request->method() !== 'GET') {
            return $next($request);
        }
        
        $cacheKey = 'page-response-' . md5($request->fullUrl());
        
        return Cache::remember($cacheKey, 3600, function () use ($request, $next) {
            return $next($request);
        });
    }
}