<?php

namespace App\Domain\ValueObjects;

use InvalidArgumentException;

final class Money
{
    private const MULTIPLY_SCALE = 12;

    private const DIVIDE_SCALE = 12;

    private function __construct(private readonly string $amount)
    {
    }

    public static function of(mixed $value): self
    {
        if (is_float($value)) {
            throw new InvalidArgumentException(
                'El dinero no se construye desde punto flotante; use el valor de texto que entrega la base de datos.'
            );
        }

        if (! is_string($value) && ! is_int($value)) {
            throw new InvalidArgumentException('El dinero se construye solo desde una cadena o un entero.');
        }

        if (! is_numeric($value)) {
            throw new InvalidArgumentException("Valor monetario inválido: {$value}");
        }

        return new self(bcadd((string) $value, '0', self::DIVIDE_SCALE));
    }

    public static function zero(): self
    {
        return new self(bcadd('0', '0', self::DIVIDE_SCALE));
    }

    public function times(string|int $factor): self
    {
        return new self(bcmul($this->amount, (string) $factor, self::MULTIPLY_SCALE));
    }

    public function dividedBy(string|int $divisor): self
    {
        if (bccomp((string) $divisor, '0', 0) === 0) {
            throw new InvalidArgumentException('División de dinero por cero.');
        }

        return new self(bcdiv($this->amount, (string) $divisor, self::DIVIDE_SCALE));
    }

    public function plus(self $other): self
    {
        return new self(bcadd($this->amount, $other->amount, self::DIVIDE_SCALE));
    }

    public function minus(self $other): self
    {
        return new self(bcsub($this->amount, $other->amount, self::DIVIDE_SCALE));
    }

    public function rounded(): self
    {
        $negative = bccomp($this->amount, '0', self::DIVIDE_SCALE) < 0;
        $absolute = $negative ? bcmul($this->amount, '-1', self::DIVIDE_SCALE) : $this->amount;

        $whole = bcadd($absolute, '0', 0);
        $fraction = bcsub($absolute, $whole, self::DIVIDE_SCALE);

        if (bccomp($fraction, '0.5', self::DIVIDE_SCALE) >= 0) {
            $whole = bcadd($whole, '1', 0);
        }

        return new self(($negative ? '-' : '').$whole);
    }

    public function negative(): self
    {
        return $this->times('-1');
    }

    public function isZero(): bool
    {
        return bccomp($this->amount, '0', self::DIVIDE_SCALE) === 0;
    }

    public function isNegative(): bool
    {
        return bccomp($this->amount, '0', self::DIVIDE_SCALE) < 0;
    }

    public function atLeast(self $other): bool
    {
        return bccomp($this->amount, $other->amount, self::DIVIDE_SCALE) >= 0;
    }

    public function atMost(self $other): bool
    {
        return bccomp($this->amount, $other->amount, self::DIVIDE_SCALE) <= 0;
    }

    public function equals(self $other): bool
    {
        return $this->rounded()->amount === $other->rounded()->amount;
    }

    public function amount(): string
    {
        return $this->rounded()->amount;
    }

    public function exactAmount(): string
    {
        return $this->amount;
    }

    public function __toString(): string
    {
        return $this->amount();
    }
}
