<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::connection()->getDriverName() !== 'pgsql') {
            return;
        }

        DB::statement('ALTER TABLE contracts DROP CONSTRAINT IF EXISTS ex_contract_no_overlap');
        DB::statement('DROP EXTENSION IF EXISTS btree_gist');
    }

    public function down(): void
    {
        if (DB::connection()->getDriverName() !== 'pgsql') {
            return;
        }

        DB::statement('CREATE EXTENSION IF NOT EXISTS btree_gist');
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
};
