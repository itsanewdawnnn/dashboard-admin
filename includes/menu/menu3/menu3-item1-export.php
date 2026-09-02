<?php
// Ikut filter tanggal aktif di halaman tabel; detail checklist per item disertakan.
require_once __DIR__ . '/../../auth.php';
require_once __DIR__ . '/../../helpers/format-helpers.php';
require_once __DIR__ . '/../../helpers/xlsx-writer.php';
require_once __DIR__ . '/menu3-item1-filter.php';
require_once __DIR__ . '/menu3-item1-labels.php';

requireLogin();

$pdo = getDbConnection();

$itemKeys = array_keys($checklistItemLabels);

$activeFilter = parseDateRangeFilter();
$rows         = fetchMenu3Item1FilteredRows($pdo, $activeFilter);

$headers = ['No', 'Hari', 'Tanggal', 'Unit', 'Status', 'Laporan', 'Tindak Lanjut', 'Checker'];
foreach ($checklistItemLabels as $itemLabel) {
    $headers[] = $itemLabel;
    $headers[] = 'Keterangan';
}

$exportRows = [];
$rowNumber  = 0;
foreach ($rows as $row) {
    $rowNumber++;

    $checkDateDisplay = formatDateDisplay($row['check_date']);
    $unitDisplay       = menu3UnitLabel($row['unit'], $unitLabels);

    $kendalaCount  = countMenu3Item1Kendala($row, $itemKeys);
    $kendalaStatus = menu3Item1KendalaStatus($kendalaCount);
    $statusDisplay = $kendalaStatus['text'];

    $laporanDisplay = $row['laporan'];
    if ($laporanDisplay === '') {
        $laporanDisplay = '-';
    }

    $tindakLanjutDisplay = $row['tindak_lanjut'];
    if ($tindakLanjutDisplay === '') {
        $tindakLanjutDisplay = '-';
    }

    $exportRow = [
        $rowNumber,
        indonesianDayName($row['check_date']),
        $checkDateDisplay,
        $unitDisplay,
        $statusDisplay,
        $laporanDisplay,
        $tindakLanjutDisplay,
        $row['checker_name'],
    ];
    foreach ($itemKeys as $itemKey) {
        $itemChecked = (bool) $row[$itemKey . '_check'];
        if ($itemChecked) {
            $exportRow[] = '✓';
        } else {
            $exportRow[] = '✕';
        }

        $itemNoteDisplay = $row[$itemKey . '_note'];
        if ($itemNoteDisplay === '') {
            $itemNoteDisplay = '-';
        }
        $exportRow[] = $itemNoteDisplay;
    }
    $exportRows[] = $exportRow;
}

$fileName = buildFilteredExportFileName('menu3-item1', $activeFilter);

downloadSimpleXlsx(
    'Checklist Pekerjaan',
    $headers,
    $exportRows,
    [0],
    $fileName
);
