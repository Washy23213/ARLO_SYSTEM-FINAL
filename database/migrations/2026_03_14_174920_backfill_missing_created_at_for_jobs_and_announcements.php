<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        DB::statement(<<<'SQL'
            UPDATE announcements
            SET created_at = COALESCE(created_at, published_at, updated_at, CURRENT_TIMESTAMP),
                updated_at = COALESCE(updated_at, created_at, published_at, CURRENT_TIMESTAMP)
            WHERE created_at IS NULL
               OR updated_at IS NULL
        SQL);

        DB::statement(<<<'SQL'
            WITH ranked_jobs AS (
                SELECT
                    id,
                    ROW_NUMBER() OVER (ORDER BY application_deadline ASC NULLS LAST, title ASC) AS row_num
                FROM jobs
                WHERE created_at IS NULL
            )
            UPDATE jobs AS j
            SET created_at = CURRENT_TIMESTAMP - (ranked_jobs.row_num * INTERVAL '3 days'),
                updated_at = COALESCE(
                    j.updated_at,
                    CURRENT_TIMESTAMP - (ranked_jobs.row_num * INTERVAL '3 days')
                )
            FROM ranked_jobs
            WHERE j.id = ranked_jobs.id
        SQL);

        DB::statement("ALTER TABLE jobs ALTER COLUMN created_at SET DEFAULT CURRENT_TIMESTAMP");
        DB::statement("ALTER TABLE jobs ALTER COLUMN updated_at SET DEFAULT CURRENT_TIMESTAMP");
        DB::statement("ALTER TABLE announcements ALTER COLUMN created_at SET DEFAULT CURRENT_TIMESTAMP");
        DB::statement("ALTER TABLE announcements ALTER COLUMN updated_at SET DEFAULT CURRENT_TIMESTAMP");
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        DB::statement("ALTER TABLE jobs ALTER COLUMN created_at DROP DEFAULT");
        DB::statement("ALTER TABLE jobs ALTER COLUMN updated_at DROP DEFAULT");
        DB::statement("ALTER TABLE announcements ALTER COLUMN created_at DROP DEFAULT");
        DB::statement("ALTER TABLE announcements ALTER COLUMN updated_at DROP DEFAULT");
    }
};
