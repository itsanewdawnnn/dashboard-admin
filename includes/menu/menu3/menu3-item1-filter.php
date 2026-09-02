<?php
require_once __DIR__ . '/../../helpers/date-range-filter.php';
require_once __DIR__ . '/../../helpers/format-helpers.php';
require_once __DIR__ . '/menu3-item1-labels.php';

// Satu-satunya tempat query SELECT-nya, agar tabel & Export Excel selalu sama.
function fetchMenu3Item1FilteredRows(PDO $pdo, array $filter): array
{
    $conditions = [];
    $params     = [];

    if ($filter['date_from'] !== '') {
        $conditions[] = 'check_date >= :date_from';
        $params['date_from'] = $filter['date_from'];
    }
    if ($filter['date_to'] !== '') {
        $conditions[] = 'check_date <= :date_to';
        $params['date_to'] = $filter['date_to'];
    }

    $sql = 'SELECT
        id, check_date, checker_name, unit, laporan, tindak_lanjut,
        item1_check, item1_note, item2_check, item2_note,
        item3_check, item3_note, item4_check, item4_note,
        item5_check, item5_note, item6_check, item6_note,
        item7_check, item7_note, item8_check, item8_note,
        item9_check, item9_note, item10_check, item10_note
    FROM menu3_item1';
    if (!empty($conditions)) {
        $sql .= ' WHERE ' . implode(' AND ', $conditions);
    }
    $sql .= ' ORDER BY id ASC';

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    return $stmt->fetchAll();
}

// Jumlah item checklist yang TIDAK tercentang -- dipakai bareng tabel & Export Excel untuk Status.
function countMenu3Item1Kendala(array $row, array $itemKeys): int
{
    $kendalaCount = 0;
    foreach ($itemKeys as $itemKey) {
        $checked = (bool) $row[$itemKey . '_check'];
        if ($checked === false) {
            $kendalaCount = $kendalaCount + 1;
        }
    }

    return $kendalaCount;
}

// Status checklist berdasarkan jumlah kendala -- dipakai bareng tabel, modal Lihat, & Export Excel.
function menu3Item1KendalaStatus(int $kendalaCount): array
{
    if ($kendalaCount === 0) {
        return ['text' => 'Normal', 'badge_class' => 'bg-success'];
    }

    return ['text' => $kendalaCount . ' Kendala', 'badge_class' => 'bg-danger'];
}

// Dipakai Pencarian Global (lihat menu-config.php -> 'search'). Prioritas subtitle:
// Tindak Lanjut, lalu Laporan, baru tanggal saja kalau keduanya kosong.
function menu3Item1SearchResult(array $row): array
{
    global $unitLabels;

    $unitDisplay = menu3UnitLabel($row['unit'], $unitLabels);

    $subtitleText = 'Tgl: ' . formatDateDisplay($row['check_date']);
    if ($row['tindak_lanjut'] !== '') {
        $subtitleText = truncateForSearchResult($row['tindak_lanjut'], 60);
    } elseif ($row['laporan'] !== '') {
        $subtitleText = truncateForSearchResult($row['laporan'], 60);
    }

    return [
        'title'    => $unitDisplay . ' -- ' . $row['checker_name'],
        'subtitle' => $subtitleText,
    ];
}
