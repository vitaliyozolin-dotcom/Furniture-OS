<?php

declare(strict_types=1);

namespace App\Calculation\Domain;

/** Exact decimal arithmetic: no binary floating point operations on money. */
final readonly class Decimal
{
    public function __construct(public string $value)
    {
    }
    public function plus(self|string|int $other): self
    {
        return new self(bcadd($this->value, $other instanceof self ? $other->value : (string) $other, 18));
    }
    public function times(string|int $other): self
    {
        return new self(bcmul($this->value, (string) $other, 18));
    }
    public function dividedBy(string|int $other): self
    {
        return new self(bcdiv($this->value, (string) $other, 18));
    }
}
