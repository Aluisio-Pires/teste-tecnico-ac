<?php

declare(strict_types=1);

namespace App\Actions\Finance;

use App\Jobs\ProcessTransferJob;
use App\Models\User;
use App\ValueObjects\Money;
use InvalidArgumentException;

final class TransferMoneyAction
{
    public function execute(User $from, User $to, Money $amount): void
    {
        if ($from->id === $to->id) {
            throw new InvalidArgumentException('Cannot transfer money to the same account.');
        }

        $from->loadMissing('latestLedger');

        if ($from->balance->microns < $amount->microns) {
            throw new InvalidArgumentException('Insufficient balance.');
        }

        ProcessTransferJob::dispatch($from, $to, $amount->microns);
    }
}
