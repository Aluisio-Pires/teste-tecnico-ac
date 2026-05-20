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

final class ReverseTransactionAction
{
    public function execute(Subledger $subledger): void
    {
        if ($subledger->type === FinancialOperation::Reversal) {
            throw new InvalidArgumentException('Cannot reverse a reversal.');
        }

        // Check if already reversed by checking if any reversal points to this subledger
        $alreadyReversed = Subledger::where('type', FinancialOperation::Reversal)
            ->whereJsonContains('metadata->original_subledger_id', $subledger->id)
            ->exists();

        if ($alreadyReversed) {
            throw new InvalidArgumentException('This transaction has already been reversed.');
        }

        DB::transaction(function () use ($subledger): void {
            $ledgerEntries = $subledger->ledgers;
            $userIds = $ledgerEntries->pluck('user_id')->unique()->toArray();
            sort($userIds);

            // Lock users
            $users = User::whereIn('id', $userIds)->lockForUpdate()->get()->keyBy('id');

            $reversalSubledger = Subledger::create([
                'type' => FinancialOperation::Reversal,
                'amount' => $subledger->amount,
                'metadata' => [
                    'original_subledger_id' => $subledger->id,
                ],
            ]);

            foreach ($ledgerEntries as $entry) {
                /** @var User $user */
                $user = $users->get($entry->user_id);
                $reverseAmount = Money::fromMicrons(-$entry->amount->microns);

                Ledger::create([
                    'subledger_id' => $reversalSubledger->id,
                    'user_id' => $user->id,
                    'amount' => $reverseAmount,
                    'balance_after' => $user->balance->add($reverseAmount),
                ]);
            }
        });
    }
}
