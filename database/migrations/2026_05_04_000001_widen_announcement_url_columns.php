<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['image_url', 'attachment_url', 'facebook_post_url'] as $column) {
            if (Schema::hasColumn('announcements', $column)) {
                $this->alterColumn($column, 'text');
            }
        }
    }

    public function down(): void
    {
        foreach (['image_url', 'attachment_url', 'facebook_post_url'] as $column) {
            if (Schema::hasColumn('announcements', $column)) {
                $this->alterColumn($column, 'varchar(500)');
            }
        }
    }

    private function alterColumn(string $column, string $type): void
    {
        $driver = DB::getDriverName();

        if ($driver === 'pgsql') {
            DB::statement(sprintf('ALTER TABLE announcements ALTER COLUMN %s TYPE %s', $column, $type));

            return;
        }

        if ($driver === 'mysql') {
            DB::statement(sprintf('ALTER TABLE announcements MODIFY %s %s NULL', $column, $type));
        }
    }
};
