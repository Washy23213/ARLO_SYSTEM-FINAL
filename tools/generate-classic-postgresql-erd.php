<?php

declare(strict_types=1);

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;

require __DIR__.'/../vendor/autoload.php';

$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

$outputDir = __DIR__.'/../docs/diagrams';
if (! is_dir($outputDir)) {
    mkdir($outputDir, 0775, true);
}

$svgPath = $outputDir.'/arlo-postgresql-classic-erd-landscape.svg';
$htmlPath = $outputDir.'/arlo-postgresql-classic-erd-landscape.html';

$tableRows = DB::select(<<<'SQL'
    select table_name
    from information_schema.tables
    where table_schema = current_schema()
      and table_type = 'BASE TABLE'
    order by table_name
SQL);

$columnRows = DB::select(<<<'SQL'
    select
        c.table_name,
        c.column_name,
        c.ordinal_position,
        c.data_type,
        c.character_maximum_length,
        c.numeric_precision,
        c.numeric_scale,
        c.is_nullable,
        c.column_default
    from information_schema.columns c
    join information_schema.tables t
      on t.table_schema = c.table_schema
     and t.table_name = c.table_name
    where c.table_schema = current_schema()
      and t.table_type = 'BASE TABLE'
    order by c.table_name, c.ordinal_position
SQL);

$constraintRows = DB::select(<<<'SQL'
    select
        con.conname as constraint_name,
        con.contype as constraint_type,
        child.relname as table_name,
        parent.relname as foreign_table_name,
        string_agg(child_att.attname, ',' order by child_cols.ord) as columns,
        string_agg(parent_att.attname, ',' order by child_cols.ord) as foreign_columns,
        con.confdeltype as on_delete
    from pg_constraint con
    join pg_class child on child.oid = con.conrelid
    join pg_namespace ns on ns.oid = child.relnamespace
    left join pg_class parent on parent.oid = con.confrelid
    left join lateral unnest(con.conkey) with ordinality as child_cols(attnum, ord) on true
    left join pg_attribute child_att
      on child_att.attrelid = con.conrelid
     and child_att.attnum = child_cols.attnum
    left join lateral unnest(con.confkey) with ordinality as parent_cols(attnum, ord)
      on parent_cols.ord = child_cols.ord
    left join pg_attribute parent_att
      on parent_att.attrelid = con.confrelid
     and parent_att.attnum = parent_cols.attnum
    where ns.nspname = current_schema()
      and con.contype in ('p', 'u', 'f')
    group by
        con.conname,
        con.contype,
        child.relname,
        parent.relname,
        con.confdeltype
    order by child.relname, con.contype, con.conname
SQL);

$tables = [];
foreach ($tableRows as $row) {
    $tables[$row->table_name] = [
        'columns' => [],
        'constraints' => [],
        'row_count' => DB::table($row->table_name)->count(),
    ];
}

foreach ($columnRows as $row) {
    $tables[$row->table_name]['columns'][] = $row;
}

foreach ($constraintRows as $row) {
    $tables[$row->table_name]['constraints'][] = $row;
}

$positions = [
    'users' => [90, 350],
    'admins' => [920, 350],
    'super_admins' => [1750, 350],
    'roles' => [2580, 350],
    'permissions' => [3410, 350],
    'admin_role' => [2580, 720],
    'role_permission' => [3410, 720],
    'password_reset_tokens' => [920, 1050],
    'sessions' => [1750, 1050],

    'tracer_records' => [90, 1760],
    'jobs' => [1070, 1760],
    'job_submissions' => [1900, 1760],
    'announcements' => [2730, 1760],
    'announcement_reads' => [3560, 1760],
    'notifications' => [3560, 2140],
    'feedback' => [4390, 1760],

    'system_logs' => [1070, 3000],
    'cache' => [1900, 3000],
    'cache_locks' => [2730, 3000],
    'failed_jobs' => [3560, 3000],
    'migrations' => [4390, 3000],
];

$tableWidth = 760;
$headerHeight = 44;
$columnHeaderHeight = 26;
$rowHeight = 23;
$footerHeight = 22;

