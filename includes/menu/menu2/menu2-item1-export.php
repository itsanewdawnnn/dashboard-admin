<?php
/**
 * Export Excel untuk tabel pada halaman ini, mengikuti filter tanggal yang aktif
 * di halaman tabel.
 */
require_once __DIR__ . '/../../auth.php';
require_once __DIR__ . '/../../helpers/format-helpers.php';
require_once __DIR__ . '/../../helpers/xlsx-writer.php';
require_once __DIR__ . '/../../menu-config.php';
require_once __DIR__ . '/menu2-item1-filter.php';

requireLogin();

$pdo = getDbConnection();

$activeFilter = parseDateRangeFilter();
$rows         = fetchMenu2Item1FilteredRows($pdo, $activeFilter);

$exportRows = [];
$rowNumber  = 0;
foreach ($rows as $row) {
    $rowNumber++;

    $workDateDisplay = formatDateDisplay($row['work_date']);

    $exportRows[] = [
        $rowNumber,
        indonesianDayName($row['work_date']),
        $workDateDisplay,
        $row['job_description'],
    ];
}

$fileName = buildFilteredExportFileName('menu2-item1', $activeFilter);

downloadSimpleXlsx(
    menuModuleLabel('menu2_item1'),
    ['No', 'Hari', 'Tanggal', 'Pekerjaan'],
    $exportRows,
    [0],
    $fileName
);
