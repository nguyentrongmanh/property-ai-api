<?php

namespace Tests\Feature;

use Illuminate\Support\Str;
use Tests\TestCase;

class CorrelationIdMiddlewareTest extends TestCase
{
    public function test_generates_correlation_id_when_missing(): void
    {
        $response = $this->get('/up');

        $response->assertStatus(200);
        $response->assertHeader('x-correlation-id');

        $this->assertTrue(Str::isUuid((string) $response->headers->get('x-correlation-id')));
    }

    public function test_keeps_incoming_correlation_id(): void
    {
        $incomingCorrelationId = 'test-correlation-id-123';

        $response = $this->withHeaders([
            'x-correlation-id' => $incomingCorrelationId,
        ])->get('/up');

        $response->assertStatus(200);
        $response->assertHeader('x-correlation-id', $incomingCorrelationId);
    }
}
