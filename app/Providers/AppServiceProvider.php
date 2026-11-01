<?php

namespace App\Providers;

use App\Application\LiquidationDraftMapper;
use App\Domain\Calculation\Calculators\AuxilioTransporteCalculator;
use App\Domain\Calculation\Calculators\CesantiasCalculator;
use App\Domain\Calculation\Calculators\IndemnizacionCalculator;
use App\Domain\Calculation\Calculators\InteresesCesantiasCalculator;
use App\Domain\Calculation\Calculators\ManualValueCalculator;
use App\Domain\Calculation\Calculators\PrimaCalculator;
use App\Domain\Calculation\Calculators\SalarioPendienteCalculator;
use App\Domain\Calculation\Calculators\SocialSecurityContributionCalculator;
use App\Domain\Calculation\Calculators\VacacionesCalculator;
use App\Domain\Calculation\Indemnization\IndemnizationStrategyFactory;
use App\Domain\Calculation\LiquidationEngine;
use App\Domain\Contracts\ConceptCatalogRepository;
use App\Domain\Contracts\LegalParameterRepository;
use App\Domain\Contracts\LiquidationRepository;
use App\Domain\Contracts\MinimumWageRepository;
use App\Infrastructure\Persistence\Eloquent\EloquentConceptCatalogRepository;
use App\Infrastructure\Persistence\Eloquent\EloquentLegalParameterRepository;
use App\Infrastructure\Persistence\Eloquent\EloquentLiquidationRepository;
use App\Infrastructure\Persistence\Eloquent\EloquentMinimumWageRepository;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(LegalParameterRepository::class, EloquentLegalParameterRepository::class);
        $this->app->singleton(MinimumWageRepository::class, EloquentMinimumWageRepository::class);
        $this->app->singleton(ConceptCatalogRepository::class, EloquentConceptCatalogRepository::class);
        $this->app->singleton(LiquidationRepository::class, EloquentLiquidationRepository::class);
        $this->app->singleton(LiquidationDraftMapper::class);

        $this->app->singleton(LiquidationEngine::class, fn ($app): LiquidationEngine => new LiquidationEngine(
            $app->make(ConceptCatalogRepository::class),
            $app->make(MinimumWageRepository::class),
            $app->make(LegalParameterRepository::class),
            $app->make(IndemnizationStrategyFactory::class),
            [
                new CesantiasCalculator,
                new InteresesCesantiasCalculator,
                new PrimaCalculator,
                new VacacionesCalculator,
                new SalarioPendienteCalculator,
                new AuxilioTransporteCalculator,
                new IndemnizacionCalculator,
                new ManualValueCalculator,
                new SocialSecurityContributionCalculator('health_contribution', 'health_employee_rate'),
                new SocialSecurityContributionCalculator('pension_contribution', 'pension_employee_rate'),
            ],
        ));
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureDefaults();
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null,
        );
    }
}
