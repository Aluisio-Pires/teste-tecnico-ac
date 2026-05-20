<?php

declare(strict_types=1);

namespace App\Models;

use App\Casts\MoneyCast;
use App\Enums\FinancialOperation;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property-read int $id
 * @property FinancialOperation $type
 * @property \App\ValueObjects\Money $amount
 * @property array<string, mixed>|null $metadata
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Ledger> $ledgers
 */
final class Subledger extends Model
{
    protected $casts = [
        'type' => FinancialOperation::class,
        'amount' => MoneyCast::class,
        'metadata' => 'array',
    ];

    protected $fillable = [
        'type',
        'amount',
        'metadata',
    ];

    /**
     * @return HasMany<Ledger, $this>
     */
    public function ledgers(): HasMany
    {
        return $this->hasMany(Ledger::class);
    }
}
