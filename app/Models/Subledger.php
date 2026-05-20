<?php

declare(strict_types=1);

namespace App\Models;

use App\Casts\MoneyCast;
use App\Enums\FinancialOperation;
use App\ValueObjects\Money;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property-read int $id
 * @property-read FinancialOperation $type
 * @property-read Money $amount
 * @property-read array<string, mixed>|null $metadata
 * @property bool|null $was_reversed
 * @property-read Carbon|null $created_at
 * @property-read Carbon|null $updated_at
 * @property-read Collection<int, Ledger> $ledgers
 */
#[Fillable(['type', 'amount', 'metadata'])]
final class Subledger extends Model
{
    protected $casts = [
        'type' => FinancialOperation::class,
        'amount' => MoneyCast::class,
        'metadata' => 'array',
    ];

    /**
     * @return HasMany<Ledger, $this>
     */
    public function ledgers(): HasMany
    {
        return $this->hasMany(Ledger::class);
    }
}
