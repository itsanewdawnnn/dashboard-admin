<?php
// Format tampilan tanggal/waktu, dipakai bersama oleh Menu 1/2/3, dashboard, dan
// Pencarian Global -- supaya format yang terlihat user selalu sama di seluruh aplikasi.

// Y-m-d (database) -> d-m-Y (tampilan).
function formatDateDisplay(string $date): string
{
    $parts = explode('-', $date);

    return $parts[2] . '-' . $parts[1] . '-' . $parts[0];
}

// $date formatnya "Y-m-d", sama seperti kolom DATE di database.
function indonesianDayName(string $date): string
{
    $dayNames = [
        'Sunday'    => 'Minggu',
        'Monday'    => 'Senin',
        'Tuesday'   => 'Selasa',
        'Wednesday' => 'Rabu',
        'Thursday'  => 'Kamis',
        'Friday'    => 'Jumat',
        'Saturday'  => 'Sabtu',
    ];

    $englishDayName = date('l', strtotime($date));

    return $dayNames[$englishDayName];
}

// Format DB "Y-m-d H:i:s" -> "d-m-Y H:i". NULL (belum pernah login) -> "-".
function formatDateTimeIndonesian(?string $rawDateTime): string
{
    if ($rawDateTime === null) {
        return '-';
    }

    $timestamp = strtotime($rawDateTime);

    return date('d-m-Y H:i', $timestamp);
}

// Potong teks panjang untuk ditampilkan ringkas (mis. hasil Pencarian Global).
function truncateForSearchResult(string $text, int $maxLength): string
{
    if (mb_strlen($text) <= $maxLength) {
        return $text;
    }

    return mb_substr($text, 0, $maxLength) . '...';
}
