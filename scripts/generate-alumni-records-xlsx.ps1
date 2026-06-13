param(
    [string]$OutputPath = "storage/app/alumni-records/cmu_alumni_records.xlsx"
)

Set-StrictMode -Version Latest
$ErrorActionPreference = "Stop"

function Escape-XmlValue {
    param([string]$Value)

    if ($null -eq $Value) {
        return ""
    }

    return [System.Security.SecurityElement]::Escape($Value)
}

function Get-ColumnLetters {
    param([int]$ColumnIndex)

    $dividend = $ColumnIndex
    $columnName = ""

    while ($dividend -gt 0) {
        $modulo = ($dividend - 1) % 26
        $columnName = [char](65 + $modulo) + $columnName
        $dividend = [math]::Floor(($dividend - $modulo) / 26)
    }

    return $columnName
}

$rows = @(
    @("Last Name", "First Name", "Middle Name", "Suffix", "College Graduated", "Degree Graduated", "Year Graduated", "Student Number", "Status"),
    @("Doe", "Jane", "", "", "College of Information Sciences and Computing", "BS Information Technology", "2024", "2024-0001", "Graduated"),
    @("Reyes", "John", "Aquino", "", "College of Engineering", "BS Civil Engineering", "2021", "2021-0142", "Graduated"),
    @("Santos", "Maria", "Lopez", "", "College of Education", "Bachelor of Secondary Education", "2020", "2020-0234", "Graduated"),
    @("Dela Cruz", "Paolo", "Garcia", "Jr.", "College of Business and Management", "BS Business Administration", "2019", "2019-0318", "Graduated"),
    @("Fernandez", "Liza", "", "", "College of Human Ecology", "BS Nutrition and Dietetics", "2018", "2018-0440", "Graduated"),
    @("Valencia", "Mark", "Torres", "", "College of Agriculture", "BS Agriculture", "2017", "2017-0521", "Graduated"),
    @("Navarro", "Elena", "Ramos", "", "College of Arts and Sciences", "BS Biology", "2016", "2016-0675", "Graduated"),
    @("Morales", "Carla", "Dizon", "", "Non-Degree Programs", "Certificate in Teaching", "2015", "2015-0788", "Graduated")
)

$jsonRows = @(
    @{
        last_name = "Doe"
        first_name = "Jane"
        middle_name = ""
        suffix = ""
        college_graduated = "College of Information Sciences and Computing"
        degree_graduated = "BS Information Technology"
        year_graduated = "2024"
        student_number = "2024-0001"
        status = "Graduated"
    }
    @{
        last_name = "Reyes"
        first_name = "John"
        middle_name = "Aquino"
        suffix = ""
        college_graduated = "College of Engineering"
        degree_graduated = "BS Civil Engineering"
        year_graduated = "2021"
        student_number = "2021-0142"
        status = "Graduated"
    }
    @{
        last_name = "Santos"
        first_name = "Maria"
        middle_name = "Lopez"
        suffix = ""
        college_graduated = "College of Education"
        degree_graduated = "Bachelor of Secondary Education"
        year_graduated = "2020"
        student_number = "2020-0234"
        status = "Graduated"
    }
    @{
        last_name = "Dela Cruz"
        first_name = "Paolo"
        middle_name = "Garcia"
        suffix = "Jr."
        college_graduated = "College of Business and Management"
        degree_graduated = "BS Business Administration"
        year_graduated = "2019"
        student_number = "2019-0318"
        status = "Graduated"
    }
    @{
        last_name = "Fernandez"
        first_name = "Liza"
        middle_name = ""
        suffix = ""
        college_graduated = "College of Human Ecology"
        degree_graduated = "BS Nutrition and Dietetics"
        year_graduated = "2018"
        student_number = "2018-0440"
        status = "Graduated"
    }
    @{
        last_name = "Valencia"
        first_name = "Mark"
        middle_name = "Torres"
        suffix = ""
        college_graduated = "College of Agriculture"
        degree_graduated = "BS Agriculture"
        year_graduated = "2017"
        student_number = "2017-0521"
        status = "Graduated"
    }
    @{
        last_name = "Navarro"
        first_name = "Elena"
        middle_name = "Ramos"
        suffix = ""
        college_graduated = "College of Arts and Sciences"
        degree_graduated = "BS Biology"
        year_graduated = "2016"
        student_number = "2016-0675"
        status = "Graduated"
    }
    @{
        last_name = "Morales"
        first_name = "Carla"
        middle_name = "Dizon"
        suffix = ""
        college_graduated = "Non-Degree Programs"
        degree_graduated = "Certificate in Teaching"
        year_graduated = "2015"
        student_number = "2015-0788"
        status = "Graduated"
    }
)

