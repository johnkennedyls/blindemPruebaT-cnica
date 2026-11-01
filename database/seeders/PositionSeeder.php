<?php

namespace Database\Seeders;

use App\Models\Position;
use Illuminate\Database\Seeder;

class PositionSeeder extends Seeder
{
    public function run(): void
    {
        $positions = [
            ['ANL-OP', 'Analista Operativo', 'Operaciones', 'Profesional'],
            ['ASIG-AD', 'Asistente Administrativo', 'Administrativa', 'Técnico'],
            ['AUX-LOG', 'Auxiliar de Logística', 'Operaciones', 'Técnico'],
            ['VEN-CAM', 'Vendedor de Campo', 'Comercial', 'Asistencial'],
            ['LID-OP', 'Líder de Operaciones', 'Operaciones', 'Directivo'],
            ['CONT', 'Contador', 'Financiera', 'Profesional'],
        ];

        foreach ($positions as [$code, $name, $area, $level]) {
            Position::updateOrCreate(
                ['code' => $code],
                ['name' => $name, 'area' => $area, 'level' => $level, 'is_active' => true]
            );
        }
    }
}
