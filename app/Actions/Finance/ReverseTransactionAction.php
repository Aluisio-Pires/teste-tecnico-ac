<?php

declare(strict_types=1);

namespace App\Actions\Finance;

use App\Enums\FinancialOperation;
use App\Jobs\ProcessReversalJob;
use App\Models\Subledger;
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

        ProcessReversalJob::dispatch($subledger);
    }
}
