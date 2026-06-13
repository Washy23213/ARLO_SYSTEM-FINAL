<?php

use App\Support\SystemOptions;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('tracer_records') || ! Schema::hasColumn('tracer_records', 'monthly_income')) {
            return;
        }

        $this->alterMonthlyIncomeToString();

        foreach (DB::table('tracer_records')->whereNotNull('monthly_income')->select('id', 'monthly_income')->get() as $record) {
            $range = SystemOptions::presentJobEarningsRangeForIncome($record->monthly_income);

            if ($range !== null && $range !== (string) $record->monthly_income) {
                DB::table('tracer_records')
                    ->where('id', $record->id)
                    ->update(['monthly_income' => $range]);
            }
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('tracer_records') || ! Schema::hasColumn('tracer_records', 'monthly_income')) {
            return;
        }

        DB::table('tracer_records')->whereNotNull('monthly_income')->update(['monthly_income' => null]);
        $this->alterMonthlyIncomeToDecimal();
    }

    protected function alterMonthlyIncomeToString(): void
    {
        match (DB::connection()->getDriverName()) {
            'pgsql' => DB::statement('ALTER TABLE tracer_records ALTER COLUMN monthly_income TYPE VARCHAR(100) USING monthly_income::VARCHAR'),
            'mysql', 'mariadb' => DB::statement('ALTER TABLE tracer_records MODIFY monthly_income VARCHAR(100) NULL'),
            'sqlsrv' => DB::statement('ALTER TABLE tracer_records ALTER COLUMN monthly_income NVARCHAR(100) NULL'),
            default => null,
        };
    }

    protected function alterMonthlyIncomeToDecimal(): void
    {
        match (DB::connection()->getDriverName()) {
            'pgsql' => DB::statement('ALTER TABLE tracer_records ALTER COLUMN monthly_income TYPE NUMERIC(10, 2) USING NULL'),
            'mysql', 'mariadb' => DB::statement('ALTER TABLE tracer_records MODIFY monthly_income DECIMAL(10, 2) NULL'),
            'sqlsrv' => DB::statement('ALTER TABLE tracer_records ALTER COLUMN monthly_income DECIMAL(10, 2) NULL'),
            default => null,
        };
    }
};
