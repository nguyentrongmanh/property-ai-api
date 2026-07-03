<?php

namespace App\Services\AI;

use App\Enums\WorkOrderCategory;
use App\Enums\WorkOrderPriority;
use App\Exceptions\WorkOrderClassificationException;
use Illuminate\Support\Str;

/**
 * Validated result of an AI classification. Instances can only be built
 * through fromArray(), which rejects incomplete or invalid model output,
 * so a half-filled work order can never reach the database.
 */
final readonly class WorkOrderClassification
{
    private const MAX_TITLE_LENGTH = 120;

    private const MAX_SUMMARY_LENGTH = 500;

    private function __construct(
        public string $title,
        public WorkOrderCategory $category,
        public WorkOrderPriority $priority,
        public string $summary,
    ) {}

    /**
     * Build a classification from raw model output, validating every field.
     *
     * @param  array<string, mixed>  $data
     *
     * @throws WorkOrderClassificationException
     */
    public static function fromArray(array $data): self
    {
        $title = self::cleanString($data['title'] ?? null);
        $summary = self::cleanString($data['summary'] ?? null);

        if ($title === null || $summary === null) {
            throw WorkOrderClassificationException::invalidResponse('title or summary is missing or empty');
        }

        $category = WorkOrderCategory::tryFrom((string) ($data['category'] ?? ''));
        $priority = WorkOrderPriority::tryFrom((string) ($data['priority'] ?? ''));

        if ($category === null || $priority === null) {
            throw WorkOrderClassificationException::invalidResponse('category or priority is not an allowed value');
        }

        return new self(
            title: Str::limit($title, self::MAX_TITLE_LENGTH, ''),
            category: $category,
            priority: $priority,
            summary: Str::limit($summary, self::MAX_SUMMARY_LENGTH, ''),
        );
    }

    private static function cleanString(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $value = trim($value);

        return $value === '' ? null : $value;
    }
}
