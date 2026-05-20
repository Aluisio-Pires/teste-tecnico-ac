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
use InvalidArgumentException;

final class ProcessTransferJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public User $from,
        public User $to,
        public int $amountMicrons,
    ) {}

    public function handle(): void
    {
        $amount = Money::fromMicrons($this->amountMicrons);

        DB::transaction(function () use ($amount): void {
            // Lock users in consistent order to prevent deadlocks
            $userIds = [$this->from->id, $this->to->id];
            sort($userIds);

            $users = User::whereIn('id', $userIds)->lockForUpdate()->get()->keyBy('id');

            /** @var User $from */
            $from = $users->get($this->from->id);
            /** @var User $to */
            $to = $users->get($this->to->id);

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

            FinancialOperationCompleted::dispatch($subledger);
        });
    }
}
