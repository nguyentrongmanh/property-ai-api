<?php

namespace App\Services\AI\DTOs;

use App\Models\Building;
use Illuminate\Support\Collection;

readonly class BuildingSummaryInputDTO
{
    public function __construct(
        public Building $building,
        public Collection $openWorkOrders,
    ) {}
}
