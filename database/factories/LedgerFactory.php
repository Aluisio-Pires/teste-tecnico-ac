<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Ledger;
use App\Models\Subledger;
use App\Models\User;
use App\ValueObjects\Money;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Ledger>
 */
final class LedgerFactory extends Factory
{
    /**
     * @var class-string<Ledger>
     */
    protected $model = Ledger::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'subledger_id' => Subledger::factory(),
            'user_id' => User::factory(),
            'amount' => Money::fromDecimal($this->faker->randomFloat(2, 10, 1000)),
            'balance_after' => Money::fromDecimal($this->faker->randomFloat(2, 1000, 5000)),
        ];
    }
}
