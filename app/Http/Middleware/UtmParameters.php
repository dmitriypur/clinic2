<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Session;
use Symfony\Component\HttpFoundation\Response;

class UtmParameters
{
    private const COOKIE_NAME = 'zrenie_utm';
    private const COOKIE_LIFETIME_MINUTES = 90 * 24 * 60;
    private const KEYS = [
        'utm_source',
        'utm_medium',
        'utm_campaign',
        'utm_content',
        'utm_term',
    ];

    /**
     * Handle an incoming request.
     *
     * @param Closure(Request): (Response) $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $queryParameters = $this->queryParameters($request);
        $storedParameters = $this->storedParameters($request);
        $parameters = $queryParameters !== [] ? $queryParameters : $storedParameters;

        if ($parameters !== []) {
            Session::put($parameters);
            Session::forget(array_values(array_diff(self::KEYS, array_keys($parameters))));
        }

        $response = $next($request);

        if ($parameters !== []) {
            Cookie::queue(cookie(
                self::COOKIE_NAME,
                json_encode($parameters, JSON_UNESCAPED_UNICODE),
                self::COOKIE_LIFETIME_MINUTES,
                null,
                null,
                $request->isSecure(),
                true,
                false,
                'Lax'
            ));
        }

        return $response;
    }

    private function queryParameters(Request $request): array
    {
        $parameters = [];

        foreach (self::KEYS as $key) {
            if (! $request->query->has($key)) {
                continue;
            }

            $value = $this->normalizeValue($request->query($key));

            if ($value !== null) {
                $parameters[$key] = $value;
            }
        }

        return $parameters;
    }

    private function storedParameters(Request $request): array
    {
        $payload = json_decode((string) $request->cookie(self::COOKIE_NAME, ''), true);

        if (! is_array($payload)) {
            return [];
        }

        $parameters = [];

        foreach (self::KEYS as $key) {
            $value = $this->normalizeValue($payload[$key] ?? null);

            if ($value !== null) {
                $parameters[$key] = $value;
            }
        }

        return $parameters;
    }

    private function normalizeValue(mixed $value): ?string
    {
        if (! is_scalar($value)) {
            return null;
        }

        $value = trim((string) $value);

        if ($value === '') {
            return null;
        }

        return mb_substr($value, 0, 255);
    }
}
