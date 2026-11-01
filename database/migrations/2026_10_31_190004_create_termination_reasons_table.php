<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('termination_reasons', function (Blueprint $table) {
            $table->id();
            $table->string('code', 16)->unique('uq_tr_code');
            $table->string('description', 200);
            $table->string('classification', 24);
            $table->boolean('applies_indemnization')->default(false);
            $table->string('indemnization_rule', 24)->default('ninguna');
            $table->boolean('applies_sanction_art65')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        foreach ([
            'ck_tr_class' => "classification IN ('voluntad_general', 'justa_causa', 'sin_justa_causa', 'vencimiento', 'mutuo_acuerdo', 'fallecimiento')",
            'ck_tr_rule' => "indemnization_rule IN ('art64_segun_salario', 'art64_lt_a', 'art64_lt_b', 'plazo_restante', 'ninguna')",
        ] as $name => $expression) {
            DB::statement("ALTER TABLE termination_reasons ADD CONSTRAINT {$name} CHECK ({$expression})");
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('termination_reasons');
    }
};
