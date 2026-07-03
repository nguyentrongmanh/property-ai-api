<?php

namespace App\Repositories;

use App\Enums\WorkOrderPriority;
use App\Models\WorkOrder;
use App\Repositories\Contracts\WorkOrderRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class EloquentWorkOrderRepository implements WorkOrderRepositoryInterface
{
    public function filter(array $filters, int $perPage): LengthAwarePaginator
    {
        return WorkOrder::query()
            ->when($filters['property_id'] ?? null, fn ($query, string $propertyId) => $query->where('property_id', $propertyId))
            ->when($filters['status'] ?? null, fn ($query, string $status) => $query->where('status', $status))
            ->when($filters['priority'] ?? null, fn ($query, string $priority) => $query->where('priority', $priority))
            ->when($filters['category'] ?? null, fn ($query, string $category) => $query->where('category', $category))
            ->orderByRaw($this->urgencyOrder())
            ->orderByDesc('created_at')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function detail(string $id): WorkOrder
    {
        return WorkOrder::query()->findOrFail($id);
    }

    public function create(array $attributes): WorkOrder
    {
        return WorkOrder::query()->create($attributes);
    }

    public function edit(string $id, array $attributes): WorkOrder
    {
        $workOrder = WorkOrder::query()->findOrFail($id);

        $workOrder->update($attributes);

        return $workOrder;
    }

    public function delete(string $id): void
    {
        WorkOrder::query()->findOrFail($id)->delete();
    }

    /**
     * Build a CASE expression ranking priorities by their enum weight,
     * so the most urgent work orders come first on any database driver.
     */
    private function urgencyOrder(): string
    {
        $cases = collect(WorkOrderPriority::cases())
            ->map(fn (WorkOrderPriority $priority) => "when '{$priority->value}' then {$priority->weight()}")
            ->implode(' ');

        return "case priority {$cases} else 0 end desc";
    }
}
