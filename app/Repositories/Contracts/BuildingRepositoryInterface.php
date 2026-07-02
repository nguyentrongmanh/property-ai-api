<?php

namespace App\Repositories\Contracts;

use App\Models\Building;

/**
 * Buildings matching filters are returned fullest (highest occupancy) first;
 * detail() includes the open work order count.
 *
 * Supported filters: city, type, status, min_occupancy.
 *
 * @extends RepositoryInterface<Building>
 */
interface BuildingRepositoryInterface extends RepositoryInterface {}
