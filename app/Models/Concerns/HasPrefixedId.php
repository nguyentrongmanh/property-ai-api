<?php

namespace App\Models\Concerns;

use Illuminate\Support\Str;

/**
 * Generates human-readable, prefixed string primary keys such as "P-001" or "WO-1001".
 *
 * Models using this trait must define idPrefix() and may override idStartNumber()
 * and idPadLength() to control the numbering format.
 */
trait HasPrefixedId
{
    public static function bootHasPrefixedId(): void
    {
        static::creating(function (self $model): void {
            if (empty($model->getKey())) {
                $model->{$model->getKeyName()} = static::nextPrefixedId();
            }
        });
    }

    public function initializeHasPrefixedId(): void
    {
        $this->incrementing = false;
        $this->keyType = 'string';
    }

    abstract protected static function idPrefix(): string;

    protected static function idStartNumber(): int
    {
        return 1;
    }

    protected static function idPadLength(): int
    {
        return 0;
    }

    protected static function nextPrefixedId(): string
    {
        $latestId = static::query()
            ->where('id', 'like', static::idPrefix().'%')
            ->orderByRaw('LENGTH(id) DESC')
            ->orderByDesc('id')
            ->value('id');

        $number = $latestId === null
            ? static::idStartNumber()
            : (int) Str::after($latestId, static::idPrefix()) + 1;

        return static::idPrefix().Str::padLeft((string) $number, static::idPadLength(), '0');
    }
}
