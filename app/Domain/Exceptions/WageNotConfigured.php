<?php

namespace App\Domain\Exceptions;

use RuntimeException;

class WageNotConfigured extends RuntimeException
{
    public static function missingYear(int $year): self
    {
        return new self("No existe salario mínimo registrado para el año {$year}.");
    }

    public static function missingDate(string $date): self
    {
        return new self("Ningún salario mínimo registrado cubre la fecha {$date}.");
    }
}
