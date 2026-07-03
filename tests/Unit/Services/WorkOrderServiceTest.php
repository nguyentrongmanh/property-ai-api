<?php

namespace Tests\Unit\Services;

use App\Enums\WorkOrderCategory;
use App\Enums\WorkOrderPriority;
use App\Exceptions\WorkOrderClassificationException;
use App\Models\WorkOrder;
use App\Repositories\Contracts\WorkOrderRepositoryInterface;
use App\Services\WorkOrderService;
use Illuminate\Pagination\LengthAwarePaginator;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Mockery\MockInterface;
use PHPUnit\Framework\TestCase;
use Tests\Support\FakeWorkOrderClassifier;

class WorkOrderServiceTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    private WorkOrderRepositoryInterface&MockInterface $repository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = Mockery::mock(WorkOrderRepositoryInterface::class);
    }

    public function test_create_classifies_the_description_and_persists_the_result(): void
    {
        $classifier = new FakeWorkOrderClassifier([
            'title' => 'Lobby elevator stopping',
            'category' => 'elevator',
            'priority' => 'high',
            'summary' => 'Elevator stops between floors.',
        ]);
        $workOrder = new WorkOrder;

        $this->repository->shouldReceive('create')
            ->once()
            ->with([
                'property_id' => 'P-001',
                'requester_email' => 'tenant@example.com',
                'source_text' => 'the elevator keeps stopping',
                'title' => 'Lobby elevator stopping',
                'category' => WorkOrderCategory::Elevator,
                'priority' => WorkOrderPriority::High,
                'summary' => 'Elevator stops between floors.',
            ])
            ->andReturn($workOrder);

        $service = new WorkOrderService($this->repository, $classifier);

        $result = $service->create([
            'property_id' => 'P-001',
            'email' => 'tenant@example.com',
            'description' => 'the elevator keeps stopping',
        ]);

        $this->assertSame($workOrder, $result);
        $this->assertSame(1, $classifier->calls);
    }

    public function test_create_saves_nothing_when_classification_fails(): void
    {
        $this->repository->shouldNotReceive('create');

        $service = new WorkOrderService($this->repository, FakeWorkOrderClassifier::failing());

        $this->expectException(WorkOrderClassificationException::class);

        $service->create([
            'property_id' => 'P-001',
            'email' => 'tenant@example.com',
            'description' => 'the elevator keeps stopping',
        ]);
    }

    public function test_filter_delegates_with_the_default_page_size(): void
    {
        $paginator = new LengthAwarePaginator([], 0, 15, 1, ['path' => '/']);

        $this->repository->shouldReceive('filter')
            ->once()
            ->with(['status' => 'open'], 15)
            ->andReturn($paginator);

        $service = new WorkOrderService($this->repository, new FakeWorkOrderClassifier);

        $this->assertSame($paginator, $service->filter(['status' => 'open']));
    }
}
