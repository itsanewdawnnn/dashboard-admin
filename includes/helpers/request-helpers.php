<?php
// Cara SATU-SATUNYA untuk baca input dari $_POST/$_GET di seluruh aplikasi -- kalau
// nanti butuh aturan baru (mis. sanitasi tambahan), cukup ubah di sini, semua halaman ikut.

function requestStringValue(array $source, string $key, bool $trim = true): string
{
    $value = '';
    if (isset($source[$key])) {
        $value = (string) $source[$key];
    }

    if ($trim) {
        $value = trim($value);
    }

    return $value;
}

// $trim=false untuk field yang nilainya tidak boleh diubah (password, form_action, dsb).
function postValue(string $key, bool $trim = true): string
{
    return requestStringValue($_POST, $key, $trim);
}

function getValue(string $key, bool $trim = true): string
{
    return requestStringValue($_GET, $key, $trim);
}

function postInt(string $key): int
{
    $value = 0;
    if (isset($_POST[$key])) {
        $value = (int) $_POST[$key];
    }

    return $value;
}

function postArray(string $key): array
{
    $value = [];
    if (isset($_POST[$key])) {
        $value = $_POST[$key];
    }

    return $value;
}

// Untuk field upload ($_FILES['nama_field']) -- null kalau tidak ada file terkirim.
function postFile(string $key): ?array
{
    if (isset($_FILES[$key])) {
        return $_FILES[$key];
    }

    return null;
}
