<?php
// Contoh konfigurasi -- copy file ini jadi "database.php" di folder yang sama,
// lalu isi DB_NAME/DB_USER/DB_PASS dengan kredensial database Anda sendiri.
// "database.php" yang asli TIDAK ikut di-commit ke git (lihat .gitignore).
date_default_timezone_set('Asia/Jakarta');

define('DB_NAME', 'nama_database');
define('DB_USER', 'user_database');
define('DB_PASS', 'password_database');

function getDbConnection(): PDO
{
    static $pdo = null;

    if ($pdo === null) {
        $dsn = 'mysql:host=localhost;dbname=' . DB_NAME . ';charset=utf8mb4';
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];

        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
            $pdo->exec("SET time_zone = '+07:00'");
        } catch (PDOException $e) {
            error_log('Database connection failed: ' . $e->getMessage());
            die('Koneksi ke database gagal. Silakan hubungi administrator.');
        }
    }

    return $pdo;
}
