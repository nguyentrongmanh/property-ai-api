<?php

namespace App\Repositories\Contracts;

use App\Models\Building;
use Illuminate\Database\Eloquent\ModelNotFoundException;

/**
 * Buildings matching filters are returned fullest (highest occupancy) first;
 * detail() includes the open work order count.
 *
 * Supported filters: city, type, status, min_occupancy.
 *
 * @extends RepositoryInterface<Building>
 */
interface BuildingRepositoryInterface extends RepositoryInterface
{
    /**
     * Return a single building with its open work orders loaded.
     *
     * @throws ModelNotFoundException
     */
    public function detailWithOpenWorkOrders(string $id): Building;
}
