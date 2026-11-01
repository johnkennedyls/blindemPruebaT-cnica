<?php

namespace App\Domain\Calculation\Dto;

final class DayCounts
{
    public function __construct(
        public readonly int $commercialYear,
        public readonly int $commercialSemester,
        public readonly int $naturalTotal,
        public readonly int $cesantias,
        public readonly int $prima,
        public readonly int $vacaciones,
        public readonly int $pendingSalary,
    ) {}
}
