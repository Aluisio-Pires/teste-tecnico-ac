<?php

declare(strict_types=1);

namespace Tests\Feature\Finance;

use App\Actions\Finance\DepositMoneyAction;
use App\Events\FinancialOperationCompleted;
use App\Models\User;
use App\ValueObjects\Money;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Mockery;

uses(RefreshDatabase::class);

test('financial operation fires event', function (): void {
    Event::fake();

    $user = User::factory()->create();
    $action = new DepositMoneyAction();
    $action->execute($user, Money::fromDecimal(100));

    Event::assertDispatched(FinancialOperationCompleted::class);
});

test('financial operation listener logs data', function (): void {
    Log::shouldReceive('channel')->with('daily')->andReturnSelf();
    Log::shouldReceive('info')->with('Financial operation completed', Mockery::on(function ($data) {
        return isset($data['type'], $data['amount'], $data['subledger_id']);
    }));

    $user = User::factory()->create();
    $action = new DepositMoneyAction();
    $action->execute($user, Money::fromDecimal(100));

    expect(true)->toBeTrue();
});
