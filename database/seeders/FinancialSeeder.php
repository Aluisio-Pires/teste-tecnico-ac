<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Actions\Finance\DepositMoneyAction;
use App\Actions\Finance\TransferMoneyAction;
use App\Models\User;
use App\ValueObjects\Money;
use Illuminate\Database\Seeder;

final class FinancialSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $depositAction = new DepositMoneyAction();
        $transferAction = new TransferMoneyAction();

        // Create some users
        $users = User::factory(10)->create();

        foreach ($users as $user) {
            // Initial deposit for everyone
            $depositAction->execute($user, Money::fromDecimal(rand(500, 5000)));

            // Some random transfers
            $otherUsers = $users->except($user->id)->random(rand(1, 3));
            foreach ($otherUsers as $recipient) {
                $amount = rand(50, 200);
                if ($user->balance->toDecimal() > $amount) {
                    $transferAction->execute($user, $recipient, Money::fromDecimal($amount));
                }
            }
        }

        // Create a test user with a known email
        if (!User::where('email', 'test@example.com')->exists()) {
            $testUser = User::factory()->create([
                'name' => 'Test User',
                'email' => 'test@example.com',
            ]);
            $depositAction->execute($testUser, Money::fromDecimal(1000));
        }
    }
}
