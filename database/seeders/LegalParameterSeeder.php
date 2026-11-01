<?php

namespace Database\Seeders;

use App\Models\LegalParameter;
use Illuminate\Database\Seeder;

class LegalParameterSeeder extends Seeder
{
    public function run(): void
    {
        $parameters = [
            ['commercial_year_days', 360, 'days', 'Días del año comercial usado por el Excel en cesantías e intereses.'],
            ['commercial_month_days', 30, 'days', 'Días del mes comercial para el salario diario.'],
            ['fractional_year_days', 365, 'days', 'Divisor de la fracción del año en la indemnización (E28/365 del Excel).'],

            ['cesantias_days_per_year', 30, 'days', 'Art. 99 C.S.T.: 30 días de salario por año trabajado.'],
            ['cesantias_divisor', 360, 'days', 'Denominador del cálculo proporcional de cesantías.'],
            ['cesantias_interest_rate', 0.12, 'rate', 'Art. 99 C.S.T.: 12 % anual sobre las cesantías causadas.'],

            ['prima_days_per_semester', 180, 'days', 'Art. 306 C.S.T.: prima de 180 días por año (dos semestres).'],
            ['prima_divisor', 360, 'days', 'Denominador que usa el Excel (E20) para la prima proporcional.'],

            ['vacation_days_per_year', 15, 'days', 'Art. 186 C.S.T.: 15 días hábiles de vacaciones por año.'],
            ['vacation_divisor', 720, 'days', 'Denominador del Excel (E21) para vacaciones proporcionales.'],

            ['transport_auxiliary_max_multiple', 2, 'rate', 'El auxilio de transporte solo aplica hasta 2 SMLMV de salario.'],
            ['indemnization_threshold_multiple', 10, 'rate', 'Umbral del Art. 64: se compara el salario integral contra 10 SMLMV.'],
            ['indemnization_first_year_days_low', 30, 'days', 'Art. 64 lit. a: 30 días el primer año (salario < 10 SMLMV).'],
            ['indemnization_per_year_days_low', 20, 'days', 'Art. 64 lit. a: 20 días por cada año siguiente.'],
            ['indemnization_first_year_days_high', 20, 'days', 'Art. 64 lit. b: 20 días el primer año (salario ≥ 10 SMLMV).'],
            ['indemnization_per_year_days_high', 15, 'days', 'Art. 64 lit. b: 15 días por cada año siguiente.'],

            ['health_employee_rate', 0.04, 'rate', '4 % de salud a cargo del trabajador sobre el salario pendiente y vacaciones.'],
            ['pension_employee_rate', 0.04, 'rate', '4 % de pensión a cargo del trabajador sobre el salario pendiente y vacaciones.'],
        ];

        foreach ($parameters as [$key, $numeric, $unit, $description]) {
            LegalParameter::updateOrCreate(
                ['key' => $key, 'effective_from' => '2021-01-01'],
                [
                    'numeric_value' => $numeric,
                    'unit' => $unit,
                    'description' => $description,
                ]
            );
        }
    }
}
