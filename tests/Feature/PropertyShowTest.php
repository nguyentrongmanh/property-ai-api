<?php

namespace Tests\Feature;

use App\Models\Building;
use App\Models\WorkOrder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class PropertyShowTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_returns_full_building_details(): void
    {
        $building = Building::factory()->create([
            'id' => 'P-001',
            'name' => 'Keizersgracht 128',
            'type' => 'office',
            'city' => 'Amsterdam',
            'units' => 14,
            'occupancy_rate' => 0.86,
            'amenities' => ['elevator', 'parking'],
        ]);

        $response = $this->getJson('/api/properties/P-001');

        $response->assertOk()->assertJson([
            'data' => [
                'id' => 'P-001',
                'name' => 'Keizersgracht 128',
                'type' => 'office',
                'status' => 'active',
                'city' => 'Amsterdam',
                'units' => 14,
                'occupancy_rate' => 0.86,
                'amenities' => ['elevator', 'parking'],
                'open_work_orders' => 0,
            ],
        ]);
    }

    public function test_counts_only_open_work_orders(): void
    {
        $building = Building::factory()->create();

        WorkOrder::factory()->count(2)->for($building, 'building')->create();
        WorkOrder::factory()->inProgress()->for($building, 'building')->create();
        WorkOrder::factory()->completed()->for($building, 'building')->create();

        $response = $this->getJson("/api/properties/{$building->id}");

        $response->assertOk()->assertJsonPath('data.open_work_orders', 2);
    }

    public function test_handles_buildings_with_incomplete_data(): void
    {
        $building = Building::factory()->incomplete()->create();

        $response = $this->getJson("/api/properties/{$building->id}");

        $response->assertOk()
            ->assertJsonPath('data.type', null)
            ->assertJsonPath('data.city', null)
            ->assertJsonPath('data.units', null)
            ->assertJsonPath('data.occupancy_rate', null)
            ->assertJsonPath('data.amenities', null);
    }

    public function test_returns_clear_not_found_response(): void
    {
        $response = $this->getJson('/api/properties/P-999');

        $response->assertNotFound()->assertExactJson([
            'message' => 'Building P-999 was not found.',
        ]);
    }
}
