<?php

namespace App\Services\AI\Contracts;

use App\Services\AI\DTOs\AIWorkOrderDTO;
use App\Services\AI\DTOs\BuildingSummaryInputDTO;

interface AIServiceInterface
{
    public function generateWorkOrder(
        string $description
    ): AIWorkOrderDTO;

    public function generateBuildingSummary(
        BuildingSummaryInputDTO $input
    ): string;
}
