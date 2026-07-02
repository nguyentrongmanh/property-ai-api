<?php

namespace Database\Factories;

use App\Enums\BuildingStatus;
use App\Enums\BuildingType;
use App\Models\Building;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Building>
 */
class BuildingFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $amenities = fake()->randomElements(
            ['elevator', 'parking', 'gym', 'rooftop_terrace', 'bike_storage', 'security_desk'],
            fake()->numberBetween(0, 4),
        );

        return [
            'name' => fake()->streetName().' '.fake()->buildingNumber(),
            'type' => fake()->randomElement(BuildingType::cases()),
            'status' => BuildingStatus::Active,
            'city' => fake()->city(),
            'units' => fake()->numberBetween(4, 120),
            'occupancy_rate' => fake()->randomFloat(2, 0.2, 1.0),
            'amenities' => $amenities === [] ? null : $amenities,
        ];
    }

    public function inactive(): static
    {
        return $this->state(['status' => BuildingStatus::Inactive]);
    }

    public function underRenovation(): static
    {
        return $this->state(['status' => BuildingStatus::UnderRenovation]);
    }

    /**
     * A building with sparse data, mimicking incomplete records.
     */
    public function incomplete(): static
    {
        return $this->state([
            'type' => null,
            'city' => null,
            'units' => null,
            'occupancy_rate' => null,
            'amenities' => null,
        ]);
    }
}
