<?php
require_once __DIR__ . '/../helpers/date-range-filter.php';

// Urut dari yang PALING BARU dulu (beda dari tabel lain yang ASC).
function fetchActivityLogFilteredRows(PDO $pdo, array $filter): array
{
    $conditions = [];
    $params     = [];

    if ($filter['date_from'] !== '') {
        $conditions[] = 'DATE(created_at) >= :date_from';
        $params['date_from'] = $filter['date_from'];
    }
    if ($filter['date_to'] !== '') {
        $conditions[] = 'DATE(created_at) <= :date_to';
        $params['date_to'] = $filter['date_to'];
    }

    $sql = 'SELECT
        id, user_id, username, full_name, action_type, module, record_id,
        description, old_values, new_values, created_at
    FROM activity_log';
    if (!empty($conditions)) {
        $sql .= ' WHERE ' . implode(' AND ', $conditions);
    }
    $sql .= ' ORDER BY id DESC';

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    return $stmt->fetchAll();
}
