<?php

namespace App\Http\Middleware;

use App\Jobs\SendSourceToUnf;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class InterceptSource
{
    /**
     * Handle an incoming request.
     *
     * @param Closure(Request): (Response) $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->has('source') && $request->has('place')) {
            SendSourceToUnf::dispatch([
                'source' => $request->get('source'),
                'place' => $request->get('place')
            ]);

//            return redirect($request->fullUrlWithoutQuery(['source', 'place']));
        }

        return $next($request);
    }
}
