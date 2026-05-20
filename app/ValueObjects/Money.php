<?php

declare(strict_types=1);

namespace App\ValueObjects;

use JsonSerializable;
use Stringable;

final readonly class Money implements JsonSerializable, Stringable
{
    private const int PRECISION = 1000000;

    public function __construct(
        public int $microns
    ) {}

    public function __toString(): string
    {
        return (string) $this->toDecimal();
    }

    public static function fromDecimal(float|int|string $amount): self
    {
        return new self((int) round((float) $amount * self::PRECISION));
    }

    public static function fromMicrons(int $microns): self
    {
        return new self($microns);
    }

    public function toDecimal(): float
    {
        return $this->microns / self::PRECISION;
    }

    public function toFormatted(): string
    {
        return number_format($this->toDecimal(), 2, ',', '.');
    }

    public function add(self $other): self
    {
        return new self($this->microns + $other->microns);
    }

    public function subtract(self $other): self
    {
        return new self($this->microns - $other->microns);
    }

    public function isNegative(): bool
    {
        return $this->microns < 0;
    }

    public function isPositive(): bool
    {
        return $this->microns > 0;
    }

    public function isZero(): bool
    {
        return $this->microns === 0;
    }

    public function jsonSerialize(): float
    {
        return $this->toDecimal();
    }
}
