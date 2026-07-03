<?php

namespace App\Services\AI\Contracts;

use App\Exceptions\WorkOrderClassificationException;
use App\Services\AI\WorkOrderClassification;

interface WorkOrderClassifierInterface
{
    /**
     * Turn a plain-language maintenance request into a structured
     * work order classification (title, category, priority, summary).
     *
     * @throws WorkOrderClassificationException when the
     *                                          model cannot be reached or returns an unusable answer.
     */
    public function classify(string $description): WorkOrderClassification;
}
