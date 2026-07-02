<?php

namespace Database\Factories;

use App\Enums\WorkOrderCategory;
use App\Enums\WorkOrderPriority;
use App\Enums\WorkOrderStatus;
use App\Models\Building;
use App\Models\WorkOrder;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<WorkOrder>
 */
class WorkOrderFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'property_id' => Building::factory(),
            'source_text' => fake()->sentence(12),
            'requester_email' => fake()->safeEmail(),
            'title' => fake()->sentence(6),
            'category' => fake()->randomElement(WorkOrderCategory::cases()),
            'priority' => fake()->randomElement(WorkOrderPriority::cases()),
            'summary' => fake()->paragraph(),
            'status' => WorkOrderStatus::Open,
        ];
    }

    public function inProgress(): static
    {
        return $this->state(['status' => WorkOrderStatus::InProgress]);
    }

    public function completed(): static
    {
        return $this->state(['status' => WorkOrderStatus::Completed]);
    }

    public function urgent(): static
    {
        return $this->state(['priority' => WorkOrderPriority::Urgent]);
    }
}
