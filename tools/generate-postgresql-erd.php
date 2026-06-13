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

$svgPath = $outputDir.'/arlo-postgresql-full-erd-landscape.svg';
$htmlPath = $outputDir.'/arlo-postgresql-full-erd-landscape.html';

$tables = [];

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
        c.udt_name,
        c.character_maximum_length,
        c.numeric_precision,
        c.numeric_scale,
        c.datetime_precision,
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
        con.confdeltype as on_delete,
        con.confupdtype as on_update
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
        con.confdeltype,
        con.confupdtype
    order by child.relname, con.contype, con.conname
SQL);

$indexRows = DB::select(<<<'SQL'
    select tablename as table_name, indexname as index_name, indexdef
    from pg_indexes
    where schemaname = current_schema()
    order by tablename, indexname
SQL);

foreach ($tableRows as $row) {
    $name = $row->table_name;
    $tables[$name] = [
        'name' => $name,
        'columns' => [],
        'constraints' => [],
        'indexes' => [],
        'row_count' => DB::table($name)->count(),
    ];
}

foreach ($columnRows as $row) {
    $tables[$row->table_name]['columns'][] = $row;
}

foreach ($constraintRows as $row) {
    $tables[$row->table_name]['constraints'][] = $row;
}

foreach ($indexRows as $row) {
    $tables[$row->table_name]['indexes'][] = $row;
}

$moduleMap = [
    'Identity & Access' => [
        'description' => 'Alumni accounts, staff accounts, roles, permissions, sign-in sessions, and password reset support.',
        'color' => '#0f766e',
        'fill' => '#ecfdf5',
        'tables' => ['users', 'admins', 'super_admins', 'roles', 'permissions', 'admin_role', 'role_permission', 'password_reset_tokens', 'sessions'],
        'box' => [100, 420, 2380, 1780],
    ],
    'Alumni Profile & Tracer' => [
        'description' => 'Graduate profile linkage, tracer submissions, employment status, survey answers, and proof review fields.',
        'color' => '#1d4ed8',
        'fill' => '#eff6ff',
        'tables' => ['tracer_records'],
        'box' => [2540, 420, 1840, 1780],
    ],
    'Jobs & Approval Workflow' => [
        'description' => 'Admin job board records and alumni-submitted job opportunities awaiting review or rejection reason.',
        'color' => '#b45309',
        'fill' => '#fffbeb',
        'tables' => ['jobs', 'job_submissions'],
        'box' => [4440, 420, 1660, 1780],
    ],
    'Announcements, Feedback & Notifications' => [
        'description' => 'Published notices, read tracking, in-system notifications, and alumni support tickets.',
        'color' => '#7c3aed',
        'fill' => '#f5f3ff',
        'tables' => ['announcements', 'announcement_reads', 'notifications', 'feedback'],
        'box' => [100, 2250, 2380, 1400],
    ],
    'Audit & Platform Tables' => [
        'description' => 'System activity logs plus Laravel operational tables used by cache, queues, migrations, and failures.',
        'color' => '#334155',
        'fill' => '#f8fafc',
        'tables' => ['system_logs', 'cache', 'cache_locks', 'failed_jobs', 'migrations'],
        'box' => [2540, 2250, 3560, 1400],
    ],
];

$positions = [
    'users' => [140, 560],
    'admins' => [1320, 560],
    'super_admins' => [1900, 560],
    'roles' => [1320, 1080],
    'permissions' => [1900, 1080],
    'admin_role' => [1320, 1450],
    'role_permission' => [1900, 1450],
    'password_reset_tokens' => [1320, 1800],
    'sessions' => [1900, 1800],

    'tracer_records' => [2580, 560],

    'jobs' => [4480, 560],
    'job_submissions' => [5060, 560],

    'announcements' => [140, 2390],
    'announcement_reads' => [1320, 2390],
    'notifications' => [1900, 2390],
    'feedback' => [1320, 2930],

    'system_logs' => [2580, 2390],
    'cache' => [3160, 2390],
    'cache_locks' => [3740, 2390],
    'failed_jobs' => [4320, 2390],
    'migrations' => [4900, 2390],
];

$moduleForTable = [];
foreach ($moduleMap as $moduleName => $module) {
    foreach ($module['tables'] as $tableName) {
        $moduleForTable[$tableName] = $moduleName;
    }
}

