<?php

namespace Tests\Unit\Services;

use App\Models\Building;
use App\Repositories\Contracts\BuildingRepositoryInterface;
use App\Services\AI\Contracts\AIServiceInterface;
use App\Services\AI\DTOs\BuildingSummaryInputDTO;
use App\Services\PropertyService;
use Illuminate\Pagination\LengthAwarePaginator;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Mockery\MockInterface;
use PHPUnit\Framework\TestCase;

class PropertyServiceTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    private BuildingRepositoryInterface&MockInterface $repository;

    private AIServiceInterface&MockInterface $aiService;

    private PropertyService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = Mockery::mock(BuildingRepositoryInterface::class);
        $this->aiService = Mockery::mock(AIServiceInterface::class);
        $this->service = new PropertyService($this->repository, $this->aiService);
    }

    public function test_summary_summarizes_the_building_with_its_open_work_orders(): void
    {
        $building = new Building;

        $this->repository->shouldReceive('detailWithOpenWorkOrders')
            ->once()
            ->with('P-001')
            ->andReturn($building);

        $building->setRelation('openWorkOrders', collect());

        $this->aiService->shouldReceive('generateBuildingSummary')
            ->once()
            ->with(Mockery::on(function (BuildingSummaryInputDTO $input) use ($building): bool {
                return $input->building === $building
                    && $input->openWorkOrders === $building->openWorkOrders;
            }))
            ->andReturn('A busy office building.');

        $this->assertSame('A busy office building.', $this->service->summary('P-001'));
    }

    public function test_filter_uses_the_default_page_size(): void
    {
        $paginator = new LengthAwarePaginator([], 0, 15, 1, ['path' => '/']);

        $this->repository->shouldReceive('filter')
            ->once()
            ->with(['city' => 'Amsterdam'], 15)
            ->andReturn($paginator);

        $this->assertSame($paginator, $this->service->filter(['city' => 'Amsterdam']));
    }

    public function test_filter_passes_the_requested_page_size_as_int(): void
    {
        $paginator = new LengthAwarePaginator([], 0, 5, 1, ['path' => '/']);

        $this->repository->shouldReceive('filter')
            ->once()
            ->with(Mockery::any(), 5)
            ->andReturn($paginator);

        $this->service->filter(['per_page' => '5']);
    }

    public function test_detail_delegates_to_the_repository(): void
    {
        $building = new Building;

        $this->repository->shouldReceive('detail')
            ->once()
            ->with('P-001')
            ->andReturn($building);

        $this->assertSame($building, $this->service->detail('P-001'));
    }

    public function test_create_delegates_to_the_repository(): void
    {
        $building = new Building;

        $this->repository->shouldReceive('create')
            ->once()
            ->with(['name' => 'Weena Tower'])
            ->andReturn($building);

        $this->assertSame($building, $this->service->create(['name' => 'Weena Tower']));
    }

    public function test_edit_delegates_to_the_repository(): void
    {
        $building = new Building;

        $this->repository->shouldReceive('edit')
            ->once()
            ->with('P-001', ['name' => 'Renamed'])
            ->andReturn($building);

        $this->assertSame($building, $this->service->edit('P-001', ['name' => 'Renamed']));
    }

    public function test_delete_delegates_to_the_repository(): void
    {
        $this->repository->shouldReceive('delete')
            ->once()
            ->with('P-001');

        $this->service->delete('P-001');
    }
}
