<?php

namespace App\Http\Presenters;

final class LiquidationGuidePresenter
{
    /**
     * @return array{
     *     steps: array<int, array{title: string, detail: string}>,
     *     rules: array<int, array{title: string, detail: string}>,
     *     formulas: array<int, array{concept: string, formula: string}>
     * }
     */
    public static function sections(): array
    {
        return [
            'steps' => [
                ['title' => 'Paso 1: Datos generales', 'detail' => 'Nombre, documento, fecha de ingreso y fecha de retiro vienen del trabajador y del contrato; son obligatorios.'],
                ['title' => 'Paso 2: Tipo de contrato', 'detail' => 'La modalidad (indefinido o término fijo) define cómo se mide la indemnización del artículo 64.'],
                ['title' => 'Paso 3: Salario y parámetros', 'detail' => 'Salario básico, auxilio de transporte y promedio de horas extras alimentan las bases del cálculo.'],
                ['title' => 'Paso 4: Días pendientes', 'detail' => 'Días de vacaciones, prima, cesantías y salario pendiente; vacíos, cesantías y prima toman el año comercial.'],
                ['title' => 'Paso 5: Revisión de resultados', 'detail' => 'Las prestaciones, la indemnización y las deducciones se calculan solas con las fórmulas del formato.'],
            ],
            'rules' => [
                ['title' => 'A) Término indefinido, salario menor a 10 SMLMV', 'detail' => 'Primer año de servicio o fracción: 30 días de salario. Segundo año en adelante: 20 días adicionales por año o proporcional por fracción.'],
                ['title' => 'B) Término indefinido, salario igual o mayor a 10 SMLMV', 'detail' => 'Primer año de servicio o fracción: 20 días de salario. Segundo año en adelante: 15 días adicionales por año o proporcional por fracción.'],
                ['title' => 'C) Término fijo', 'detail' => 'Corresponde al valor de los salarios faltantes para cumplir el plazo pactado del contrato.'],
            ],
            'formulas' => [
                ['concept' => 'Cesantías', 'formula' => '(Base prestacional × días laborados del año) / 360'],
                ['concept' => 'Intereses sobre cesantías', 'formula' => '(Cesantías acumuladas × días × 12 %) / 360'],
                ['concept' => 'Prima de servicios', 'formula' => '(Base prestacional × días laborados del semestre) / 360'],
                ['concept' => 'Vacaciones', 'formula' => '(Base vacacional × días pendientes) / 720'],
                ['concept' => 'Salud y pensión', 'formula' => '4 % sobre el salario pendiente más las vacaciones'],
            ],
        ];
    }
}
