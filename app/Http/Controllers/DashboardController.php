<?php

namespace App\Http\Controllers;

use App\Domain\Enum\ContractStatus;
use App\Http\Presenters\ContractPresenter;
use App\Models\Contract;
use App\Models\Liquidation;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(Request $request): Response
    {
        /**
         * @var User
         */
        $user = $request->user();

        $liquidatable = [ContractStatus::Terminado->value, ContractStatus::EnLiquidacion->value];

        $pending = Contract::query()
            ->with(['worker.documentType', 'company', 'contractType', 'position', 'terminationReason', 'liquidations'])
            ->visibleTo($user)
            ->whereIn('status', $liquidatable)
            ->orderBy('end_date')
            ->limit(5)
            ->get()
            ->map(fn (Contract $contract): array => ContractPresenter::card($contract))
            ->all();

        return Inertia::render('dashboard', [
            'summary' => [
                'active' => Contract::query()->visibleTo($user)->byStatus(ContractStatus::Activo->value)->count(),
                'liquidatable' => Contract::query()->visibleTo($user)->whereIn('status', $liquidatable)->count(),
                'liquidated' => Contract::query()->visibleTo($user)->byStatus(ContractStatus::Liquidado->value)->count(),
                'liquidations' => Liquidation::query()
                    ->whereHas('contract', fn (Builder $contracts): Builder => $contracts->visibleTo($user))
                    ->count(),
            ],
            'pending' => $pending,
        ]);
    }
}
