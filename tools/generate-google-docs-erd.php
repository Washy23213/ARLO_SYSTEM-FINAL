<?php

declare(strict_types=1);

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;

require __DIR__.'/../vendor/autoload.php';

$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

$outputDir = __DIR__.'/../docs/diagrams/google-docs-erd';
if (! is_dir($outputDir)) {
    mkdir($outputDir, 0775, true);
}

$pageWidth = 3300;
$pageHeight = 2550;
$databaseName = config('database.connections.'.config('database.default').'.database');
$generatedAt = now()->format('F j, Y g:i A');

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

$foreignKeys = array_values(array_filter($constraintRows, fn ($constraint) => $constraint->constraint_type === 'f'));
$totalColumns = array_sum(array_map(fn ($table) => count($table['columns']), $tables));

function gdEsc(mixed $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function gdClass(string $value): string
{
    return preg_replace('/[^a-z0-9_-]+/', '-', strtolower($value));
}

function gdType(object $column): string
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
        default => $type,
    };
}

function gdDefault(?string $default): string
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

    return strlen($default) > 22 ? substr($default, 0, 19).'...' : $default;
}

function gdDeleteAction(?string $action): string
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

function gdColumnFlags(string $tableName, string $columnName, array $tables): array
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

function gdColumnTitle(string $tableName, object $column, array $tables): string
{
    $lines = [
        $tableName.'.'.$column->column_name,
        'Type: '.gdType($column),
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

function gdSegmentCount(string $tableName, array $table, int $maxRows): int
{
    return max(1, (int) ceil(count($table['columns']) / max(1, $maxRows)));
}

function gdTableBox(array $spec, array $tables): array
{
    $tableName = $spec['table'];
    $rowHeight = $spec['rowHeight'] ?? 30;
    $headerHeight = $spec['headerHeight'] ?? 64;
    $columnHeaderHeight = $spec['columnHeaderHeight'] ?? 34;
    $footerHeight = $spec['footerHeight'] ?? 26;
    $maxRows = $spec['maxRows'] ?? 24;
    $segments = gdSegmentCount($tableName, $tables[$tableName], $maxRows);
    $visibleRows = min($maxRows, max(1, count($tables[$tableName]['columns'])));
    $h = $headerHeight + $columnHeaderHeight + ($visibleRows * $rowHeight) + $footerHeight;

    return [
        'x' => $spec['x'],
        'y' => $spec['y'],
        'w' => $spec['w'],
        'h' => $h,
        'maxRows' => $maxRows,
        'segments' => $segments,
        'rowHeight' => $rowHeight,
        'headerHeight' => $headerHeight,
        'columnHeaderHeight' => $columnHeaderHeight,
        'footerHeight' => $footerHeight,
        'title' => $spec['title'] ?? $tableName,
    ];
}

function gdTableSvg(string $tableName, array $table, array $box, array $tables): string
{
    $x = $box['x'];
    $y = $box['y'];
    $w = $box['w'];
    $h = $box['h'];
    $segments = $box['segments'];
    $segmentWidth = $w / $segments;
    $rowHeight = $box['rowHeight'];
    $headerHeight = $box['headerHeight'];
    $columnHeaderHeight = $box['columnHeaderHeight'];
    $footerHeight = $box['footerHeight'];
    $maxRows = $box['maxRows'];
    $headerBottom = $y + $headerHeight;
    $columnBottom = $headerBottom + $columnHeaderHeight;
    $footerTop = $y + $h - $footerHeight;
    $title = strtoupper((string) $box['title']);
    $rowCount = number_format($table['row_count']);
    $columnCount = count($table['columns']);
    $svg = '';

    $svg .= '<g id="table-'.gdClass($tableName).'" class="erd-table">';
    $svg .= '<rect x="'.$x.'" y="'.$y.'" width="'.$w.'" height="'.$h.'" rx="10" fill="#ffffff" stroke="#111827" stroke-width="3"/>';
    $svg .= '<rect x="'.$x.'" y="'.$y.'" width="'.$w.'" height="'.$headerHeight.'" rx="10" fill="#111827"/>';
    $svg .= '<text x="'.($x + 22).'" y="'.($y + 39).'" font-size="24" font-weight="900" fill="#ffffff">'.gdEsc($title).'</text>';
    $svg .= '<text x="'.($x + $w - 22).'" y="'.($y + 39).'" text-anchor="end" font-size="15" font-weight="800" fill="#e5e7eb">'.gdEsc($columnCount.' columns | '.$rowCount.' rows').'</text>';

    for ($segment = 0; $segment < $segments; $segment++) {
        $sx = $x + ($segment * $segmentWidth);
        $svg .= '<rect x="'.$sx.'" y="'.$headerBottom.'" width="'.$segmentWidth.'" height="'.$columnHeaderHeight.'" fill="#e5e7eb" stroke="#111827" stroke-width="1.2"/>';
        $svg .= '<text x="'.($sx + 14).'" y="'.($headerBottom + 23).'" font-size="13" font-weight="900" fill="#111827">KEY</text>';
        $svg .= '<text x="'.($sx + 78).'" y="'.($headerBottom + 23).'" font-size="13" font-weight="900" fill="#111827">COLUMN</text>';
        $svg .= '<text x="'.($sx + ($segmentWidth * 0.56)).'" y="'.($headerBottom + 23).'" font-size="13" font-weight="900" fill="#111827">TYPE</text>';
        $svg .= '<text x="'.($sx + ($segmentWidth * 0.76)).'" y="'.($headerBottom + 23).'" font-size="13" font-weight="900" fill="#111827">NULL / DEFAULT</text>';
        $svg .= '<line x1="'.($sx + 62).'" y1="'.$headerBottom.'" x2="'.($sx + 62).'" y2="'.$footerTop.'" stroke="#9ca3af" stroke-width="1.1"/>';
        $svg .= '<line x1="'.($sx + ($segmentWidth * 0.54)).'" y1="'.$headerBottom.'" x2="'.($sx + ($segmentWidth * 0.54)).'" y2="'.$footerTop.'" stroke="#9ca3af" stroke-width="1.1"/>';
        $svg .= '<line x1="'.($sx + ($segmentWidth * 0.74)).'" y1="'.$headerBottom.'" x2="'.($sx + ($segmentWidth * 0.74)).'" y2="'.$footerTop.'" stroke="#9ca3af" stroke-width="1.1"/>';
    }

    foreach ($table['columns'] as $index => $column) {
        $segment = intdiv($index, $maxRows);
        $row = $index % $maxRows;
        $sx = $x + ($segment * $segmentWidth);
        $rowY = $columnBottom + ($row * $rowHeight);
        $textY = $rowY + 20;
        $flags = implode('/', gdColumnFlags($tableName, $column->column_name, $tables));
        $nullDefault = $column->is_nullable === 'NO' ? 'NN' : 'NULL';
        $default = gdDefault($column->column_default);
        if ($default !== '') {
            $nullDefault .= ' / '.$default;
        }
        $fill = $row % 2 === 0 ? '#ffffff' : '#f9fafb';

        $svg .= '<g class="erd-column">';
        $svg .= '<title>'.gdEsc(gdColumnTitle($tableName, $column, $tables)).'</title>';
        $svg .= '<rect x="'.$sx.'" y="'.$rowY.'" width="'.$segmentWidth.'" height="'.$rowHeight.'" fill="'.$fill.'" stroke="#d1d5db" stroke-width="0.8"/>';
        $svg .= '<text x="'.($sx + 31).'" y="'.$textY.'" text-anchor="middle" font-size="13" font-weight="900" fill="#111827">'.gdEsc($flags).'</text>';
        $svg .= '<text x="'.($sx + 78).'" y="'.$textY.'" font-size="16" font-weight="750" fill="#111827">'.gdEsc($column->column_name).'</text>';
        $svg .= '<text x="'.($sx + ($segmentWidth * 0.56)).'" y="'.$textY.'" font-size="15" font-weight="700" fill="#374151">'.gdEsc(gdType($column)).'</text>';
        $svg .= '<text x="'.($sx + ($segmentWidth * 0.76)).'" y="'.$textY.'" font-size="15" font-weight="700" fill="#374151">'.gdEsc($nullDefault).'</text>';
        $svg .= '</g>';
    }

    $svg .= '<rect x="'.$x.'" y="'.$footerTop.'" width="'.$w.'" height="'.$footerHeight.'" fill="#f3f4f6" stroke="#111827" stroke-width="1.2"/>';
    $svg .= '<text x="'.($x + 18).'" y="'.($footerTop + 18).'" font-size="13" font-weight="800" fill="#4b5563">Physical PostgreSQL table</text>';
    $svg .= '</g>';

    return $svg;
}

function gdColumnAnchor(string $tableName, string $columnName, array $tables, array $boxes, string $side): array
{
    $box = $boxes[$tableName];
    $index = 0;

    foreach ($tables[$tableName]['columns'] as $candidateIndex => $column) {
        if ($column->column_name === $columnName) {
            $index = $candidateIndex;
            break;
        }
    }

    $segment = intdiv($index, $box['maxRows']);
    $row = $index % $box['maxRows'];
    $segmentWidth = $box['w'] / $box['segments'];
    $x = $side === 'right'
        ? $box['x'] + (($segment + 1) * $segmentWidth)
        : $box['x'] + ($segment * $segmentWidth);
    $y = $box['y'] + $box['headerHeight'] + $box['columnHeaderHeight'] + ($row * $box['rowHeight']) + ($box['rowHeight'] / 2);

    return [$x, $y];
}

function gdRelationshipSvg(object $foreignKey, array $tables, array $boxes, int $index): string
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
    [$x1, $y1] = gdColumnAnchor($parent, $parentColumn, $tables, $boxes, $parentSide);
    [$x2, $y2] = gdColumnAnchor($child, $childColumn, $tables, $boxes, $childSide);
    $offset = (($index % 5) - 2) * 18;
    $midX = (($x1 + $x2) / 2) + $offset;
    $path = 'M '.$x1.' '.$y1.' C '.$midX.' '.$y1.', '.$midX.' '.$y2.', '.$x2.' '.$y2;
    $action = gdDeleteAction($foreignKey->on_delete);
    $label = $child.'.'.$foreignKey->columns.($action ? ' / '.$action : '');
    $labelX = ($x1 + $x2) / 2;
    $labelY = ($y1 + $y2) / 2 - 9 - (($index % 3) * 11);
    $labelW = min(360, max(160, strlen($label) * 7.4));
    $svg = '';

    $svg .= '<g class="erd-relationship">';
    $svg .= '<path d="'.$path.'" fill="none" stroke="#111827" stroke-width="2.1" marker-start="url(#one)" marker-end="url(#crow)" stroke-linecap="round"/>';
    $svg .= '<rect x="'.($labelX - ($labelW / 2)).'" y="'.($labelY - 16).'" width="'.$labelW.'" height="25" fill="#ffffff" stroke="#d1d5db" stroke-width="1"/>';
    $svg .= '<text x="'.$labelX.'" y="'.($labelY + 2).'" text-anchor="middle" font-size="12.5" font-weight="850" fill="#111827">'.gdEsc($label).'</text>';
    $svg .= '</g>';

    return $svg;
}

function gdPageStart(int $width, int $height, string $title, string $subtitle, string $stats): string
{
    $svg = '';
    $svg .= '<?xml version="1.0" encoding="UTF-8"?>'."\n";
    $svg .= '<svg xmlns="http://www.w3.org/2000/svg" width="'.$width.'" height="'.$height.'" viewBox="0 0 '.$width.' '.$height.'" role="img">'."\n";
    $svg .= '<defs>';
    $svg .= '<pattern id="grid" width="48" height="48" patternUnits="userSpaceOnUse"><path d="M 48 0 L 0 0 0 48" fill="none" stroke="#eef0f3" stroke-width="1"/></pattern>';
    $svg .= '<marker id="crow" viewBox="0 0 18 18" refX="17" refY="9" markerWidth="17" markerHeight="17" orient="auto"><path d="M 1 2 L 17 9 L 1 16 M 1 9 L 17 9" fill="none" stroke="#111827" stroke-width="1.8" stroke-linecap="round"/></marker>';
    $svg .= '<marker id="one" viewBox="0 0 18 18" refX="1" refY="9" markerWidth="16" markerHeight="16" orient="auto"><path d="M 4 2 L 4 16" fill="none" stroke="#111827" stroke-width="2.2" stroke-linecap="round"/></marker>';
    $svg .= '<style>svg{font-family:"Segoe UI",Arial,sans-serif;background:#fff}.erd-relationship text{paint-order:stroke;stroke:#fff;stroke-width:4px;stroke-linejoin:round}</style>';
    $svg .= '</defs>';
    $svg .= '<rect width="100%" height="100%" fill="#ffffff"/><rect width="100%" height="100%" fill="url(#grid)" opacity="0.55"/>';
    $svg .= '<rect x="70" y="42" width="'.($width - 140).'" height="165" rx="12" fill="#ffffff" stroke="#111827" stroke-width="3"/>';
    $svg .= '<text x="100" y="92" font-size="34" font-weight="950" fill="#111827">'.gdEsc($title).'</text>';
    $svg .= '<text x="100" y="132" font-size="20" font-weight="750" fill="#374151">'.gdEsc($subtitle).'</text>';
    $svg .= '<text x="100" y="170" font-size="17" font-weight="850" fill="#111827">'.gdEsc($stats).'</text>';

    return $svg;
}

function gdPageEnd(int $width, int $height): string
{
    return '<text x="'.($width - 80).'" y="'.($height - 45).'" text-anchor="end" font-size="13" font-weight="800" fill="#4b5563">CMU ARLO PostgreSQL ERD | Google Docs readable page</text></svg>';
}

function gdOverviewBox(string $name, int $x, int $y, int $w, int $h, array $table): string
{
    $columnCount = count($table['columns']);
    $rowCount = number_format($table['row_count']);

    $svg = '<g id="overview-'.gdClass($name).'">';
    $svg .= '<rect x="'.$x.'" y="'.$y.'" width="'.$w.'" height="'.$h.'" rx="8" fill="#ffffff" stroke="#111827" stroke-width="2.2"/>';
    $svg .= '<rect x="'.$x.'" y="'.$y.'" width="'.$w.'" height="42" rx="8" fill="#111827"/>';
    $svg .= '<text x="'.($x + 16).'" y="'.($y + 29).'" font-size="17" font-weight="900" fill="#ffffff">'.gdEsc($name).'</text>';
    $svg .= '<text x="'.($x + 16).'" y="'.($y + 72).'" font-size="13" font-weight="800" fill="#374151">'.gdEsc($columnCount.' columns | '.$rowCount.' rows').'</text>';
    $svg .= '</g>';

    return $svg;
}

$statsLine = count($tables).' tables | '.$totalColumns.' columns | '.count($foreignKeys).' physical foreign keys | '.$databaseName.' | '.$generatedAt;

$pages = [
    [
        'slug' => '01-overview',
        'title' => 'CMU ARLO ERD Overview',
        'subtitle' => 'Relationship map for the full PostgreSQL schema. Detailed complete-column pages follow.',
        'tables' => [],
    ],
    [
        'slug' => '02-alumni-users',
        'title' => 'Alumni Users and Login Support',
        'subtitle' => 'Complete users table plus user session and password reset support tables.',
        'tables' => [
            ['table' => 'users', 'x' => 90, 'y' => 300, 'w' => 3120, 'maxRows' => 24],
            ['table' => 'sessions', 'x' => 90, 'y' => 1360, 'w' => 1520, 'maxRows' => 18],
            ['table' => 'password_reset_tokens', 'x' => 1690, 'y' => 1360, 'w' => 1520, 'maxRows' => 18],
        ],
    ],
    [
        'slug' => '03-admin-rbac',
        'title' => 'Admin Accounts and RBAC',
        'subtitle' => 'Complete admin, super admin, role, permission, and pivot tables.',
        'tables' => [
            ['table' => 'admins', 'x' => 90, 'y' => 300, 'w' => 980, 'maxRows' => 16],
            ['table' => 'super_admins', 'x' => 1160, 'y' => 300, 'w' => 980, 'maxRows' => 16],
            ['table' => 'roles', 'x' => 2230, 'y' => 300, 'w' => 980, 'maxRows' => 16],
            ['table' => 'permissions', 'x' => 90, 'y' => 860, 'w' => 980, 'maxRows' => 16],
            ['table' => 'admin_role', 'x' => 1160, 'y' => 860, 'w' => 980, 'maxRows' => 16],
            ['table' => 'role_permission', 'x' => 2230, 'y' => 860, 'w' => 980, 'maxRows' => 16],
        ],
    ],
    [
        'slug' => '04-tracer-records',
        'title' => 'Tracer Records',
        'subtitle' => 'Complete tracer_records table with employment, survey, proof, and review fields.',
        'tables' => [
            ['table' => 'tracer_records', 'x' => 90, 'y' => 300, 'w' => 3120, 'maxRows' => 26],
        ],
    ],
    [
        'slug' => '05-jobs-workflow',
        'title' => 'Jobs and Job Submission Workflow',
        'subtitle' => 'Complete job board and alumni job submission approval tables.',
        'tables' => [
            ['table' => 'jobs', 'x' => 90, 'y' => 300, 'w' => 1520, 'maxRows' => 18],
            ['table' => 'job_submissions', 'x' => 1690, 'y' => 300, 'w' => 1520, 'maxRows' => 18],
        ],
    ],
    [
        'slug' => '06-communications-support',
        'title' => 'Announcements, Feedback, and Notifications',
        'subtitle' => 'Complete communication, read tracking, support, and notification tables.',
        'tables' => [
            ['table' => 'announcements', 'x' => 90, 'y' => 300, 'w' => 1520, 'maxRows' => 20],
            ['table' => 'feedback', 'x' => 1690, 'y' => 300, 'w' => 1520, 'maxRows' => 20],
            ['table' => 'announcement_reads', 'x' => 90, 'y' => 1420, 'w' => 980, 'maxRows' => 14],
            ['table' => 'notifications', 'x' => 1160, 'y' => 1420, 'w' => 980, 'maxRows' => 14],
        ],
    ],
    [
        'slug' => '07-audit-platform',
        'title' => 'Audit and Platform Tables',
        'subtitle' => 'Complete system log and Laravel operational support tables.',
        'tables' => [
            ['table' => 'system_logs', 'x' => 90, 'y' => 300, 'w' => 1520, 'maxRows' => 18],
            ['table' => 'cache', 'x' => 1690, 'y' => 300, 'w' => 700, 'maxRows' => 12],
            ['table' => 'cache_locks', 'x' => 2510, 'y' => 300, 'w' => 700, 'maxRows' => 12],
            ['table' => 'failed_jobs', 'x' => 1690, 'y' => 780, 'w' => 700, 'maxRows' => 14],
            ['table' => 'migrations', 'x' => 2510, 'y' => 780, 'w' => 700, 'maxRows' => 12],
        ],
    ],
];

$overviewPositions = [
    'users' => [90, 320],
    'admins' => [650, 320],
    'super_admins' => [1210, 320],
    'roles' => [1770, 320],
    'permissions' => [2330, 320],
    'admin_role' => [1770, 600],
    'role_permission' => [2330, 600],
    'sessions' => [90, 600],
    'password_reset_tokens' => [650, 600],
    'tracer_records' => [90, 980],
    'jobs' => [650, 980],
    'job_submissions' => [1210, 980],
    'announcements' => [1770, 980],
    'announcement_reads' => [2330, 980],
    'feedback' => [90, 1360],
    'notifications' => [650, 1360],
    'system_logs' => [1210, 1360],
    'cache' => [1770, 1360],
    'cache_locks' => [2330, 1360],
    'failed_jobs' => [1770, 1640],
    'migrations' => [2330, 1640],
];

$generatedFiles = [];

foreach ($pages as $pageIndex => $page) {
    $svg = gdPageStart($pageWidth, $pageHeight, $page['title'], $page['subtitle'], $statsLine);

    if ($page['slug'] === '01-overview') {
        $overviewBoxes = [];
        foreach ($overviewPositions as $tableName => [$x, $y]) {
            if (! isset($tables[$tableName])) {
                continue;
            }

            $overviewBoxes[$tableName] = ['x' => $x, 'y' => $y, 'w' => 460, 'h' => 96];
        }

        foreach ($foreignKeys as $index => $foreignKey) {
            $child = $foreignKey->table_name;
            $parent = $foreignKey->foreign_table_name;

            if (! isset($overviewBoxes[$child], $overviewBoxes[$parent])) {
                continue;
            }

            $from = $overviewBoxes[$parent];
            $to = $overviewBoxes[$child];
            $x1 = $from['x'] + $from['w'];
            $y1 = $from['y'] + ($from['h'] / 2);
            $x2 = $to['x'];
            $y2 = $to['y'] + ($to['h'] / 2);

            if ($x1 > $x2) {
                $x1 = $from['x'];
                $x2 = $to['x'] + $to['w'];
            }

            $midX = (($x1 + $x2) / 2) + ((($index % 5) - 2) * 14);
            $svg .= '<path d="M '.$x1.' '.$y1.' C '.$midX.' '.$y1.', '.$midX.' '.$y2.', '.$x2.' '.$y2.'" fill="none" stroke="#111827" stroke-width="1.9" marker-start="url(#one)" marker-end="url(#crow)" stroke-linecap="round"/>';
        }

        foreach ($overviewPositions as $tableName => [$x, $y]) {
            if (isset($tables[$tableName])) {
                $svg .= gdOverviewBox($tableName, $x, $y, 460, 96, $tables[$tableName]);
            }
        }

        $svg .= '<rect x="90" y="2020" width="3120" height="220" rx="12" fill="#ffffff" stroke="#111827" stroke-width="2.4"/>';
        $svg .= '<text x="120" y="2070" font-size="25" font-weight="950" fill="#111827">Reading note</text>';
        $svg .= '<text x="120" y="2110" font-size="18" font-weight="750" fill="#374151">This page is the relationship map only. Use pages 2-7 for complete column-level details.</text>';
        $svg .= '<text x="120" y="2150" font-size="18" font-weight="750" fill="#374151">Notifications use Laravel polymorphic notifiable_type/notifiable_id, so that connection is logical and not a physical PostgreSQL FK.</text>';
    } else {
        $boxes = [];
        foreach ($page['tables'] as $spec) {
            if (! isset($tables[$spec['table']])) {
                continue;
            }
            $boxes[$spec['table']] = gdTableBox($spec, $tables);
        }

        foreach ($foreignKeys as $index => $foreignKey) {
            $svg .= gdRelationshipSvg($foreignKey, $tables, $boxes, $index);
        }

        foreach ($page['tables'] as $spec) {
            $tableName = $spec['table'];
            if (! isset($boxes[$tableName], $tables[$tableName])) {
                continue;
            }

            $svg .= gdTableSvg($tableName, $tables[$tableName], $boxes[$tableName], $tables);
        }
    }

    $svg .= gdPageEnd($pageWidth, $pageHeight);

    $filename = 'arlo-erd-google-docs-'.$page['slug'].'.svg';
    $path = $outputDir.'/'.$filename;
    file_put_contents($path, $svg);
    $generatedFiles[] = $path;
}

$indexHtml = '<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>CMU ARLO Google Docs ERD Packet</title><style>@page{size:landscape;margin:8mm}body{margin:0;background:#f3f4f6;font-family:"Segoe UI",Arial,sans-serif;color:#111827}.page{padding:18px;break-after:page}.page h2{margin:0 0 10px;font-size:18px}.page img{width:100%;height:auto;background:#fff;border:1px solid #d1d5db}@media print{.page{padding:0}.page h2{display:none}.page img{border:0}}</style></head><body>';
foreach ($pages as $page) {
    $svgFile = 'arlo-erd-google-docs-'.$page['slug'].'.svg';
    $pngFile = 'arlo-erd-google-docs-'.$page['slug'].'.png';
    $indexHtml .= '<section class="page"><h2>'.gdEsc($page['title']).'</h2><img src="'.gdEsc(file_exists($outputDir.'/'.$pngFile) ? $pngFile : $svgFile).'" alt="'.gdEsc($page['title']).'"></section>';
}
$indexHtml .= '</body></html>';
file_put_contents($outputDir.'/index.html', $indexHtml);

echo 'Generated '.count($generatedFiles).' Google Docs ERD SVG pages in '.$outputDir.PHP_EOL;

