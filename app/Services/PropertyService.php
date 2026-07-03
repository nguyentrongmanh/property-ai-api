<?php

namespace App\Services;

use App\Models\Building;
use App\Repositories\Contracts\BuildingRepositoryInterface;
use App\Services\AI\Contracts\AIServiceInterface;
use App\Services\AI\DTOs\BuildingSummaryInputDTO;
use App\Services\Contracts\PropertyServiceInterface;

/**
 * @extends BaseCrudService<Building>
 */
class PropertyService extends BaseCrudService implements PropertyServiceInterface
{
    public function __construct(
        private readonly BuildingRepositoryInterface $buildings,
        private readonly AIServiceInterface $aiService,
    ) {
        parent::__construct($buildings);
    }

    public function summary(string $id): string
    {
        $building = $this->buildings->detailWithOpenWorkOrders($id);

        return $this->aiService->generateBuildingSummary(new BuildingSummaryInputDTO(
            building: $building,
            openWorkOrders: $building->openWorkOrders,
        ));
    }
}
