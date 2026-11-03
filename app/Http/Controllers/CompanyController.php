<?php

namespace App\Http\Controllers;

use App\Domain\Enum\ActivityAction;
use App\Http\Presenters\CompanyPresenter;
use App\Http\Requests\CompanyRequest;
use App\Infrastructure\Auditing\ActivityLogger;
use App\Models\Company;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CompanyController extends Controller
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

        $companies = Company::query()
            ->withCount('contracts')
            ->visibleTo($user)
            ->when($term !== '', fn (Builder $query) => $query->named($term))
            ->orderBy('business_name')
            ->paginate(12)
            ->withQueryString()
            ->through(fn (Company $company): array => CompanyPresenter::card($company));

        return Inertia::render('companies/index', [
            'companies' => $companies,
            'filters' => ['q' => $term === '' ? null : $term],
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', Company::class);

        return Inertia::render('companies/form', ['company' => null]);
    }

    public function store(CompanyRequest $request): RedirectResponse
    {
        $this->authorize('create', Company::class);

        $company = Company::query()->create($request->validated());

        $this->logger->log(
            ActivityAction::Crear,
            $company,
            sprintf('Registró la empresa %s con NIT %s', $company->business_name, $company->nit),
        );

        Inertia::flash('toast', ['type' => 'success', 'message' => "La empresa {$company->business_name} quedó registrada."]);

        return to_route('companies.edit', $company);
    }

    public function edit(Company $company): Response
    {
        $this->authorize('update', $company);

        $company->loadCount('contracts');

        return Inertia::render('companies/form', ['company' => CompanyPresenter::card($company)]);
    }

    public function update(CompanyRequest $request, Company $company): RedirectResponse
    {
        $this->authorize('update', $company);

        $company->update($request->validated());

        $this->logger->log(
            ActivityAction::Actualizar,
            $company,
            sprintf('Actualizó los datos de la empresa %s', $company->business_name),
        );

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Los datos de la empresa fueron actualizados.']);

        return to_route('companies.edit', $company);
    }

    public function destroy(Company $company): RedirectResponse
    {
        $this->authorize('delete', $company);

        if ($company->contracts()->exists()) {
            Inertia::flash('toast', ['type' => 'error', 'message' => 'No se puede eliminar una empresa que ya tiene contratos registrados. Desactívela en su lugar.']);

            return back();
        }

        $name = $company->business_name;
        $company->delete();

        $this->logger->log(ActivityAction::Eliminar, $company, sprintf('Eliminó la empresa %s', $name));

        Inertia::flash('toast', ['type' => 'success', 'message' => "La empresa {$name} fue eliminada."]);

        return to_route('companies.index');
    }
}
