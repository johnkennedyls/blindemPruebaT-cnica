<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('companies', function (Blueprint $table) {
            $table->id();
            $table->string('nit', 30);
            $table->string('business_name', 200);
            $table->string('trade_name', 200)->nullable();
            $table->string('activity_code', 20)->nullable();
            $table->string('address', 250)->nullable();
            $table->string('city', 60)->nullable();
            $table->string('phone', 30)->nullable();
            $table->string('email', 160)->nullable();
            $table->string('legal_rep_name', 200)->nullable();
            $table->string('legal_rep_document', 30)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique('nit', 'uq_company_nit');
        });

        foreach ([
            'ck_company_nit' => 'char_length(nit) BETWEEN 8 AND 15',
            'ck_company_name' => "business_name <> ''",
        ] as $name => $expression) {
            DB::statement("ALTER TABLE companies ADD CONSTRAINT {$name} CHECK ({$expression})");
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('companies');
    }
};
