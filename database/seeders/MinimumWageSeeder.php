<?php

namespace Database\Seeders;

use App\Models\MinimumWage;
use Illuminate\Database\Seeder;

class MinimumWageSeeder extends Seeder
{
    public function run(): void
    {
        $rows = [
            [2019, 851500, 102000, '851.500 Decreto 1595 de 2018 · auxilio Decreto 1594 de 2018'],
            [2020, 908528, 106454, '908.528 Decreto 1592 de 2019 · auxilio Decreto 1590 de 2019'],
            [2021, 970646, 113070, '970.646 Decreto 1508 de 2020 · auxilio Decreto 1507 de 2020'],
            [2022, 1000000, 130506, '1.000.000 Decreto 1449 de 2021 · auxilio Decreto 1447 de 2021'],
            [2023, 1160000, 140500, '1.160.000 Decreto 1499 de 2022 · auxilio Decreto 1446 de 2022'],
            [2024, 1300000, 162000, '1.300.000 Decreto 1542 de 2023 · auxilio Decreto 1494 de 2023'],
            [2025, 1423500, 200000, '1.423.500 Decreto 1542 de 2024 · auxilio Decreto 1542 de 2024'],
            [2026, 1750905, 249095, '1.750.905 valor usado en el Excel de la prueba técnica'],
        ];

        foreach ($rows as [$year, $smlmv, $auxiliary, $source]) {
            MinimumWage::updateOrCreate(
                ['year' => $year],
                [
                    'smlmv' => $smlmv,
                    'transport_auxiliary' => $auxiliary,
                    'effective_from' => "{$year}-01-01",
                    'effective_to' => "{$year}-12-31",
                    'source' => $source,
                ]
            );
        }
    }
}
