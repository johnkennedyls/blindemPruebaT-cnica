<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('liquidations', function (Blueprint $table) {
            $table->id();
            $table->string('folio', 40);
            $table->foreignId('contract_id')->constrained('contracts');
            $table->foreignId('created_by')->constrained('users');
            $table->foreignId('approved_by')->nullable()->constrained('users');
            $table->string('variant', 24);
            $table->string('status', 16)->default('calculada');
            $table->date('settlement_date');
            $table->date('period_from')->nullable();
            $table->date('period_to')->nullable();
            $table->decimal('base_prestacional_snapshot', 14, 2);
            $table->decimal('base_vacacional_snapshot', 14, 2);
            $table->decimal('smlmv_snapshot', 14, 2);
            $table->smallInteger('cesantia_days');
            $table->smallInteger('prima_days');
            $table->smallInteger('vacation_days');
            $table->smallInteger('pending_salary_days');
            $table->smallInteger('total_days_worked');
            $table->jsonb('params_snapshot');
            $table->decimal('total_earned', 14, 2);
            $table->decimal('total_deductions', 14, 2);
            $table->decimal('net_total', 14, 2);
            $table->text('observations')->nullable();
            $table->timestamp('voided_at')->nullable();
            $table->timestamps();

            $table->unique('folio', 'uq_liq_folio');
            $table->index(['contract_id', 'status'], 'ix_liq_contract_status');
            $table->index('settlement_date', 'ix_liq_settlement_date');
        });

        foreach ([
            'ck_liq_variant' => "variant IN ('prestacional', 'con_indemnizacion')",
            'ck_liq_status' => "status IN ('borrador', 'calculada', 'aprobada', 'anulada')",
            'ck_liq_period' => 'period_to IS NULL OR period_to >= period_from',
            'ck_liq_math' => 'net_total = total_earned - total_deductions',
            'ck_liq_signs' => 'total_earned >= 0 AND total_deductions >= 0',
            'ck_liq_snapshot' => "jsonb_typeof(params_snapshot) = 'object'",
        ] as $name => $expression) {
            DB::statement("ALTER TABLE liquidations ADD CONSTRAINT {$name} CHECK ({$expression})");
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('liquidations');
    }
};
