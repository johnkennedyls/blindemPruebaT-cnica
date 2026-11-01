<?php

namespace App\Domain\Exceptions;

use RuntimeException;

class ContractNotLiquidatable extends RuntimeException
{
    public static function missingType(): self
    {
        return new self('El contrato no tiene un tipo de contrato registrado; no se puede determinar la modalidad.');
    }
}
