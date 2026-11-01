<?php

namespace App\Domain\Contracts;

use App\Domain\Calculation\Dto\LiquidationResult;
use App\Domain\ValueObjects\LiquidationReceipt;

interface LiquidationRepository
{
    public function save(
        LiquidationResult $result,
        int $contractId,
        int $userId,
        ?string $observations = null,
    ): LiquidationReceipt;
}
