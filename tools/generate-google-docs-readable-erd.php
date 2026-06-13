<?php

declare(strict_types=1);

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;

require __DIR__.'/../vendor/autoload.php';

$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

$outputDir = __DIR__.'/../docs/diagrams/google-docs-erd-readable';
if (! is_dir($outputDir)) {
    mkdir($outputDir, 0775, true);
}

$pageWidth = 1600;
$pageHeight = 1200;
$databaseName = config('database.connections.'.config('database.default').'.database');
$generatedAt = now()->format('F j, Y g:i A');

$tableRows = DB::select("
    select table_name
    from information_schema.tables
    where table_schema = current_schema()
      and table_type = 'BASE TABLE'
    order by table_name
");

$columnRows = DB::select("
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
");

$constraintRows = DB::select("
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
");

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
$statsLine = count($tables).' tables | '.$totalColumns.' columns | '.count($foreignKeys).' physical foreign keys | '.$databaseName.' | '.$generatedAt;

function rdEsc(mixed $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function rdSlug(string $value): string
{
    return preg_replace('/[^a-z0-9]+/', '-', strtolower($value));
}

function rdType(object $column): string
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

function rdDefault(?string $default): string
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

    return strlen($default) > 18 ? substr($default, 0, 15).'...' : $default;
}

function rdDeleteAction(?string $action): string
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

function rdColumnConstraints(string $tableName, string $columnName, array $tables): array
{
    $matches = [];

    foreach ($tables[$tableName]['constraints'] as $constraint) {
        $columns = array_filter(explode(',', (string) $constraint->columns));
        if (in_array($columnName, $columns, true)) {
            $matches[] = $constraint;
        }
    }

    return $matches;
}

function rdColumnFlags(string $tableName, string $columnName, array $tables): string
{
    $flags = [];

    foreach (rdColumnConstraints($tableName, $columnName, $tables) as $constraint) {
        if ($constraint->constraint_type === 'p') {
            $flags[] = 'PK';
        } elseif ($constraint->constraint_type === 'f') {
            $flags[] = 'FK';
        } elseif ($constraint->constraint_type === 'u') {
            $flags[] = 'UQ';
        }
    }

    return implode('/', array_values(array_unique($flags)));
}

function rdRelationSummary(string $tableName, string $columnName, array $tables): string
{
    $parts = [];

    foreach (rdColumnConstraints($tableName, $columnName, $tables) as $constraint) {
        if ($constraint->constraint_type === 'f') {
            $delete = rdDeleteAction($constraint->on_delete);
            $parts[] = '-> '.$constraint->foreign_table_name.'.'.$constraint->foreign_columns.($delete ? ' / '.$delete : '');
        } elseif ($constraint->constraint_type === 'u') {
            $parts[] = 'unique';
        } elseif ($constraint->constraint_type === 'p') {
            $parts[] = 'primary key';
        }
    }

    return implode('; ', $parts);
}

function rdPageStart(int $width, int $height, string $title, string $subtitle, string $stats): string
{
    $svg = '<?xml version="1.0" encoding="UTF-8"?>';
    $svg .= '<svg xmlns="http://www.w3.org/2000/svg" width="'.$width.'" height="'.$height.'" viewBox="0 0 '.$width.' '.$height.'" role="img">';
    $svg .= '<defs>';
    $svg .= '<pattern id="grid" width="32" height="32" patternUnits="userSpaceOnUse"><path d="M 32 0 L 0 0 0 32" fill="none" stroke="#eef0f3" stroke-width="1"/></pattern>';
    $svg .= '<marker id="crow" viewBox="0 0 18 18" refX="17" refY="9" markerWidth="12" markerHeight="12" orient="auto"><path d="M 1 2 L 17 9 L 1 16 M 1 9 L 17 9" fill="none" stroke="#111827" stroke-width="1.8" stroke-linecap="round"/></marker>';
    $svg .= '<marker id="one" viewBox="0 0 18 18" refX="1" refY="9" markerWidth="11" markerHeight="11" orient="auto"><path d="M 4 2 L 4 16" fill="none" stroke="#111827" stroke-width="2.1" stroke-linecap="round"/></marker>';
    $svg .= '<style>svg{font-family:"Segoe UI",Arial,sans-serif;background:#fff}.rel text{paint-order:stroke;stroke:#fff;stroke-width:4px;stroke-linejoin:round}</style>';
    $svg .= '</defs>';
    $svg .= '<rect width="100%" height="100%" fill="#fff"/><rect width="100%" height="100%" fill="url(#grid)" opacity="0.5"/>';
    $svg .= '<rect x="36" y="30" width="'.($width - 72).'" height="116" rx="10" fill="#fff" stroke="#111827" stroke-width="2.5"/>';
    $svg .= '<text x="58" y="72" font-size="28" font-weight="950" fill="#111827">'.rdEsc($title).'</text>';
    $svg .= '<text x="58" y="104" font-size="16" font-weight="750" fill="#374151">'.rdEsc($subtitle).'</text>';
    $svg .= '<text x="58" y="130" font-size="13" font-weight="850" fill="#111827">'.rdEsc($stats).'</text>';

    return $svg;
}

function rdPageEnd(int $width, int $height): string
{
    return '<text x="'.($width - 40).'" y="'.($height - 30).'" text-anchor="end" font-size="11" font-weight="800" fill="#4b5563">CMU ARLO ERD readable packet</text></svg>';
}

function rdTableDetailSvg(string $tableName, array $table, array $columns, int $start, int $total, int $part, int $parts): string
{
    $x = 50;
    $y = 180;
    $w = 1500;
    $headerH = 48;
    $columnHeaderH = 30;
    $rowH = 28;
    $footerH = 24;
    $h = $headerH + $columnHeaderH + (count($columns) * $rowH) + $footerH;
    $footerY = $y + $h - $footerH;
    $svg = '';

    $svg .= '<g class="table-detail">';
    $svg .= '<rect x="'.$x.'" y="'.$y.'" width="'.$w.'" height="'.$h.'" rx="8" fill="#fff" stroke="#111827" stroke-width="2.4"/>';
    $svg .= '<rect x="'.$x.'" y="'.$y.'" width="'.$w.'" height="'.$headerH.'" rx="8" fill="#111827"/>';
    $svg .= '<text x="'.($x + 18).'" y="'.($y + 31).'" font-size="21" font-weight="950" fill="#fff">'.rdEsc(strtoupper($tableName)).'</text>';
    $svg .= '<text x="'.($x + $w - 18).'" y="'.($y + 31).'" text-anchor="end" font-size="14" font-weight="850" fill="#e5e7eb">'.rdEsc('columns '.($start + 1).'-'.($start + count($columns)).' of '.$total.' | part '.$part.' of '.$parts).'</text>';
    $svg .= '<rect x="'.$x.'" y="'.($y + $headerH).'" width="'.$w.'" height="'.$columnHeaderH.'" fill="#e5e7eb" stroke="#111827" stroke-width="1"/>';
    $svg .= '<text x="'.($x + 18).'" y="'.($y + $headerH + 21).'" font-size="12" font-weight="950" fill="#111827">KEY</text>';
    $svg .= '<text x="'.($x + 92).'" y="'.($y + $headerH + 21).'" font-size="12" font-weight="950" fill="#111827">COLUMN</text>';
    $svg .= '<text x="'.($x + 575).'" y="'.($y + $headerH + 21).'" font-size="12" font-weight="950" fill="#111827">TYPE</text>';
    $svg .= '<text x="'.($x + 820).'" y="'.($y + $headerH + 21).'" font-size="12" font-weight="950" fill="#111827">NULL / DEFAULT</text>';
    $svg .= '<text x="'.($x + 1080).'" y="'.($y + $headerH + 21).'" font-size="12" font-weight="950" fill="#111827">RELATION / CONSTRAINT</text>';
    $svg .= '<line x1="'.($x + 74).'" y1="'.($y + $headerH).'" x2="'.($x + 74).'" y2="'.$footerY.'" stroke="#9ca3af" stroke-width="1"/>';
    $svg .= '<line x1="'.($x + 560).'" y1="'.($y + $headerH).'" x2="'.($x + 560).'" y2="'.$footerY.'" stroke="#9ca3af" stroke-width="1"/>';
    $svg .= '<line x1="'.($x + 805).'" y1="'.($y + $headerH).'" x2="'.($x + 805).'" y2="'.$footerY.'" stroke="#9ca3af" stroke-width="1"/>';
    $svg .= '<line x1="'.($x + 1065).'" y1="'.($y + $headerH).'" x2="'.($x + 1065).'" y2="'.$footerY.'" stroke="#9ca3af" stroke-width="1"/>';

    foreach ($columns as $index => $column) {
        $rowY = $y + $headerH + $columnHeaderH + ($index * $rowH);
        $textY = $rowY + 19;
        $fill = $index % 2 === 0 ? '#fff' : '#f9fafb';
        $flags = rdColumnFlags($tableName, $column->column_name, $GLOBALS['tables']);
        $nullDefault = $column->is_nullable === 'NO' ? 'NN' : 'NULL';
        $default = rdDefault($column->column_default);
        if ($default !== '') {
            $nullDefault .= ' / '.$default;
        }

        $svg .= '<g>';
        $svg .= '<rect x="'.$x.'" y="'.$rowY.'" width="'.$w.'" height="'.$rowH.'" fill="'.$fill.'" stroke="#d1d5db" stroke-width="0.8"/>';
        $svg .= '<text x="'.($x + 37).'" y="'.$textY.'" text-anchor="middle" font-size="12.5" font-weight="950" fill="#111827">'.rdEsc($flags).'</text>';
        $svg .= '<text x="'.($x + 92).'" y="'.$textY.'" font-size="15.5" font-weight="750" fill="#111827">'.rdEsc($column->column_name).'</text>';
        $svg .= '<text x="'.($x + 575).'" y="'.$textY.'" font-size="14.5" font-weight="700" fill="#374151">'.rdEsc(rdType($column)).'</text>';
        $svg .= '<text x="'.($x + 820).'" y="'.$textY.'" font-size="14.5" font-weight="700" fill="#374151">'.rdEsc($nullDefault).'</text>';
        $svg .= '<text x="'.($x + 1080).'" y="'.$textY.'" font-size="13.5" font-weight="700" fill="#374151">'.rdEsc(rdRelationSummary($tableName, $column->column_name, $GLOBALS['tables'])).'</text>';
        $svg .= '</g>';
    }

    $svg .= '<rect x="'.$x.'" y="'.$footerY.'" width="'.$w.'" height="'.$footerH.'" fill="#f3f4f6" stroke="#111827" stroke-width="1"/>';
    $svg .= '<text x="'.($x + 14).'" y="'.($footerY + 17).'" font-size="11.5" font-weight="800" fill="#4b5563">'.rdEsc(number_format($table['row_count']).' rows in current database').'</text>';
    $svg .= '</g>';

    return $svg;
}

function rdOverview(array $tables, array $foreignKeys, int $width, int $height, string $statsLine): string
{
    $svg = rdPageStart($width, $height, 'CMU ARLO ERD Overview', 'Readable overview. Detailed complete-column table pages follow.', $statsLine);
    $positions = [];
    $names = array_keys($tables);
    $boxW = 320;
    $boxH = 62;
    $gapX = 70;
    $gapY = 76;
    $startX = 50;
    $startY = 190;
    $cols = 4;

    foreach ($names as $index => $name) {
        $col = $index % $cols;
        $row = intdiv($index, $cols);
        $positions[$name] = [
            'x' => $startX + ($col * ($boxW + $gapX)),
            'y' => $startY + ($row * ($boxH + $gapY)),
            'w' => $boxW,
            'h' => $boxH,
        ];
    }

    foreach ($foreignKeys as $index => $foreignKey) {
        $child = $foreignKey->table_name;
        $parent = $foreignKey->foreign_table_name;
        if (! isset($positions[$child], $positions[$parent])) {
            continue;
        }

        $from = $positions[$parent];
        $to = $positions[$child];
        $x1 = $from['x'] + $from['w'];
        $y1 = $from['y'] + ($from['h'] / 2);
        $x2 = $to['x'];
        $y2 = $to['y'] + ($to['h'] / 2);
        if ($x1 > $x2) {
            $x1 = $from['x'];
            $x2 = $to['x'] + $to['w'];
        }
        $midX = (($x1 + $x2) / 2) + ((($index % 5) - 2) * 9);
        $svg .= '<path d="M '.$x1.' '.$y1.' C '.$midX.' '.$y1.', '.$midX.' '.$y2.', '.$x2.' '.$y2.'" fill="none" stroke="#111827" stroke-width="1.4" marker-start="url(#one)" marker-end="url(#crow)" stroke-linecap="round"/>';
    }

    foreach ($positions as $name => $pos) {
        $svg .= '<rect x="'.$pos['x'].'" y="'.$pos['y'].'" width="'.$pos['w'].'" height="'.$pos['h'].'" rx="7" fill="#fff" stroke="#111827" stroke-width="1.8"/>';
        $svg .= '<text x="'.($pos['x'] + 12).'" y="'.($pos['y'] + 25).'" font-size="15" font-weight="950" fill="#111827">'.rdEsc($name).'</text>';
        $svg .= '<text x="'.($pos['x'] + 12).'" y="'.($pos['y'] + 48).'" font-size="11.5" font-weight="800" fill="#4b5563">'.count($tables[$name]['columns']).' columns | '.number_format($tables[$name]['row_count']).' rows</text>';
    }

    $svg .= '<rect x="50" y="1045" width="1500" height="86" rx="8" fill="#fff" stroke="#111827" stroke-width="1.8"/>';
    $svg .= '<text x="72" y="1080" font-size="18" font-weight="950" fill="#111827">How to use this packet</text>';
    $svg .= '<text x="72" y="1110" font-size="14.5" font-weight="750" fill="#374151">Insert this overview first, then insert the detailed table pages as appendix images. Each detailed page keeps text readable in Google Docs.</text>';

    return $svg.rdPageEnd($width, $height);
}

$pages = [];
$pages[] = [
    'slug' => '00-overview',
    'title' => 'CMU ARLO ERD Overview',
    'svg' => rdOverview($tables, $foreignKeys, $pageWidth, $pageHeight, $statsLine),
];

$columnsPerPage = 30;
$pageNumber = 1;
foreach ($tables as $tableName => $table) {
    $chunks = array_chunk($table['columns'], $columnsPerPage);
    $partCount = count($chunks);

    foreach ($chunks as $chunkIndex => $columns) {
        $part = $chunkIndex + 1;
        $start = $chunkIndex * $columnsPerPage;
        $title = strtoupper($tableName).($partCount > 1 ? ' Columns Part '.$part : ' Table');
        $subtitle = 'Complete PostgreSQL column details for '.$tableName.($partCount > 1 ? ' (part '.$part.' of '.$partCount.')' : '').'.';
        $svg = rdPageStart($pageWidth, $pageHeight, $title, $subtitle, $statsLine);
        $svg .= rdTableDetailSvg($tableName, $table, $columns, $start, count($table['columns']), $part, $partCount);
        $svg .= rdPageEnd($pageWidth, $pageHeight);

        $pages[] = [
            'slug' => str_pad((string) $pageNumber, 2, '0', STR_PAD_LEFT).'-'.rdSlug($tableName).($partCount > 1 ? '-part-'.$part : ''),
            'title' => $title,
            'svg' => $svg,
        ];
        $pageNumber++;
    }
}

foreach ($pages as $page) {
    file_put_contents($outputDir.'/arlo-erd-readable-'.$page['slug'].'.svg', $page['svg']);
}

$index = '<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>CMU ARLO Easy Read ERD</title><style>@page{size:landscape;margin:8mm}body{margin:0;background:#f3f4f6;font-family:"Segoe UI",Arial,sans-serif}.page{padding:16px;break-after:page}.page h2{margin:0 0 8px;font-size:16px}.page img{display:block;width:100%;height:auto;border:1px solid #d1d5db;background:#fff}@media print{.page{padding:0}.page h2{display:none}.page img{border:0}}</style></head><body>';
foreach ($pages as $page) {
    $png = 'arlo-erd-readable-'.$page['slug'].'.png';
    $svg = 'arlo-erd-readable-'.$page['slug'].'.svg';
    $index .= '<section class="page"><h2>'.rdEsc($page['title']).'</h2><img src="'.rdEsc(file_exists($outputDir.'/'.$png) ? $png : $svg).'" alt="'.rdEsc($page['title']).'"></section>';
}
$index .= '</body></html>';
file_put_contents($outputDir.'/index.html', $index);

echo 'Generated '.count($pages).' easy-read ERD SVG pages in '.$outputDir.PHP_EOL;

