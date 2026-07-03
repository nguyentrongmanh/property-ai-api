<?php

namespace App\Integrations;

use App\Exceptions\AiServiceException;
use App\Integrations\Contracts\AIClientInterface;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

/**
 * Thin HTTP client for Gemini's generateContent endpoint, shared by every
 * AI feature. Maps transport failures onto AiServiceException.
 */
class GeminiClient implements AIClientInterface
{
    private const BASE_URL = 'https://generativelanguage.googleapis.com/v1beta/models';

    public function __construct(
        private readonly string $apiKey,
        private readonly string $model,
    ) {}

    /**
     * Send a prompt and return the raw text of the first candidate.
     *
     * @param  array<string, mixed>  $generationConfig
     *
     * @throws AiServiceException
     */
    public function generateText(string $prompt, array $generationConfig = []): string
    {
        if ($this->apiKey === '') {
            throw AiServiceException::unreachable('no API key configured (set GEMINI_API_KEY)');
        }

        try {
            $response = Http::baseUrl(self::BASE_URL)
                ->withHeader('x-goog-api-key', $this->apiKey)
                ->timeout(20)
                ->connectTimeout(5)
                ->retry(2, 500, fn (Throwable $e) => $e instanceof ConnectionException, throw: true)
                ->post("{$this->model}:generateContent", array_filter([
                    'contents' => [
                        [
                            'parts' => [
                                ['text' => $prompt],
                            ],
                        ],
                    ],
                    'generationConfig' => $generationConfig,
                ]));

            $response->throw();
        } catch (ConnectionException|RequestException $exception) {
            if ($exception instanceof RequestException && $exception->response->status() === Response::HTTP_TOO_MANY_REQUESTS) {
                throw AiServiceException::rateLimited();
            }

            throw AiServiceException::unreachable($exception->getMessage());
        }

        $answer = $response->json('candidates.0.content.parts.0.text');

        if (! is_string($answer) || trim($answer) === '') {
            throw AiServiceException::invalidResponse('response contained no text candidate');
        }

        return $answer;
    }
}
