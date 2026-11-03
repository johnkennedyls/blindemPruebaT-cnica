<?php

namespace App\Policies;

use App\Domain\Enum\ContractStatus;
use App\Models\Contract;
use App\Models\User;

class ContractPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function view(User $user, Contract $contract): bool
    {
        return $user->isAdmin()
            || $contract->created_by === $user->id
            || $user->companies()->whereKey($contract->company_id)->exists();
    }

    public function update(User $user, Contract $contract): bool
    {
        return $this->view($user, $contract) && $contract->status !== ContractStatus::Liquidado;
    }

    public function delete(User $user, Contract $contract): bool
    {
        return $this->update($user, $contract);
    }

    public function liquidate(User $user, Contract $contract): bool
    {
        return $this->view($user, $contract) && $contract->status->canBeLiquidated();
    }
}