foreach (array_keys($tables) as $tableName) {
    if (! isset($positions[$tableName])) {
        $positions[$tableName] = [90 + ((count($positions) % 6) * 830), 3420 + (intdiv(count($positions), 6) * 520)];
    }
}

$boxes = [];
$canvasWidth = 5400;
$canvasHeight = 3820;

foreach ($tables as $tableName => $table) {
    [$x, $y] = $positions[$tableName];
    $height = $headerHeight + $columnHeaderHeight + (count($table['columns']) * $rowHeight) + $footerHeight;
    $boxes[$tableName] = ['x' => $x, 'y' => $y, 'w' => $tableWidth, 'h' => $height];
    $canvasWidth = max($canvasWidth, $x + $tableWidth + 120);
    $canvasHeight = max($canvasHeight, $y + $height + 140);
}

function erdEsc(mixed $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function erdClassName(string $value): string
{
    return preg_replace('/[^a-z0-9_-]+/', '-', strtolower($value));
}

function erdType(object $column): string
{
    $type = (string) $column->data_type;
    $length = $column->character_maximum_length;
    $precision = $column->numeric_precision;
    $scale = $column->numeric_scale;

    return match ($type) {
        'character varying' => $length ? "varchar({$length})" : 'varchar',
        'character' => $length ? "char({$length})" : 'char',
        'timestamp with time zone' => 'timestamptz',
        'timestamp without time zone' => 'timestamp',
        'time with time zone' => 'timetz',
        'time without time zone' => 'time',
        'double precision' => 'double',
        'numeric' => $precision ? 'numeric('.$precision.($scale ? ','.$scale : '').')' : 'numeric',
        'USER-DEFINED' => 'uuid',
        default => $type,
    };
}

function erdDefault(?string $default): string
{
    if ($default === null || $default === '') {
        return '';
    }

    if (str_contains($default, 'nextval(')) {
        return 'seq';
    }

    $default = preg_replace("/::[a-zA-Z0-9_\\s]+/", '', $default) ?? $default;
    $default = str_replace(['CURRENT_TIMESTAMP', 'current_timestamp'], 'now()', $default);
    $default = trim($default, "'");

    return strlen($default) > 20 ? substr($default, 0, 17).'...' : $default;
}

function erdDeleteAction(?string $action): string
{
    return match ($action) {
        'c' => 'CASCADE',
        'n' => 'SET NULL',
        'r' => 'RESTRICT',
        'd' => 'SET DEFAULT',
        'a' => 'NO ACTION',
        default => '',
    };
}

function erdColumnFlags(string $tableName, string $columnName, array $tables): array
{
    $flags = [];

    foreach ($tables[$tableName]['constraints'] as $constraint) {
        $columns = array_filter(explode(',', (string) $constraint->columns));

        if (! in_array($columnName, $columns, true)) {
            continue;
        }

        if ($constraint->constraint_type === 'p') {
            $flags[] = 'PK';
        } elseif ($constraint->constraint_type === 'f') {
            $flags[] = 'FK';
        } elseif ($constraint->constraint_type === 'u') {
            $flags[] = 'UQ';
        }
    }

    return array_values(array_unique($flags));
}

function erdColumnTitle(string $tableName, object $column, array $tables): string
{
    $lines = [
        $tableName.'.'.$column->column_name,
        'Type: '.erdType($column),
        'Nullable: '.($column->is_nullable === 'NO' ? 'NO' : 'YES'),
    ];

    if ($column->column_default !== null) {
        $lines[] = 'Default: '.$column->column_default;
    }

    foreach ($tables[$tableName]['constraints'] as $constraint) {
        $columns = array_filter(explode(',', (string) $constraint->columns));

        if (! in_array($column->column_name, $columns, true)) {
            continue;
        }

        if ($constraint->constraint_type === 'p') {
            $lines[] = 'Primary key: '.$constraint->constraint_name;
        } elseif ($constraint->constraint_type === 'u') {
            $lines[] = 'Unique key: '.$constraint->constraint_name;
        } elseif ($constraint->constraint_type === 'f') {
            $lines[] = 'Foreign key: '.$constraint->constraint_name.' -> '.$constraint->foreign_table_name.'.'.$constraint->foreign_columns;
        }
    }

    return implode("\n", $lines);
}

function erdTableSvg(string $tableName, array $table, array $box, array $tables, int $headerHeight, int $columnHeaderHeight, int $rowHeight, int $footerHeight): string
{
    $x = $box['x'];
    $y = $box['y'];
    $w = $box['w'];
    $h = $box['h'];
    $headerBottom = $y + $headerHeight;
    $columnsBottom = $headerBottom + $columnHeaderHeight;
    $footerTop = $y + $h - $footerHeight;
    $tableId = 'table-'.erdClassName($tableName);
    $tableTitle = strtoupper($tableName);
    $rowCount = number_format($table['row_count']);
    $columnCount = count($table['columns']);
    $svg = '';

    $svg .= '<g id="'.erdEsc($tableId).'" class="entity">';
    $svg .= '<rect x="'.$x.'" y="'.$y.'" width="'.$w.'" height="'.$h.'" fill="#ffffff" stroke="#111827" stroke-width="2"/>';
    $svg .= '<rect x="'.$x.'" y="'.$y.'" width="'.$w.'" height="'.$headerHeight.'" fill="#111827"/>';
    $svg .= '<text x="'.($x + 16).'" y="'.($y + 29).'" font-size="17" font-weight="900" fill="#ffffff">'.erdEsc($tableTitle).'</text>';
    $svg .= '<text x="'.($x + $w - 16).'" y="'.($y + 29).'" text-anchor="end" font-size="12" font-weight="800" fill="#d1d5db">'.erdEsc($columnCount.' columns | '.$rowCount.' rows').'</text>';
    $svg .= '<rect x="'.$x.'" y="'.$headerBottom.'" width="'.$w.'" height="'.$columnHeaderHeight.'" fill="#e5e7eb" stroke="#111827" stroke-width="1"/>';
    $svg .= '<text x="'.($x + 16).'" y="'.($headerBottom + 18).'" font-size="11" font-weight="900" fill="#111827">KEY</text>';
    $svg .= '<text x="'.($x + 76).'" y="'.($headerBottom + 18).'" font-size="11" font-weight="900" fill="#111827">COLUMN</text>';
    $svg .= '<text x="'.($x + 410).'" y="'.($headerBottom + 18).'" font-size="11" font-weight="900" fill="#111827">TYPE</text>';
    $svg .= '<text x="'.($x + 560).'" y="'.($headerBottom + 18).'" font-size="11" font-weight="900" fill="#111827">NULL / DEFAULT</text>';
    $svg .= '<line x1="'.($x + 62).'" y1="'.$headerBottom.'" x2="'.($x + 62).'" y2="'.$footerTop.'" stroke="#9ca3af" stroke-width="1"/>';
    $svg .= '<line x1="'.($x + 395).'" y1="'.$headerBottom.'" x2="'.($x + 395).'" y2="'.$footerTop.'" stroke="#9ca3af" stroke-width="1"/>';
    $svg .= '<line x1="'.($x + 545).'" y1="'.$headerBottom.'" x2="'.($x + 545).'" y2="'.$footerTop.'" stroke="#9ca3af" stroke-width="1"/>';

    foreach ($table['columns'] as $index => $column) {
        $rowY = $columnsBottom + ($index * $rowHeight);
        $textY = $rowY + 16;
        $flags = implode('/', erdColumnFlags($tableName, $column->column_name, $tables));
        $nullDefault = $column->is_nullable === 'NO' ? 'NN' : 'NULL';
        $default = erdDefault($column->column_default);
        if ($default !== '') {
            $nullDefault .= ' / '.$default;
        }

        $fill = $index % 2 === 0 ? '#ffffff' : '#f9fafb';
        $svg .= '<g class="column">';
        $svg .= '<title>'.erdEsc(erdColumnTitle($tableName, $column, $tables)).'</title>';
        $svg .= '<rect x="'.$x.'" y="'.$rowY.'" width="'.$w.'" height="'.$rowHeight.'" fill="'.$fill.'" stroke="#d1d5db" stroke-width="0.7"/>';
        $svg .= '<text x="'.($x + 31).'" y="'.$textY.'" text-anchor="middle" font-size="10.5" font-weight="900" fill="#111827">'.erdEsc($flags).'</text>';
        $svg .= '<text x="'.($x + 76).'" y="'.$textY.'" font-size="12.5" font-weight="750" fill="#111827">'.erdEsc($column->column_name).'</text>';
        $svg .= '<text x="'.($x + 410).'" y="'.$textY.'" font-size="12" font-weight="700" fill="#374151">'.erdEsc(erdType($column)).'</text>';
        $svg .= '<text x="'.($x + 560).'" y="'.$textY.'" font-size="12" font-weight="700" fill="#374151">'.erdEsc($nullDefault).'</text>';
        $svg .= '</g>';
    }

    $svg .= '<rect x="'.$x.'" y="'.$footerTop.'" width="'.$w.'" height="'.$footerHeight.'" fill="#f3f4f6" stroke="#111827" stroke-width="1"/>';
    $svg .= '<text x="'.($x + 16).'" y="'.($footerTop + 15).'" font-size="10.5" font-weight="800" fill="#4b5563">PostgreSQL base table</text>';
    $svg .= '</g>';

    return $svg;
}

function erdColumnAnchor(string $tableName, string $columnName, array $tables, array $boxes, string $side, int $headerHeight, int $columnHeaderHeight, int $rowHeight): array
{
    $index = 0;
    foreach ($tables[$tableName]['columns'] as $candidateIndex => $column) {
        if ($column->column_name === $columnName) {
            $index = $candidateIndex;
            break;
        }
    }

    $box = $boxes[$tableName];
    $x = $side === 'right' ? $box['x'] + $box['w'] : $box['x'];
    $y = $box['y'] + $headerHeight + $columnHeaderHeight + ($index * $rowHeight) + ($rowHeight / 2);

    return [$x, $y];
}

function erdRelationshipSvg(object $foreignKey, array $tables, array $boxes, int $headerHeight, int $columnHeaderHeight, int $rowHeight, int $index): string
{
    $child = $foreignKey->table_name;
    $parent = $foreignKey->foreign_table_name;

    if (! isset($boxes[$child], $boxes[$parent])) {
        return '';
    }

    $childColumn = explode(',', (string) $foreignKey->columns)[0] ?? 'id';
    $parentColumn = explode(',', (string) $foreignKey->foreign_columns)[0] ?? 'id';
    $childBox = $boxes[$child];
    $parentBox = $boxes[$parent];
    $childCenter = $childBox['x'] + ($childBox['w'] / 2);
    $parentCenter = $parentBox['x'] + ($parentBox['w'] / 2);
    $parentSide = $parentCenter <= $childCenter ? 'right' : 'left';
    $childSide = $parentSide === 'right' ? 'left' : 'right';
    [$x1, $y1] = erdColumnAnchor($parent, $parentColumn, $tables, $boxes, $parentSide, $headerHeight, $columnHeaderHeight, $rowHeight);
    [$x2, $y2] = erdColumnAnchor($child, $childColumn, $tables, $boxes, $childSide, $headerHeight, $columnHeaderHeight, $rowHeight);
    $offset = (($index % 7) - 3) * 20;
    $midX = (($x1 + $x2) / 2) + $offset;
    $path = 'M '.$x1.' '.$y1.' C '.$midX.' '.$y1.', '.$midX.' '.$y2.', '.$x2.' '.$y2;
    $label = $child.'.'.$foreignKey->columns;
    $action = erdDeleteAction($foreignKey->on_delete);
    $label .= $action ? ' / '.$action : '';
    $labelX = ($x1 + $x2) / 2;
    $labelY = ($y1 + $y2) / 2 - 8 - (($index % 3) * 10);
    $labelW = min(300, max(110, strlen($label) * 6.2));

    $svg = '';
    $svg .= '<g class="relationship">';
    $svg .= '<path d="'.$path.'" fill="none" stroke="#111827" stroke-width="1.6" marker-start="url(#one)" marker-end="url(#crow)" stroke-linecap="round"/>';
    $svg .= '<rect x="'.($labelX - ($labelW / 2)).'" y="'.($labelY - 13).'" width="'.$labelW.'" height="20" fill="#ffffff" stroke="#d1d5db" stroke-width="0.8"/>';
    $svg .= '<text x="'.$labelX.'" y="'.($labelY + 2).'" text-anchor="middle" font-size="9.5" font-weight="800" fill="#111827">'.erdEsc($label).'</text>';
    $svg .= '</g>';

    return $svg;
}

$foreignKeys = array_values(array_filter($constraintRows, fn ($constraint) => $constraint->constraint_type === 'f'));
$totalColumns = array_sum(array_map(fn ($table) => count($table['columns']), $tables));
$databaseName = config('database.connections.'.config('database.default').'.database');
$generatedAt = now()->format('F j, Y g:i A');
$tableCount = count($tables);
$foreignKeyCount = count($foreignKeys);

$relationshipsSvg = '';
foreach ($foreignKeys as $index => $foreignKey) {
    $relationshipsSvg .= erdRelationshipSvg($foreignKey, $tables, $boxes, $headerHeight, $columnHeaderHeight, $rowHeight, $index);
}

$tablesSvg = '';
foreach (array_keys($tables) as $tableName) {
    $tablesSvg .= erdTableSvg($tableName, $tables[$tableName], $boxes[$tableName], $tables, $headerHeight, $columnHeaderHeight, $rowHeight, $footerHeight);
}

$titleX = 90;
$titleY = 72;
$subtitleY = 112;
$statsY = 150;
$legendX = 3860;
$legendY = 42;
$legendTextX = $legendX + 18;
$legendTitleY = $legendY + 30;
$legendFlagsY = $legendY + 62;
$legendLineY = $legendY + 95;
$legendLineEndX = $legendX + 180;
$legendLineLabelX = $legendX + 205;
$legendLineLabelY = $legendY + 100;
$legendNoteY = $legendY + 124;
$sourceX = $canvasWidth - 90;
$sourceY = $canvasHeight - 44;

$svg = <<<SVG
<?xml version="1.0" encoding="UTF-8"?>
<svg xmlns="http://www.w3.org/2000/svg" width="{$canvasWidth}" height="{$canvasHeight}" viewBox="0 0 {$canvasWidth} {$canvasHeight}" role="img" aria-labelledby="title desc">
    <title id="title">CMU ARLO Classic PostgreSQL ERD</title>
    <desc id="desc">Classic landscape database ERD with complete PostgreSQL tables and columns.</desc>
    <defs>
        <pattern id="grid" width="40" height="40" patternUnits="userSpaceOnUse">
            <path d="M 40 0 L 0 0 0 40" fill="none" stroke="#e5e7eb" stroke-width="1"/>
        </pattern>
        <marker id="crow" viewBox="0 0 18 18" refX="17" refY="9" markerWidth="15" markerHeight="15" orient="auto">
            <path d="M 1 2 L 17 9 L 1 16 M 1 9 L 17 9" fill="none" stroke="#111827" stroke-width="1.7" stroke-linecap="round"/>
        </marker>
        <marker id="one" viewBox="0 0 18 18" refX="1" refY="9" markerWidth="14" markerHeight="14" orient="auto">
            <path d="M 4 2 L 4 16" fill="none" stroke="#111827" stroke-width="2" stroke-linecap="round"/>
        </marker>
        <style>
            svg { background: #ffffff; font-family: "Segoe UI", Arial, sans-serif; }
            .entity text { letter-spacing: 0; }
            .relationship text { paint-order: stroke; stroke: #ffffff; stroke-width: 3px; stroke-linejoin: round; }
        </style>
    </defs>
    <rect width="100%" height="100%" fill="#ffffff"/>
    <rect width="100%" height="100%" fill="url(#grid)" opacity="0.55"/>
    <rect x="70" y="38" width="3100" height="140" fill="#ffffff" stroke="#111827" stroke-width="2"/>
    <text id="title-text" x="{$titleX}" y="{$titleY}" font-size="34" font-weight="950" fill="#111827">CMU ARLO PostgreSQL Database ERD</text>
    <text x="{$titleX}" y="{$subtitleY}" font-size="17" font-weight="750" fill="#374151">Classic physical ERD layout generated from live database: {$databaseName}</text>
    <text x="{$titleX}" y="{$statsY}" font-size="16" font-weight="850" fill="#111827">{$tableCount} tables | {$totalColumns} columns | {$foreignKeyCount} physical foreign keys | Generated {$generatedAt}</text>
    <g class="legend">
        <rect x="{$legendX}" y="{$legendY}" width="1400" height="136" fill="#ffffff" stroke="#111827" stroke-width="2"/>
        <text x="{$legendTextX}" y="{$legendTitleY}" font-size="18" font-weight="950" fill="#111827">Legend</text>
        <text x="{$legendTextX}" y="{$legendFlagsY}" font-size="13" font-weight="800" fill="#111827">PK = Primary Key | FK = Foreign Key | UQ = Unique Key | NN = Not Nullable</text>
        <line x1="{$legendTextX}" y1="{$legendLineY}" x2="{$legendLineEndX}" y2="{$legendLineY}" stroke="#111827" stroke-width="1.8" marker-start="url(#one)" marker-end="url(#crow)"/>
        <text x="{$legendLineLabelX}" y="{$legendLineLabelY}" font-size="13" font-weight="800" fill="#111827">One parent record connects to many child records.</text>
        <text x="{$legendTextX}" y="{$legendNoteY}" font-size="12" font-weight="750" fill="#4b5563">Note: Laravel notifications use notifiable_type/notifiable_id polymorphic columns, so those are shown as columns without a physical FK.</text>
    </g>
    <g class="relationships-layer">
        {$relationshipsSvg}
    </g>
    <g class="tables-layer">
        {$tablesSvg}
    </g>
    <text x="{$sourceX}" y="{$sourceY}" text-anchor="end" font-size="12.5" font-weight="800" fill="#4b5563">Source: PostgreSQL information_schema and pg_catalog</text>
</svg>
SVG;

file_put_contents($svgPath, $svg);

$svgFile = basename($svgPath);
$html = <<<HTML
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>CMU ARLO Classic PostgreSQL ERD</title>
    <style>
        @page { size: landscape; margin: 8mm; }
        body { margin: 0; background: #f3f4f6; font-family: "Segoe UI", Arial, sans-serif; color: #111827; }
        .toolbar {
            position: sticky;
            top: 0;
            z-index: 5;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 16px;
            padding: 12px 18px;
            background: rgba(255, 255, 255, 0.96);
            border-bottom: 1px solid #d1d5db;
        }
        h1 { margin: 0; font-size: 18px; }
        p { margin: 2px 0 0; color: #4b5563; font-size: 13px; font-weight: 700; }
        a, button {
            border: 1px solid #111827;
            background: #111827;
            color: #ffffff;
            border-radius: 4px;
            cursor: pointer;
            font: inherit;
            font-weight: 800;
            padding: 9px 14px;
            text-decoration: none;
        }
        .canvas { overflow: auto; padding: 16px; }
        img { display: block; width: 100%; min-width: 1900px; height: auto; background: #fff; border: 1px solid #d1d5db; }
        @media print {
            .toolbar { display: none; }
            .canvas { overflow: visible; padding: 0; }
            img { min-width: 0; border: 0; width: 100%; }
        }
    </style>
</head>
<body>
    <div class="toolbar">
        <div>
            <h1>CMU ARLO Classic PostgreSQL ERD</h1>
            <p>{$tableCount} tables, {$totalColumns} columns, {$foreignKeyCount} physical foreign keys. Classic database layout.</p>
        </div>
        <div>
            <a href="{$svgFile}" download>Download SVG</a>
            <button type="button" onclick="window.print()">Print Landscape</button>
        </div>
    </div>
    <main class="canvas">
        <img src="{$svgFile}" alt="CMU ARLO classic PostgreSQL ERD">
    </main>
</body>
</html>
HTML;

file_put_contents($htmlPath, $html);

echo "Generated classic ERD SVG: {$svgPath}".PHP_EOL;
echo "Generated classic ERD HTML: {$htmlPath}".PHP_EOL;
echo "{$tableCount} tables, {$totalColumns} columns, {$foreignKeyCount} physical foreign keys.".PHP_EOL;
