<?php

namespace App\Services\Contracts;

use App\Models\Building;

/**
 * Buildings are listed fullest (highest occupancy) first;
 * detail() includes the open work order count.
 *
 * Supported filters: city, type, status, min_occupancy, per_page, page.
 *
 * @extends CrudServiceInterface<Building>
 */
interface PropertyServiceInterface extends CrudServiceInterface {}
