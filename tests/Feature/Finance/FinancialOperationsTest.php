<?php

declare(strict_types=1);

use App\Actions\Finance\DepositMoneyAction;
use App\Actions\Finance\ReverseTransactionAction;
use App\Actions\Finance\TransferMoneyAction;
use App\Enums\FinancialOperation;
use App\Models\Subledger;
use App\Models\User;
use App\ValueObjects\Money;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('user can deposit money', function (): void {
    $user = User::factory()->create();
    $amount = Money::fromDecimal(100.50);

    $action = new DepositMoneyAction();
    $action->execute($user, $amount);

    $user->refresh();

    expect($user->balance->toDecimal())->toBe(100.50)
        ->and($user->ledgers)->toHaveCount(1)
        ->and($user->ledgers->first()->amount->toDecimal())->toBe(100.50)
        ->and($user->ledgers->first()->balance_after->toDecimal())->toBe(100.50);
});

test('user can transfer money to another user', function (): void {
    $from = User::factory()->create();
    $to = User::factory()->create();

    // Initial deposit
    (new DepositMoneyAction())->execute($from, Money::fromDecimal(200));

    $transferAmount = Money::fromDecimal(150.75);
    (new TransferMoneyAction())->execute($from, $to, $transferAmount);

    $from->refresh();
    $to->refresh();

    expect($from->balance->toDecimal())->toBe(49.25)
        ->and($to->balance->toDecimal())->toBe(150.75);

    $subledger = Subledger::where('type', FinancialOperation::Transfer)->first();
    expect($subledger->ledgers)->toHaveCount(2);
});

test('transaction can be reversed', function (): void {
    $user = User::factory()->create();
    $amount = Money::fromDecimal(100);

    $depositAction = new DepositMoneyAction();
    $ledger = $depositAction->execute($user, $amount);
    $subledger = $ledger->subledger;

    expect($user->refresh()->balance->toDecimal())->toBe(100.0);

    (new ReverseTransactionAction())->execute($subledger);

    expect($user->refresh()->balance->toDecimal())->toBe(0.0)
        ->and($user->ledgers)->toHaveCount(2);
});

test('cannot transfer more than balance', function (): void {
    $from = User::factory()->create();
    $to = User::factory()->create();

    (new DepositMoneyAction())->execute($from, Money::fromDecimal(50));

    $transferAmount = Money::fromDecimal(100);

    expect(fn () => (new TransferMoneyAction())->execute($from, $to, $transferAmount))
        ->toThrow(InvalidArgumentException::class, 'Insufficient balance.');
});

test('user cannot reverse transaction they did not originate', function (): void {
    $from = User::factory()->create();
    $to = User::factory()->create();

    (new DepositMoneyAction())->execute($from, Money::fromDecimal(100));
    (new TransferMoneyAction())->execute($from, $to, Money::fromDecimal(50));

    $subledger = Subledger::where('type', FinancialOperation::Transfer)->first();

    $this->actingAs($to);
    $response = $this->post(route('finance.reverse', $subledger));

    $response->assertSessionHasErrors(['error' => 'You can only reverse transactions you originated.']);

    $from->refresh();
    $to->refresh();

    expect($from->balance->toDecimal())->toBe(50.0)
        ->and($to->balance->toDecimal())->toBe(50.0);
});
