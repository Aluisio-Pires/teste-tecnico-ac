<?php

declare(strict_types=1);

namespace App\Actions\Finance;

use App\Enums\FinancialOperation;
use App\Models\Ledger;
use App\Models\Subledger;
use App\Models\User;
use App\ValueObjects\Money;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

final class TransferMoneyAction
{
    public function execute(User $from, User $to, Money $amount): void
    {
        if ($from->id === $to->id) {
            throw new InvalidArgumentException('Cannot transfer money to the same account.');
        }

        if ($from->balance->microns < $amount->microns) {
            throw new InvalidArgumentException('Insufficient balance.');
        }

        DB::transaction(function () use ($from, $to, $amount): void {
            // Lock users in consistent order to prevent deadlocks
            $userIds = [$from->id, $to->id];
            sort($userIds);

            $users = User::whereIn('id', $userIds)->lockForUpdate()->get()->keyBy('id');

            /** @var User $from */
            $from = $users->get($from->id);
            /** @var User $to */
            $to = $users->get($to->id);

            // Double check balance after lock
            if ($from->balance->microns < $amount->microns) {
                throw new InvalidArgumentException('Insufficient balance.');
            }

            $subledger = Subledger::create([
                'type' => FinancialOperation::Transfer,
                'amount' => $amount,
                'metadata' => [
                    'from_user_id' => $from->id,
                    'from_user_email' => $from->email,
                    'to_user_id' => $to->id,
                    'to_user_email' => $to->email,
                ],
            ]);

            // Debit from
            Ledger::create([
                'subledger_id' => $subledger->id,
                'user_id' => $from->id,
                'amount' => Money::fromMicrons(-$amount->microns),
                'balance_after' => $from->balance->subtract($amount),
            ]);

            // Credit to
            Ledger::create([
                'subledger_id' => $subledger->id,
                'user_id' => $to->id,
                'amount' => $amount,
                'balance_after' => $to->balance->add($amount),
            ]);
        });
    }
}
