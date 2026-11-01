<?php

namespace App\Http\Controllers;

use App\Application\LiquidationDraftMapper;
use App\Domain\Calculation\LiquidationEngine;
use App\Domain\Contracts\ConceptCatalogRepository;
use App\Domain\Contracts\LiquidationRepository;
use App\Domain\Enum\ActivityAction;
use App\Domain\Enum\BaseKind;
use App\Domain\Enum\ContractStatus;
use App\Domain\Enum\HolderType;
use App\Domain\Enum\LiquidationVariant;
use App\Domain\ValueObjects\ConceptDefinition;
use App\Http\Presenters\ContractPresenter;
use App\Http\Presenters\LiquidationPresenter;
use App\Http\Requests\StoreLiquidationRequest;
use App\Infrastructure\Auditing\ActivityLogger;
use App\Models\Contract;
use App\Models\DocumentType;
use App\Models\Liquidation;
use App\Models\User;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class LiquidationController extends Controller
{
    use AuthorizesRequests;

    public function __construct(
        private readonly LiquidationEngine $engine,
        private readonly LiquidationDraftMapper $mapper,
        private readonly LiquidationRepository $repository,
        private readonly ConceptCatalogRepository $catalog,
        private readonly ActivityLogger $logger,
    ) {}

    public function index(Request $request): Response
    {
        /**
         * @var User
         */
        $user = $request->user();

        $status = ContractStatus::tryFrom((string) $request->query('status'));
        $rawTypeId = $request->query('document_type_id');
        $documentTypeId = is_numeric($rawTypeId) ? (int) $rawTypeId : null;
        $documentNumber = trim((string) $request->query('document_number'));

        $contracts = Contract::query()
            ->with(['worker.documentType', 'company', 'contractType', 'position', 'terminationReason', 'liquidations'])
            ->visibleTo($user)
            ->byStatus($status?->value)
            ->ofDocument($documentTypeId, $documentNumber)
            ->orderByDesc('start_date')
            ->orderByDesc('id')
            ->paginate(10)
            ->withQueryString()
            ->through(fn (Contract $contract): array => ContractPresenter::card($contract));

        return Inertia::render('liquidations/index', [
            'contracts' => $contracts,
            'filters' => [
                'status' => $status?->value,
                'document_type_id' => $documentTypeId,
                'document_number' => $documentNumber === '' ? null : $documentNumber,
            ],
            'statuses' => array_map(
                fn (ContractStatus $case): array => ['value' => $case->value, 'label' => $case->label()],
                ContractStatus::cases(),
            ),
            'documentTypes' => DocumentType::query()
                ->active()
                ->whereIn('holder_type', [HolderType::Natural, HolderType::Both])
                ->orderBy('id')
                ->get()
                ->map(fn (DocumentType $type): array => ['value' => $type->id, 'label' => $type->optionLabel()])
                ->all(),
        ]);
    }

    public function create(Request $request, Contract $contract): Response
    {
        $this->authorize('liquidate', $contract);

        return Inertia::render('liquidations/create', [
            'contract' => ContractPresenter::card(
                $contract->load(['worker.documentType', 'company', 'contractType', 'position', 'terminationReason', 'liquidations'])
            ),
            'variants' => array_map(
                fn (LiquidationVariant $case): array => ['value' => $case->value, 'label' => $case->label()],
                LiquidationVariant::cases(),
            ),
            'suggestedVariant' => $this->mapper->suggestsVariant($contract)->value,
            'manualConcepts' => array_values(array_map(
                fn (ConceptDefinition $concept): array => [
                    'code' => $concept->code,
                    'name' => $concept->name,
                    'side' => $concept->side->label(),
                ],
                array_filter(
                    $this->catalog->activeConcepts()->inOrder(),
                    fn (ConceptDefinition $concept): bool => $concept->baseKind === BaseKind::Manual,
                ),
            )),
        ]);
    }

    public function store(StoreLiquidationRequest $request, Contract $contract): RedirectResponse
    {
        $draft = $this->mapper->fromContract(
            contract: $contract,
            settlementDate: $request->settlementDate(),
            variant: $request->variant(),
            vacationDays: $request->vacationDays(),
            pendingSalaryDays: $request->pendingSalaryDays(),
            manualValues: $request->manualValues(),
            cesantiasDays: $request->cesantiasDays(),
            primaDays: $request->primaDays(),
        );

        $receipt = $this->repository->save(
            $this->engine->calculate($draft),
            $contract->id,
            $request->user()->id,
            $request->observations(),
        );

        $liquidation = Liquidation::query()->findOrFail($receipt->id);

        $this->logger->log(
            ActivityAction::Liquidar,
            $liquidation,
            sprintf('Liquidió el contrato %s con el folio %s', $contract->code, $receipt->folio),
            ['folio' => $receipt->folio, 'net_total' => $liquidation->net_total],
            $request->user()->id,
        );

        return to_route('liquidations.show', $liquidation)
            ->with('status', "La liquidación {$receipt->folio} quedó guardada.");
    }

    public function show(Request $request, Liquidation $liquidation): Response
    {
        $liquidation->load([
            'contract.worker.documentType',
            'contract.company',
            'contract.contractType',
            'contract.position',
            'contract.terminationReason',
            'items',
            'creator',
        ]);

        $this->authorize('view', $liquidation);

        return Inertia::render('liquidations/show', [
            'liquidation' => LiquidationPresenter::document($liquidation),
            'contract' => ContractPresenter::card($liquidation->contract),
        ]);
    }
}
