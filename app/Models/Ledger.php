<?php

declare(strict_types=1);

namespace App\Models;

use App\Casts\MoneyCast;
use App\ValueObjects\Money;
use Database\Factories\LedgerFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property-read int $id
 * @property-read int $subledger_id
 * @property-read int $user_id
 * @property-read Money $amount
 * @property-read Money $balance_after
 * @property-read Carbon|null $created_at
 * @property-read Carbon|null $updated_at
 * @property-read Subledger $subledger
 * @property-read User $user
 */
#[Fillable(['subledger_id', 'user_id', 'amount', 'balance_after'])]
final class Ledger extends Model
{
    /** @use HasFactory<LedgerFactory> */
    use HasFactory;

    protected $casts = [
        'amount' => MoneyCast::class,
        'balance_after' => MoneyCast::class,
    ];

    /**
     * @return BelongsTo<Subledger, $this>
     */
    public function subledger(): BelongsTo
    {
        return $this->belongsTo(Subledger::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
