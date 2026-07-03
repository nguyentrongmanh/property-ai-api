<?php

namespace App\Services\AI\Validators;

use App\Enums\WorkOrderCategory;
use App\Enums\WorkOrderPriority;
use App\Exceptions\AiServiceException;
use App\Services\AI\DTOs\AIWorkOrderDTO;
use Illuminate\Support\Str;

final class WorkOrderResponseValidator
{
    private const MAX_TITLE_LENGTH = 120;

    private const MAX_SUMMARY_LENGTH = 500;

    /**
     * @param  array<string, mixed>  $data
     *
     * @throws AiServiceException
     */
    public function validate(array $data): AIWorkOrderDTO
    {
        $title = $this->cleanString($data['title'] ?? null);
        $summary = $this->cleanString($data['summary'] ?? null);

        if ($title === null || $summary === null) {
            throw AiServiceException::invalidResponse('title or summary is missing or empty');
        }

        $category = WorkOrderCategory::tryFrom((string) ($data['category'] ?? ''));
        $priority = WorkOrderPriority::tryFrom((string) ($data['priority'] ?? ''));

        if ($category === null || $priority === null) {
            throw AiServiceException::invalidResponse('category or priority is not an allowed value');
        }

        return new AIWorkOrderDTO(
            title: Str::limit($title, self::MAX_TITLE_LENGTH, ''),
            category: $category->value,
            priority: $priority->value,
            summary: Str::limit($summary, self::MAX_SUMMARY_LENGTH, ''),
        );
    }

    private function cleanString(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $value = trim($value);

        return $value === '' ? null : $value;
    }
}
