<?php
// Endpoint JSON untuk Pencarian Global (dipanggil dari footer.php via fetch()).
// Otomatis mencari semua menu yang punya konfigurasi "search" di menu-config.php --
// nambah Menu 4 TIDAK perlu edit file ini, cukup tambahkan blok "search" di sana
// + fungsi hasilnya di menuX-itemY-filter.php (lihat contoh menu1Item1SearchResult()).

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/menu-config.php';
require_once __DIR__ . '/menu/menu1/menu1-item1-filter.php';
require_once __DIR__ . '/menu/menu2/menu2-item1-filter.php';
require_once __DIR__ . '/menu/menu3/menu3-item1-filter.php';

requireLogin();

header('Content-Type: application/json; charset=utf-8');

$searchKeyword = getValue('q');

$menuConfig = getMenuConfig();

// Grup hasil selalu ikut key menu-config.php, walau kosong (dipakai footer.php untuk urutan tampilan).
$emptyResult = [];
foreach ($menuConfig as $menuKey => $menu) {
    $emptyResult[$menuKey] = [];
}

$minimumKeywordLength = 2;
if (mb_strlen($searchKeyword) < $minimumKeywordLength) {
    echo json_encode($emptyResult);
    exit;
}

$pdo = getDbConnection();

// "%" dan "_" adalah wildcard LIKE -- di-escape agar diperlakukan sebagai teks biasa.
function escapeLikeKeyword(string $keyword): string
{
    $keyword = str_replace('\\', '\\\\', $keyword);
    $keyword = str_replace('%', '\\%', $keyword);
    $keyword = str_replace('_', '\\_', $keyword);

    return $keyword;
}

// Cari di semua kolom yang didaftarkan item['search']['columns'] (OR kalau lebih dari satu).
// Table/kolomnya dari menu-config.php (data internal, bukan input user) -- aman digabung ke SQL.
function runGlobalSearchQuery(PDO $pdo, array $searchConfig, string $likePattern, int $limit): array
{
    $conditions = [];
    $params     = [];
    foreach ($searchConfig['columns'] as $index => $column) {
        $paramName          = 'keyword' . $index;
        $conditions[]       = $column . " LIKE :{$paramName} ESCAPE '\\\\'";
        $params[$paramName] = $likePattern;
    }

    $sql = 'SELECT * FROM ' . $searchConfig['table']
        . ' WHERE ' . implode(' OR ', $conditions)
        . ' ORDER BY ' . $searchConfig['order_column'] . ' DESC'
        . ' LIMIT ' . $limit;

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    return $stmt->fetchAll();
}

$likePattern        = '%' . escapeLikeKeyword($searchKeyword) . '%';
$resultLimitPerMenu = 5;

$results = $emptyResult;

foreach ($menuConfig as $menuKey => $menu) {
    foreach ($menu['items'] as $item) {
        if ($item['enabled'] === false || !isset($item['search'])) {
            continue;
        }

        $searchConfig  = $item['search'];
        $rows          = runGlobalSearchQuery($pdo, $searchConfig, $likePattern, $resultLimitPerMenu);
        $resultBuilder = $searchConfig['result'];

        foreach ($rows as $row) {
            // $resultBuilder isi-nya nama fungsi (string), mis. "menu1Item1SearchResult" --
            // fungsi itu SENGAJA cuma balikin title/subtitle, TIDAK boleh ikut memilih kolom
            // sensitif (mis. "password") karena baris di sini tidak pernah di-echo langsung.
            $formatted = $resultBuilder($row);
            $dateValue = $row[$searchConfig['date_column']];

            $results[$menuKey][] = [
                'title'    => $formatted['title'],
                'subtitle' => $formatted['subtitle'],
                'link'     => $item['url'] . '?date_from=' . urlencode($dateValue) . '&date_to=' . urlencode($dateValue),
            ];
        }
    }
}

echo json_encode($results);
