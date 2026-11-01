<?php

namespace App\Policies;

use App\Models\Contract;
use App\Models\User;

class ContractPolicy
{
    public function view(User $user, Contract $contract): bool
    {
        return $user->role->isAdmin()
            || $contract->created_by === $user->id
            || $user->companies()->whereKey($contract->company_id)->exists();
    }

    public function liquidate(User $user, Contract $contract): bool
    {
        return $this->view($user, $contract) && $contract->status->canBeLiquidated();
    }
}
