<?php

declare(strict_types=1);

namespace App\Actions\Finance;

use App\Enums\FinancialOperation;
use App\Models\Ledger;
use App\Models\Subledger;
use App\Models\User;
use App\ValueObjects\Money;
use Illuminate\Support\Facades\DB;

final class DepositMoneyAction
{
    public function execute(User $user, Money $amount): Ledger
    {
        return DB::transaction(function () use ($user, $amount) {
            // Lock user for update to prevent concurrent balance changes
            $user = User::where('id', $user->id)->lockForUpdate()->firstOrFail();

            $subledger = Subledger::create([
                'type' => FinancialOperation::Deposit,
                'amount' => $amount,
                'metadata' => [
                    'user_id' => $user->id,
                ],
            ]);

            return Ledger::create([
                'subledger_id' => $subledger->id,
                'user_id' => $user->id,
                'amount' => $amount,
                'balance_after' => $user->balance->add($amount),
            ]);
        });
    }
}
