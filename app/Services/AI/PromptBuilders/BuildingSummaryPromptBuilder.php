<?php

namespace App\Services\AI\PromptBuilders;

use App\Models\WorkOrder;
use App\Services\AI\DTOs\BuildingSummaryInputDTO;

class BuildingSummaryPromptBuilder
{
    public function build(BuildingSummaryInputDTO $input): string
    {
        $facts = collect([
            'Name' => $input->building->name,
            'Type' => $input->building->type?->value,
            'Status' => $input->building->status->value,
            'City' => $input->building->city,
            'Units' => $input->building->units,
            'Occupancy rate' => $input->building->occupancy_rate,
            'Amenities' => $input->building->amenities ? implode(', ', $input->building->amenities) : null,
        ])
            ->map(fn (mixed $value, string $label) => $label.': '.($value ?? 'not recorded'))
            ->implode("\n");

        $workOrders = $input->openWorkOrders
            ->map(fn (WorkOrder $workOrder) => "- [{$workOrder->priority->value}] {$workOrder->title} ({$workOrder->category->value})")
            ->implode("\n");

        $workOrders = $workOrders === '' ? 'None' : $workOrders;

        return <<<PROMPT
            You write short operational summaries for property managers.

            Write a single paragraph (3-4 sentences, plain text, no markdown) summarising
            the building below and its open maintenance work orders. Mention the overall
            state of the building and call out the most urgent open issues first. If data
            is marked "not recorded", do not invent it.

            Building:
            {$facts}

            Open work orders:
            {$workOrders}
            PROMPT;
    }
}
