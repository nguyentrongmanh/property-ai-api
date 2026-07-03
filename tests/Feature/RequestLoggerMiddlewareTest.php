<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class RequestLoggerMiddlewareTest extends TestCase
{
    public function test_logs_before_and_after_request_with_correlation_id(): void
    {
        Log::spy();

        $incomingCorrelationId = 'cid-test-001';

        $response = $this->withHeaders([
            'x-correlation-id' => $incomingCorrelationId,
        ])->get('/up');

        $response->assertOk();

        Log::shouldHaveReceived('info')
            ->withArgs(function (string $message, array $context) use ($incomingCorrelationId): bool {
                return $message === 'http.request.started'
                    && ($context['correlation_id'] ?? null) === $incomingCorrelationId;
            })
            ->once();

        Log::shouldHaveReceived('info')
            ->withArgs(function (string $message, array $context) use ($incomingCorrelationId): bool {
                return $message === 'http.request.finished'
                    && ($context['correlation_id'] ?? null) === $incomingCorrelationId
                    && ($context['status_code'] ?? null) === 200;
            })
            ->once();
    }
}
