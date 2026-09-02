<?php
// Filter rentang tanggal (date_from/date_to) dipakai bersama oleh semua tabel yang
// punya filter tanggal (Menu 1/2/3, Log Aktivitas) -- baca dari $_GET & bangun ulang
// jadi query string, supaya semua tabel berperilaku sama persis.

require_once __DIR__ . '/request-helpers.php';

function parseDateRangeFilter(): array
{
    $dateFrom = getValue('date_from');
    if ($dateFrom !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateFrom)) {
        $dateFrom = '';
    }

    $dateTo = getValue('date_to');
    if ($dateTo !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateTo)) {
        $dateTo = '';
    }

    return ['date_from' => $dateFrom, 'date_to' => $dateTo];
}

function dateRangeFilterQueryString(array $filter): string
{
    $parts = [];
    if ($filter['date_from'] !== '') {
        $parts['date_from'] = $filter['date_from'];
    }
    if ($filter['date_to'] !== '') {
        $parts['date_to'] = $filter['date_to'];
    }

    return http_build_query($parts);
}
