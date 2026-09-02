<?php
// Ikut filter tanggal yang aktif di halaman tabel Log Aktivitas.
require_once __DIR__ . '/../auth.php';
require_once __DIR__ . '/../helpers/format-helpers.php';
require_once __DIR__ . '/../helpers/xlsx-writer.php';
require_once __DIR__ . '/activity-log-filter.php';

requireLogin();
requireRoot();

$pdo = getDbConnection();

$activeFilter = parseDateRangeFilter();
$rows         = fetchActivityLogFilteredRows($pdo, $activeFilter);

$exportRows = [];
$rowNumber  = 0;
foreach ($rows as $row) {
    $rowNumber = $rowNumber + 1;

    $createdAtDisplay = formatDateTimeIndonesian($row['created_at']);

    $userDisplay = $row['full_name'] . ' (' . $row['username'] . ')';

    $exportRows[] = [
        $rowNumber,
        $createdAtDisplay,
        $userDisplay,
        activityLogActionTypeLabel($row['action_type']),
        activityLogModuleLabel($row['module']),
        $row['description'],
    ];
}

$fileName = buildFilteredExportFileName('activity-log', $activeFilter);

downloadSimpleXlsx(
    'Log Aktivitas',
    ['No', 'Waktu', 'User', 'Aksi', 'Modul', 'Deskripsi'],
    $exportRows,
    [0],
    $fileName
);
