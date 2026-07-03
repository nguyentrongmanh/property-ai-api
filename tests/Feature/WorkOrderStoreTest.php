<?php

namespace Tests\Feature;

use App\Models\Building;
use App\Services\AI\Contracts\AIServiceInterface;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\Support\FakeAIService;
use Tests\TestCase;

class WorkOrderStoreTest extends TestCase
{
    use LazilyRefreshDatabase;

    private function fakeAIService(?FakeAIService $fake = null): FakeAIService
    {
        $fake ??= new FakeAIService;

        $this->app->instance(AIServiceInterface::class, $fake);

        return $fake;
    }

    /**
     * @return array{property_id: string, email: string, description: string}
     */
    private function validPayload(Building $building): array
    {
        return [
            'property_id' => $building->id,
            'email' => 'tenant@gmail.com',
            'description' => 'the elevator in the lobby keeps stopping and makes a grinding noise',
        ];
    }

    public function test_creates_a_work_order_from_plain_language(): void
    {
        $this->fakeAIService();
        $building = Building::factory()->create(['id' => 'P-001']);

        $response = $this->postJson('/api/work-orders', $this->validPayload($building));

        $response->assertCreated()->assertJson([
            'data' => [
                'id' => 'WO-1001',
                'property_id' => 'P-001',
                'source_text' => 'the elevator in the lobby keeps stopping and makes a grinding noise',
                'requester_email' => 'tenant@gmail.com',
                'title' => 'Lobby elevator stopping and making noise',
                'category' => 'elevator',
                'priority' => 'high',
                'summary' => 'Lobby elevator is stopping between floors and producing a grinding noise.',
                'status' => 'open',
                'created_at' => now()->toDateString(),
            ],
        ]);

        $this->assertDatabaseHas('work_orders', [
            'id' => 'WO-1001',
            'property_id' => 'P-001',
            'status' => 'open',
        ]);
    }

    public function test_rejects_unknown_building_without_calling_the_ai(): void
    {
        $fake = $this->fakeAIService();

        $response = $this->postJson('/api/work-orders', [
            'property_id' => 'P-999',
            'email' => 'tenant@gmail.com',
            'description' => 'the heating is broken in unit 4',
        ]);

        $response->assertUnprocessable()->assertJsonValidationErrors('property_id');
        $this->assertSame(0, $fake->workOrderCalls);
    }

    public function test_rejects_implausible_email_without_calling_the_ai(): void
    {
        $fake = $this->fakeAIService();
        $building = Building::factory()->create();

        $response = $this->postJson('/api/work-orders', [
            ...$this->validPayload($building),
            'email' => 'not-an-email',
        ]);

        $response->assertUnprocessable()->assertJsonValidationErrors('email');
        $this->assertSame(0, $fake->workOrderCalls);
    }

    public function test_rejects_too_short_description(): void
    {
        $this->fakeAIService();
        $building = Building::factory()->create();

        $response = $this->postJson('/api/work-orders', [
            ...$this->validPayload($building),
            'description' => 'help',
        ]);

        $response->assertUnprocessable()->assertJsonValidationErrors('description');
    }

    public function test_saves_nothing_when_the_ai_fails(): void
    {
        $this->fakeAIService(FakeAIService::failingWorkOrder());
        $building = Building::factory()->create();

        $response = $this->postJson('/api/work-orders', $this->validPayload($building));

        $response->assertStatus(502)->assertExactJson([
            'message' => 'We could not process the maintenance request right now. Please try again shortly.',
        ]);
        $this->assertDatabaseCount('work_orders', 0);
    }

    public function test_maps_ai_rate_limit_to_429_with_retry_after(): void
    {
        $this->fakeAIService(FakeAIService::rateLimitedWorkOrder());
        $building = Building::factory()->create();

        $response = $this->postJson('/api/work-orders', $this->validPayload($building));

        $response->assertStatus(429)->assertHeader('Retry-After', 60);
        $this->assertDatabaseCount('work_orders', 0);
    }

    public function test_throttles_rapid_requests(): void
    {
        $this->fakeAIService();
        $building = Building::factory()->create();

        foreach (range(1, 10) as $i) {
            $this->postJson('/api/work-orders', $this->validPayload($building))->assertCreated();
        }

        $this->postJson('/api/work-orders', $this->validPayload($building))->assertStatus(429);
    }
}
