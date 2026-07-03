<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\IndexWorkOrdersRequest;
use App\Http\Requests\StoreWorkOrderRequest;
use App\Http\Resources\WorkOrderResource;
use App\Services\Contracts\WorkOrderServiceInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class WorkOrderController extends BaseApiController
{
    protected string $resource = WorkOrderResource::class;

    protected string $emptyListMessage = 'No work orders matched the given filters.';

    public function __construct(WorkOrderServiceInterface $workOrderService)
    {
        parent::__construct($workOrderService);
    }

    public function index(IndexWorkOrdersRequest $request): JsonResponse|AnonymousResourceCollection
    {
        return $this->respondList($this->service->filter($request->validated()));
    }

    public function store(StoreWorkOrderRequest $request): JsonResponse
    {
        $workOrder = $this->service->create($request->validated());

        return $this->respondCreated($this->respondItem($workOrder));
    }
}
