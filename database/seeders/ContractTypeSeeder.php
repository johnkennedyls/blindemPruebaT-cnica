<?php

namespace Database\Seeders;

use App\Models\ContractType;
use Illuminate\Database\Seeder;

class ContractTypeSeeder extends Seeder
{
    public function run(): void
    {
        $types = [
            ['INDE', 'Indefinido', 'indefinido', false, null, true, 'art64_segun_salario'],
            ['FIJO', 'Término fijo', 'termino_fijo', true, 36, true, 'plazo_restante'],
            ['OBRA', 'Por obra o labor', 'obra_o_labor', false, null, true, 'art64_segun_salario'],
            ['APRE', 'Aprendizaje SENA', 'aprendizaje', true, 24, false, 'ninguna'],
            ['SERV', 'Prestación de servicios', 'servicios', true, 12, false, 'ninguna'],
        ];

        foreach ($types as [$code, $name, $modality, $needsEnd, $maxMonths, $indemnizable, $rule]) {
            ContractType::updateOrCreate(
                ['code' => $code],
                [
                    'name' => $name,
                    'modality' => $modality,
                    'requires_scheduled_end' => $needsEnd,
                    'statutory_max_months' => $maxMonths,
                    'applies_indemnization' => $indemnizable,
                    'indemnization_rule' => $rule,
                    'is_active' => true,
                ]
            );
        }
    }
}
