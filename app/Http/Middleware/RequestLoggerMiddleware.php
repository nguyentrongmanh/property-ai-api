<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class RequestLoggerMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $startedAt = microtime(true);
        $correlationId = (string) $request->header(CorrelationIdMiddleware::XCID, '');

        Log::info('http.request.started', [
            'correlation_id' => $correlationId,
            'method' => $request->method(),
            'path' => '/'.$request->path(),
            'ip' => $request->ip(),
        ]);

        $response = $next($request);

        Log::info('http.request.finished', [
            'correlation_id' => $correlationId,
            'method' => $request->method(),
            'path' => '/'.$request->path(),
            'status_code' => $response->getStatusCode(),
            'duration_ms' => (int) round((microtime(true) - $startedAt) * 1000),
        ]);

        return $response;
    }
}
