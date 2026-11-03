<?php

namespace App\Http\Controllers;

use App\Domain\Enum\ActivityAction;
use App\Http\Parametrics\FieldType;
use App\Http\Parametrics\ParametricDefinition;
use App\Http\Parametrics\ParametricRegistry;
use App\Http\Requests\ParametricRequest;
use App\Infrastructure\Auditing\ActivityLogger;
use App\Models\ParametricCatalog;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use Inertia\Response;

class ParametricController extends Controller
{
    use AuthorizesRequests;

    public function __construct(private readonly ActivityLogger $logger) {}

    public function index(Request $request, string $resource): Response
    {
        $definition = ParametricRegistry::resolve($resource);
        $term = trim((string) $request->query('q'));

        $model = $definition->model;

        $rows = $model::query()
            ->when($term !== '', fn (Builder $query): Builder => $query->where(
                fn (Builder $inner): Builder => $inner
                    ->whereAny($definition->searchAttributes, 'ILIKE', '%'.$term.'%'),
            ))
            ->orderBy($definition->headline)
            ->paginate(12)
            ->withQueryString()
            ->through(fn (\App\Models\ParametricCatalog&Model $record): array => $this->row($definition, $record));

        return Inertia::render('parametrics/index', [
            'definition' => $definition->toFrontend(),
            'rows' => $rows,
            'filters' => ['q' => $term === '' ? null : $term],
        ]);
    }

    public function create(Request $request, string $resource): Response
    {
        $this->authorizeAdmin($request);

        $definition = ParametricRegistry::resolve($resource);

        return Inertia::render('parametrics/form', [
            'definition' => $definition->toFrontend(),
            'row' => null,
        ]);
    }

    public function store(ParametricRequest $request, string $resource): RedirectResponse
    {
        $definition = ParametricRegistry::resolve($resource);

        $record = $this->fromRequest($definition, $request);
        $record->save();

        $this->logger->log(
            ActivityAction::Crear,
            $record,
            sprintf('Registró el %s "%s" en el catálogo de %s', $definition->one, $this->primaryText($definition, $record), $definition->title),
        );

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => sprintf('%s: "%s" quedó registrado.', $definition->title, $this->primaryText($definition, $record)),
        ]);

        return to_route('parametrics.edit', ['resource' => $definition->slug, 'parametric' => $record->getKey()]);
    }

    public function edit(Request $request, string $resource): Response
    {
        $this->authorizeAdmin($request);

        $definition = ParametricRegistry::resolve($resource);

        $record = $this->findRecord($definition, $request);

        return Inertia::render('parametrics/form', [
            'definition' => $definition->toFrontend(),
            'row' => $this->form($definition, $record),
        ]);
    }

    public function update(ParametricRequest $request, string $resource): RedirectResponse
    {
        $definition = ParametricRegistry::resolve($resource);
        $record = $this->findRecord($definition, $request);

        $this->fromRequest($definition, $request, $record);

        $record->save();

        $this->logger->log(
            ActivityAction::Actualizar,
            $record,
            sprintf('Actualizó el %s "%s" en %s', $definition->one, $this->primaryText($definition, $record), $definition->title),
        );

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => sprintf('Los datos del %s fueron actualizados.', $definition->one),
        ]);

        return to_route('parametrics.edit', ['resource' => $definition->slug, 'parametric' => $record->getKey()]);
    }

    public function destroy(Request $request, string $resource): RedirectResponse
    {
        $this->authorizeAdmin($request);

        $definition = ParametricRegistry::resolve($resource);
        $record = $this->findRecord($definition, $request);

        if ($definition->usageRelation !== null && (int) $record->{$definition->usageRelation}()->count() > 0) {
            Inertia::flash('toast', [
                'type' => 'error',
                'message' => sprintf('No se puede eliminar: hay registros que usan este %s. Desactívelo en su lugar.', $definition->one),
            ]);

            return back();
        }

        $record->delete();

        $this->logger->log(
            ActivityAction::Eliminar,
            $record,
            sprintf('Eliminó el %s "%s" de %s', $definition->one, $this->primaryText($definition, $record), $definition->title),
        );

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => sprintf('"%s" fue eliminado.', $this->primaryText($definition, $record)),
        ]);

        return to_route('parametrics.index', ['resource' => $definition->slug]);
    }

    private function fromRequest(ParametricDefinition $definition, ParametricRequest $request, ?Model $record = null): Model
    {
        $model = $definition->model;

        $payload = $request->safe()->all();

        if ($definition->slug === 'salarios-minimos') {
            $year = (int) $payload['year'];
            $payload['effective_from'] = Carbon::create($year, 1, 1)->toDateString();
            $payload['effective_to'] = Carbon::create($year, 12, 31)->toDateString();
        }

        if (isset($payload['indemnization_rule'])) {
            $payload['applies_indemnization'] = $payload['indemnization_rule'] !== 'ninguna';
        }

        $target = $record === null ? new $model : $record;
        $fillable = $target->getFillable();

        if ($definition->hasPauseState) {
            $payload['is_active'] = $request->boolean('is_active', true);
        }

        foreach ($payload as $attribute => $value) {
            if (in_array($attribute, $fillable, true)) {
                $target->setAttribute($attribute, $value);
            }
        }

        return $target;
    }

    /**
     * @return array<string, mixed>
     */
    private function row(ParametricDefinition $definition, ParametricCatalog&Model $record): array
    {
        $usage = null;

        if ($definition->usageRelation !== null) {
            $usage = $record->{$definition->usageRelation}()->count();
        }

        return [
            'id' => (int) $record->getKey(),
            'headline' => (string) $record->getAttribute($definition->headline),
            'primary' => $this->primaryText($definition, $record),
            'secondary' => $record->detailLabel(),
            'is_active' => $definition->hasPauseState ? $record->getAttribute('is_active') === true : null,
            'usage_count' => $usage,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function form(ParametricDefinition $definition, ParametricCatalog&Model $record): array
    {
        $values = [];

        foreach ($definition->fields as $field) {
            $value = $record->getAttribute($field->name);

            $values[$field->name] = $field->kind === FieldType::Checkbox
                ? $value === true || $value === 1 || $value === '1'
                : ($value === null ? '' : (string) $value);
        }

        return [
            'id' => (int) $record->getKey(),
            'headline' => (string) $record->getAttribute($definition->headline),
            'values' => $values + ($definition->hasPauseState ? ['is_active' => $record->getAttribute('is_active') === true] : []),
        ];
    }

    private function primaryText(ParametricDefinition $definition, Model $record): string
    {
        return (string) $record->getAttribute($definition->primaryAttribute ?? $definition->headline);
    }

    /**
     * @return ParametricCatalog&Model
     */
    private function findRecord(ParametricDefinition $definition, Request $request): Model
    {
        $id = $request->route('parametric');

        $model = $definition->model;

        return $model::query()->findOrFail((int) $id);
    }

    private function authorizeAdmin(Request $request): void
    {
        abort_unless($request->user()?->isAdmin(), 403);
    }
}
