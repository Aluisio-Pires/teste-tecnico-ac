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

final class ProcessDepositJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public User $user,
        public int $amountMicrons,
    ) {}

    public function handle(): void
    {
        $amount = Money::fromMicrons($this->amountMicrons);

        DB::transaction(function () use ($amount): void {
            // Lock user for update to prevent concurrent balance changes
            $user = User::with('latestLedger')->where('id', $this->user->id)->lockForUpdate()->firstOrFail();

            $subledger = Subledger::create([
                'type' => FinancialOperation::Deposit,
                'amount' => $amount,
                'metadata' => [
                    'user_id' => $user->id,
                ],
            ]);

            Ledger::create([
                'subledger_id' => $subledger->id,
                'user_id' => $user->id,
                'amount' => $amount,
                'balance_after' => $user->balance->add($amount),
            ]);

            FinancialOperationCompleted::dispatch($subledger);
        });
    }
}
