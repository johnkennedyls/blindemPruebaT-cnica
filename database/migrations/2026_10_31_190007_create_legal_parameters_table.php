<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('legal_parameters', function (Blueprint $table) {
            $table->id();
            $table->string('key', 64);
            $table->decimal('numeric_value', 12, 6)->nullable();
            $table->string('text_value', 120)->nullable();
            $table->string('unit', 12)->default('rate');
            $table->date('effective_from');
            $table->date('effective_to')->nullable();
            $table->string('description', 400)->nullable();
            $table->timestamps();

            $table->unique(['key', 'effective_from'], 'uq_lp_key_period');
        });

        foreach ([
            'ck_lp_unit' => "unit IN ('rate', 'days', 'amount')",
            'ck_lp_value' => "numeric_value IS NULL OR numeric_value >= 0",
            'ck_lp_some_value' => "numeric_value IS NOT NULL OR text_value IS NOT NULL",
            'ck_lp_period' => "effective_to IS NULL OR effective_to >= effective_from",
        ] as $name => $expression) {
            DB::statement("ALTER TABLE legal_parameters ADD CONSTRAINT {$name} CHECK ({$expression})");
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('legal_parameters');
    }
};
