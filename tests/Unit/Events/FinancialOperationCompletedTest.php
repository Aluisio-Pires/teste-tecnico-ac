<?php

declare(strict_types=1);

namespace Tests\Unit\Events;

use App\Enums\FinancialOperation;
use App\Events\FinancialOperationCompleted;
use App\Models\Subledger;
use App\Models\User;
use App\ValueObjects\Money;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class FinancialOperationCompletedTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_has_correct_broadcast_data(): void
    {
        $subledger = Subledger::factory()->create([
            'type' => FinancialOperation::Deposit,
            'amount' => Money::fromDecimal(100),
        ]);

        $event = new FinancialOperationCompleted($subledger);

        $data = $event->broadcastWith();

        $this->assertEquals($subledger->id, $data['subledger_id']);
        $this->assertEquals('deposit', $data['type']);
        $this->assertEquals(100.0, $data['amount']);
    }

    public function test_it_broadcasts_on_correct_channels(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        $subledger = Subledger::factory()->create();

        $subledger->ledgers()->create([
            'user_id' => $user1->id,
            'amount' => Money::fromDecimal(50),
            'balance_after' => Money::fromDecimal(50),
        ]);

        $subledger->ledgers()->create([
            'user_id' => $user2->id,
            'amount' => Money::fromDecimal(-50),
            'balance_after' => Money::fromDecimal(150),
        ]);

        $event = new FinancialOperationCompleted($subledger);

        $channels = $event->broadcastOn();

        $this->assertCount(2, $channels);
        $this->assertInstanceOf(PrivateChannel::class, $channels[0]);
        $this->assertInstanceOf(PrivateChannel::class, $channels[1]);
        $this->assertEquals("private-App.Models.User.{$user1->id}", $channels[0]->name);
        $this->assertEquals("private-App.Models.User.{$user2->id}", $channels[1]->name);
    }

    public function test_it_has_correct_broadcast_name(): void
    {
        $subledger = Subledger::factory()->create();
        $event = new FinancialOperationCompleted($subledger);

        $this->assertEquals('financial-operation.completed', $event->broadcastAs());
    }
}
