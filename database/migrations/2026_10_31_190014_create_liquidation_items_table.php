<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('liquidation_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('liquidation_id')->constrained('liquidations')->cascadeOnDelete();
            $table->string('concept_code', 32);
            $table->string('concept_name', 160);
            $table->string('side', 12);
            $table->decimal('calculation_base', 14, 2)->default(0);
            $table->decimal('quantity', 9, 4)->default(0);
            $table->decimal('rate', 9, 5)->nullable();
            $table->smallInteger('divisor')->nullable();
            $table->string('formula_text', 160);
            $table->string('calc_trace', 255)->nullable();
            $table->decimal('amount', 14, 2);
            $table->smallInteger('sequence');
            $table->boolean('applies_ss_contribution')->default(false);

            $table->unique(['liquidation_id', 'concept_code'], 'uq_item_liq_concept');
            $table->index(['liquidation_id', 'side'], 'ix_item_liq_side');
        });

        foreach ([
            'ck_item_side' => "side IN ('earning', 'deduction')",
            'ck_item_amount' => 'amount >= 0',
            'ck_item_div' => 'divisor IS NULL OR divisor > 0',
            'ck_item_seq' => 'sequence > 0',
        ] as $name => $expression) {
            DB::statement("ALTER TABLE liquidation_items ADD CONSTRAINT {$name} CHECK ({$expression})");
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('liquidation_items');
    }
};
