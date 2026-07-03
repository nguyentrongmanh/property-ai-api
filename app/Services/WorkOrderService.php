<?php

namespace App\Services;

use App\Models\WorkOrder;
use App\Repositories\Contracts\WorkOrderRepositoryInterface;
use App\Services\AI\Contracts\AIServiceInterface;
use App\Services\Contracts\WorkOrderServiceInterface;
use Illuminate\Database\Eloquent\Model;

/**
 * @extends BaseCrudService<WorkOrder>
 */
class WorkOrderService extends BaseCrudService implements WorkOrderServiceInterface
{
    public function __construct(
        WorkOrderRepositoryInterface $workOrders,
        private readonly AIServiceInterface $aiService,
    ) {
        parent::__construct($workOrders);
    }

    /**
     * Create a work order from a plain-language maintenance request:
     * the AI classifier turns the description into a title, category,
     * priority and summary, and nothing is saved if it fails.
     *
     * @param  array{property_id: string, email: string, description: string}  $attributes
     */
    public function create(array $attributes): Model
    {
        $workOrder = $this->aiService->generateWorkOrder($attributes['description']);

        return $this->repository->create([
            'property_id' => $attributes['property_id'],
            'requester_email' => $attributes['email'],
            'source_text' => $attributes['description'],
            'title' => $workOrder->title,
            'category' => $workOrder->category,
            'priority' => $workOrder->priority,
            'summary' => $workOrder->summary,
        ]);
    }
}
