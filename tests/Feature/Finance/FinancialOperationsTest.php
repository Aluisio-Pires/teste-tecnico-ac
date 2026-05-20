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
    $depositAction->execute($user, $amount);
    $ledger = $user->refresh()->ledgers()->latest()->first();
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

test('cannot transfer to same account', function (): void {
    $user = User::factory()->create();
    $transferAmount = Money::fromDecimal(100);

    expect(fn () => (new TransferMoneyAction())->execute($user, $user, $transferAmount))
        ->toThrow(InvalidArgumentException::class, 'Cannot transfer money to the same account.');
});

test('cannot reverse a reversal', function (): void {
    $user = User::factory()->create();
    $amount = Money::fromDecimal(100);

    $depositAction = new DepositMoneyAction();
    $depositAction->execute($user, $amount);
    $ledger = $user->refresh()->ledgers()->latest()->first();
    $subledger = $ledger->subledger;

    (new ReverseTransactionAction())->execute($subledger);

    $reversalSubledger = Subledger::where('type', FinancialOperation::Reversal)->first();

    expect(fn () => (new ReverseTransactionAction())->execute($reversalSubledger))
        ->toThrow(InvalidArgumentException::class, 'Cannot reverse a reversal.');
});

test('cannot reverse same transaction twice', function (): void {
    $user = User::factory()->create();
    $amount = Money::fromDecimal(100);

    $depositAction = new DepositMoneyAction();
    $depositAction->execute($user, $amount);
    $ledger = $user->refresh()->ledgers()->latest()->first();
    $subledger = $ledger->subledger;

    (new ReverseTransactionAction())->execute($subledger);

    expect(fn () => (new ReverseTransactionAction())->execute($subledger))
        ->toThrow(InvalidArgumentException::class, 'This transaction has already been reversed.');
});

test('user cannot reverse transaction they did not originate', function (): void {
    $from = User::factory()->create();
    $to = User::factory()->create();

    (new DepositMoneyAction())->execute($from, Money::fromDecimal(100));
    (new TransferMoneyAction())->execute($from, $to, Money::fromDecimal(50));

    $subledger = Subledger::where('type', FinancialOperation::Transfer)->first();

    $this->actingAs($to);
    $response = $this->post(route('finance.reverse', $subledger));

    $response->assertForbidden();

    $from->refresh();
    $to->refresh();

    expect($from->balance->toDecimal())->toBe(50.0)
        ->and($to->balance->toDecimal())->toBe(50.0);
});

test('policy returns false for reversal type', function (): void {
    $user = User::factory()->create();
    $subledger = Subledger::factory()->create(['type' => FinancialOperation::Reversal]);

    $this->actingAs($user);
    $this->post(route('finance.reverse', $subledger))->assertForbidden();
});

test('finance pages are accessible', function (): void {
    $user = User::factory()->create();
    $this->actingAs($user);

    $this->get(route('dashboard'))->assertOk();
    $this->get(route('finance.history'))->assertOk();
    $this->get(route('finance.show-deposit'))->assertOk();
    $this->get(route('finance.show-transfer'))->assertOk();
});

test('user can deposit money via web request', function (): void {
    $user = User::factory()->create();
    $this->actingAs($user);

    $response = $this->post(route('finance.deposit'), [
        'amount' => '150.25',
    ]);

    $response->assertRedirect();
    expect($user->refresh()->balance->toDecimal())->toBe(150.25);
});

test('user can transfer money via web request', function (): void {
    $from = User::factory()->create();
    $to = User::factory()->create();
    (new DepositMoneyAction())->execute($from, Money::fromDecimal(500));

    $this->actingAs($from);

    $response = $this->post(route('finance.transfer'), [
        'email' => $to->email,
        'amount' => '200.50',
    ]);

    $response->assertRedirect();
    expect($from->refresh()->balance->toDecimal())->toBe(299.50)
        ->and($to->refresh()->balance->toDecimal())->toBe(200.50);
});

