<?php

namespace App\Services\Contracts;

use App\Models\WorkOrder;

/**
 * Work orders are listed most urgent first, then most recent.
 *
 * create() expects [property_id, email, description]; the AI classifier
 * fills in title, category, priority and summary before saving.
 *
 * Supported filters: property_id, status, priority, category, per_page, page.
 *
 * @extends CrudServiceInterface<WorkOrder>
 */
interface WorkOrderServiceInterface extends CrudServiceInterface {}
