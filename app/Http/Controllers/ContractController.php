<?php

namespace App\Http\Controllers;

use App\Domain\Enum\ActivityAction;
use App\Domain\Enum\ContractStatus;
use App\Domain\Enum\TransportAuxiliaryMode;
use App\Http\Presenters\ContractPresenter;
use App\Http\Requests\ContractRequest;
use App\Infrastructure\Auditing\ActivityLogger;
use App\Models\Company;
use App\Models\Contract;
use App\Models\ContractType;
use App\Models\Position;
use App\Models\TerminationReason;
use App\Models\User;
use App\Models\Worker;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ContractController extends Controller
{
    use AuthorizesRequests;

    public function __construct(private readonly ActivityLogger $logger) {}

    public function index(Request $request): Response
    {
        /**
         * @var User
         */
        $user = $request->user();

        $term = trim((string) $request->query('q'));
        $status = ContractStatus::tryFrom((string) $request->query('status'));
        $rawCompany = $request->query('company_id');
        $companyId = is_numeric($rawCompany) ? (int) $rawCompany : null;

        $contracts = Contract::query()
            ->with(['worker.documentType', 'company', 'contractType', 'position', 'terminationReason', 'liquidations'])
            ->visibleTo($user)
            ->when($term !== '', fn (Builder $query): Builder => $query->where(
                fn (Builder $inner): Builder => $inner
                    ->where('code', 'ILIKE', '%'.$term.'%')
                    ->orWhereHas('worker', fn (Builder $worker): Builder => $worker
                        ->named($term)
                        ->orWhere('document_number', 'ILIKE', '%'.$term.'%')),
            ))
            ->byStatus($status?->value)
            ->ofCompany($companyId)
            ->orderByDesc('start_date')
            ->orderByDesc('id')
            ->paginate(10)
            ->withQueryString()
            ->through(fn (Contract $contract): array => ContractPresenter::card($contract));

        return Inertia::render('contracts/index', [
            'contracts' => $contracts,
            'filters' => [
                'q' => $term === '' ? null : $term,
                'status' => $status?->value,
                'company_id' => $companyId,
            ],
            'statuses' => $this->statusFilterOptions(),
            'companies' => $this->companyOptions($user),
        ]);
    }

    public function create(Request $request): Response
    {
        /**
         * @var User
         */
        $user = $request->user();

        $this->authorize('create', Contract::class);

        return Inertia::render('contracts/form', [
            'contract' => null,
            'suggestedCode' => $this->nextCode(),
        ] + $this->optionLists(null, $user));
    }

    public function store(ContractRequest $request): RedirectResponse
    {
        $this->authorize('create', Contract::class);

        $contract = Contract::query()->create([
            ...$request->validated(),
            'created_by' => $request->user()->id,
        ]);

        $this->logger->log(
            ActivityAction::Crear,
            $contract,
            sprintf(
                'Registró el contrato %s de %s en %s',
                $contract->code,
                $contract->worker->fullName(),
                $contract->company->displayName(),
            ),
            ['code' => $contract->code, 'start_date' => $contract->start_date->toDateString()],
        );

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => "El contrato {$contract->code} quedó registrado.",
        ]);

        return to_route('contracts.edit', $contract);
    }

    public function edit(Request $request, Contract $contract): Response
    {
        $this->authorize('update', $contract);

        /**
         * @var User
         */
        $user = $request->user();

        $contract->load(['worker', 'company']);

        return Inertia::render('contracts/form', [
            'contract' => ContractPresenter::form($contract),
            'suggestedCode' => null,
        ] + $this->optionLists($contract, $user));
    }

    public function update(ContractRequest $request, Contract $contract): RedirectResponse
    {
        $this->authorize('update', $contract);

        $contract->update($request->validated());

        $this->logger->log(
            ActivityAction::Actualizar,
            $contract,
            sprintf('Actualizó el contrato %s', $contract->code),
            $request->safe()->only(['status', 'base_salary_monthly', 'end_date']),
        );

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => "El contrato {$contract->code} fue actualizado.",
        ]);

        return to_route('contracts.edit', $contract);
    }

    public function destroy(Contract $contract): RedirectResponse
    {
        $this->authorize('delete', $contract);

        if ($contract->liquidations()->exists()) {
            Inertia::flash('toast', [
                'type' => 'error',
                'message' => "El contrato {$contract->code} ya tiene liquidaciones y no se puede eliminar.",
            ]);

            return back();
        }

        $code = $contract->code;
        $contract->delete();

        $this->logger->log(ActivityAction::Eliminar, $contract, sprintf('Eliminó el contrato %s', $code));

        Inertia::flash('toast', ['type' => 'success', 'message' => "El contrato {$code} fue eliminado."]);

        return to_route('contracts.index');
    }

    /**
     * @return array<string, mixed>
     */
    private function optionLists(?Contract $contract = null, ?User $user = null): array
    {
        return [
            'workers' => $this->workerOptions($contract),
            'companies' => $this->companyOptions($user, $contract),
            'contractTypes' => ContractType::query()
                ->active()
                ->orderBy('id')
                ->get()
                ->map(fn (ContractType $type): array => [
                    'value' => $type->id,
                    'label' => "{$type->code} · {$type->name}",
                ])
                ->all(),
            'positions' => Position::query()
                ->active()
                ->orderBy('name')
                ->get()
                ->map(fn (Position $position): array => [
                    'value' => $position->id,
                    'label' => $position->optionLabel(),
                ])
                ->all(),
            'terminationReasons' => TerminationReason::query()
                ->active()
                ->orderBy('id')
                ->get()
                ->map(fn (TerminationReason $reason): array => [
                    'value' => $reason->id,
                    'label' => "{$reason->code} · {$reason->description}",
                ])
                ->all(),
            'statuses' => $this->statusOptions(),
            'auxiliaryModes' => array_map(
                fn (TransportAuxiliaryMode $mode): array => ['value' => $mode->value, 'label' => $mode->label()],
                TransportAuxiliaryMode::cases(),
            ),
        ];
    }

    /**
     * @return array<int, array{value: string, label: string}>
     */
    private function statusOptions(): array
    {
        return $this->toOptions(ContractStatus::assignableCases());
    }

    /**
     * @return array<int, array{value: string, label: string}>
     */
    private function statusFilterOptions(): array
    {
        return $this->toOptions(ContractStatus::cases());
    }

    /**
     * @param  list<ContractStatus>  $cases
     * @return array<int, array{value: string, label: string}>
     */
    private function toOptions(array $cases): array
    {
        return array_map(
            fn (ContractStatus $case): array => ['value' => $case->value, 'label' => $case->label()],
            $cases,
        );
    }

    /**
     * @return array<int, array{value: int, label: string}>
     */
    private function workerOptions(?Contract $contract): array
    {
        $workers = Worker::query()
            ->active()
            ->orderBy('last_names')
            ->orderBy('first_names')
            ->get();

        if ($contract instanceof Contract && ! $workers->contains('id', $contract->worker_id)) {
            $workers->prepend($contract->worker);
        }

        return $workers
            ->map(fn (Worker $worker): array => ['value' => $worker->id, 'label' => $worker->optionLabel()])
            ->all();
    }

    /**
     * @return array<int, array{value: int, label: string}>
     */
    private function companyOptions(?User $user = null, ?Contract $contract = null): array
    {
        $query = Company::query()->active()->orderBy('business_name');

        if ($user !== null && ! $user->isAdmin()) {
            $query->where(
                fn (Builder $inner): Builder => $inner
                    ->whereKey($contract?->company_id)
                    ->orWhereHas(
                        'users',
                        fn (Builder $members): Builder => $members->whereKey($user->id),
                    ),
            );
        }

        return $query
            ->get()
            ->map(fn (Company $company): array => [
                'value' => $company->id,
                'label' => "{$company->nit} - {$company->displayName()}",
            ])
            ->all();
    }

    private function nextCode(): string
    {
        $sequence = Contract::withTrashed()->count('id') + 1;
        $code = sprintf('CTR-%06d', $sequence);

        while (Contract::withTrashed()->where('code', $code)->exists()) {
            $sequence++;
            $code = sprintf('CTR-%06d', $sequence);
        }

        return $code;
    }
}
