<?php

declare(strict_types=1);

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\ValueObjects\Money;
use Carbon\Carbon;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Fortify\Contracts\PasskeyUser;
use Laravel\Fortify\PasskeyAuthenticatable;
use Laravel\Fortify\TwoFactorAuthenticatable;

/**
 * @property-read int $id
 * @property-read string $name
 * @property-read string $email
 * @property-read string $password
 * @property-read string $two_factor_secret
 * @property-read string $two_factor_recovery_codes
 * @property-read string $remember_token
 * @property-read Carbon $created_at
 * @property-read Carbon $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Ledger> $ledgers
 * @property-read Ledger|null $latestLedger
 * @property-read Money $balance
 */
#[Fillable(['name', 'email', 'password'])]
#[Hidden(['password', 'two_factor_secret', 'two_factor_recovery_codes', 'remember_token'])]
final class User extends Authenticatable implements PasskeyUser
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, PasskeyAuthenticatable, TwoFactorAuthenticatable;

    protected $appends = ['balance'];

    /**
     * @return HasMany<Ledger, $this>
     */
    public function ledgers(): HasMany
    {
        return $this->hasMany(Ledger::class);
    }

    /**
     * @return HasOne<Ledger, $this>
     */
    public function latestLedger(): HasOne
    {
        return $this->hasOne(Ledger::class)->latestOfMany();
    }

    public function getBalanceAttribute(): Money
    {
        $latest = $this->latestLedger;

        return $latest ? $latest->balance_after : Money::fromMicrons(0);
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'two_factor_confirmed_at' => 'datetime',
        ];
    }
}
