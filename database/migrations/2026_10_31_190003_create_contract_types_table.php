<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contract_types', function (Blueprint $table) {
            $table->id();
            $table->string('code', 16)->unique('uq_ct_code');
            $table->string('name', 150);
            $table->string('modality', 24);
            $table->boolean('requires_scheduled_end')->default(false);
            $table->smallInteger('statutory_max_months')->nullable();
            $table->boolean('applies_indemnization')->default(false);
            $table->string('indemnization_rule', 24)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        foreach ([
            'ck_ct_modality' => "modality IN ('indefinido', 'termino_fijo', 'obra_o_labor', 'aprendizaje', 'ocasional', 'servicios')",
            'ck_ct_rule' => "indemnization_rule IN ('art64_segun_salario', 'art64_lt_a', 'art64_lt_b', 'plazo_restante', 'ninguna')",
            'ck_ct_months' => 'statutory_max_months IS NULL OR statutory_max_months > 0',
        ] as $name => $expression) {
            DB::statement("ALTER TABLE contract_types ADD CONSTRAINT {$name} CHECK ({$expression})");
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('contract_types');
    }
};
