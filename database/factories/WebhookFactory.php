<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\User;
use App\Models\Webhook;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Webhook>
 */
final class WebhookFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'url' => $this->faker->url(),
            'event_type' => 'financial_operation.completed',
            'is_active' => true,
        ];
    }
}
