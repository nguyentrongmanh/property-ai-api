<?php

namespace Tests\Support;

use App\Exceptions\WorkOrderClassificationException;
use App\Services\AI\Contracts\WorkOrderClassifierInterface;
use App\Services\AI\WorkOrderClassification;

class FakeWorkOrderClassifier implements WorkOrderClassifierInterface
{
    public int $calls = 0;

    /**
     * @param  array<string, mixed>  $result  raw classification data
     */
    public function __construct(
        private readonly array $result = [
            'title' => 'Lobby elevator stopping and making noise',
            'category' => 'elevator',
            'priority' => 'high',
            'summary' => 'Lobby elevator is stopping between floors and producing a grinding noise.',
        ],
        private readonly ?WorkOrderClassificationException $throws = null,
    ) {}

    public static function failing(): self
    {
        return new self(throws: WorkOrderClassificationException::invalidResponse('fake failure'));
    }

    public static function rateLimited(): self
    {
        return new self(throws: WorkOrderClassificationException::rateLimited());
    }

    public function classify(string $description): WorkOrderClassification
    {
        $this->calls++;

        if ($this->throws !== null) {
            throw $this->throws;
        }

        return WorkOrderClassification::fromArray($this->result);
    }
}
