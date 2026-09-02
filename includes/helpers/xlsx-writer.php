<?php
// Penulis .xlsx sederhana tanpa library pihak ketiga (hanya ext-zip bawaan PHP).
// Hanya mendukung satu sheet, header tebal, dan baris data teks/angka biasa.

// Nama file export mengikuti filter tanggal aktif -- dipakai bersama semua halaman export Excel.
function buildFilteredExportFileName(string $baseName, array $filter): string
{
    if ($filter['date_from'] === '' && $filter['date_to'] === '') {
        return $baseName . '_semua-data.xlsx';
    }

    $fromLabel = 'awal';
    if ($filter['date_from'] !== '') {
        $fromLabel = $filter['date_from'];
    }

    $toLabel = 'akhir';
    if ($filter['date_to'] !== '') {
        $toLabel = $filter['date_to'];
    }

    return $baseName . '_' . $fromLabel . '_sd_' . $toLabel . '.xlsx';
}

function xlsxEscapeText(string $text): string
{
    return htmlspecialchars($text, ENT_XML1 | ENT_COMPAT, 'UTF-8');
}

// 0, 1, 2, ... -> "A", "B", ..., "Z", "AA", ...
function xlsxColumnLetter(int $columnIndex): string
{
    $letter = '';
    $columnIndex++;

    while ($columnIndex > 0) {
        $remainder   = ($columnIndex - 1) % 26;
        $letter      = chr(65 + $remainder) . $letter;
        $columnIndex = intdiv($columnIndex - 1, 26);
    }

    return $letter;
}

// $cells: array ber-index kolom, tiap elemen ['value','type'=>'string'|'number','bold'=>bool].
function xlsxBuildRowXml(int $rowNumber, array $cells): string
{
    $xml = '<row r="' . $rowNumber . '">';

    foreach ($cells as $columnIndex => $cell) {
        $cellRef   = xlsxColumnLetter((int) $columnIndex) . $rowNumber;
        $styleAttr = !empty($cell['bold']) ? ' s="1"' : '';

        if (($cell['type'] ?? 'string') === 'number') {
            $value = $cell['value'];
            $xml .= '<c r="' . $cellRef . '"' . $styleAttr . '><v>' . (is_numeric($value) ? $value : 0) . '</v></c>';
        } else {
            $text = xlsxEscapeText((string) $cell['value']);
            $xml .= '<c r="' . $cellRef . '"' . $styleAttr . ' t="inlineStr"><is><t xml:space="preserve">' . $text . '</t></is></c>';
        }
    }

    $xml .= '</row>';

    return $xml;
}

// Mengirim header HTTP -- harus dipanggil paling akhir, tanpa output lain sebelumnya.
// $numericColumns: index kolom yang nilainya angka murni; kolom lain otomatis teks.
function downloadSimpleXlsx(
    string $sheetName,
    array $headers,
    array $rows,
    array $numericColumns,
    string $downloadFileName
): void {
    $tmpPath = tempnam(sys_get_temp_dir(), 'xlsx_');
    if ($tmpPath === false) {
        throw new RuntimeException('Tidak bisa membuat file sementara untuk export Excel.');
    }

    $zip = new ZipArchive();
    if ($zip->open($tmpPath, ZipArchive::OVERWRITE) !== true) {
        throw new RuntimeException('Tidak bisa membuat file .xlsx sementara.');
    }

    $safeSheetName = substr((string) preg_replace('/[\\\\\/\?\*\[\]:]/', ' ', $sheetName), 0, 31);
    if ($safeSheetName === '') {
        $safeSheetName = 'Sheet1';
    }

    $contentTypesXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
        . '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
        . '<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
        . '<Default Extension="xml" ContentType="application/xml"/>'
        . '<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>'
        . '<Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>'
        . '<Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>'
        . '</Types>';

    $rootRelsXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
        . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
        . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>'
        . '</Relationships>';

    $workbookXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
        . '<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
        . '<sheets><sheet name="' . xlsxEscapeText($safeSheetName) . '" sheetId="1" r:id="rId1"/></sheets>'
        . '</workbook>';

    $workbookRelsXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
        . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
        . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>'
        . '<Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>'
        . '</Relationships>';

    $stylesXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
        . '<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
        . '<fonts count="2"><font><sz val="11"/><name val="Calibri"/></font><font><b/><sz val="11"/><name val="Calibri"/></font></fonts>'
        . '<fills count="2"><fill><patternFill patternType="none"/></fill><fill><patternFill patternType="gray125"/></fill></fills>'
        . '<borders count="1"><border><left/><right/><top/><bottom/><diagonal/></border></borders>'
        . '<cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>'
        . '<cellXfs count="2">'
        . '<xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/>'
        . '<xf numFmtId="0" fontId="1" fillId="0" borderId="0" xfId="0" applyFont="1"/>'
        . '</cellXfs>'
        . '<cellStyles count="1"><cellStyle name="Normal" xfId="0" builtinId="0"/></cellStyles>'
        . '</styleSheet>';

    // Baris 1 = header (dicetak tebal, style index 1).
    $headerCells = [];
    foreach (array_values($headers) as $columnIndex => $headerText) {
        $headerCells[$columnIndex] = ['value' => $headerText, 'type' => 'string', 'bold' => true];
    }
    $rowsXml = xlsxBuildRowXml(1, $headerCells);

    $excelRowNumber = 2;
    foreach ($rows as $row) {
        $cells = [];
        foreach (array_values($row) as $columnIndex => $value) {
            $isNumeric        = in_array($columnIndex, $numericColumns, true);
            $cells[$columnIndex] = ['value' => $value, 'type' => $isNumeric ? 'number' : 'string', 'bold' => false];
        }
        $rowsXml .= xlsxBuildRowXml($excelRowNumber, $cells);
        $excelRowNumber++;
    }

    // Lebih lega dari default (8.43) agar isi langsung terlihat tanpa resize manual.
    $colsXml = '<cols>';
    foreach (array_values($headers) as $columnIndex => $headerText) {
        $colsXml .= '<col min="' . ($columnIndex + 1) . '" max="' . ($columnIndex + 1) . '" width="22" customWidth="1"/>';
    }
    $colsXml .= '</cols>';

    $sheetXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
        . '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
        . $colsXml
        . '<sheetData>' . $rowsXml . '</sheetData>'
        . '</worksheet>';

    $zip->addFromString('[Content_Types].xml', $contentTypesXml);
    $zip->addFromString('_rels/.rels', $rootRelsXml);
    $zip->addFromString('xl/workbook.xml', $workbookXml);
    $zip->addFromString('xl/_rels/workbook.xml.rels', $workbookRelsXml);
    $zip->addFromString('xl/styles.xml', $stylesXml);
    $zip->addFromString('xl/worksheets/sheet1.xml', $sheetXml);
    $zip->close();

    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment; filename="' . $downloadFileName . '"');
    header('Content-Length: ' . filesize($tmpPath));
    header('Cache-Control: max-age=0');

    readfile($tmpPath);
    unlink($tmpPath);
    exit();
}
