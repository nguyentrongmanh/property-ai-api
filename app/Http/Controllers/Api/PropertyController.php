<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\IndexPropertiesRequest;
use App\Http\Resources\BuildingResource;
use App\Services\Contracts\PropertyServiceInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Resources\Json\JsonResource;

class PropertyController extends BaseApiController
{
    protected string $resource = BuildingResource::class;

    protected string $emptyListMessage = 'No properties matched the given filters.';

    public function __construct(
        private readonly PropertyServiceInterface $properties,
    ) {
        parent::__construct($properties);
    }

    public function summary(string $id): JsonResponse
    {
        return $this->respondSuccess([
            'property_id' => $id,
            'summary' => $this->properties->summary($id),
        ]);
    }

    public function index(IndexPropertiesRequest $request): JsonResponse|AnonymousResourceCollection
    {
        return $this->respondList($this->service->filter($request->validated()));
    }

    public function show(string $id): JsonResource
    {
        return $this->respondItem($this->service->detail($id));
    }
}