test('cannot transfer more than balance via web request', function (): void {
    $from = User::factory()->create();
    $to = User::factory()->create();
    (new DepositMoneyAction())->execute($from, Money::fromDecimal(50));

    $this->actingAs($from);

    $response = $this->post(route('finance.transfer'), [
        'email' => $to->email,
        'amount' => '100',
    ]);

    $response->assertSessionHasErrors(['amount' => 'Insufficient balance.']);
});

test('cannot transfer with invalid data', function (): void {
    $user = User::factory()->create();
    $this->actingAs($user);

    $this->post(route('finance.transfer'), [
        'email' => 'nonexistent@example.com',
        'amount' => '100',
    ])->assertSessionHasErrors(['email']);

    $this->post(route('finance.transfer'), [
        'email' => $user->email,
        'amount' => '100',
    ])->assertSessionHasErrors(['email']);

    $this->post(route('finance.transfer'), [
        'email' => 'other@example.com',
        'amount' => '-10',
    ])->assertSessionHasErrors(['amount']);
});

test('cannot deposit invalid amount', function (): void {
    $user = User::factory()->create();
    $this->actingAs($user);

    $this->post(route('finance.deposit'), [
        'amount' => '-50',
    ])->assertSessionHasErrors(['amount']);

    $this->post(route('finance.deposit'), [
        'amount' => 'abc',
    ])->assertSessionHasErrors(['amount']);
});

test('user can reverse their own transfer', function (): void {
    $from = User::factory()->create();
    $to = User::factory()->create();
    (new DepositMoneyAction())->execute($from, Money::fromDecimal(500));
    (new TransferMoneyAction())->execute($from, $to, Money::fromDecimal(200));

    $subledger = Subledger::where('type', FinancialOperation::Transfer)->first();

    $this->actingAs($from);
    $response = $this->post(route('finance.reverse', $subledger));

    $response->assertRedirect();
    expect($from->refresh()->balance->toDecimal())->toBe(500.0)
        ->and($to->refresh()->balance->toDecimal())->toBe(0.0);
});

test('user can reverse their own deposit', function (): void {
    $user = User::factory()->create();
    (new DepositMoneyAction())->execute($user, Money::fromDecimal(500));
    $subledger = $user->refresh()->ledgers()->latest()->first()->subledger;

    $this->actingAs($user);
    $response = $this->post(route('finance.reverse', $subledger));

    $response->assertRedirect();
    expect($user->refresh()->balance->toDecimal())->toBe(0.0);
});

test('reversal catch block in controller', function (): void {
    $user = User::factory()->create();
    (new DepositMoneyAction())->execute($user, Money::fromDecimal(500));
    $subledger = $user->refresh()->ledgers()->latest()->first()->subledger;

    // Simulate already reversed but bypass policy if possible or just use a mock
    (new ReverseTransactionAction())->execute($subledger);

    $this->actingAs($user);
    $response = $this->post(route('finance.reverse', $subledger));

    $response->assertSessionHasErrors(['error' => 'This transaction has already been reversed.']);
});

test('transfer money action inner balance check (race condition)', function (): void {
    $from = User::factory()->create();
    (new DepositMoneyAction())->execute($from, Money::fromDecimal(100));
    $to = User::factory()->create();

    $action = new TransferMoneyAction();

    // Ensure the first check passes by having the relation already loaded
    $from->load('latestLedger');
    expect($from->balance->toDecimal())->toBe(100.0);

    // Manually empty the balance in the database
    $from->ledgers()->delete();

    // Now $from->balance still returns 100 (cached in relation)
    // but the internal re-query in TransferMoneyAction will find 0 balance
    expect(fn () => $action->execute($from, $to, Money::fromDecimal(50)))
        ->toThrow(InvalidArgumentException::class, 'Insufficient balance.');
});
