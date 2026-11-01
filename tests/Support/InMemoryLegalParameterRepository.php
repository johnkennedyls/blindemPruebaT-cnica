<?php

namespace Tests\Support;

use App\Domain\Contracts\LegalParameterRepository;
use App\Domain\Exceptions\ParameterNotConfigured;

final class InMemoryLegalParameterRepository implements LegalParameterRepository
{
    /**
     * @param  array<string, string>  $values
     */
    public function __construct(private readonly array $values = []) {}

    public static function withStatutoryDefaults(): self
    {
        return new self(self::defaults());
    }

    /**
     * @param  array<string, string>  $overrides
     */
    public static function withOverrides(array $overrides): self
    {
        return new self([...self::defaults(), ...$overrides]);
    }

    /**
     * @return array<string, string>
     */
    private static function defaults(): array
    {
        return [
            'commercial_year_days' => '360',
            'commercial_month_days' => '30',
            'fractional_year_days' => '365',
            'cesantias_days_per_year' => '30',
            'cesantias_divisor' => '360',
            'cesantias_interest_rate' => '0.12',
            'prima_days_per_semester' => '180',
            'prima_divisor' => '360',
            'vacation_days_per_year' => '15',
            'vacation_divisor' => '720',
            'transport_auxiliary_max_multiple' => '2',
            'indemnization_threshold_multiple' => '10',
            'indemnization_first_year_days_low' => '30',
            'indemnization_per_year_days_low' => '20',
            'indemnization_first_year_days_high' => '20',
            'indemnization_per_year_days_high' => '15',
            'health_employee_rate' => '0.04',
            'pension_employee_rate' => '0.04',
        ];
    }

    public function value(string $key, string $asOfDate): string
    {
        if (! array_key_exists($key, $this->values)) {
            throw new ParameterNotConfigured("El parámetro {$key} no está configurado.");
        }

        return $this->values[$key];
    }
}
