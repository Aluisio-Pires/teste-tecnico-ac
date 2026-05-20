<?php

declare(strict_types=1);

namespace Tests\Unit\ValueObjects;

use App\ValueObjects\Money;

test('it can be created from decimal', function (): void {
    $money = Money::fromDecimal(100.50);
    expect($money->microns)->toBe(100500000);
});

test('it can be created from microns', function (): void {
    $money = Money::fromMicrons(100500000);
    expect($money->microns)->toBe(100500000);
});

test('it can return decimal value', function (): void {
    $money = Money::fromMicrons(100500000);
    expect($money->toDecimal())->toBe(100.50);
});

test('it can be formatted', function (): void {
    $money = Money::fromDecimal(1234.56);
    expect($money->toFormatted())->toBe('1.234,56');
});

test('it can add money', function (): void {
    $a = Money::fromDecimal(100);
    $b = Money::fromDecimal(50.50);
    $result = $a->add($b);
    expect($result->toDecimal())->toBe(150.50);
});

test('it can subtract money', function (): void {
    $a = Money::fromDecimal(100);
    $b = Money::fromDecimal(40.25);
    $result = $a->subtract($b);
    expect($result->toDecimal())->toBe(59.75);
});

test('it can check if negative, positive or zero', function (): void {
    expect(Money::fromDecimal(-10)->isNegative())->toBeTrue()
        ->and(Money::fromDecimal(10)->isNegative())->toBeFalse()
        ->and(Money::fromDecimal(10)->isPositive())->toBeTrue()
        ->and(Money::fromDecimal(-10)->isPositive())->toBeFalse()
        ->and(Money::fromDecimal(0)->isZero())->toBeTrue()
        ->and(Money::fromDecimal(10)->isZero())->toBeFalse();
});

test('it can be serialized to json', function (): void {
    $money = Money::fromDecimal(100.50);
    expect(json_encode($money))->toBe('100.5');
});

test('it can be cast to string', function (): void {
    $money = Money::fromDecimal(100.50);
    expect((string) $money)->toBe('100.5');
});
