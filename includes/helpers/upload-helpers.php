<?php
// Foto TIDAK disimpan apa adanya -- otomatis di-resize & dikompres (lihat saveUploadedPhoto()).

// Diperiksa dari ISI file, bukan cuma ekstensinya -- menolak file yang hanya diganti nama.
const ALLOWED_PHOTO_MIME_TYPES = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
const ALLOWED_PHOTO_EXTENSIONS = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
const MAX_PHOTO_BYTES = 10 * 1024 * 1024; // Batas file ASLI, sebelum dioptimasi.

const MAX_PHOTO_DIMENSION = 2048;

const PHOTO_WEBP_QUALITY = 82;
const PHOTO_JPEG_QUALITY = 85;

// .htaccess mencegah file PHP dijalankan dari folder upload ini.
function ensureUploadDirExists(string $uploadDir): void
{
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0775, true);
    }

    // chmod() terpisah karena mode di mkdir() bisa "dipotong" oleh umask server.
    chmod($uploadDir, 0755);

    $htaccessPath = $uploadDir . '/.htaccess';
    if (!file_exists($htaccessPath)) {
        file_put_contents(
            $htaccessPath,
            "<FilesMatch \"\\.(?i:php|phtml|php\\d|phar|cgi|pl)\$\">\n" .
            "    Require all denied\n" .
            "</FilesMatch>\n"
        );
    }
}

// Mengembalikan pesan error jika tidak valid, atau null jika valid.
function validateUploadedPhoto(string $tmpPath, int $fileSize, string $originalName): ?string
{
    if (!is_uploaded_file($tmpPath)) {
        return "Upload foto \"{$originalName}\" tidak valid.";
    }

    if ($fileSize > MAX_PHOTO_BYTES) {
        return "Foto \"{$originalName}\" ukurannya lebih dari 10MB.";
    }

    $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
    if (!in_array($extension, ALLOWED_PHOTO_EXTENSIONS, true)) {
        return "Foto \"{$originalName}\" formatnya tidak didukung (hanya JPG, PNG, GIF, atau WEBP).";
    }

    $imageInfo = getimagesize($tmpPath);
    if ($imageInfo === false || !in_array($imageInfo['mime'], ALLOWED_PHOTO_MIME_TYPES, true)) {
        return "Foto \"{$originalName}\" bukan file gambar yang valid.";
    }

    return null;
}

// Buka file gambar menjadi "resource" GD yang bisa diputar/di-resize/disimpan ulang.
function loadPhotoAsGdImage(string $tmpPath, string $mimeType)
{
    switch ($mimeType) {
        case 'image/jpeg':
            return imagecreatefromjpeg($tmpPath);
        case 'image/png':
            return imagecreatefrompng($tmpPath);
        case 'image/gif':
            return imagecreatefromgif($tmpPath);
        case 'image/webp':
            return imagecreatefromwebp($tmpPath);
        default:
            return false;
    }
}

// Putar foto sesuai EXIF Orientation kamera HP. Jika ekstensi "exif" tidak aktif
// di server, koreksi ini dilewati saja (bukan error) -- foto tetap tersimpan.
function correctPhotoOrientation($gdImage, string $tmpPath, string $mimeType)
{
    if ($mimeType !== 'image/jpeg' || !function_exists('exif_read_data')) {
        return $gdImage;
    }

    $exifData = @exif_read_data($tmpPath);
    if ($exifData === false || empty($exifData['Orientation'])) {
        return $gdImage;
    }

    switch ($exifData['Orientation']) {
        case 3: // Foto terbalik 180 derajat.
            return imagerotate($gdImage, 180, 0);
        case 6: // Foto perlu diputar 90 derajat searah jarum jam.
            return imagerotate($gdImage, -90, 0);
        case 8: // Foto perlu diputar 90 derajat berlawanan jarum jam.
            return imagerotate($gdImage, 90, 0);
        default:
            return $gdImage;
    }
}

// Tidak diperbesar kalau sudah lebih kecil dari MAX_PHOTO_DIMENSION (upscale sia-sia).
function resizePhotoIfTooLarge($gdImage)
{
    $originalWidth  = imagesx($gdImage);
    $originalHeight = imagesy($gdImage);
    $longestSide    = max($originalWidth, $originalHeight);

    if ($longestSide <= MAX_PHOTO_DIMENSION) {
        return $gdImage;
    }

    $scale     = MAX_PHOTO_DIMENSION / $longestSide;
    $newWidth  = (int) round($originalWidth * $scale);
    $newHeight = (int) round($originalHeight * $scale);

    $resizedImage = imagecreatetruecolor($newWidth, $newHeight);

    // Tanpa ini area transparan (PNG/WebP) jadi hitam solid setelah di-resize.
    imagealphablending($resizedImage, false);
    imagesavealpha($resizedImage, true);

    imagecopyresampled($resizedImage, $gdImage, 0, 0, 0, 0, $newWidth, $newHeight, $originalWidth, $originalHeight);
    imagedestroy($gdImage);

    return $resizedImage;
}

// WebP kalau server mendukung, JPEG sebagai cadangan.
function savePhotoToDisk($gdImage, string $uploadDir, string $fileNameWithoutExtension): string
{
    if (function_exists('imagewebp')) {
        $fileName = $fileNameWithoutExtension . '.webp';
        imagewebp($gdImage, $uploadDir . '/' . $fileName, PHOTO_WEBP_QUALITY);
    } else {
        $fileName = $fileNameWithoutExtension . '.jpg';
        imagejpeg($gdImage, $uploadDir . '/' . $fileName, PHOTO_JPEG_QUALITY);
    }

    imagedestroy($gdImage);

    return $fileName;
}

function saveUploadedPhoto(string $tmpPath, string $originalName, string $uploadDir): string
{
    $imageInfo = getimagesize($tmpPath);
    $mimeType  = $imageInfo['mime'];

    $gdImage = loadPhotoAsGdImage($tmpPath, $mimeType);
    $gdImage = correctPhotoOrientation($gdImage, $tmpPath, $mimeType);
    $gdImage = resizePhotoIfTooLarge($gdImage);

    $fileNameWithoutExtension = bin2hex(random_bytes(16));

    return savePhotoToDisk($gdImage, $uploadDir, $fileNameWithoutExtension);
}

// Validasi SEMUA foto dulu sebelum ada yang disimpan (all-or-nothing).
function validateUploadedPhotosArray(?array $filesField): ?string
{
    if ($filesField === null) {
        return null;
    }

    foreach ($filesField['name'] as $index => $originalName) {
        if ($filesField['error'][$index] === UPLOAD_ERR_NO_FILE) {
            continue; // Slot kosong (user tidak memilih foto di sini), lewati.
        }

        if ($filesField['error'][$index] !== UPLOAD_ERR_OK) {
            return "Gagal mengupload foto \"{$originalName}\".";
        }

        $error = validateUploadedPhoto($filesField['tmp_name'][$index], $filesField['size'][$index], $originalName);
        if ($error !== null) {
            return $error;
        }
    }

    return null;
}

function saveUploadedPhotosArray(?array $filesField, string $uploadDir): array
{
    if ($filesField === null) {
        return [];
    }

    ensureUploadDirExists($uploadDir);

    $savedFileNames = [];
    foreach ($filesField['name'] as $index => $originalName) {
        if ($filesField['error'][$index] === UPLOAD_ERR_NO_FILE) {
            continue;
        }

        $savedFileNames[] = saveUploadedPhoto($filesField['tmp_name'][$index], $originalName, $uploadDir);
    }

    return $savedFileNames;
}
