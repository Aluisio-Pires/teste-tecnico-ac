<?php

declare(strict_types=1);

namespace Tests\Unit\Casts;

use App\Casts\MoneyCast;
use App\Models\User;
use App\ValueObjects\Money;
use InvalidArgumentException;

beforeEach(function (): void {
    $this->cast = new MoneyCast();
    $this->model = new User();
});

test('it gets money object from microns integer', function (): void {
    $money = $this->cast->get($this->model, 'balance', 100500000, []);
    expect($money)->toBeInstanceOf(Money::class)
        ->and($money->toDecimal())->toBe(100.50);
});

test('it gets null when value is null', function (): void {
    expect($this->cast->get($this->model, 'balance', null, []))->toBeNull();
});

test('it gets null when value is not numeric', function (): void {
    expect($this->cast->get($this->model, 'balance', 'abc', []))->toBeNull();
});

test('it sets microns from money object', function (): void {
    $money = Money::fromDecimal(100.50);
    $value = $this->cast->set($this->model, 'balance', $money, []);
    expect($value)->toBe(100500000);
});

test('it sets microns from integer', function (): void {
    $value = $this->cast->set($this->model, 'balance', 100500000, []);
    expect($value)->toBe(100500000);
});

test('it sets microns from numeric string', function (): void {
    $value = $this->cast->set($this->model, 'balance', '100.50', []);
    expect($value)->toBe(100500000);
});

test('it sets null when setting null', function (): void {
    expect($this->cast->set($this->model, 'balance', null, []))->toBeNull();
});

test('it throws exception for invalid value', function (): void {
    $this->cast->set($this->model, 'balance', [], []);
})->throws(InvalidArgumentException::class);
