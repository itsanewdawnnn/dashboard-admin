<?php
require_once __DIR__ . '/../menu-config.php';
require_once __DIR__ . '/../menu/menu3/menu3-item1-labels.php';

function currentActivityLogActor(): array
{
    ensureSessionStarted();

    $userId = null;
    if (isset($_SESSION['user_id'])) {
        $userId = (int) $_SESSION['user_id'];
    }

    $username = '';
    if (isset($_SESSION['username'])) {
        $username = $_SESSION['username'];
    }

    $fullName = '';
    if (isset($_SESSION['full_name'])) {
        $fullName = $_SESSION['full_name'];
    }

    return [
        'user_id'   => $userId,
        'username'  => $username,
        'full_name' => $fullName,
    ];
}

// $oldValues/$newValues: null jika tidak relevan (mis. $oldValues untuk aksi "create").
function logActivity(
    PDO $pdo,
    ?int $userId,
    string $username,
    string $fullName,
    string $actionType,
    string $module,
    ?int $recordId,
    string $description,
    ?array $oldValues = null,
    ?array $newValues = null
): void {
    $oldValuesJson = null;
    if ($oldValues !== null) {
        $oldValuesJson = json_encode($oldValues, JSON_UNESCAPED_UNICODE);
    }

    $newValuesJson = null;
    if ($newValues !== null) {
        $newValuesJson = json_encode($newValues, JSON_UNESCAPED_UNICODE);
    }

    $stmt = $pdo->prepare(
        'INSERT INTO activity_log (
            user_id, username, full_name, action_type, module, record_id,
            description, old_values, new_values
        ) VALUES (
            :user_id, :username, :full_name, :action_type, :module, :record_id,
            :description, :old_values, :new_values
        )'
    );
    $stmt->execute([
        'user_id'     => $userId,
        'username'    => $username,
        'full_name'   => $fullName,
        'action_type' => $actionType,
        'module'      => $module,
        'record_id'   => $recordId,
        'description' => $description,
        'old_values'  => $oldValuesJson,
        'new_values'  => $newValuesJson,
    ]);
}

function activityLogActionTypeLabel(string $actionType): string
{
    $labels = [
        'login'  => 'Login',
        'logout' => 'Logout',
        'create' => 'Tambah',
        'update' => 'Ubah',
        'delete' => 'Hapus',
    ];

    if (isset($labels[$actionType])) {
        return $labels[$actionType];
    }

    return $actionType;
}

function activityLogActionTypeBadgeColor(string $actionType): string
{
    $colors = [
        'login'  => 'info',
        'logout' => 'secondary',
        'create' => 'success',
        'update' => 'primary',
        'delete' => 'danger',
    ];

    if (isset($colors[$actionType])) {
        return $colors[$actionType];
    }

    return 'secondary';
}

function activityLogModuleLabel(string $module): string
{
    // auth & users bukan bagian menu bernomor, jadi tidak ada di menu-config.php.
    $nonMenuLabels = [
        'auth'  => 'Login / Logout',
        'users' => 'Manajemen User',
    ];
    if (isset($nonMenuLabels[$module])) {
        return $nonMenuLabels[$module];
    }

    if ($module === 'menu2_item1_signatures') {
        return menuModuleLabel('menu2_item1') . ' (Tanda Tangan)';
    }

    $moduleLabel = menuModuleLabel($module);
    if ($moduleLabel !== '') {
        return $moduleLabel;
    }

    return $module;
}

