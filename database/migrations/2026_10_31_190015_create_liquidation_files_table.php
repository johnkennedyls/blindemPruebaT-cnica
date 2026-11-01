<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('liquidation_files', function (Blueprint $table) {
            $table->id();
            $table->foreignId('liquidation_id')->constrained('liquidations')->cascadeOnDelete();
            $table->string('kind', 16);
            $table->string('storage_path');
            $table->string('filename', 160);
            $table->timestamp('generated_at')->nullable();

            $table->unique(['liquidation_id', 'kind', 'filename'], 'uq_liq_file');
        });

        foreach ([
            'ck_file_kind' => "kind IN ('excel', 'pdf', 'pdf_firmado')",
        ] as $name => $expression) {
            DB::statement("ALTER TABLE liquidation_files ADD CONSTRAINT {$name} CHECK ({$expression})");
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('liquidation_files');
    }
};
