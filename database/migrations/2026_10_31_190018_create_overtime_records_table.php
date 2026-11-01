<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('overtime_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contract_id')->constrained('contracts')->cascadeOnDelete();
            $table->date('occurrence_date');
            $table->string('type', 20);
            $table->decimal('hours', 5, 2);
            $table->decimal('amount', 14, 2);

            $table->unique(['contract_id', 'occurrence_date', 'type'], 'uq_overtime_once');
            $table->index(['contract_id', 'occurrence_date'], 'ix_overtime_contract_date');
        });

        foreach ([
            'ck_otr_type' => "type IN ('extra_diurna', 'extra_nocturna', 'dominical', 'recargo')",
            'ck_otr_hours' => "hours > 0 AND hours <= 24",
            'ck_otr_amount' => "amount >= 0",
        ] as $name => $expression) {
            DB::statement("ALTER TABLE overtime_records ADD CONSTRAINT {$name} CHECK ({$expression})");
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('overtime_records');
    }
};
