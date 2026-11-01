<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Worker;

class WorkerPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Worker $worker): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        return $worker->contracts()
            ->whereIn('company_id', $user->companies()->select('companies.id'))
            ->exists();
    }

    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    public function update(User $user, Worker $worker): bool
    {
        return $user->isAdmin();
    }

    public function delete(User $user, Worker $worker): bool
    {
        return $user->isAdmin();
    }
}
