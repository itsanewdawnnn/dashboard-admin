<?php
const MAX_SIGNATURE_BYTES = 2 * 1024 * 1024;

function getMenu2Item1SignatureForUser(PDO $pdo, int $itemId, int $userId): ?array
{
    $stmt = $pdo->prepare(
        'SELECT id, file_name FROM menu2_item1_signatures WHERE item_id = :item_id AND user_id = :user_id'
    );
    $stmt->execute(['item_id' => $itemId, 'user_id' => $userId]);
    $signature = $stmt->fetch();

    return $signature === false ? null : $signature;
}

// Sekaligus untuk semua item (bukan query satu-satu), JOIN users agar nama selalu terbaru.
function fetchMenu2Item1SignaturesForItems(PDO $pdo, array $itemIds): array
{
    if (empty($itemIds)) {
        return [];
    }

    $placeholders = implode(',', array_fill(0, count($itemIds), '?'));
    $stmt = $pdo->prepare(
        "SELECT s.id, s.item_id, s.user_id, s.file_name, u.full_name
         FROM menu2_item1_signatures s
         JOIN users u ON u.id = s.user_id
         WHERE s.item_id IN ({$placeholders})
         ORDER BY s.id ASC"
    );
    $stmt->execute(array_values($itemIds));

    $signaturesByItemId = [];
    foreach ($stmt->fetchAll() as $signature) {
        $signaturesByItemId[(int) $signature['item_id']][] = $signature;
    }

    return $signaturesByItemId;
}

// Null jika data URI-nya tidak valid/bukan PNG asli.
function saveMenu2Item1SignatureImage(string $uploadDir, string $dataUri): ?string
{
    if (strpos($dataUri, 'data:image/png;base64,') !== 0) {
        return null;
    }

    $base64Data = substr($dataUri, strlen('data:image/png;base64,'));
    $binaryData = base64_decode($base64Data, true);

    if ($binaryData === false || $binaryData === '' || strlen($binaryData) > MAX_SIGNATURE_BYTES) {
        return null;
    }

    $tmpPath = tempnam(sys_get_temp_dir(), 'sig');
    file_put_contents($tmpPath, $binaryData);

    $imageInfo = getimagesize($tmpPath);
    if ($imageInfo === false || $imageInfo['mime'] !== 'image/png') {
        unlink($tmpPath);
        return null;
    }

    ensureUploadDirExists($uploadDir);

    $fileName  = bin2hex(random_bytes(16)) . '.png';
    $finalPath = $uploadDir . '/' . $fileName;
    rename($tmpPath, $finalPath);

    // tempnam() membuat file berizin ketat, rename() tidak melonggarkannya.
    chmod($finalPath, 0644);

    return $fileName;
}

// $userId selalu dari session di pemanggilnya -- hanya bisa hapus tanda tangan sendiri.
function deleteMenu2Item1Signature(PDO $pdo, string $uploadDir, int $itemId, int $userId): void
{
    $signature = getMenu2Item1SignatureForUser($pdo, $itemId, $userId);
    if ($signature === null) {
        return;
    }

    @unlink($uploadDir . '/' . $signature['file_name']);

    $stmt = $pdo->prepare('DELETE FROM menu2_item1_signatures WHERE id = :id');
    $stmt->execute(['id' => $signature['id']]);
}
