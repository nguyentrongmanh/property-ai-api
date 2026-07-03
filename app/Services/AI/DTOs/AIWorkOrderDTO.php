<?php

namespace App\Services\AI\DTOs;

readonly class AIWorkOrderDTO
{
    public function __construct(
        public string $title,
        public string $category,
        public string $priority,
        public string $summary
    ) {}
}
