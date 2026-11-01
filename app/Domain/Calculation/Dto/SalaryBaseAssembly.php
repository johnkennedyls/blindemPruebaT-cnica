<?php

namespace App\Domain\Calculation\Dto;

final class SalaryBaseAssembly
{
    /**
     * @param  array<int, string>  $notices
     */
    public function __construct(
        public readonly SalaryBases $bases,
        public readonly array $notices,
    ) {}
}
