<?php

namespace App\Http\Controllers;

use App\Domain\Enum\ActivityAction;
use App\Domain\Enum\Gender;
use App\Domain\Enum\HolderType;
use App\Http\Presenters\WorkerPresenter;
use App\Http\Requests\WorkerRequest;
use App\Infrastructure\Auditing\ActivityLogger;
use App\Models\DocumentType;
use App\Models\User;
use App\Models\Worker;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class WorkerController extends Controller
{
    use AuthorizesRequests;

    public function __construct(private readonly ActivityLogger $logger) {}

    public function index(Request $request): Response
    {
        $term = trim((string) $request->query('q'));

        /**
         * @var User
         */
        $user = $request->user();

        $workers = Worker::query()
            ->with(['documentType'])
            ->withCount('contracts')
            ->when(! $user->isAdmin(), fn (Builder $query) => $query->whereHas(
                'contracts',
                fn (Builder $contracts) => $contracts->whereIn('company_id', $user->companies()->select('companies.id')),
            ))
            ->when($term !== '', function (Builder $query) use ($term): void {
                $query->where(function (Builder $inner) use ($term): void {
                    $inner->named($term)->orWhere('document_number', 'ILIKE', '%'.$term.'%');
                });
            })
            ->orderBy('last_names')
            ->orderBy('first_names')
            ->paginate(12)
            ->withQueryString()
            ->through(fn (Worker $worker): array => WorkerPresenter::card($worker));

        return Inertia::render('workers/index', [
            'workers' => $workers,
            'filters' => ['q' => $term === '' ? null : $term],
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', Worker::class);

        return Inertia::render('workers/form', [
            'worker' => null,
            'documentTypes' => $this->documentTypes(),
            'genders' => array_map(
                fn (Gender $case): array => ['value' => $case->value, 'label' => $case->label()],
                Gender::cases(),
            ),
        ]);
    }

    public function store(WorkerRequest $request): RedirectResponse
    {
        $this->authorize('create', Worker::class);

        $worker = Worker::query()->create($request->validated());

        $this->logger->log(
            ActivityAction::Crear,
            $worker,
            sprintf('Registró al trabajador %s con %s', $worker->fullName(), $worker->documentLabel()),
        );

        Inertia::flash('toast', ['type' => 'success', 'message' => "{$worker->fullName()} quedó registrado."]);

        return to_route('workers.edit', $worker);
    }

    public function edit(Worker $worker): Response
    {
        $this->authorize('update', $worker);

        $worker->load(['documentType'])->loadCount('contracts');

        return Inertia::render('workers/form', [
            'worker' => WorkerPresenter::card($worker),
            'documentTypes' => $this->documentTypes(),
            'genders' => array_map(
                fn (Gender $case): array => ['value' => $case->value, 'label' => $case->label()],
                Gender::cases(),
            ),
        ]);
    }

    public function update(WorkerRequest $request, Worker $worker): RedirectResponse
    {
        $this->authorize('update', $worker);

        $worker->update($request->validated());

        $this->logger->log(
            ActivityAction::Actualizar,
            $worker,
            sprintf('Actualizó los datos de %s', $worker->fullName()),
        );

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Los datos del trabajador fueron actualizados.']);

        return to_route('workers.edit', $worker);
    }

    public function destroy(Worker $worker): RedirectResponse
    {
        $this->authorize('delete', $worker);

        if ($worker->contracts()->exists()) {
            Inertia::flash('toast', ['type' => 'error', 'message' => 'No se puede eliminar un trabajador con contratos registrados. Desactívelo en su lugar.']);

            return back();
        }

        $name = $worker->fullName();
        $worker->delete();

        $this->logger->log(ActivityAction::Eliminar, $worker, sprintf('Eliminó al trabajador %s', $name));

        Inertia::flash('toast', ['type' => 'success', 'message' => "{$name} fue eliminado."]);

        return to_route('workers.index');
    }

    /**
     * @return array<int, array{value: int, label: string}>
     */
    private function documentTypes(): array
    {
        return DocumentType::query()
            ->active()
            ->whereIn('holder_type', [HolderType::Natural, HolderType::Both])
            ->orderBy('id')
            ->get()
            ->map(fn (DocumentType $type): array => [
                'value' => $type->id,
                'label' => "{$type->code} · {$type->name}",
            ])
            ->all();
    }
}
