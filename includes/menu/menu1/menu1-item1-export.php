<?php
/**
 * Export Excel untuk tabel pada halaman ini, mengikuti filter tanggal yang aktif
 * di halaman tabel. Kolom Password ikut diekspor plain text (sengaja).
 */
require_once __DIR__ . '/../../auth.php';
require_once __DIR__ . '/../../helpers/format-helpers.php';
require_once __DIR__ . '/../../helpers/xlsx-writer.php';
require_once __DIR__ . '/../../menu-config.php';
require_once __DIR__ . '/menu1-item1-filter.php';

requireLogin();

$pdo = getDbConnection();

$activeFilter = parseDateRangeFilter();
$rows         = fetchMenu1Item1FilteredRows($pdo, $activeFilter);

$exportRows = [];
$rowNumber  = 0;
foreach ($rows as $row) {
    $rowNumber++;

    $activeDateDisplay = formatDateDisplay($row['active_date']);

    $exportRows[] = [
        $rowNumber,
        $row['full_name'],
        $activeDateDisplay,
        $row['password'],
    ];
}

$fileName = buildFilteredExportFileName('menu1-item1', $activeFilter);

downloadSimpleXlsx(
    menuModuleLabel('menu1_item1'),
    ['No', 'Nama Lengkap', 'Tgl Aktif', 'Password'],
    $exportRows,
    [0],
    $fileName
);
