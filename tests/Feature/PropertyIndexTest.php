<?php

namespace Tests\Feature;

use App\Models\Building;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class PropertyIndexTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_lists_buildings_fullest_first(): void
    {
        Building::factory()->create(['id' => 'P-001', 'occupancy_rate' => 0.50]);
        Building::factory()->create(['id' => 'P-002', 'occupancy_rate' => 0.95]);
        Building::factory()->create(['id' => 'P-003', 'occupancy_rate' => 0.70]);

        $response = $this->getJson('/api/properties');

        $response->assertOk();
        $this->assertSame(['P-002', 'P-003', 'P-001'], array_column($response->json('data'), 'id'));
    }

    public function test_buildings_without_occupancy_rate_come_last(): void
    {
        Building::factory()->incomplete()->create(['id' => 'P-001']);
        Building::factory()->create(['id' => 'P-002', 'occupancy_rate' => 0.10]);

        $response = $this->getJson('/api/properties');

        $response->assertOk();
        $this->assertSame(['P-002', 'P-001'], array_column($response->json('data'), 'id'));
    }

    public function test_filters_by_city(): void
    {
        Building::factory()->create(['city' => 'Amsterdam']);
        Building::factory()->create(['city' => 'Rotterdam']);

        $response = $this->getJson('/api/properties?city=Amsterdam');

        $response->assertOk()->assertJsonCount(1, 'data');
        $this->assertSame('Amsterdam', $response->json('data.0.city'));
    }

    public function test_filters_by_type_and_status(): void
    {
        Building::factory()->create(['type' => 'office', 'status' => 'active']);
        Building::factory()->create(['type' => 'office', 'status' => 'inactive']);
        Building::factory()->create(['type' => 'retail', 'status' => 'active']);

        $response = $this->getJson('/api/properties?type=office&status=active');

        $response->assertOk()->assertJsonCount(1, 'data');
        $this->assertSame('office', $response->json('data.0.type'));
        $this->assertSame('active', $response->json('data.0.status'));
    }

    public function test_filters_by_minimum_occupancy(): void
    {
        Building::factory()->create(['id' => 'P-001', 'occupancy_rate' => 0.90]);
        Building::factory()->create(['id' => 'P-002', 'occupancy_rate' => 0.40]);
        Building::factory()->incomplete()->create(['id' => 'P-003']);

        $response = $this->getJson('/api/properties?min_occupancy=0.5');

        $response->assertOk()->assertJsonCount(1, 'data');
        $this->assertSame('P-001', $response->json('data.0.id'));
    }

    public function test_says_so_clearly_when_nothing_matches(): void
    {
        Building::factory()->create(['city' => 'Amsterdam']);

        $response = $this->getJson('/api/properties?city=Paris');

        $response->assertOk()->assertExactJson([
            'message' => 'No properties matched the given filters.',
            'data' => [],
        ]);
    }

    public function test_rejects_filter_values_outside_the_enums(): void
    {
        $this->getJson('/api/properties?type=castle')->assertUnprocessable()
            ->assertJsonValidationErrors('type');

        $this->getJson('/api/properties?status=demolished')->assertUnprocessable()
            ->assertJsonValidationErrors('status');

        $this->getJson('/api/properties?min_occupancy=2')->assertUnprocessable()
            ->assertJsonValidationErrors('min_occupancy');
    }

    public function test_paginates_results(): void
    {
        Building::factory()->count(7)->create();

        $response = $this->getJson('/api/properties?per_page=3&page=2');

        $response->assertOk()
            ->assertJsonCount(3, 'data')
            ->assertJsonPath('meta.current_page', 2)
            ->assertJsonPath('meta.per_page', 3)
            ->assertJsonPath('meta.total', 7);
    }

    public function test_rejects_out_of_range_per_page(): void
    {
        $this->getJson('/api/properties?per_page=500')->assertUnprocessable()
            ->assertJsonValidationErrors('per_page');
    }
}
