<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RedirectFormSubdomainRequests
{
    public function handle(Request $request, Closure $next): Response
    {
        $mainHost = parse_url(config('app.url'), PHP_URL_HOST);

        if (
            $request->getHost() === "form.{$mainHost}"
            && !in_array($request->getPathInfo(), ['/', '/robots.txt'], true)
        ) {
            return redirect()->away("https://{$mainHost}{$request->getRequestUri()}", 301);
        }

        return $next($request);
    }
}
