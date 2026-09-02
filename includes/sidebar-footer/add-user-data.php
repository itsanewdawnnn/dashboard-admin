<?php
require_once __DIR__ . '/../helpers/format-helpers.php';

// Dipakai bersama add-user.php (tabel) & add-user-export.php (Excel) agar selalu sama.
function fetchAllUsersForManage(PDO $pdo): array
{
    $stmt = $pdo->query(
        'SELECT id, role, username, password, full_name, last_login_at, created_at, updated_at FROM users ORDER BY id ASC'
    );

    return $stmt->fetchAll();
}

