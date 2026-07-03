<?php

namespace Tests\Feature;

use App\Models\Building;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class PropertyStatsTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_returns_totals_and_average_occupancy_per_city(): void
    {
        Building::factory()->create(['city' => 'Amsterdam', 'occupancy_rate' => 0.80]);
        Building::factory()->create(['city' => 'Amsterdam', 'occupancy_rate' => 0.60]);
        Building::factory()->create(['city' => 'Rotterdam', 'occupancy_rate' => null]);
        Building::factory()->create(['city' => null, 'occupancy_rate' => 0.90]);

        $response = $this->getJson('/api/properties/stats');

        $response->assertOk()->assertExactJson([
            'data' => [
                [
                    'city' => 'Amsterdam',
                    'total_properties' => 2,
                    'average_occupancy_rate' => 0.7,
                ],
                [
                    'city' => 'Rotterdam',
                    'total_properties' => 1,
                    'average_occupancy_rate' => null,
                ],
            ],
        ]);
    }
}
