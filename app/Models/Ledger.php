<?php

declare(strict_types=1);

namespace App\Models;

use App\Casts\MoneyCast;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property-read int $id
 * @property int $subledger_id
 * @property int $user_id
 * @property \App\ValueObjects\Money $amount
 * @property \App\ValueObjects\Money $balance_after
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read Subledger $subledger
 * @property-read User $user
 */
final class Ledger extends Model
{
    protected $casts = [
        'amount' => MoneyCast::class,
        'balance_after' => MoneyCast::class,
    ];

    protected $fillable = [
        'subledger_id',
        'user_id',
        'amount',
        'balance_after',
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
