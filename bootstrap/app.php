<?php

use App\Exceptions\AiServiceException;
use App\Http\Middleware\CorrelationIdMiddleware;
use App\Http\Middleware\RequestLoggerMiddleware;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->append(CorrelationIdMiddleware::class);
        $middleware->append(RequestLoggerMiddleware::class);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*'),
        );

        $exceptions->render(function (AiServiceException $e, Request $request) {
            if ($e->isRateLimited()) {
                return response()->json([
                    'message' => 'The AI service is receiving too many requests. Please try again in a minute.',
                    'status_code' => Response::HTTP_TOO_MANY_REQUESTS,
                ], Response::HTTP_TOO_MANY_REQUESTS, ['Retry-After' => 60]);
            }

            // Bad gateway: the request was fine, the upstream AI dependency failed.
            return response()->json([
                'message' => 'We could not process the maintenance request right now. Please try again shortly.',
                'status_code' => Response::HTTP_BAD_GATEWAY,
            ], Response::HTTP_BAD_GATEWAY);
        });

        $exceptions->render(function (TooManyRequestsHttpException $e, Request $request) {
            if (! $request->is('api/*')) {
                return null;
            }

            return response()->json([
                'message' => 'Too many requests.',
                'status_code' => Response::HTTP_TOO_MANY_REQUESTS,
            ], Response::HTTP_TOO_MANY_REQUESTS, ['Retry-After' => $e->getHeaders()['Retry-After'] ?? 60]);
        });

        $exceptions->render(function (NotFoundHttpException $e, Request $request) {
            if (! $request->is('api/*')) {
                return null;
            }

            $previous = $e->getPrevious();

            $message = $previous instanceof ModelNotFoundException
                ? sprintf(
                    '%s %s was not found.',
                    class_basename($previous->getModel()),
                    implode(', ', $previous->getIds()),
                )
                : 'Resource not found.';

            return response()->json([
                'message' => $message,
                'status_code' => Response::HTTP_NOT_FOUND,
            ], Response::HTTP_NOT_FOUND);
        });

        $exceptions->render(function (Throwable $e, Request $request) {
            if (! $request->is('api/*')) {
                return null;
            }

            // Keep Laravel's default JSON rendering for expected client errors.
            if ($e instanceof ValidationException) {
                return null;
            }

            Log::error('http.request.failed', [
                'correlation_id' => $request->header(CorrelationIdMiddleware::XCID),
                'method' => $request->method(),
                'path' => '/'.$request->path(),
                'exception' => $e::class,
                'reason' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => 'Internal server error.',
                'status_code' => Response::HTTP_INTERNAL_SERVER_ERROR,
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        });
    })->create();
