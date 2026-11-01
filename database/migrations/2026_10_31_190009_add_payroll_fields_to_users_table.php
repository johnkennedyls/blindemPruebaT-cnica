<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('role', 24)->default('collaborator');
            $table->string('job_title', 80)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_login_at')->nullable();
        });

        foreach ([
            'ck_users_role' => "role IN ('administrator', 'collaborator')",
        ] as $name => $expression) {
            DB::statement("ALTER TABLE users ADD CONSTRAINT {$name} CHECK ({$expression})");
        }

        if (DB::connection()->getDriverName() === 'pgsql') {
            DB::statement('CREATE UNIQUE INDEX uq_users_email_lower ON users (lower(email))');
        }
    }

    public function down(): void
    {
        if (DB::connection()->getDriverName() === 'pgsql') {
            DB::statement('DROP INDEX IF EXISTS uq_users_email_lower');
        }

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['role', 'job_title', 'is_active', 'last_login_at']);
        });
    }
};
