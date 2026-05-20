<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\Subledger;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class FinancialOperationCompleted implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Subledger $subledger,
    ) {}

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return $this->subledger->ledgers->map(function ($ledger) {
            return new PrivateChannel("App.Models.User.{$ledger->user_id}");
        })->toArray();
    }

    /**
     * The name of the event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'financial-operation.completed';
    }

    /**
     * Get the data to broadcast.
     *
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'subledger_id' => $this->subledger->id,
            'type' => $this->subledger->type->value,
            'amount' => $this->subledger->amount->toDecimal(),
        ];
    }
}
