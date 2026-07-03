<?php

namespace App\Repositories\Contracts;

use App\Models\WorkOrder;

/**
 * Work orders matching filters are returned most urgent first,
 * then most recent.
 *
 * Supported filters: property_id, status, priority, category.
 *
 * @extends RepositoryInterface<WorkOrder>
 */
interface WorkOrderRepositoryInterface extends RepositoryInterface {}
