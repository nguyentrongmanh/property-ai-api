<?php

namespace App\Http\Resources;

use App\Models\Building;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Building
 */
class BuildingResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'type' => $this->type,
            'status' => $this->status,
            'city' => $this->city,
            'units' => $this->units,
            'occupancy_rate' => $this->occupancy_rate,
            'amenities' => $this->amenities,
            'open_work_orders' => $this->whenCounted('openWorkOrders'),
        ];
    }
}
