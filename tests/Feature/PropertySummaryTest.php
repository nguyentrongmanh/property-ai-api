<?php

namespace Tests\Feature;

use App\Models\Building;
use App\Services\AI\Contracts\AIServiceInterface;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\Support\FakeAIService;
use Tests\TestCase;

class PropertySummaryTest extends TestCase
{
    use LazilyRefreshDatabase;

    private function fakeAIService(?FakeAIService $fake = null): FakeAIService
    {
        $fake ??= new FakeAIService;

        $this->app->instance(AIServiceInterface::class, $fake);

        return $fake;
    }

    public function test_returns_an_ai_written_summary(): void
    {
        $this->fakeAIService();
        Building::factory()->create(['id' => 'P-001']);

        $response = $this->getJson('/api/properties/P-001/summary');

        $response->assertOk()->assertExactJson([
            'data' => [
                'property_id' => 'P-001',
                'summary' => 'A well occupied office building with two open work orders, the most urgent being the lobby elevator.',
            ],
        ]);
    }

    public function test_returns_clear_not_found_without_calling_the_ai(): void
    {
        $fake = $this->fakeAIService();

        $response = $this->getJson('/api/properties/P-999/summary');

        $response->assertNotFound()->assertExactJson([
            'message' => 'Building P-999 was not found.',
            'status_code' => 404,
        ]);
        $this->assertSame(0, $fake->buildingSummaryCalls);
    }

    public function test_maps_ai_failure_to_502(): void
    {
        $this->fakeAIService(FakeAIService::failingBuildingSummary());
        Building::factory()->create(['id' => 'P-001']);

        $response = $this->getJson('/api/properties/P-001/summary');

        $response->assertStatus(502);
    }
}
