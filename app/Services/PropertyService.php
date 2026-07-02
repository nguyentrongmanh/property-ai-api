<?php

namespace App\Services;

use App\Models\Building;
use App\Repositories\Contracts\BuildingRepositoryInterface;
use App\Services\Contracts\PropertyServiceInterface;

/**
 * @extends BaseCrudService<Building>
 */
class PropertyService extends BaseCrudService implements PropertyServiceInterface
{
    public function __construct(BuildingRepositoryInterface $buildings)
    {
        parent::__construct($buildings);
    }
}
