<?php

use App\Models\TracerRecord;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('tracer_records') || Schema::hasColumn('tracer_records', 'tracer_number')) {
            return;
        }

        Schema::table('tracer_records', function (Blueprint $table) {
            $table->string('tracer_number', 20)->nullable()->after('id');
        });

        DB::table('tracer_records')
            ->orderBy('tracer_date')
            ->orderBy('created_at')
            ->orderBy('id')
            ->pluck('id')
            ->values()
            ->each(function (string $id, int $index) {
                DB::table('tracer_records')
                    ->where('id', $id)
                    ->update(['tracer_number' => TracerRecord::formatTracerNumber($index + 1)]);
            });

        Schema::table('tracer_records', function (Blueprint $table) {
            $table->unique('tracer_number');
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('tracer_records') || ! Schema::hasColumn('tracer_records', 'tracer_number')) {
            return;
        }

        Schema::table('tracer_records', function (Blueprint $table) {
            $table->dropUnique(['tracer_number']);
            $table->dropColumn('tracer_number');
        });
    }
};
