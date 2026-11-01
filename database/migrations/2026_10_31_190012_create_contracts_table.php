<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contracts', function (Blueprint $table) {
            $table->id();
            $table->string('code', 30);
            $table->foreignId('worker_id')->constrained('workers');
            $table->foreignId('company_id')->constrained('companies');
            $table->foreignId('contract_type_id')->constrained('contract_types');
            $table->foreignId('position_id')->constrained('positions');
            $table->foreignId('termination_reason_id')->nullable()->constrained('termination_reasons');
            $table->foreignId('created_by')->constrained('users');
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->date('scheduled_end_date')->nullable();
            $table->date('salary_effective_from')->nullable();
            $table->decimal('base_salary_monthly', 14, 2);
            $table->decimal('transport_auxiliary_monthly', 14, 2)->default(0);
            $table->string('transport_auxiliary_mode', 16)->default('manual');
            $table->decimal('overtime_average_monthly', 14, 2)->default(0);
            $table->string('status', 20)->default('activo');
            $table->smallInteger('pending_salary_days')->default(0);
            $table->smallInteger('pending_vacation_days')->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique('code', 'uq_contract_code');
            $table->index(['worker_id', 'status'], 'ix_contract_worker_status');
            $table->index(['company_id', 'status'], 'ix_contract_company_status');
            $table->index('end_date', 'ix_contract_end');
            $table->index('created_by', 'ix_contract_creator');
        });

        foreach ([
            'ck_c_dates' => "end_date IS NULL OR end_date >= start_date",
            'ck_c_sched' => "scheduled_end_date IS NULL OR scheduled_end_date > start_date",
            'ck_c_salary' => "base_salary_monthly > 0",
            'ck_c_aux' => "transport_auxiliary_monthly >= 0",
            'ck_c_overtime' => "overtime_average_monthly >= 0",
            'ck_c_aux_mode' => "transport_auxiliary_mode IN ('auto', 'manual', 'sin_derecho')",
            'ck_c_status' => "status IN ('activo', 'terminado', 'en_liquidacion', 'liquidado')",
            'ck_c_pending_days' => "pending_salary_days BETWEEN 0 AND 30",
            'ck_c_pending_vac' => "pending_vacation_days BETWEEN 0 AND 365",
        ] as $name => $expression) {
            DB::statement("ALTER TABLE contracts ADD CONSTRAINT {$name} CHECK ({$expression})");
        }

        if (DB::connection()->getDriverName() === 'pgsql') {
            DB::statement(<<<'SQL'
                ALTER TABLE contracts
                    ADD CONSTRAINT ex_contract_no_overlap
                    EXCLUDE USING gist (
                        worker_id WITH =,
                        daterange(start_date, COALESCE(end_date, 'infinity'::date), '[]') WITH &&
                    )
                    WHERE (deleted_at IS NULL)
            SQL);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('contracts');
    }
};
