<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Enums\FinancialOperation;
use App\Events\FinancialOperationCompleted;
use App\Models\Ledger;
use App\Models\Subledger;
use App\Models\User;
use App\ValueObjects\Money;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;

final class ProcessReversalJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public Subledger $subledger,
    ) {}

    public function handle(): void
    {
        DB::transaction(function (): void {
            $ledgerEntries = $this->subledger->ledgers;
            $userIds = $ledgerEntries->pluck('user_id')->unique()->toArray();
            sort($userIds);

            // Lock users
            $users = User::whereIn('id', $userIds)->lockForUpdate()->get()->keyBy('id');

            $reversalSubledger = Subledger::create([
                'type' => FinancialOperation::Reversal,
                'amount' => $this->subledger->amount,
                'metadata' => [
                    'original_subledger_id' => $this->subledger->id,
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

            FinancialOperationCompleted::dispatch($reversalSubledger);
        });
    }
}
