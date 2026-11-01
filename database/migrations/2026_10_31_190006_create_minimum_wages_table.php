<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('minimum_wages', function (Blueprint $table) {
            $table->id();
            $table->smallInteger('year')->unique('uq_mw_year');
            $table->decimal('smlmv', 14, 2);
            $table->decimal('transport_auxiliary', 14, 2);
            $table->date('effective_from');
            $table->date('effective_to');
            $table->string('source', 400)->nullable();
            $table->timestamps();
        });

        foreach ([
            'ck_mw_year' => "year BETWEEN 2000 AND 2100",
            'ck_mw_smlmv' => "smlmv > 0",
            'ck_mw_aux' => "transport_auxiliary >= 0",
            'ck_mw_period' => "effective_to >= effective_from",
        ] as $name => $expression) {
            DB::statement("ALTER TABLE minimum_wages ADD CONSTRAINT {$name} CHECK ({$expression})");
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('minimum_wages');
    }
};