$unplacedX = 5480;
$unplacedY = 2390;
foreach (array_keys($tables) as $tableName) {
    if (! isset($positions[$tableName])) {
        $positions[$tableName] = [$unplacedX, $unplacedY];
        $unplacedY += 390;
    }
}

$colors = [
    'page' => '#f6f8f3',
    'ink' => '#062b1d',
    'muted' => '#526071',
    'line' => '#64748b',
    'softLine' => '#d8e0db',
    'white' => '#ffffff',
    'green' => '#065f46',
    'gold' => '#c98a08',
    'danger' => '#be123c',
];

$rowHeight = 34;
$headerHeight = 78;
$footerHeight = 40;
$segmentWidth = 560;
$defaultMaxRows = 16;
$maxRowsByTable = [
    'users' => 28,
    'tracer_records' => 30,
    'announcements' => 26,
    'jobs' => 28,
    'job_submissions' => 24,
    'system_logs' => 20,
];

$tableBoxes = [];
foreach ($tables as $tableName => $table) {
    $columnCount = count($table['columns']);
    $maxRows = $maxRowsByTable[$tableName] ?? $defaultMaxRows;
    $segments = max(1, (int) ceil($columnCount / $maxRows));
    $visibleRows = max(1, min($maxRows, $columnCount));
    [$x, $y] = $positions[$tableName];

    $tableBoxes[$tableName] = [
        'x' => $x,
        'y' => $y,
        'w' => $segments * $segmentWidth,
        'h' => $headerHeight + ($visibleRows * $rowHeight) + $footerHeight,
        'segments' => $segments,
        'max_rows' => $maxRows,
        'visible_rows' => $visibleRows,
    ];
}

$canvasWidth = 6260;
$canvasHeight = 3820;

foreach ($tableBoxes as $box) {
    $canvasWidth = max($canvasWidth, $box['x'] + $box['w'] + 140);
    $canvasHeight = max($canvasHeight, $box['y'] + $box['h'] + 140);
}

