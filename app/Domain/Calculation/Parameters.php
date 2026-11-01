<?php

namespace App\Domain\Calculation;

use App\Domain\Contracts\LegalParameterRepository;
use App\Domain\ValueObjects\Money;
use InvalidArgumentException;

final class Parameters
{
    public function __construct(
        private readonly LegalParameterRepository $repository,
        private readonly string $asOfDate,
    ) {}

    public function asOfDate(): string
    {
        return $this->asOfDate;
    }

    public function days(string $key): int
    {
        return (int) bcadd($this->numeric($key), '0', 0);
    }

    public function rate(string $key): Money
    {
        return Money::of($this->numeric($key));
    }

    public function money(string $key): Money
    {
        return Money::of($this->numeric($key));
    }

    /**
     * @return numeric-string
     */
    private function numeric(string $key): string
    {
        $value = $this->repository->value($key, $this->asOfDate);

        if (! is_numeric($value)) {
            throw new InvalidArgumentException("El parámetro {$key} no tiene un valor numérico.");
        }

        return $value;
    }
}
