<?php

namespace Tests\Unit\Services;

use App\Exceptions\AiServiceException;
use App\Models\WorkOrder;
use App\Repositories\Contracts\WorkOrderRepositoryInterface;
use App\Services\AI\Contracts\AIServiceInterface;
use App\Services\AI\DTOs\AIWorkOrderDTO;
use App\Services\WorkOrderService;
use Illuminate\Pagination\LengthAwarePaginator;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Mockery\MockInterface;
use PHPUnit\Framework\TestCase;

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
        $aiService = Mockery::mock(AIServiceInterface::class);
        $workOrder = new WorkOrder;

        $aiService->shouldReceive('generateWorkOrder')
            ->once()
            ->with('the elevator keeps stopping')
            ->andReturn(new AIWorkOrderDTO(
                title: 'Lobby elevator stopping',
                category: 'elevator',
                priority: 'high',
                summary: 'Elevator stops between floors.',
            ));

        $this->repository->shouldReceive('create')
            ->once()
            ->with([
                'property_id' => 'P-001',
                'requester_email' => 'tenant@example.com',
                'source_text' => 'the elevator keeps stopping',
                'title' => 'Lobby elevator stopping',
                'category' => 'elevator',
                'priority' => 'high',
                'summary' => 'Elevator stops between floors.',
            ])
            ->andReturn($workOrder);

        $service = new WorkOrderService($this->repository, $aiService);

        $result = $service->create([
            'property_id' => 'P-001',
            'email' => 'tenant@example.com',
            'description' => 'the elevator keeps stopping',
        ]);

        $this->assertSame($workOrder, $result);
    }

    public function test_create_saves_nothing_when_classification_fails(): void
    {
        $this->repository->shouldNotReceive('create');

        $aiService = Mockery::mock(AIServiceInterface::class);
        $aiService->shouldReceive('generateWorkOrder')
            ->once()
            ->andThrow(AiServiceException::invalidResponse('fake failure'));

        $service = new WorkOrderService($this->repository, $aiService);

        $this->expectException(AiServiceException::class);

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

        $service = new WorkOrderService($this->repository, Mockery::mock(AIServiceInterface::class));

        $this->assertSame($paginator, $service->filter(['status' => 'open']));
    }
}
