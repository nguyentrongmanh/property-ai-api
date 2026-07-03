<?php

namespace Tests\Unit\Repositories;

use App\Models\Building;
use App\Models\WorkOrder;
use App\Repositories\EloquentBuildingRepository;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class EloquentBuildingRepositoryTest extends TestCase
{
    use LazilyRefreshDatabase;

    private EloquentBuildingRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = new EloquentBuildingRepository;
    }

    public function test_filter_returns_buildings_fullest_first(): void
    {
        Building::factory()->create(['id' => 'P-001', 'occupancy_rate' => 0.40]);
        Building::factory()->create(['id' => 'P-002', 'occupancy_rate' => 0.90]);
        Building::factory()->incomplete()->create(['id' => 'P-003']);

        $result = $this->repository->filter([], 15);

        $this->assertSame(['P-002', 'P-001', 'P-003'], $result->pluck('id')->all());
    }

    public function test_filter_applies_all_filters_together(): void
    {
        $match = Building::factory()->create([
            'city' => 'Utrecht', 'type' => 'office', 'status' => 'active', 'occupancy_rate' => 0.80,
        ]);
        Building::factory()->create(['city' => 'Utrecht', 'type' => 'office', 'status' => 'active', 'occupancy_rate' => 0.30]);
        Building::factory()->create(['city' => 'Utrecht', 'type' => 'retail', 'status' => 'active', 'occupancy_rate' => 0.80]);
        Building::factory()->inactive()->create(['city' => 'Utrecht', 'type' => 'office', 'occupancy_rate' => 0.80]);
        Building::factory()->create(['city' => 'Breda', 'type' => 'office', 'status' => 'active', 'occupancy_rate' => 0.80]);

        $result = $this->repository->filter([
            'city' => 'Utrecht',
            'type' => 'office',
            'status' => 'active',
            'min_occupancy' => 0.5,
        ], 15);

        $this->assertSame([$match->id], $result->pluck('id')->all());
    }

    public function test_filter_paginates(): void
    {
        Building::factory()->count(5)->create();

        $result = $this->repository->filter([], 2);

        $this->assertCount(2, $result->items());
        $this->assertSame(5, $result->total());
        $this->assertSame(3, $result->lastPage());
    }

    public function test_stats_by_city_returns_totals_and_average_occupancy(): void
    {
        Building::factory()->create(['city' => 'Amsterdam', 'occupancy_rate' => 0.80]);
        Building::factory()->create(['city' => 'Amsterdam', 'occupancy_rate' => 0.60]);
        Building::factory()->create(['city' => 'Rotterdam', 'occupancy_rate' => null]);
        Building::factory()->create(['city' => null, 'occupancy_rate' => 0.90]);

        $result = $this->repository->statsByCity();

        $this->assertSame([
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
        ], $result);
    }

    public function test_detail_includes_open_work_order_count(): void
    {
        $building = Building::factory()->create();
        WorkOrder::factory()->count(2)->for($building, 'building')->create();
        WorkOrder::factory()->completed()->for($building, 'building')->create();

        $found = $this->repository->detail($building->id);

        $this->assertSame(2, $found->open_work_orders_count);
    }

    public function test_detail_throws_for_unknown_id(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $this->repository->detail('P-999');
    }

    public function test_create_persists_and_generates_prefixed_id(): void
    {
        $building = $this->repository->create([
            'name' => 'Keizersgracht 128',
            'city' => 'Amsterdam',
        ]);

        $this->assertSame('P-001', $building->id);
        $this->assertDatabaseHas('buildings', ['id' => 'P-001', 'name' => 'Keizersgracht 128']);
    }

    public function test_edit_updates_and_returns_the_building(): void
    {
        $building = Building::factory()->create(['name' => 'Old name']);

        $updated = $this->repository->edit($building->id, ['name' => 'New name']);

        $this->assertSame('New name', $updated->name);
        $this->assertDatabaseHas('buildings', ['id' => $building->id, 'name' => 'New name']);
    }

    public function test_delete_removes_the_building(): void
    {
        $building = Building::factory()->create();

        $this->repository->delete($building->id);

        $this->assertDatabaseMissing('buildings', ['id' => $building->id]);
    }

    public function test_edit_and_delete_throw_for_unknown_id(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $this->repository->edit('P-999', ['name' => 'Nope']);
    }
}
