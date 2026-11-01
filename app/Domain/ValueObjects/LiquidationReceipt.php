<?php

namespace App\Domain\ValueObjects;

final class LiquidationReceipt
{
    public function __construct(
        public readonly int $id,
        public readonly string $folio,
    ) {}

    public function __toString(): string
    {
        return $this->folio;
    }
}
