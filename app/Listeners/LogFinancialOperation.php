<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\FinancialOperationCompleted;
use Illuminate\Support\Facades\Log;

final class LogFinancialOperation
{
    public function handle(FinancialOperationCompleted $event): void
    {
        $subledger = $event->subledger;

        Log::channel('daily')->info('Financial operation completed', [
            'type' => $subledger->type->value,
            'amount' => $subledger->amount->toDecimal(),
            'subledger_id' => $subledger->id,
            'metadata' => $subledger->metadata,
            'timestamp' => now()->toIso8601String(),
        ]);
    }
}
