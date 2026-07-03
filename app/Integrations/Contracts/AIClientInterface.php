<?php

namespace App\Integrations\Contracts;

interface AIClientInterface
{
    /**
     * @param  array<string, mixed>  $generationConfig
     */
    public function generateText(string $prompt, array $generationConfig = []): string;
}
