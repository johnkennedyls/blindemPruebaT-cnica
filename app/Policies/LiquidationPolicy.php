<?php

namespace App\Policies;

use App\Models\Liquidation;
use App\Models\User;

class LiquidationPolicy
{
    public function view(User $user, Liquidation $liquidation): bool
    {
        return $user->role->isAdmin()
            || $liquidation->created_by === $user->id
            || $user->can('view', $liquidation->contract);
    }
}
