<?php

use App\Exceptions\WorkOrderClassificationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        //
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*'),
        );

        $exceptions->render(function (WorkOrderClassificationException $e, Request $request) {
            if ($e->isRateLimited()) {
                return response()->json([
                    'message' => 'The AI service is receiving too many requests. Please try again in a minute.',
                ], Response::HTTP_TOO_MANY_REQUESTS, ['Retry-After' => 60]);
            }

            // Bad gateway: the request was fine, the upstream AI dependency failed.
            return response()->json([
                'message' => 'We could not process the maintenance request right now. Please try again shortly.',
            ], Response::HTTP_BAD_GATEWAY);
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

            return response()->json(['message' => $message], Response::HTTP_NOT_FOUND);
        });
    })->create();