$sheetRows = New-Object System.Collections.Generic.List[string]

for ($rowIndex = 0; $rowIndex -lt $rows.Count; $rowIndex++) {
    $cells = New-Object System.Collections.Generic.List[string]
    $currentRow = $rows[$rowIndex]

    for ($columnIndex = 0; $columnIndex -lt $currentRow.Count; $columnIndex++) {
        $cellReference = "$(Get-ColumnLetters ($columnIndex + 1))$($rowIndex + 1)"
        $value = [string]$currentRow[$columnIndex]

        if ($columnIndex -eq 6 -and $rowIndex -gt 0) {
            $cells.Add("<c r=`"$cellReference`"><v>$value</v></c>")
            continue
        }

        $cells.Add("<c r=`"$cellReference`" t=`"inlineStr`"><is><t>$(Escape-XmlValue $value)</t></is></c>")
    }

    $sheetRows.Add("<row r=`"$($rowIndex + 1)`">$($cells -join '')</row>")
}

$worksheetXml = @"
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">
  <dimension ref="A1:I$($rows.Count)" />
  <sheetViews>
    <sheetView workbookViewId="0" />
  </sheetViews>
  <sheetFormatPr defaultRowHeight="15" />
  <sheetData>
    $($sheetRows -join "`n    ")
  </sheetData>
</worksheet>
"@

$contentTypesXml = @"
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">
  <Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml" />
  <Default Extension="xml" ContentType="application/xml" />
  <Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml" />
  <Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml" />
</Types>
"@

$rootRelsXml = @"
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
  <Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml" />
</Relationships>
"@

$workbookXml = @"
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">
  <sheets>
    <sheet name="Alumni Records" sheetId="1" r:id="rId1" />
  </sheets>
</workbook>
"@

$workbookRelsXml = @"
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
  <Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml" />
</Relationships>
"@

$outputFullPath = Join-Path (Get-Location) $OutputPath
$outputDirectory = Split-Path -Parent $outputFullPath
$jsonOutputPath = [System.IO.Path]::ChangeExtension($outputFullPath, ".json")
$tempRoot = Join-Path ([System.IO.Path]::GetTempPath()) ("arlo-alumni-records-" + [guid]::NewGuid().ToString("N"))

New-Item -ItemType Directory -Path $tempRoot -Force | Out-Null
New-Item -ItemType Directory -Path (Join-Path $tempRoot "_rels") -Force | Out-Null
New-Item -ItemType Directory -Path (Join-Path $tempRoot "xl") -Force | Out-Null
New-Item -ItemType Directory -Path (Join-Path $tempRoot "xl/_rels") -Force | Out-Null
New-Item -ItemType Directory -Path (Join-Path $tempRoot "xl/worksheets") -Force | Out-Null
New-Item -ItemType Directory -Path $outputDirectory -Force | Out-Null

[System.IO.File]::WriteAllText((Join-Path $tempRoot "[Content_Types].xml"), $contentTypesXml, [System.Text.Encoding]::UTF8)
[System.IO.File]::WriteAllText((Join-Path $tempRoot "_rels/.rels"), $rootRelsXml, [System.Text.Encoding]::UTF8)
[System.IO.File]::WriteAllText((Join-Path $tempRoot "xl/workbook.xml"), $workbookXml, [System.Text.Encoding]::UTF8)
[System.IO.File]::WriteAllText((Join-Path $tempRoot "xl/_rels/workbook.xml.rels"), $workbookRelsXml, [System.Text.Encoding]::UTF8)
[System.IO.File]::WriteAllText((Join-Path $tempRoot "xl/worksheets/sheet1.xml"), $worksheetXml, [System.Text.Encoding]::UTF8)

if (Test-Path $outputFullPath) {
    Remove-Item -LiteralPath $outputFullPath -Force
}

Add-Type -AssemblyName System.IO.Compression.FileSystem
[System.IO.Compression.ZipFile]::CreateFromDirectory($tempRoot, $outputFullPath)
[System.IO.File]::WriteAllText($jsonOutputPath, ($jsonRows | ConvertTo-Json -Depth 4), [System.Text.Encoding]::UTF8)

Remove-Item -LiteralPath $tempRoot -Recurse -Force

Write-Host "Created alumni records workbook at $outputFullPath"
