<?php

namespace App\Services\Contracts;

use App\Exceptions\AiServiceException;
use App\Models\Building;
use Illuminate\Database\Eloquent\ModelNotFoundException;

/**
 * Buildings are listed fullest (highest occupancy) first;
 * detail() includes the open work order count.
 *
 * Supported filters: city, type, status, min_occupancy, per_page, page.
 *
 * @extends CrudServiceInterface<Building>
 */
interface PropertyServiceInterface extends CrudServiceInterface
{
    /**
     * @return array<int, array{city: string, total_properties: int, average_occupancy_rate: float|null}>
     */
    public function statsByCity(): array;

    /**
     * AI-written summary of a building and its open work orders.
     *
     * @throws ModelNotFoundException
     * @throws AiServiceException
     */
    public function summary(string $id): string;
}
