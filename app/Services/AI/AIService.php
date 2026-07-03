<?php

namespace App\Services\AI;

use App\Enums\WorkOrderCategory;
use App\Enums\WorkOrderPriority;
use App\Exceptions\AiServiceException;
use App\Integrations\Contracts\AIClientInterface;
use App\Services\AI\Contracts\AIServiceInterface;
use App\Services\AI\DTOs\AIWorkOrderDTO;
use App\Services\AI\DTOs\BuildingSummaryInputDTO;
use App\Services\AI\PromptBuilders\BuildingSummaryPromptBuilder;
use App\Services\AI\PromptBuilders\WorkOrderPromptBuilder;
use App\Services\AI\Validators\WorkOrderResponseValidator;
use Illuminate\Support\Facades\Log;

class AIService implements AIServiceInterface
{
    private const MAX_ATTEMPTS = 2;

    public function __construct(
        private readonly BuildingSummaryPromptBuilder $buildingSummaryPromptBuilder,
        private readonly WorkOrderPromptBuilder $workOrderPromptBuilder,
        private readonly WorkOrderResponseValidator $workOrderResponseValidator,
        private readonly AIClientInterface $client,
    ) {}

    public function generateWorkOrder(string $description): AIWorkOrderDTO
    {
        $lastException = null;

        for ($attempt = 1; $attempt <= self::MAX_ATTEMPTS; $attempt++) {
            try {
                return $this->requestWorkOrder($description);
            } catch (AiServiceException $exception) {
                if ($exception->isRateLimited()) {
                    throw $exception;
                }

                $lastException = $exception;

                Log::warning('Work order generation attempt failed.', [
                    'attempt' => $attempt,
                    'reason' => $exception->getMessage(),
                ]);
            }
        }

        throw $lastException;
    }

    public function generateBuildingSummary(BuildingSummaryInputDTO $input): string
    {
        $answer = $this->client->generateText($this->buildingSummaryPromptBuilder->build($input), [
            'temperature' => 0.4,
        ]);

        return trim($answer);
    }

    private function requestWorkOrder(string $description): AIWorkOrderDTO
    {
        $answer = $this->client->generateText(
            $this->workOrderPromptBuilder->build($description),
            $this->workOrderGenerationConfig(),
        );

        $decoded = json_decode($answer, true);

        if (! is_array($decoded)) {
            throw AiServiceException::invalidResponse('answer is not valid JSON');
        }

        return $this->workOrderResponseValidator->validate($decoded);
    }

    /**
     * @return array<string, mixed>
     */
    private function workOrderGenerationConfig(): array
    {
        return [
            'temperature' => 0.2,
            'responseMimeType' => 'application/json',
            'responseSchema' => [
                'type' => 'OBJECT',
                'properties' => [
                    'title' => ['type' => 'STRING'],
                    'category' => ['type' => 'STRING', 'enum' => array_column(WorkOrderCategory::cases(), 'value')],
                    'priority' => ['type' => 'STRING', 'enum' => array_column(WorkOrderPriority::cases(), 'value')],
                    'summary' => ['type' => 'STRING'],
                ],
                'required' => ['title', 'category', 'priority', 'summary'],
            ],
        ];
    }
}
