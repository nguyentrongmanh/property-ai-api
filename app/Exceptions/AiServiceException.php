<?php

namespace App\Exceptions;

use Exception;

class AiServiceException extends Exception
{
    private bool $rateLimited = false;

    public static function unreachable(string $reason): self
    {
        return new self("The AI service could not be reached: {$reason}");
    }

    public static function invalidResponse(string $reason): self
    {
        return new self("The AI service returned an unusable answer: {$reason}");
    }

    public static function rateLimited(): self
    {
        $exception = new self('The AI service rate limit was reached.');
        $exception->rateLimited = true;

        return $exception;
    }

    public function isRateLimited(): bool
    {
        return $this->rateLimited;
    }
}
