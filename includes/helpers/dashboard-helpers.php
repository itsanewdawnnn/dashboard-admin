<?php
require_once __DIR__ . '/../menu/menu3/menu3-item1-filter.php';
require_once __DIR__ . '/../menu/menu3/menu3-item1-labels.php';
require_once __DIR__ . '/format-helpers.php';

// "Kendala aktif": ada item checklist tidak tercentang DAN Tindak Lanjut masih kosong.
// Dipakai untuk notifikasi bel (header.php).
function fetchActiveKendalaRecords(PDO $pdo): array
{
    global $checklistItemLabels;
    require_once __DIR__ . '/../menu/menu3/menu3-item1-labels.php';
    $itemKeys = array_keys($checklistItemLabels);

    $stmt = $pdo->query(
        'SELECT id, check_date, unit,
            item1_check, item2_check, item3_check, item4_check, item5_check,
            item6_check, item7_check, item8_check, item9_check, item10_check
        FROM menu3_item1
        WHERE tindak_lanjut = \'\'
        ORDER BY check_date DESC, id DESC'
    );
    $rows = $stmt->fetchAll();

    $activeKendalaRecords = [];
    foreach ($rows as $row) {
        $kendalaCount = countMenu3Item1Kendala($row, $itemKeys);
        if ($kendalaCount > 0) {
            $activeKendalaRecords[] = [
                'id'            => $row['id'],
                'check_date'    => $row['check_date'],
                'unit'          => $row['unit'],
                'kendala_count' => $kendalaCount,
            ];
        }
    }

    return $activeKendalaRecords;
}

// Mengubah data kendala jadi bentuk notifikasi generik (icon/judul/sub-judul/link),
// supaya markup bel di header.php tidak terikat ke bentuk data kendala saja.
function buildKendalaNotificationItems(array $activeKendalaRecords, array $unitLabels): array
{
    $notificationItems = [];

    foreach ($activeKendalaRecords as $kendalaRecord) {
        $dateDisplay = formatDateDisplay($kendalaRecord['check_date']);
        $unitDisplay = menu3UnitLabel($kendalaRecord['unit'], $unitLabels);

        $notificationItems[] = [
            'icon_class'    => 'bi-exclamation-triangle',
            'icon_bg_class' => 'bg-danger',
            'title'         => $unitDisplay . ' -- ' . (int) $kendalaRecord['kendala_count'] . ' Kendala',
            'subtitle'      => $dateDisplay,
            'link'          => '/includes/menu/menu3/menu3-item1.php?date_from=' . urlencode($kendalaRecord['check_date']) . '&date_to=' . urlencode($kendalaRecord['check_date']),
        ];
    }

    return $notificationItems;
}
