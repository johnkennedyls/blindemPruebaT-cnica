<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('workers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_type_id')->constrained('document_types');
            $table->string('document_number', 30);
            $table->string('first_names', 120);
            $table->string('last_names', 120);
            $table->date('birth_date')->nullable();
            $table->string('gender', 8)->nullable();
            $table->string('email', 160)->nullable();
            $table->string('phone', 30)->nullable();
            $table->string('address', 250)->nullable();
            $table->string('city', 60)->nullable();
            $table->string('department', 60)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['document_type_id', 'document_number'], 'uq_worker_doc');
            $table->index('document_number', 'ix_worker_number');
            $table->index(['last_names', 'first_names'], 'ix_worker_names');
        });

        foreach ([
            'ck_worker_gender' => "gender IN ('m', 'f', 'otro')",
            'ck_worker_doc_number' => "document_number <> ''",
        ] as $name => $expression) {
            DB::statement("ALTER TABLE workers ADD CONSTRAINT {$name} CHECK ({$expression})");
        }

        if (DB::connection()->getDriverName() === 'pgsql') {
            DB::statement("CREATE INDEX ix_worker_name_trgm ON workers USING gin ((first_names || ' ' || last_names) gin_trgm_ops)");
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('workers');
    }
};
