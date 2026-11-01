<?php

namespace Database\Seeders;

use App\Models\DocumentType;
use Illuminate\Database\Seeder;

class DocumentTypeSeeder extends Seeder
{
    public function run(): void
    {
        $types = [
            ['CC', 'Cédula de Ciudadanía', 'natural'],
            ['CE', 'Cédula de Extranjería', 'natural'],
            ['TI', 'Tarjeta de Identidad', 'natural'],
            ['PA', 'Pasaporte', 'natural'],
            ['NIT', 'Número de Identificación Tributaria', 'juridica'],
        ];

        foreach ($types as [$code, $name, $holder]) {
            DocumentType::updateOrCreate(
                ['code' => $code],
                ['name' => $name, 'abbreviation' => $code, 'holder_type' => $holder, 'is_active' => true]
            );
        }
    }
}
