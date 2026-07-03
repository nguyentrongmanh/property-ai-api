<?php

namespace Tests\Feature\AI;

use App\Enums\WorkOrderCategory;
use App\Enums\WorkOrderPriority;
use App\Exceptions\WorkOrderClassificationException;
use App\Services\AI\Contracts\WorkOrderClassifierInterface;
use App\Services\AI\GeminiWorkOrderClassifier;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class GeminiWorkOrderClassifierTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Http::preventStrayRequests();
    }

    /**
     * @param  array<string, mixed>|string  $answer
     * @return array<string, mixed>
     */
    private function geminiResponse(array|string $answer): array
    {
        return [
            'candidates' => [
                [
                    'content' => [
                        'parts' => [
                            ['text' => is_string($answer) ? $answer : json_encode($answer)],
                        ],
                    ],
                ],
            ],
        ];
    }

    public function test_returns_validated_classification(): void
    {
        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response($this->geminiResponse([
                'title' => 'Lobby elevator stopping and making noise',
                'category' => 'elevator',
                'priority' => 'high',
                'summary' => 'Lobby elevator stops between floors.',
            ])),
        ]);

        $classification = app(WorkOrderClassifierInterface::class)
            ->classify('the elevator keeps stopping');

        $this->assertSame('Lobby elevator stopping and making noise', $classification->title);
        $this->assertSame(WorkOrderCategory::Elevator, $classification->category);
        $this->assertSame(WorkOrderPriority::High, $classification->priority);
        $this->assertSame('Lobby elevator stops between floors.', $classification->summary);
    }

    public function test_sends_a_schema_constrained_request(): void
    {
        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response($this->geminiResponse([
                'title' => 'T', 'category' => 'general', 'priority' => 'low', 'summary' => 'S',
            ])),
        ]);

        app(WorkOrderClassifierInterface::class)->classify('a leaking tap in the kitchen');

        Http::assertSent(function (Request $request): bool {
            $config = $request->data()['generationConfig'] ?? [];

            return str_contains($request->url(), 'gemini-test:generateContent')
                && $request->hasHeader('x-goog-api-key', 'test-key')
                && $config['responseMimeType'] === 'application/json'
                && $config['responseSchema']['properties']['category']['enum'] === array_column(WorkOrderCategory::cases(), 'value')
                && $config['responseSchema']['properties']['priority']['enum'] === array_column(WorkOrderPriority::cases(), 'value')
                && str_contains($request->data()['contents'][0]['parts'][0]['text'], 'a leaking tap in the kitchen');
        });
    }

    public function test_retries_once_when_the_answer_is_unusable(): void
    {
        Http::fakeSequence('generativelanguage.googleapis.com/*')
            ->push($this->geminiResponse('this is not json'))
            ->push($this->geminiResponse([
                'title' => 'Broken tap', 'category' => 'plumbing', 'priority' => 'medium', 'summary' => 'Tap leaks.',
            ]));

        $classification = app(WorkOrderClassifierInterface::class)->classify('leaking tap');

        $this->assertSame('Broken tap', $classification->title);
        Http::assertSentCount(2);
    }

    public function test_gives_up_after_two_unusable_answers(): void
    {
        Http::fakeSequence('generativelanguage.googleapis.com/*')
            ->push($this->geminiResponse('garbage'))
            ->push($this->geminiResponse(['title' => 'T', 'category' => 'spaceship', 'priority' => 'low', 'summary' => 'S']));

        $this->expectException(WorkOrderClassificationException::class);

        app(WorkOrderClassifierInterface::class)->classify('leaking tap');
    }

    public function test_maps_upstream_rate_limit_without_retrying(): void
    {
        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response(['error' => 'quota'], 429),
        ]);

        try {
            app(WorkOrderClassifierInterface::class)->classify('leaking tap');
            $this->fail('Expected a rate limited exception.');
        } catch (WorkOrderClassificationException $exception) {
            $this->assertTrue($exception->isRateLimited());
        }

        Http::assertSentCount(1);
    }

    public function test_upstream_server_errors_are_unreachable_failures(): void
    {
        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response('upstream exploded', 500),
        ]);

        try {
            app(WorkOrderClassifierInterface::class)->classify('leaking tap');
            $this->fail('Expected a classification exception.');
        } catch (WorkOrderClassificationException $exception) {
            $this->assertFalse($exception->isRateLimited());
        }
    }

    public function test_fails_fast_without_an_api_key(): void
    {
        $classifier = new GeminiWorkOrderClassifier(apiKey: '', model: 'gemini-test');

        $this->expectException(WorkOrderClassificationException::class);
        $this->expectExceptionMessage('no API key configured');

        $classifier->classify('leaking tap');

        Http::assertNothingSent();
    }
}
