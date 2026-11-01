<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('action', 24);
            $table->string('subject_type', 120)->nullable();
            $table->unsignedBigInteger('subject_id')->nullable();
            $table->string('description', 400)->nullable();
            $table->jsonb('changes')->nullable();
            $table->timestamp('created_at')->nullable();

            $table->index(['subject_type', 'subject_id'], 'ix_al_subject');
            $table->index(['user_id', 'created_at'], 'ix_al_user_created');
        });

        foreach ([
            'ck_al_action' => "action IN ('crear', 'actualizar', 'eliminar', 'liquidar', 'anular', 'aprobar', 'exportar', 'iniciar_sesion')",
        ] as $name => $expression) {
            DB::statement("ALTER TABLE activity_logs ADD CONSTRAINT {$name} CHECK ({$expression})");
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_logs');
    }
};
