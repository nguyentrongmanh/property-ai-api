<?php

namespace App\Models;

use App\Enums\WorkOrderCategory;
use App\Enums\WorkOrderPriority;
use App\Enums\WorkOrderStatus;
use App\Models\Concerns\HasPrefixedId;
use Database\Factories\WorkOrderFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorkOrder extends Model
{
    /** @use HasFactory<WorkOrderFactory> */
    use HasFactory;

    use HasPrefixedId;

    protected $fillable = [
        'id',
        'property_id',
        'source_text',
        'requester_email',
        'title',
        'category',
        'priority',
        'summary',
        'status',
    ];

    protected $attributes = [
        'status' => 'open',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'category' => WorkOrderCategory::class,
            'priority' => WorkOrderPriority::class,
            'status' => WorkOrderStatus::class,
        ];
    }

    public function building(): BelongsTo
    {
        return $this->belongsTo(Building::class, 'property_id');
    }

    public function scopeOpen(Builder $query): Builder
    {
        return $query->where('status', WorkOrderStatus::Open);
    }

    protected static function idPrefix(): string
    {
        return 'WO-';
    }

    protected static function idStartNumber(): int
    {
        return 1001;
    }
}
