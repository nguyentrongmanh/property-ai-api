<?php

namespace App\Enums;

enum WorkOrderPriority: string
{
    case Low = 'low';
    case Medium = 'medium';
    case High = 'high';
    case Urgent = 'urgent';

    /**
     * Numeric weight used to sort work orders from most to least urgent.
     */
    public function weight(): int
    {
        return match ($this) {
            self::Urgent => 4,
            self::High => 3,
            self::Medium => 2,
            self::Low => 1,
        };
    }
}
