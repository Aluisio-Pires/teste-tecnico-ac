<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\FinancialOperation;
use App\Models\Subledger;
use App\ValueObjects\Money;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Subledger>
 */
final class SubledgerFactory extends Factory
{
    /**
     * @var class-string<Subledger>
     */
    protected $model = Subledger::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'type' => FinancialOperation::Deposit,
            'amount' => Money::fromDecimal($this->faker->randomFloat(2, 10, 1000)),
            'metadata' => [],
        ];
    }

    public function transfer(): self
    {
        return $this->state(fn (array $attributes) => [
            'type' => FinancialOperation::Transfer,
        ]);
    }

    public function reversal(): self
    {
        return $this->state(fn (array $attributes) => [
            'type' => FinancialOperation::Reversal,
        ]);
    }
}
