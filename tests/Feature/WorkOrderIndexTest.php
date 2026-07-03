<?php

namespace Tests\Feature;

use App\Models\Building;
use App\Models\WorkOrder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class WorkOrderIndexTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_sorts_by_urgency_then_recency(): void
    {
        $building = Building::factory()->create();

        WorkOrder::factory()->for($building, 'building')->create([
            'id' => 'WO-1001',
            'priority' => 'low',
            'created_at' => now()->subDays(1),
        ]);
        WorkOrder::factory()->for($building, 'building')->create([
            'id' => 'WO-1002',
            'priority' => 'urgent',
            'created_at' => now()->subDays(3),
        ]);
        WorkOrder::factory()->for($building, 'building')->create([
            'id' => 'WO-1003',
            'priority' => 'urgent',
            'created_at' => now()->subDays(2),
        ]);
        WorkOrder::factory()->for($building, 'building')->create([
            'id' => 'WO-1004',
            'priority' => 'high',
            'created_at' => now(),
        ]);

        $response = $this->getJson('/api/work-orders');

        $response->assertOk();
        $this->assertSame(
            ['WO-1003', 'WO-1002', 'WO-1004', 'WO-1001'],
            array_column($response->json('data'), 'id'),
        );
    }

    public function test_filters_by_building(): void
    {
        [$first, $second] = Building::factory()->count(2)->create();

        WorkOrder::factory()->count(2)->for($first, 'building')->create();
        WorkOrder::factory()->for($second, 'building')->create();

        $response = $this->getJson("/api/work-orders?property_id={$first->id}");

        $response->assertOk()->assertJsonCount(2, 'data');
    }

    public function test_filters_by_status_priority_and_category(): void
    {
        $building = Building::factory()->create();

        WorkOrder::factory()->for($building, 'building')->create([
            'status' => 'open', 'priority' => 'high', 'category' => 'elevator',
        ]);
        WorkOrder::factory()->for($building, 'building')->completed()->create([
            'priority' => 'high', 'category' => 'elevator',
        ]);
        WorkOrder::factory()->for($building, 'building')->create([
            'status' => 'open', 'priority' => 'low', 'category' => 'plumbing',
        ]);

        $response = $this->getJson('/api/work-orders?status=open&priority=high&category=elevator');

        $response->assertOk()->assertJsonCount(1, 'data');
        $this->assertSame('open', $response->json('data.0.status'));
        $this->assertSame('high', $response->json('data.0.priority'));
        $this->assertSame('elevator', $response->json('data.0.category'));
    }

    public function test_says_so_clearly_when_nothing_matches(): void
    {
        $response = $this->getJson('/api/work-orders?status=cancelled');

        $response->assertOk()->assertExactJson([
            'message' => 'No work orders matched the given filters.',
            'data' => [],
        ]);
    }

    public function test_rejects_filter_values_outside_the_enums(): void
    {
        $this->getJson('/api/work-orders?priority=critical')->assertUnprocessable()
            ->assertJsonValidationErrors('priority');

        $this->getJson('/api/work-orders?status=paused')->assertUnprocessable()
            ->assertJsonValidationErrors('status');

        $this->getJson('/api/work-orders?category=gardening')->assertUnprocessable()
            ->assertJsonValidationErrors('category');
    }
}
