<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureServicesIntegrationToken
{
    public function handle(Request $request, Closure $next): Response
    {
        $configuredToken = (string) config('services-integration.token');

        if ($configuredToken === '') {
            return response()->json([
                'message' => 'Токен интеграции услуг не настроен.',
            ], 503);
        }

        $providedToken = $request->bearerToken() ?: $request->header('X-Services-Integration-Token');

        if (! is_string($providedToken) || $providedToken === '' || ! hash_equals($configuredToken, $providedToken)) {
            return response()->json([
                'message' => 'Доступ запрещён.',
            ], 401);
        }

        $allowedIps = config('services-integration.allowed_ips', []);

        if ($allowedIps !== [] && ! in_array($request->ip(), $allowedIps, true)) {
            return response()->json([
                'message' => 'Доступ с этого IP-адреса запрещён.',
            ], 403);
        }

        return $next($request);
    }
}
