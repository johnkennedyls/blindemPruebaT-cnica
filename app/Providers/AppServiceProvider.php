<?php

namespace App\Providers;

use App\Domain\Contracts\ConceptCatalogRepository;
use App\Domain\Contracts\LegalParameterRepository;
use App\Domain\Contracts\MinimumWageRepository;
use App\Infrastructure\Persistence\Eloquent\EloquentConceptCatalogRepository;
use App\Infrastructure\Persistence\Eloquent\EloquentLegalParameterRepository;
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
