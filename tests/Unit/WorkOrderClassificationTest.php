<?php

namespace Tests\Unit;

use App\Enums\WorkOrderCategory;
use App\Enums\WorkOrderPriority;
use App\Exceptions\WorkOrderClassificationException;
use App\Services\AI\WorkOrderClassification;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class WorkOrderClassificationTest extends TestCase
{
    /**
     * @return array<string, mixed>
     */
    private function validData(): array
    {
        return [
            'title' => 'Lobby elevator stopping and making noise',
            'category' => 'elevator',
            'priority' => 'high',
            'summary' => 'Lobby elevator stops between floors.',
        ];
    }

    public function test_builds_from_a_valid_model_answer(): void
    {
        $classification = WorkOrderClassification::fromArray($this->validData());

        $this->assertSame('Lobby elevator stopping and making noise', $classification->title);
        $this->assertSame(WorkOrderCategory::Elevator, $classification->category);
        $this->assertSame(WorkOrderPriority::High, $classification->priority);
        $this->assertSame('Lobby elevator stops between floors.', $classification->summary);
    }

    public function test_trims_whitespace(): void
    {
        $classification = WorkOrderClassification::fromArray([
            ...$this->validData(),
            'title' => "  Noisy elevator  \n",
        ]);

        $this->assertSame('Noisy elevator', $classification->title);
    }

    public function test_caps_overly_long_titles_and_summaries(): void
    {
        $classification = WorkOrderClassification::fromArray([
            ...$this->validData(),
            'title' => str_repeat('a', 500),
            'summary' => str_repeat('b', 1000),
        ]);

        $this->assertSame(120, mb_strlen($classification->title));
        $this->assertSame(500, mb_strlen($classification->summary));
    }

    /**
     * @return array<string, array<int, array<string, mixed>>>
     */
    public static function unusableAnswers(): array
    {
        return [
            'missing title' => [['category' => 'elevator', 'priority' => 'high', 'summary' => 'S']],
            'empty title' => [['title' => '', 'category' => 'elevator', 'priority' => 'high', 'summary' => 'S']],
            'whitespace-only summary' => [['title' => 'T', 'category' => 'elevator', 'priority' => 'high', 'summary' => '   ']],
            'missing category' => [['title' => 'T', 'priority' => 'high', 'summary' => 'S']],
            'category outside the enum' => [['title' => 'T', 'category' => 'spaceship', 'priority' => 'high', 'summary' => 'S']],
            'priority outside the enum' => [['title' => 'T', 'category' => 'elevator', 'priority' => 'critical', 'summary' => 'S']],
            'non-string title' => [['title' => ['nested'], 'category' => 'elevator', 'priority' => 'high', 'summary' => 'S']],
            'completely empty answer' => [[]],
        ];
    }

    /**
     * @param  array<string, mixed>  $answer
     */
    #[DataProvider('unusableAnswers')]
    public function test_rejects_unusable_model_answers(array $answer): void
    {
        $this->expectException(WorkOrderClassificationException::class);

        WorkOrderClassification::fromArray($answer);
    }
}