// Kalau ada modul/kolom baru, tambahkan labelnya di $knownLabels.
function activityLogColumnLabel(string $column): string
{
    $knownLabels = [
        'full_name' => 'Nama Lengkap',
        'username'  => 'Username',
        'role'      => 'Role',
        'password'  => 'Password',

        'active_date' => 'Tanggal Aktif',

        'work_date'        => 'Tanggal Pekerjaan',
        'job_description'  => 'Deskripsi Pekerjaan',
        'photos'            => 'Foto',
        'photos_added'      => 'Foto Ditambahkan',
        'photos_deleted'    => 'Foto Dihapus',
        'file_name'         => 'Nama File',
        'signatures_count'  => 'Jumlah Tanda Tangan',

        'check_date'    => 'Tanggal Checklist',
        'unit'          => 'Unit',
        'laporan'       => 'Laporan',
        'tindak_lanjut' => 'Tindak Lanjut',
    ];

    if (isset($knownLabels[$column])) {
        return $knownLabels[$column];
    }

    // item1_check..item10_note tidak didaftar satu-satu -- polanya dibaca dari nama kolom.
    if (substr($column, 0, 4) === 'item') {
        if (substr($column, -6) === '_check') {
            $itemNumber = substr($column, 4, strlen($column) - 4 - 6);

            return 'Item ' . $itemNumber . ' - Centang';
        }
        if (substr($column, -5) === '_note') {
            $itemNumber = substr($column, 4, strlen($column) - 4 - 5);

            return 'Item ' . $itemNumber . ' - Catatan';
        }
    }

    $withSpaces = str_replace('_', ' ', $column);

    return ucwords($withSpaces);
}

// PENTING: hasilnya HTML siap-tampil -- pemanggil TIDAK boleh htmlspecialchars() lagi.
function activityLogCellValueHtml(string $module, string $column, $value, array $unitLabels, string $gallerySuffix): string
{
    $menu2UploadUrlBaseValue    = menu2UploadUrlBase();
    $menu2SignatureUrlBaseValue = menu2SignatureUrlBase();

    $isMenu2PhotoColumn = false;
    if ($column === 'photos' || $column === 'photos_added' || $column === 'photos_deleted') {
        $isMenu2PhotoColumn = true;
    }

    if ($module === 'menu2_item1' && $isMenu2PhotoColumn) {
        if (is_array($value) === false || count($value) === 0) {
            return '<span class="text-muted">(kosong)</span>';
        }

        $photoGalleryName = htmlspecialchars('actlog-' . $gallerySuffix . '-' . $column, ENT_QUOTES, 'UTF-8');

        $imagesHtml = '';
        foreach ($value as $fileName) {
            $photoUrl   = $menu2UploadUrlBaseValue . (string) $fileName;
            $photoUrlSafe = htmlspecialchars($photoUrl, ENT_QUOTES, 'UTF-8');

            $imagesHtml = $imagesHtml
                . '<a href="' . $photoUrlSafe . '" class="glightbox d-inline-block me-1 mb-1" data-gallery="' . $photoGalleryName . '">'
                . '<img src="' . $photoUrlSafe . '" alt="Foto" class="rounded border" style="width: 64px; height: 64px; object-fit: cover;">'
                . '</a>';
        }

        return $imagesHtml;
    }

    if ($module === 'menu2_item1_signatures' && $column === 'file_name') {
        $signatureUrl     = $menu2SignatureUrlBaseValue . (string) $value;
        $signatureUrlSafe = htmlspecialchars($signatureUrl, ENT_QUOTES, 'UTF-8');

        $signatureGalleryName = htmlspecialchars('actlog-' . $gallerySuffix . '-signature', ENT_QUOTES, 'UTF-8');

        return '<a href="' . $signatureUrlSafe . '" class="glightbox d-inline-block" data-gallery="' . $signatureGalleryName . '">'
            . '<img src="' . $signatureUrlSafe . '" alt="Tanda Tangan" class="border rounded bg-white p-1" style="max-width: 160px;">'
            . '</a>';
    }

    $isChecklistCheckColumn = false;
    if (substr($column, 0, 4) === 'item' && substr($column, -6) === '_check') {
        $isChecklistCheckColumn = true;
    }
    if ($isChecklistCheckColumn) {
        if ((string) $value === '1') {
            return 'Ya';
        }

        return 'Tidak';
    }

    if ($module === 'menu3_item1' && $column === 'unit') {
        $unitDisplay = menu3UnitLabel((string) $value, $unitLabels);

        return htmlspecialchars($unitDisplay, ENT_QUOTES, 'UTF-8');
    }

    if (is_array($value)) {
        if (count($value) === 0) {
            return '<span class="text-muted">(kosong)</span>';
        }

        return htmlspecialchars(implode(', ', $value), ENT_QUOTES, 'UTF-8');
    }

    if ((string) $value === '') {
        return '<span class="text-muted">(kosong)</span>';
    }

    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}
