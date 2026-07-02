<?php

namespace App\Repositories;

use App\Models\Building;
use App\Repositories\Contracts\BuildingRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class EloquentBuildingRepository implements BuildingRepositoryInterface
{
    public function filter(array $filters, int $perPage): LengthAwarePaginator
    {
        return Building::query()
            ->when($filters['city'] ?? null, fn ($query, string $city) => $query->where('city', $city))
            ->when($filters['type'] ?? null, fn ($query, string $type) => $query->where('type', $type))
            ->when($filters['status'] ?? null, fn ($query, string $status) => $query->where('status', $status))
            ->when(
                isset($filters['min_occupancy']),
                fn ($query) => $query->where('occupancy_rate', '>=', (float) $filters['min_occupancy']),
            )
            ->orderByDesc('occupancy_rate')
            ->orderBy('name')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function detail(string $id): Building
    {
        return Building::query()
            ->withCount('openWorkOrders')
            ->findOrFail($id);
    }

    public function create(array $attributes): Building
    {
        return Building::query()->create($attributes);
    }

    public function edit(string $id, array $attributes): Building
    {
        $building = Building::query()->findOrFail($id);

        $building->update($attributes);

        return $building;
    }

    public function delete(string $id): void
    {
        Building::query()->findOrFail($id)->delete();
    }
}
