<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_company', function (Blueprint $table) {
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->timestamp('granted_at')->nullable();

            $table->primary(['user_id', 'company_id']);
            $table->index('company_id', 'ix_user_company_company');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_company');
    }
};
