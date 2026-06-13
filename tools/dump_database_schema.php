<?php

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;

require __DIR__.'/../vendor/autoload.php';

$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

$tables = DB::select("
    select table_name
    from information_schema.tables
    where table_schema = current_schema()
      and table_type = 'BASE TABLE'
    order by table_name
");

$columns = DB::select("
    select
        table_name,
        column_name,
        data_type,
        is_nullable,
        column_default,
        character_maximum_length,
        numeric_precision,
        numeric_scale,
        datetime_precision,
        ordinal_position
    from information_schema.columns
    where table_schema = current_schema()
    order by table_name, ordinal_position
");

$constraints = DB::select("
    select
        tc.table_name,
        tc.constraint_name,
        tc.constraint_type,
        kcu.column_name,
        ccu.table_name as foreign_table_name,
        ccu.column_name as foreign_column_name
    from information_schema.table_constraints tc
    left join information_schema.key_column_usage kcu
        on tc.constraint_name = kcu.constraint_name
       and tc.table_schema = kcu.table_schema
    left join information_schema.constraint_column_usage ccu
        on tc.constraint_name = ccu.constraint_name
       and tc.table_schema = ccu.table_schema
    where tc.table_schema = current_schema()
    order by tc.table_name, tc.constraint_type, tc.constraint_name, kcu.ordinal_position
");

$rowCounts = [];
foreach ($tables as $table) {
    $name = $table->table_name;
    $rowCounts[$name] = DB::table($name)->count();
}

echo json_encode([
    'generated_at' => now()->toDateTimeString(),
    'database' => config('database.connections.'.config('database.default').'.database'),
    'connection' => config('database.default'),
    'tables' => $tables,
    'columns' => $columns,
    'constraints' => $constraints,
    'row_counts' => $rowCounts,
], JSON_PRETTY_PRINT);
