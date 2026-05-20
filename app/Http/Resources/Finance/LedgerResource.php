<?php

declare(strict_types=1);

namespace App\Http\Resources\Finance;

use App\Enums\FinancialOperation;
use App\Models\Ledger;
use App\Models\Subledger;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Ledger
 */
final class LedgerResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'amount' => $this->amount->toDecimal(),
            'balance_after' => $this->balance_after->toDecimal(),
            'created_at' => $this->created_at?->toIso8601String(),
            'subledger' => [
                'id' => $this->subledger_id,
                'type' => $this->subledger->type->value,
                'metadata' => $this->subledger->metadata,
                'was_reversed' => $this->wasReversed(),
            ],
        ];
    }

    private function wasReversed(): bool
    {
        if ($this->subledger->type === FinancialOperation::Reversal) {
            return false;
        }

        return Subledger::query()->where('type', FinancialOperation::Reversal)
            ->whereJsonContains('metadata->original_subledger_id', $this->subledger_id)
            ->exists();
    }
}