function esc(mixed $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function cssClass(string $value): string
{
    return preg_replace('/[^a-z0-9_-]+/', '-', strtolower($value));
}

function formatDataType(object $column): string
{
    $type = (string) $column->data_type;
    $length = $column->character_maximum_length;
    $precision = $column->numeric_precision;
    $scale = $column->numeric_scale;

    return match ($type) {
        'character varying' => 'varchar'.($length ? "({$length})" : ''),
        'character' => 'char'.($length ? "({$length})" : ''),
        'timestamp with time zone' => 'timestamptz',
        'timestamp without time zone' => 'timestamp',
        'time with time zone' => 'timetz',
        'time without time zone' => 'time',
        'double precision' => 'double',
        'numeric' => $precision ? 'numeric('.$precision.($scale ? ','.$scale : '').')' : 'numeric',
        default => $type,
    };
}

function shortDefault(?string $default): string
{
    if ($default === null || $default === '') {
        return '';
    }

    if (str_contains($default, 'nextval(')) {
        return 'seq';
    }

    $default = preg_replace("/::[a-zA-Z0-9_\\s]+/", '', $default) ?? $default;
    $default = str_replace(['CURRENT_TIMESTAMP', 'current_timestamp'], 'now()', $default);
    $default = trim($default);

    return strlen($default) > 28 ? substr($default, 0, 25).'...' : $default;
}

function constraintDeleteAction(?string $action): string
{
    return match ($action) {
        'a' => 'NO ACTION',
        'r' => 'RESTRICT',
        'c' => 'CASCADE',
        'n' => 'SET NULL',
        'd' => 'SET DEFAULT',
        default => '',
    };
}

function columnFlags(string $tableName, string $columnName, array $tables): array
{
    $flags = [];

    foreach ($tables[$tableName]['constraints'] as $constraint) {
        $columns = array_filter(explode(',', (string) $constraint->columns));

        if (! in_array($columnName, $columns, true)) {
            continue;
        }

        $flags[] = match ($constraint->constraint_type) {
            'p' => 'PK',
            'u' => 'UQ',
            'f' => 'FK',
            default => null,
        };
    }

    return array_values(array_unique(array_filter($flags)));
}

function tableConstraintCounts(array $table): array
{
    $counts = ['p' => 0, 'u' => 0, 'f' => 0];

    foreach ($table['constraints'] as $constraint) {
        if (isset($counts[$constraint->constraint_type])) {
            $counts[$constraint->constraint_type]++;
        }
    }

    return $counts;
}

function columnConstraintTitle(string $tableName, string $columnName, array $tables): string
{
    $lines = [];

    foreach ($tables[$tableName]['constraints'] as $constraint) {
        $columns = array_filter(explode(',', (string) $constraint->columns));

        if (! in_array($columnName, $columns, true)) {
            continue;
        }

        if ($constraint->constraint_type === 'p') {
            $lines[] = 'Primary key: '.$constraint->constraint_name;
        } elseif ($constraint->constraint_type === 'u') {
            $lines[] = 'Unique key: '.$constraint->constraint_name;
        } elseif ($constraint->constraint_type === 'f') {
            $target = $constraint->foreign_table_name.'.'.$constraint->foreign_columns;
            $delete = constraintDeleteAction($constraint->on_delete);
            $lines[] = 'Foreign key: '.$constraint->constraint_name.' -> '.$target.($delete ? ' on delete '.$delete : '');
        }
    }

    return implode("\n", $lines);
}

function tableSvg(string $tableName, array $table, array $box, array $tables, array $moduleMap, array $moduleForTable, array $colors, int $rowHeight, int $headerHeight, int $footerHeight, int $segmentWidth): string
{
    $module = $moduleMap[$moduleForTable[$tableName] ?? 'Audit & Platform Tables'] ?? reset($moduleMap);
    $accent = $module['color'];
    $fill = $module['fill'];
    $x = $box['x'];
    $y = $box['y'];
    $w = $box['w'];
    $h = $box['h'];
    $counts = tableConstraintCounts($table);
    $constraintText = $counts['p'].' PK / '.$counts['u'].' UQ / '.$counts['f'].' FK';
    $rowText = number_format($table['row_count']).' rows';
    $columnText = count($table['columns']).' columns';
    $id = 'table-'.cssClass($tableName);
    $headerLineY = $y + $headerHeight;
    $tableRight = $x + $w;
    $titleX = $x + 24;
    $titleY = $y + 34;
    $subtitleY = $y + 60;

    $svg = <<<SVG
        <g id="{$id}" class="table table-{$id}">
            <rect x="{$x}" y="{$y}" width="{$w}" height="{$h}" rx="18" fill="{$colors['white']}" stroke="{$accent}" stroke-width="3"/>
            <rect x="{$x}" y="{$y}" width="{$w}" height="{$headerHeight}" rx="18" fill="{$fill}" stroke="none"/>
            <line x1="{$x}" y1="{$headerLineY}" x2="{$tableRight}" y2="{$headerLineY}" stroke="{$accent}" stroke-width="2"/>
            <text x="{$titleX}" y="{$titleY}" font-size="23" font-weight="900" fill="{$colors['ink']}">{$tableName}</text>
            <text x="{$titleX}" y="{$subtitleY}" font-size="15" font-weight="700" fill="{$colors['muted']}">{$columnText} | {$rowText} | {$constraintText}</text>
    SVG;

    for ($segment = 1; $segment < $box['segments']; $segment++) {
        $lineX = $x + ($segment * $segmentWidth);
        $svg .= "\n".'<line x1="'.$lineX.'" y1="'.($y + $headerHeight).'" x2="'.$lineX.'" y2="'.($y + $h - $footerHeight).'" stroke="'.$accent.'" stroke-width="1.4" opacity="0.32"/>';
    }

    foreach ($table['columns'] as $index => $column) {
        $segment = intdiv($index, $box['max_rows']);
        $row = $index % $box['max_rows'];
        $rowX = $x + ($segment * $segmentWidth);
        $rowY = $y + $headerHeight + ($row * $rowHeight);
        $flags = columnFlags($tableName, $column->column_name, $tables);
        $flagText = implode('/', $flags);
        $nullable = $column->is_nullable === 'NO' ? 'NOT NULL' : 'NULL';
        $default = shortDefault($column->column_default);
        $type = formatDataType($column);
        $line = $column->column_name.' : '.$type.' '.$nullable.($default ? ' = '.$default : '');
        $title = $tableName.'.'.$column->column_name."\nType: ".$type."\nNullable: ".$nullable;

        if ($column->column_default !== null) {
            $title .= "\nDefault: ".$column->column_default;
        }

        $constraintTitle = columnConstraintTitle($tableName, $column->column_name, $tables);
        if ($constraintTitle !== '') {
            $title .= "\n".$constraintTitle;
        }

        if ($row > 0) {
            $svg .= "\n".'<line x1="'.$rowX.'" y1="'.$rowY.'" x2="'.($rowX + $segmentWidth).'" y2="'.$rowY.'" stroke="'.$colors['softLine'].'" stroke-width="1"/>';
        }

        $badgeFill = $flagText === '' ? '#f1f5f9' : $fill;
        $badgeStroke = $flagText === '' ? '#d7dfdc' : $accent;
        $badgeText = $flagText === '' ? '-' : $flagText;
        $textClass = in_array('FK', $flags, true) ? ' column-fk' : '';
        $svg .= "\n".'<g class="column-row'.$textClass.'">';
        $svg .= '<title>'.esc($title).'</title>';
        $svg .= '<rect x="'.($rowX + 16).'" y="'.($rowY + 6).'" width="58" height="22" rx="11" fill="'.$badgeFill.'" stroke="'.$badgeStroke.'" stroke-width="1.2"/>';
        $svg .= '<text x="'.($rowX + 45).'" y="'.($rowY + 22).'" text-anchor="middle" font-size="11" font-weight="900" fill="'.$accent.'">'.esc($badgeText).'</text>';
        $svg .= '<text x="'.($rowX + 88).'" y="'.($rowY + 23).'" font-size="15.5" font-weight="700" fill="'.$colors['ink'].'">'.esc($line).'</text>';
        $svg .= '</g>';
    }

    $footerY = $y + $h - 14;
    $svg .= "\n".'<line x1="'.$x.'" y1="'.($y + $h - $footerHeight).'" x2="'.($x + $w).'" y2="'.($y + $h - $footerHeight).'" stroke="'.$colors['softLine'].'" stroke-width="1.2"/>';
    $svg .= "\n".'<text x="'.($x + 24).'" y="'.$footerY.'" font-size="13" font-weight="700" fill="'.$colors['muted'].'">Indexes: '.count($table['indexes']).' | Physical PostgreSQL table</text>';
    $svg .= "\n".'</g>';

    return $svg;
}

function columnAnchor(string $tableName, string $columnName, array $tables, array $box, string $side, int $rowHeight, int $headerHeight, int $segmentWidth): array
{
    $columns = array_map(fn ($column) => $column->column_name, $tables[$tableName]['columns']);
    $index = array_search($columnName, $columns, true);

    if ($index === false) {
        $index = 0;
    }

    $segment = intdiv((int) $index, $box['max_rows']);
    $row = (int) $index % $box['max_rows'];
    $x = $side === 'right' ? $box['x'] + (($segment + 1) * $segmentWidth) : $box['x'] + ($segment * $segmentWidth);
    $y = $box['y'] + $headerHeight + ($row * $rowHeight) + ($rowHeight / 2);

    return [$x, $y];
}

function relationshipSvg(object $constraint, array $tables, array $tableBoxes, array $colors, int $rowHeight, int $headerHeight, int $segmentWidth, int $index): string
{
    $child = $constraint->table_name;
    $parent = $constraint->foreign_table_name;

    if (! isset($tableBoxes[$child], $tableBoxes[$parent])) {
        return '';
    }

    $childColumns = explode(',', (string) $constraint->columns);
    $parentColumns = explode(',', (string) $constraint->foreign_columns);
    $childColumn = $childColumns[0] ?? 'id';
    $parentColumn = $parentColumns[0] ?? 'id';
    $childBox = $tableBoxes[$child];
    $parentBox = $tableBoxes[$parent];
    $childCenter = $childBox['x'] + ($childBox['w'] / 2);
    $parentCenter = $parentBox['x'] + ($parentBox['w'] / 2);
    $childSide = $childCenter <= $parentCenter ? 'right' : 'left';
    $parentSide = $childSide === 'right' ? 'left' : 'right';
    [$x1, $y1] = columnAnchor($child, $childColumn, $tables, $childBox, $childSide, $rowHeight, $headerHeight, $segmentWidth);
    [$x2, $y2] = columnAnchor($parent, $parentColumn, $tables, $parentBox, $parentSide, $rowHeight, $headerHeight, $segmentWidth);
    $offset = (($index % 5) - 2) * 18;
    $midX = ($x1 + $x2) / 2 + $offset;
    $labelX = ($x1 + $x2) / 2;
    $labelY = ($y1 + $y2) / 2 - 10 - (($index % 3) * 12);
    $delete = constraintDeleteAction($constraint->on_delete);
    $label = $child.'.'.$constraint->columns.' -> '.$parent.'.'.$constraint->foreign_columns;
    $deleteLabel = $delete ? 'on delete '.$delete : '';
    $path = 'M '.$x1.' '.$y1.' C '.$midX.' '.$y1.', '.$midX.' '.$y2.', '.$x2.' '.$y2;
    $labelWidth = min(620, max(260, strlen($label) * 8));
    $labelRectX = $labelX - ($labelWidth / 2);
    $labelRectY = $labelY - 21;
    $labelMainY = $labelY - 3;
    $labelSubY = $labelY + 13;

    return <<<SVG
        <g class="relationship">
            <path d="{$path}" fill="none" stroke="{$colors['line']}" stroke-width="2.4" stroke-opacity="0.58" marker-end="url(#arrow)" stroke-linecap="round"/>
            <rect x="{$labelRectX}" y="{$labelRectY}" width="{$labelWidth}" height="40" rx="20" fill="#ffffff" stroke="#d7dfdc" stroke-width="1.1" opacity="0.93"/>
            <text x="{$labelX}" y="{$labelMainY}" text-anchor="middle" font-size="12.5" font-weight="850" fill="{$colors['ink']}">{$label}</text>
            <text x="{$labelX}" y="{$labelSubY}" text-anchor="middle" font-size="11" font-weight="700" fill="{$colors['muted']}">{$deleteLabel}</text>
        </g>
    SVG;
}

function logicalRelationshipSvg(string $fromTable, string $toTable, string $label, array $tableBoxes, array $colors, int $index): string
{
    if (! isset($tableBoxes[$fromTable], $tableBoxes[$toTable])) {
        return '';
    }

    $from = $tableBoxes[$fromTable];
    $to = $tableBoxes[$toTable];
    $x1 = $from['x'] + $from['w'];
    $y1 = $from['y'] + 118 + ($index * 18);
    $x2 = $to['x'];
    $y2 = $to['y'] + 118 + ($index * 18);

    if ($x1 > $x2) {
        $x1 = $from['x'];
        $x2 = $to['x'] + $to['w'];
    }

    $midX = ($x1 + $x2) / 2;
    $path = 'M '.$x1.' '.$y1.' C '.$midX.' '.$y1.', '.$midX.' '.$y2.', '.$x2.' '.$y2;
    $labelX = ($x1 + $x2) / 2;
    $labelY = ($y1 + $y2) / 2 + 30 + ($index * 8);
    $labelRectX = $labelX - 160;
    $labelRectY = $labelY - 20;
    $labelTextY = $labelY + 2;

    return <<<SVG
        <g class="relationship logical">
            <path d="{$path}" fill="none" stroke="{$colors['gold']}" stroke-width="2.4" stroke-opacity="0.55" stroke-dasharray="12 10" marker-end="url(#arrowGold)" stroke-linecap="round"/>
            <rect x="{$labelRectX}" y="{$labelRectY}" width="320" height="34" rx="17" fill="#fff7ed" stroke="{$colors['gold']}" stroke-width="1" opacity="0.94"/>
            <text x="{$labelX}" y="{$labelTextY}" text-anchor="middle" font-size="12" font-weight="850" fill="{$colors['ink']}">{$label}</text>
        </g>
    SVG;
}

$foreignKeys = array_values(array_filter($constraintRows, fn ($constraint) => $constraint->constraint_type === 'f'));
$totalColumns = array_sum(array_map(fn ($table) => count($table['columns']), $tables));
$databaseName = config('database.connections.'.config('database.default').'.database');
$connection = config('database.default');
$generatedAt = now()->format('F j, Y g:i A');
$tableCount = count($tables);
$foreignKeyCount = count($foreignKeys);

$moduleSvg = '';
foreach ($moduleMap as $moduleName => $module) {
    [$x, $y, $w, $h] = $module['box'];
    $moduleTitleX = $x + 32;
    $moduleTitleY = $y + 46;
    $moduleDescriptionY = $y + 76;
    $moduleNameText = esc($moduleName);
    $moduleDescriptionText = esc($module['description']);
    $moduleSvg .= <<<SVG
        <g class="module">
            <rect x="{$x}" y="{$y}" width="{$w}" height="{$h}" rx="28" fill="{$module['fill']}" stroke="{$module['color']}" stroke-width="2.5" opacity="0.86"/>
            <text x="{$moduleTitleX}" y="{$moduleTitleY}" font-size="25" font-weight="950" fill="{$colors['ink']}">{$moduleNameText}</text>
            <text x="{$moduleTitleX}" y="{$moduleDescriptionY}" font-size="15" font-weight="700" fill="{$colors['muted']}">{$moduleDescriptionText}</text>
        </g>
    SVG;
}

$relationshipSvg = '';
foreach ($foreignKeys as $index => $foreignKey) {
    $relationshipSvg .= relationshipSvg($foreignKey, $tables, $tableBoxes, $colors, $rowHeight, $headerHeight, $segmentWidth, $index);
}

$relationshipSvg .= logicalRelationshipSvg('notifications', 'users', 'logical notifiable: alumni user', $tableBoxes, $colors, 0);
$relationshipSvg .= logicalRelationshipSvg('notifications', 'admins', 'logical notifiable: admin', $tableBoxes, $colors, 1);
$relationshipSvg .= logicalRelationshipSvg('notifications', 'super_admins', 'logical notifiable: super admin', $tableBoxes, $colors, 2);

$tablesSvg = '';
foreach (array_keys($tables) as $tableName) {
    $tablesSvg .= tableSvg($tableName, $tables[$tableName], $tableBoxes[$tableName], $tables, $moduleMap, $moduleForTable, $colors, $rowHeight, $headerHeight, $footerHeight, $segmentWidth);
}

$legendX = 4040;
$legendY = 130;
$legendTitleX = $legendX + 28;
$legendTitleY = $legendY + 42;
$legendRowY = $legendY + 72;
$legendTextY = $legendY + 89;
$legendLineY = $legendY + 127;
$legendLineTextY = $legendY + 132;
$legendNoteY = $legendY + 174;
$legendPkX = $legendX + 32;
$legendPkTextX = $legendX + 61;
$legendPkLabelX = $legendX + 108;
$legendFkX = $legendX + 310;
$legendFkTextX = $legendX + 339;
$legendFkLabelX = $legendX + 386;
$legendUqX = $legendX + 595;
$legendUqTextX = $legendX + 624;
$legendUqLabelX = $legendX + 671;
$legendPhysicalLineStartX = $legendX + 32;
$legendPhysicalLineEndX = $legendX + 202;
$legendPhysicalLabelX = $legendX + 220;
$legendLogicalLineStartX = $legendX + 595;
$legendLogicalLineEndX = $legendX + 765;
$legendLogicalLabelX = $legendX + 783;
$legend = <<<SVG
    <g class="legend">
        <rect x="{$legendX}" y="{$legendY}" width="2060" height="210" rx="24" fill="#ffffff" stroke="#d8e0db" stroke-width="2"/>
        <text x="{$legendTitleX}" y="{$legendTitleY}" font-size="24" font-weight="950" fill="{$colors['ink']}">How to read this ERD</text>
        <rect x="{$legendPkX}" y="{$legendRowY}" width="58" height="24" rx="12" fill="#ecfdf5" stroke="#0f766e" stroke-width="1.4"/>
        <text x="{$legendPkTextX}" y="{$legendTextY}" text-anchor="middle" font-size="11" font-weight="950" fill="#0f766e">PK</text>
        <text x="{$legendPkLabelX}" y="{$legendTextY}" font-size="15" font-weight="800" fill="{$colors['muted']}">Primary key</text>
        <rect x="{$legendFkX}" y="{$legendRowY}" width="58" height="24" rx="12" fill="#ecfdf5" stroke="#0f766e" stroke-width="1.4"/>
        <text x="{$legendFkTextX}" y="{$legendTextY}" text-anchor="middle" font-size="11" font-weight="950" fill="#0f766e">FK</text>
        <text x="{$legendFkLabelX}" y="{$legendTextY}" font-size="15" font-weight="800" fill="{$colors['muted']}">Foreign key</text>
        <rect x="{$legendUqX}" y="{$legendRowY}" width="58" height="24" rx="12" fill="#ecfdf5" stroke="#0f766e" stroke-width="1.4"/>
        <text x="{$legendUqTextX}" y="{$legendTextY}" text-anchor="middle" font-size="11" font-weight="950" fill="#0f766e">UQ</text>
        <text x="{$legendUqLabelX}" y="{$legendTextY}" font-size="15" font-weight="800" fill="{$colors['muted']}">Unique key</text>
        <line x1="{$legendPhysicalLineStartX}" y1="{$legendLineY}" x2="{$legendPhysicalLineEndX}" y2="{$legendLineY}" stroke="{$colors['line']}" stroke-width="3" marker-end="url(#arrow)"/>
        <text x="{$legendPhysicalLabelX}" y="{$legendLineTextY}" font-size="15" font-weight="800" fill="{$colors['muted']}">Physical PostgreSQL FK relationship</text>
        <line x1="{$legendLogicalLineStartX}" y1="{$legendLineY}" x2="{$legendLogicalLineEndX}" y2="{$legendLineY}" stroke="{$colors['gold']}" stroke-width="3" stroke-dasharray="12 10" marker-end="url(#arrowGold)"/>
        <text x="{$legendLogicalLabelX}" y="{$legendLineTextY}" font-size="15" font-weight="800" fill="{$colors['muted']}">Logical Laravel polymorphic notification link</text>
        <text x="{$legendPkX}" y="{$legendNoteY}" font-size="15" font-weight="800" fill="{$colors['muted']}">Each column shows: name : PostgreSQL type nullability = default. Hover an SVG row for full defaults and key details.</text>
    </g>
SVG;

$notesX = 2580;
$notesY = 1735;
$notesTextX = $notesX + 28;
$notesTitleY = $notesY + 42;
$notesLine1Y = $notesY + 78;
$notesLine2Y = $notesY + 116;
$notesTitle2Y = $notesY + 174;
$notesLine3Y = $notesY + 210;
$notesLine4Y = $notesY + 248;
$notes = <<<SVG
    <g class="model-notes">
        <rect x="{$notesX}" y="{$notesY}" width="1740" height="330" rx="24" fill="#ffffff" stroke="#bfdbfe" stroke-width="2"/>
        <text x="{$notesTextX}" y="{$notesTitleY}" font-size="24" font-weight="950" fill="{$colors['ink']}">Tracer workflow note</text>
        <text x="{$notesTextX}" y="{$notesLine1Y}" font-size="16" font-weight="750" fill="{$colors['muted']}">One alumni user can have many tracer_records. The table stores employment status, employer, ratings, survey answers, proof upload metadata, and proof review fields.</text>
        <text x="{$notesTextX}" y="{$notesLine2Y}" font-size="16" font-weight="750" fill="{$colors['muted']}">The unique tracer_number identifies a submitted tracer snapshot. user_id keeps each tracer entry tied to the graduate account.</text>
        <text x="{$notesTextX}" y="{$notesTitle2Y}" font-size="24" font-weight="950" fill="{$colors['ink']}">Notification workflow note</text>
        <text x="{$notesTextX}" y="{$notesLine3Y}" font-size="16" font-weight="750" fill="{$colors['muted']}">notifications uses Laravel's polymorphic notifiable_type and notifiable_id, so it can notify alumni, admins, or super admins without a physical FK.</text>
        <text x="{$notesTextX}" y="{$notesLine4Y}" font-size="16" font-weight="750" fill="{$colors['muted']}">Rejected job submissions notify the alumni in-system and by email, with the rejection reason stored in job_submissions.admin_notes and notification data.</text>
    </g>
SVG;

$sourceTextX = $canvasWidth - 120;
$sourceTextY = $canvasHeight - 52;

$svg = <<<SVG
<?xml version="1.0" encoding="UTF-8"?>
<svg xmlns="http://www.w3.org/2000/svg" width="{$canvasWidth}" height="{$canvasHeight}" viewBox="0 0 {$canvasWidth} {$canvasHeight}" role="img" aria-labelledby="title desc">
    <title id="title">CMU ARLO PostgreSQL Database ERD</title>
    <desc id="desc">Landscape, high-definition entity relationship diagram generated from the live PostgreSQL schema.</desc>
    <defs>
        <pattern id="grid" width="64" height="64" patternUnits="userSpaceOnUse">
            <path d="M 64 0 L 0 0 0 64" fill="none" stroke="#dfe7df" stroke-width="1" opacity="0.62"/>
        </pattern>
        <marker id="arrow" markerWidth="13" markerHeight="13" refX="11" refY="6.5" orient="auto" markerUnits="strokeWidth">
            <path d="M 1 1 L 12 6.5 L 1 12 Z" fill="{$colors['line']}"/>
        </marker>
        <marker id="arrowGold" markerWidth="13" markerHeight="13" refX="11" refY="6.5" orient="auto" markerUnits="strokeWidth">
            <path d="M 1 1 L 12 6.5 L 1 12 Z" fill="{$colors['gold']}"/>
        </marker>
        <filter id="cardShadow" x="-10%" y="-10%" width="120%" height="130%">
            <feDropShadow dx="0" dy="12" stdDeviation="16" flood-color="#0f172a" flood-opacity="0.11"/>
        </filter>
        <style>
            svg { background: {$colors['page']}; font-family: "Inter", "Segoe UI", Arial, sans-serif; }
            .table { filter: url(#cardShadow); }
            .relationship text { paint-order: stroke; stroke: #ffffff; stroke-width: 3px; stroke-linejoin: round; }
            .module text, .legend text, .model-notes text { letter-spacing: 0; }
        </style>
    </defs>
    <rect width="100%" height="100%" fill="{$colors['page']}"/>
    <rect width="100%" height="100%" fill="url(#grid)"/>
    <rect x="100" y="80" width="2940" height="260" rx="30" fill="#ffffff" stroke="#d8e0db" stroke-width="2"/>
    <text x="142" y="146" font-size="42" font-weight="950" fill="{$colors['ink']}">CMU ARLO PostgreSQL Database ERD</text>
    <text x="142" y="196" font-size="20" font-weight="750" fill="{$colors['muted']}">Landscape HD physical ERD generated from {$databaseName} on {$generatedAt}</text>
    <text x="142" y="240" font-size="18" font-weight="800" fill="{$colors['green']}">{$tableCount} tables | {$totalColumns} columns | {$foreignKeyCount} physical foreign keys | PostgreSQL connection: {$connection}</text>
    <text x="142" y="286" font-size="16" font-weight="750" fill="{$colors['muted']}">Designed for adviser review: grouped by system module, with full column lists, row counts, primary keys, unique keys, foreign keys, defaults, and logical notification links.</text>
    {$legend}
    {$moduleSvg}
    <g class="relationships-layer">
        {$relationshipSvg}
    </g>
    <g class="tables-layer">
        {$tablesSvg}
    </g>
    {$notes}
    <text x="{$sourceTextX}" y="{$sourceTextY}" text-anchor="end" font-size="15" font-weight="800" fill="{$colors['muted']}">Source: live PostgreSQL information_schema and pg_catalog for CMU ARLO</text>
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
    <title>CMU ARLO PostgreSQL ERD</title>
    <style>
        @page { size: landscape; margin: 8mm; }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            background: #eef3ea;
            color: #062b1d;
            font-family: "Inter", "Segoe UI", Arial, sans-serif;
        }
        .toolbar {
            position: sticky;
            top: 0;
            z-index: 10;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            padding: 14px 20px;
            background: rgba(255, 255, 255, 0.94);
            border-bottom: 1px solid #d8e0db;
            box-shadow: 0 12px 30px rgba(15, 23, 42, 0.08);
        }
        .toolbar h1 {
            margin: 0;
            font-size: 18px;
        }
        .toolbar p {
            margin: 2px 0 0;
            color: #526071;
            font-size: 13px;
            font-weight: 700;
        }
        .toolbar a,
        .toolbar button {
            border: 1px solid #0f766e;
            border-radius: 999px;
            background: #065f46;
            color: #fff;
            cursor: pointer;
            font: inherit;
            font-weight: 800;
            padding: 10px 16px;
            text-decoration: none;
        }
        .canvas {
            padding: 18px;
            overflow: auto;
        }
        img {
            display: block;
            width: 100%;
            min-width: 1800px;
            height: auto;
            border: 1px solid #d8e0db;
            border-radius: 18px;
            background: #fff;
        }
        @media print {
            .toolbar { display: none; }
            .canvas { padding: 0; overflow: visible; }
            img { width: 100%; min-width: 0; border: 0; border-radius: 0; }
        }
    </style>
</head>
<body>
    <div class="toolbar">
        <div>
            <h1>CMU ARLO PostgreSQL Database ERD</h1>
            <p>{$tableCount} tables, {$totalColumns} columns, {$foreignKeyCount} physical foreign keys. Use browser zoom for adviser review.</p>
        </div>
        <div>
            <a href="{$svgFile}" download>Download SVG</a>
            <button type="button" onclick="window.print()">Print Landscape</button>
        </div>
    </div>
    <main class="canvas">
        <img src="{$svgFile}" alt="CMU ARLO PostgreSQL full database ERD">
    </main>
</body>
</html>
HTML;

file_put_contents($htmlPath, $html);

echo "Generated ERD SVG: {$svgPath}".PHP_EOL;
echo "Generated ERD HTML: {$htmlPath}".PHP_EOL;
echo "{$tableCount} tables, {$totalColumns} columns, {$foreignKeyCount} physical foreign keys.".PHP_EOL;
