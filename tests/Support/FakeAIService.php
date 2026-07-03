<?php

namespace Tests\Support;

use App\Exceptions\AiServiceException;
use App\Services\AI\Contracts\AIServiceInterface;
use App\Services\AI\DTOs\AIWorkOrderDTO;
use App\Services\AI\DTOs\BuildingSummaryInputDTO;

class FakeAIService implements AIServiceInterface
{
    public int $workOrderCalls = 0;

    public int $buildingSummaryCalls = 0;

    public function __construct(
        private readonly ?AIWorkOrderDTO $workOrderResult = new AIWorkOrderDTO(
            title: 'Lobby elevator stopping and making noise',
            category: 'elevator',
            priority: 'high',
            summary: 'Lobby elevator is stopping between floors and producing a grinding noise.',
        ),
        private readonly string $buildingSummaryResult = 'A well occupied office building with two open work orders, the most urgent being the lobby elevator.',
        private readonly ?AiServiceException $workOrderException = null,
        private readonly ?AiServiceException $buildingSummaryException = null,
    ) {}

    public static function failingWorkOrder(): self
    {
        return new self(workOrderException: AiServiceException::invalidResponse('fake failure'));
    }

    public static function rateLimitedWorkOrder(): self
    {
        return new self(workOrderException: AiServiceException::rateLimited());
    }

    public static function failingBuildingSummary(): self
    {
        return new self(buildingSummaryException: AiServiceException::invalidResponse('fake failure'));
    }

    public function generateWorkOrder(string $description): AIWorkOrderDTO
    {
        $this->workOrderCalls++;

        if ($this->workOrderException !== null) {
            throw $this->workOrderException;
        }

        return $this->workOrderResult;
    }

    public function generateBuildingSummary(BuildingSummaryInputDTO $input): string
    {
        $this->buildingSummaryCalls++;

        if ($this->buildingSummaryException !== null) {
            throw $this->buildingSummaryException;
        }

        return $this->buildingSummaryResult;
    }
}
