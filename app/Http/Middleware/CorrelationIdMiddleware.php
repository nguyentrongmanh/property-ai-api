<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Ramsey\Uuid\Uuid;
use Symfony\Component\HttpFoundation\Response;

class CorrelationIdMiddleware
{
    public const XCID = 'x-correlation-id';

    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $correlationId = $request->header(self::XCID);

        if (! $correlationId) {
            $correlationId = self::generateXCID();
            $request->headers->set(self::XCID, $correlationId);
        }

        $response = $next($request);
        $response->headers->set(self::XCID, $correlationId);

        return $response;
    }

    public static function generateXCID(): string
    {
        return Uuid::uuid4()->toString();
    }
}
