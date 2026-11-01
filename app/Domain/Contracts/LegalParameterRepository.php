<?php

namespace App\Domain\Contracts;

interface LegalParameterRepository
{
    public function value(string $key, string $asOfDate): string;
}
