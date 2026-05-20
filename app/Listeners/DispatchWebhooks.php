<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\FinancialOperationCompleted;
use App\Jobs\SendWebhookJob;
use App\Models\Webhook;

final class DispatchWebhooks
{
    public function handle(FinancialOperationCompleted $event): void
    {
        $subledger = $event->subledger;

        // Find users involved to send webhooks
        $userIds = $subledger->ledgers->pluck('user_id')->unique();

        $webhooks = Webhook::whereIn('user_id', $userIds)
            ->where('event_type', 'financial_operation.completed')
            ->where('is_active', true)
            ->get();

        foreach ($webhooks as $webhook) {
            SendWebhookJob::dispatch($webhook, [
                'subledger_id' => $subledger->id,
                'type' => $subledger->type->value,
                'amount' => $subledger->amount->toDecimal(),
                'metadata' => $subledger->metadata,
            ]);
        }
    }
}
