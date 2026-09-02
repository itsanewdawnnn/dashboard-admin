<?php
require_once __DIR__ . '/../../helpers/date-range-filter.php';
require_once __DIR__ . '/../../helpers/format-helpers.php';

// Satu-satunya tempat query SELECT-nya, agar tabel & Export Excel selalu sama.
function fetchMenu1Item1FilteredRows(PDO $pdo, array $filter): array
{
    $conditions = [];
    $params     = [];

    if ($filter['date_from'] !== '') {
        $conditions[] = 'active_date >= :date_from';
        $params['date_from'] = $filter['date_from'];
    }
    if ($filter['date_to'] !== '') {
        $conditions[] = 'active_date <= :date_to';
        $params['date_to'] = $filter['date_to'];
    }

    $sql = 'SELECT id, full_name, active_date, password FROM menu1_item1';
    if (!empty($conditions)) {
        $sql .= ' WHERE ' . implode(' AND ', $conditions);
    }
    $sql .= ' ORDER BY id ASC';

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    return $stmt->fetchAll();
}

// Dipakai Pencarian Global (lihat menu-config.php -> 'search') -- ubah 1 baris hasil
// query jadi title/subtitle yang ditampilkan di dropdown pencarian.
function menu1Item1SearchResult(array $row): array
{
    return [
        'title'    => $row['full_name'],
        'subtitle' => 'Tgl Aktif: ' . formatDateDisplay($row['active_date']),
    ];
}
