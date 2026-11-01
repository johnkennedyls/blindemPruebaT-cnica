<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('document_types', function (Blueprint $table) {
            $table->id();
            $table->string('code', 8)->unique('uq_dt_code');
            $table->string('name', 120);
            $table->string('abbreviation', 16)->nullable();
            $table->string('holder_type', 16)->default('natural');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        foreach ([
            'ck_dt_holder' => "holder_type IN ('natural', 'juridica', 'both')",
        ] as $name => $expression) {
            DB::statement("ALTER TABLE document_types ADD CONSTRAINT {$name} CHECK ({$expression})");
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('document_types');
    }
};
