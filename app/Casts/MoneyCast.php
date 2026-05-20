<?php

declare(strict_types=1);

namespace App\Casts;

use App\ValueObjects\Money;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;

/**
 * @implements CastsAttributes<Money, Money|int|float|string>
 */
final class MoneyCast implements CastsAttributes
{
    /**
     * @param  array<string, mixed>  $attributes
     */
    public function get(Model $model, string $key, mixed $value, array $attributes): ?Money
    {
        if ($value === null) {
            return null;
        }

        if (!is_numeric($value)) {
            return null;
        }

        return Money::fromMicrons((int) $value);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function set(Model $model, string $key, mixed $value, array $attributes): mixed
    {
        if ($value === null) {
            return null;
        }

        if ($value instanceof Money) {
            return $value->microns;
        }

        if (is_int($value)) {
            return $value;
        }

        if (is_numeric($value)) {
            return Money::fromDecimal((float) $value)->microns;
        }

        throw new InvalidArgumentException('The given value is not a valid Money instance or numeric.');
    }
}
