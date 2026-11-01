<?php

namespace Database\Seeders;

use App\Models\ConceptCatalog;
use Illuminate\Database\Seeder;

class ConceptCatalogSeeder extends Seeder
{
    public function run(): void
    {
        $concepts = [
            ['CESANTIAS', 'Cesantías', 'earning', 'cesantias', 'prestacional', 360, false, 10],
            ['CESANTIAS_INTERES', 'Intereses sobre cesantías', 'earning', 'cesantias_interest', 'prestacional', 360, false, 20],
            ['PRIMA', 'Prima de servicios', 'earning', 'prima', 'prestacional', 360, false, 30],
            ['VACACIONES', 'Vacaciones', 'earning', 'vacation', 'vacacional', 720, true, 40],
            ['SALARIO_PENDIENTE', 'Salario pendiente de pago', 'earning', 'pending_salary', 'salario_pendiente', null, true, 50],
            ['AUXILIO_TRANSPORTE', 'Auxilio de transporte', 'earning', 'transport_auxiliary', 'salario_pendiente', null, false, 60],
            ['HORAS_EXTRAS', 'Horas extras y recargos del período', 'earning', 'manual', 'manual', null, false, 70],
            ['INDEMNIZACION', 'Indemnización Art. 64 C.S.T.', 'earning', 'indemnization', 'otro', null, false, 80],
            ['DESC_SALUD', 'Descuento salud (4 %)', 'deduction', 'health_contribution', 'otro', null, false, 90],
            ['DESC_PENSION', 'Descuento pensión (4 %)', 'deduction', 'pension_contribution', 'otro', null, false, 100],
            ['DESC_PRESTAMOS', 'Préstamos o anticipos pendientes', 'deduction', 'manual', 'manual', null, false, 110],
            ['DESC_OTRAS', 'Otras deducciones autorizadas', 'deduction', 'manual', 'manual', null, false, 120],
        ];

        foreach ($concepts as [$code, $name, $side, $formulaKey, $baseKind, $divisor, $ssBase, $sequence]) {
            ConceptCatalog::updateOrCreate(
                ['code' => $code],
                [
                    'name' => $name,
                    'side' => $side,
                    'formula_key' => $formulaKey,
                    'base_kind' => $baseKind,
                    'default_divisor' => $divisor,
                    'applies_ss_contribution' => $ssBase,
                    'is_statutory' => true,
                    'is_system' => true,
                    'default_sequence' => $sequence,
                    'is_active' => true,
                ]
            );
        }
    }
}
