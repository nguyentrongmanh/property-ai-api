<?php

namespace App\Repositories;

use App\Models\Building;
use App\Repositories\Contracts\BuildingRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class EloquentBuildingRepository implements BuildingRepositoryInterface
{
    /**
     * @return array<int, array{city: string, total_properties: int, average_occupancy_rate: float|null}>
     */
    public function statsByCity(): array
    {
        return Building::query()
            ->selectRaw('city, COUNT(*) as total_properties, AVG(occupancy_rate) as average_occupancy_rate')
            ->whereNotNull('city')
            ->groupBy('city')
            ->orderBy('city')
            ->get()
            ->map(static function (Building $building): array {
                return [
                    'city' => (string) $building->city,
                    'total_properties' => (int) $building->getAttribute('total_properties'),
                    'average_occupancy_rate' => $building->getAttribute('average_occupancy_rate') === null
                        ? null
                        : round((float) $building->getAttribute('average_occupancy_rate'), 2),
                ];
            })
            ->all();
    }

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

    public function detailWithOpenWorkOrders(string $id): Building
    {
        return Building::query()
            ->with('openWorkOrders')
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
