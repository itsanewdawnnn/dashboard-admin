<?php
// Export Excel untuk Manage User -- tanpa filter tanggal, selalu semua user.
require_once __DIR__ . '/../auth.php';
require_once __DIR__ . '/../helpers/xlsx-writer.php';
require_once __DIR__ . '/add-user-data.php';

requireLogin();
requireRoot();

$pdo  = getDbConnection();
$rows = fetchAllUsersForManage($pdo);

// PENTING: password ikut diekspor plain text (disengaja -- halaman ini hanya
// bisa diakses root).
$exportRows = [];
$rowNumber  = 0;
foreach ($rows as $row) {
    $rowNumber = $rowNumber + 1;

    $exportRows[] = [
        $rowNumber,
        ucfirst($row['role']),
        $row['full_name'],
        $row['username'],
        $row['password'],
        formatDateTimeIndonesian($row['last_login_at']),
        formatDateTimeIndonesian($row['created_at']),
        formatDateTimeIndonesian($row['updated_at']),
    ];
}

downloadSimpleXlsx(
    'Manage User',
    ['No', 'Role', 'Nama Lengkap', 'Username', 'Password', 'Login Terakhir', 'Dibuat', 'Diperbarui'],
    $exportRows,
    [0],
    'manage-user_semua-data.xlsx'
);
