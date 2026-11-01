<?php

namespace App\Domain\Exceptions;

use RuntimeException;

class ParameterNotConfigured extends RuntimeException
{
    public static function missing(string $key, string $asOfDate): self
    {
        return new self("No hay parámetro configurado para {$key} en la fecha {$asOfDate}.");
    }
}
