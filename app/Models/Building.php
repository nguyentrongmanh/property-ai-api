<?php

namespace App\Models;

use App\Enums\BuildingStatus;
use App\Enums\BuildingType;
use App\Enums\WorkOrderStatus;
use App\Models\Concerns\HasPrefixedId;
use Database\Factories\BuildingFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Building extends Model
{
    /** @use HasFactory<BuildingFactory> */
    use HasFactory;

    use HasPrefixedId;

    protected $fillable = [
        'id',
        'name',
        'type',
        'status',
        'city',
        'units',
        'occupancy_rate',
        'amenities',
    ];

    protected $attributes = [
        'status' => 'active',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => BuildingType::class,
            'status' => BuildingStatus::class,
            'units' => 'integer',
            'occupancy_rate' => 'float',
            'amenities' => 'array',
        ];
    }

    public function workOrders(): HasMany
    {
        return $this->hasMany(WorkOrder::class, 'property_id');
    }

    public function openWorkOrders(): HasMany
    {
        return $this->hasMany(WorkOrder::class, 'property_id')
            ->where('status', WorkOrderStatus::Open);
    }

    protected static function idPrefix(): string
    {
        return 'P-';
    }

    protected static function idPadLength(): int
    {
        return 3;
    }
}
