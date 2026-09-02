<?php
require_once __DIR__ . '/../../helpers/date-range-filter.php';
require_once __DIR__ . '/../../helpers/format-helpers.php';

// Satu-satunya tempat query SELECT-nya, agar tabel & Export Excel selalu sama.
function fetchMenu2Item1FilteredRows(PDO $pdo, array $filter): array
{
    $conditions = [];
    $params     = [];

    if ($filter['date_from'] !== '') {
        $conditions[] = 'work_date >= :date_from';
        $params['date_from'] = $filter['date_from'];
    }
    if ($filter['date_to'] !== '') {
        $conditions[] = 'work_date <= :date_to';
        $params['date_to'] = $filter['date_to'];
    }

    $sql = 'SELECT id, work_date, job_description FROM menu2_item1';
    if (!empty($conditions)) {
        $sql .= ' WHERE ' . implode(' AND ', $conditions);
    }
    $sql .= ' ORDER BY id ASC';

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    return $stmt->fetchAll();
}

// Dipakai Pencarian Global (lihat menu-config.php -> 'search').
function menu2Item1SearchResult(array $row): array
{
    return [
        'title'    => truncateForSearchResult($row['job_description'], 70),
        'subtitle' => 'Tgl: ' . formatDateDisplay($row['work_date']),
    ];
}
