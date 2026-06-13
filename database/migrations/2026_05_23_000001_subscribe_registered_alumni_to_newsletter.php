<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('users', 'newsletter_opt_in')) {
            return;
        }

        DB::table('users')
            ->where(function ($query): void {
                $query->where('newsletter_opt_in', false)
                    ->orWhereNull('newsletter_opt_in');
            })
            ->update(['newsletter_opt_in' => true]);
    }

    public function down(): void
    {
        // Existing subscription data is intentionally preserved.
    }
};
