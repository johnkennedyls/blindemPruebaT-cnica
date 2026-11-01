<?php

namespace Database\Seeders;

use App\Models\TerminationReason;
use Illuminate\Database\Seeder;

class TerminationReasonSeeder extends Seeder
{
    public function run(): void
    {
        $reasons = [
            ['TERM-CON', 'Terminación de Contrato', 'voluntad_general', false, 'ninguna', false],
            ['DES-SJC', 'Despido sin justa causa', 'sin_justa_causa', true, 'art64_segun_salario', false],
            ['DES-JC', 'Despido con justa causa', 'justa_causa', false, 'ninguna', false],
            ['RET-VOL', 'Retiro voluntario', 'voluntad_general', false, 'ninguna', false],
            ['VENC-PLZ', 'Vencimiento del plazo fijo', 'vencimiento', false, 'ninguna', false],
            ['MUT-ACU', 'Mutuo acuerdo', 'mutuo_acuerdo', false, 'ninguna', false],
            ['FALLEC', 'Fallecimiento del trabajador', 'fallecimiento', false, 'ninguna', false],
        ];

        foreach ($reasons as [$code, $description, $classification, $indemnizable, $rule, $sanction]) {
            TerminationReason::updateOrCreate(
                ['code' => $code],
                [
                    'description' => $description,
                    'classification' => $classification,
                    'applies_indemnization' => $indemnizable,
                    'indemnization_rule' => $rule,
                    'applies_sanction_art65' => $sanction,
                    'is_active' => true,
                ]
            );
        }
    }
}
