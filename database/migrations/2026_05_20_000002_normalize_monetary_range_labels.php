<?php

use App\Support\SystemOptions;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->normalizeColumn('users', 'salary_range', fn ($value) => SystemOptions::salaryRangeLabel($value));
        $this->normalizeColumn('jobs', 'salary_range', fn ($value) => SystemOptions::salaryRangeLabel($value));
        $this->normalizeColumn('tracer_records', 'monthly_income', fn ($value) => SystemOptions::presentJobEarningsRangeForIncome($value));
        $this->normalizeColumn('tracer_records', 'first_job_monthly_earnings', fn ($value) => SystemOptions::firstJobEarningsLabel($value));
        $this->normalizeColumn('tracer_records', 'present_job_monthly_earnings', fn ($value) => SystemOptions::presentJobEarningsLabel($value));
    }

    public function down(): void
    {
        //
    }

    protected function normalizeColumn(string $table, string $column, callable $normalizer): void
    {
        if (! Schema::hasTable($table) || ! Schema::hasColumn($table, $column)) {
            return;
        }

        foreach (DB::table($table)->whereNotNull($column)->select('id', $column)->get() as $record) {
            $normalized = $normalizer($record->{$column});

            if ($normalized !== null && $normalized !== (string) $record->{$column}) {
                DB::table($table)
                    ->where('id', $record->id)
                    ->update([$column => $normalized]);
            }
        }
    }
};
