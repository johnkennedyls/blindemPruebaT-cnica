<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('concept_catalogs', function (Blueprint $table) {
            $table->id();
            $table->string('code', 32)->unique('uq_cc_code');
            $table->string('name', 160);
            $table->string('side', 12);
            $table->string('formula_key', 32);
            $table->string('base_kind', 24);
            $table->smallInteger('default_divisor')->nullable();
            $table->boolean('applies_ss_contribution')->default(false);
            $table->boolean('is_statutory')->default(true);
            $table->boolean('is_system')->default(true);
            $table->smallInteger('default_sequence')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        foreach ([
            'ck_cc_side' => "side IN ('earning', 'deduction')",
            'ck_cc_base' => "base_kind IN ('prestacional', 'vacacional', 'salario_pendiente', 'otro', 'manual')",
            'ck_cc_div' => 'default_divisor IS NULL OR default_divisor > 0',
        ] as $name => $expression) {
            DB::statement("ALTER TABLE concept_catalogs ADD CONSTRAINT {$name} CHECK ({$expression})");
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('concept_catalogs');
    }
};
