<?php

namespace Tests\Unit\Repositories;

use App\Models\Building;
use App\Models\WorkOrder;
use App\Repositories\EloquentWorkOrderRepository;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class EloquentWorkOrderRepositoryTest extends TestCase
{
    use LazilyRefreshDatabase;

    private EloquentWorkOrderRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = new EloquentWorkOrderRepository;
    }

    public function test_filter_orders_by_urgency_then_recency(): void
    {
        $building = Building::factory()->create();

        WorkOrder::factory()->for($building, 'building')->create([
            'id' => 'WO-1001', 'priority' => 'medium', 'created_at' => now(),
        ]);
        WorkOrder::factory()->for($building, 'building')->create([
            'id' => 'WO-1002', 'priority' => 'urgent', 'created_at' => now()->subDay(),
        ]);
        WorkOrder::factory()->for($building, 'building')->create([
            'id' => 'WO-1003', 'priority' => 'medium', 'created_at' => now()->subHour(),
        ]);
        WorkOrder::factory()->for($building, 'building')->create([
            'id' => 'WO-1004', 'priority' => 'low', 'created_at' => now(),
        ]);

        $result = $this->repository->filter([], 15);

        $this->assertSame(['WO-1002', 'WO-1001', 'WO-1003', 'WO-1004'], $result->pluck('id')->all());
    }

    public function test_filter_applies_all_filters_together(): void
    {
        [$building, $other] = Building::factory()->count(2)->create();

        $match = WorkOrder::factory()->for($building, 'building')->create([
            'status' => 'open', 'priority' => 'high', 'category' => 'elevator',
        ]);
        WorkOrder::factory()->for($other, 'building')->create([
            'status' => 'open', 'priority' => 'high', 'category' => 'elevator',
        ]);
        WorkOrder::factory()->for($building, 'building')->completed()->create([
            'priority' => 'high', 'category' => 'elevator',
        ]);
        WorkOrder::factory()->for($building, 'building')->create([
            'status' => 'open', 'priority' => 'low', 'category' => 'elevator',
        ]);
        WorkOrder::factory()->for($building, 'building')->create([
            'status' => 'open', 'priority' => 'high', 'category' => 'plumbing',
        ]);

        $result = $this->repository->filter([
            'property_id' => $building->id,
            'status' => 'open',
            'priority' => 'high',
            'category' => 'elevator',
        ], 15);

        $this->assertSame([$match->id], $result->pluck('id')->all());
    }

    public function test_filter_paginates(): void
    {
        WorkOrder::factory()->count(5)->create();

        $result = $this->repository->filter([], 2);

        $this->assertCount(2, $result->items());
        $this->assertSame(5, $result->total());
    }

    public function test_detail_returns_the_work_order(): void
    {
        $workOrder = WorkOrder::factory()->create();

        $this->assertTrue($workOrder->is($this->repository->detail($workOrder->id)));
    }

    public function test_detail_throws_for_unknown_id(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $this->repository->detail('WO-9999');
    }

    public function test_create_persists_and_generates_prefixed_id(): void
    {
        $building = Building::factory()->create();

        $workOrder = $this->repository->create([
            'property_id' => $building->id,
            'source_text' => 'the elevator keeps stopping',
            'requester_email' => 'tenant@example.com',
            'title' => 'Elevator stopping',
            'category' => 'elevator',
            'priority' => 'high',
            'summary' => 'Elevator stops between floors.',
        ]);

        $this->assertSame('WO-1001', $workOrder->id);
        $this->assertSame('open', $workOrder->status->value);
        $this->assertDatabaseHas('work_orders', ['id' => 'WO-1001', 'property_id' => $building->id]);
    }

    public function test_edit_updates_and_returns_the_work_order(): void
    {
        $workOrder = WorkOrder::factory()->create();

        $updated = $this->repository->edit($workOrder->id, ['status' => 'completed']);

        $this->assertSame('completed', $updated->status->value);
        $this->assertDatabaseHas('work_orders', ['id' => $workOrder->id, 'status' => 'completed']);
    }

    public function test_delete_removes_the_work_order(): void
    {
        $workOrder = WorkOrder::factory()->create();

        $this->repository->delete($workOrder->id);

        $this->assertDatabaseMissing('work_orders', ['id' => $workOrder->id]);
    }
}
