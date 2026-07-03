<?php

namespace App\Http\Resources;

use App\Models\WorkOrder;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin WorkOrder
 */
class WorkOrderResource extends JsonResource
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
            'property_id' => $this->property_id,
            'source_text' => $this->source_text,
            'requester_email' => $this->requester_email,
            'title' => $this->title,
            'category' => $this->category,
            'priority' => $this->priority,
            'summary' => $this->summary,
            'status' => $this->status,
            'created_at' => $this->created_at?->toDateString(),
        ];
    }
}
