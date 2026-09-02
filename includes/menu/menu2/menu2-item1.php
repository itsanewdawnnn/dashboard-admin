<?php
require_once __DIR__ . '/../../auth.php';
require_once __DIR__ . '/../../helpers/upload-helpers.php';
require_once __DIR__ . '/../../helpers/format-helpers.php';
require_once __DIR__ . '/../../menu-config.php';
require_once __DIR__ . '/menu2-item1-filter.php';
require_once __DIR__ . '/menu2-item1-signature.php';

requireLogin();

$pdo = getDbConnection();

$uploadDir     = __DIR__ . '/uploads';
$uploadUrlBase = menu2UploadUrlBase();

$signatureUploadDir = $uploadDir . '/signatures';
$signatureUrlBase   = menu2SignatureUrlBase();

function getMenu2Item1Photos(PDO $pdo, int $itemId): array
{
    $stmt = $pdo->prepare('SELECT id, file_name FROM menu2_item1_photos WHERE item_id = :item_id ORDER BY id ASC');
    $stmt->execute(['item_id' => $itemId]);

    return $stmt->fetchAll();
}

$successMessage = '';
$errorMessage   = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $formAction = postValue('form_action', false);

    if (!submittedCsrfTokenIsValid()) {
        $errorMessage = 'Invalid form session (CSRF). Please reload the page and try again.';
    } elseif ($formAction === 'add') {
        $workDate       = postValue('work_date');
        $jobDescription = postValue('job_description');

        if ($workDate === '' || $jobDescription === '') {
            $errorMessage = 'Tanggal dan pekerjaan wajib diisi.';
        } else {
            // Semua foto divalidasi terlebih dahulu SEBELUM ada yang disimpan (all-or-nothing).
            $photosField = postFile('photos');
            $photoError  = validateUploadedPhotosArray($photosField);

            if ($photoError !== null) {
                $errorMessage = $photoError;
            } else {
                $stmt = $pdo->prepare(
                    'INSERT INTO menu2_item1 (work_date, job_description) VALUES (:work_date, :job_description)'
                );
                $stmt->execute([
                    'work_date'       => $workDate,
                    'job_description' => $jobDescription,
                ]);
                $newItemId = (int) $pdo->lastInsertId();

                $savedFileNames = saveUploadedPhotosArray($photosField, $uploadDir);
                $stmt = $pdo->prepare(
                    'INSERT INTO menu2_item1_photos (item_id, file_name) VALUES (:item_id, :file_name)'
                );
                foreach ($savedFileNames as $fileName) {
                    $stmt->execute(['item_id' => $newItemId, 'file_name' => $fileName]);
                }

                $activityActor = currentActivityLogActor();
                logActivity(
                    $pdo,
                    $activityActor['user_id'],
                    $activityActor['username'],
                    $activityActor['full_name'],
                    'create',
                    'menu2_item1',
                    $newItemId,
                    'Menambahkan data #' . $newItemId . ': ' . $jobDescription . '.',
                    null,
                    ['work_date' => $workDate, 'job_description' => $jobDescription, 'photos' => $savedFileNames]
                );

                $successMessage = 'Data baru berhasil ditambahkan.';
            }
        }
    } elseif ($formAction === 'edit') {
        $id             = postInt('id');
        $workDate       = postValue('work_date');
        $jobDescription = postValue('job_description');

        if ($id === 0 || $workDate === '' || $jobDescription === '') {
            $errorMessage = 'Tanggal dan pekerjaan wajib diisi.';
        } else {
            $photosField = postFile('photos');
            $photoError  = validateUploadedPhotosArray($photosField);

            if ($photoError !== null) {
                $errorMessage = $photoError;
            } else {
                $stmt = $pdo->prepare('SELECT work_date, job_description FROM menu2_item1 WHERE id = :id LIMIT 1');
                $stmt->execute(['id' => $id]);
                $oldRow = $stmt->fetch();

                $stmt = $pdo->prepare(
                    'UPDATE menu2_item1 SET work_date = :work_date, job_description = :job_description WHERE id = :id'
                );
                $stmt->execute([
                    'work_date'       => $workDate,
                    'job_description' => $jobDescription,
                    'id'              => $id,
                ]);

                // item_id ikut diperiksa agar tidak bisa menghapus foto milik data lain.
                $deletePhotoIds        = postArray('delete_photo_ids');
                $deletedPhotoFileNames = [];
                foreach ($deletePhotoIds as $photoId) {
                    $stmt = $pdo->prepare(
                        'SELECT file_name FROM menu2_item1_photos WHERE id = :photo_id AND item_id = :item_id'
                    );
                    $stmt->execute(['photo_id' => (int) $photoId, 'item_id' => $id]);
                    $photo = $stmt->fetch();

                    if ($photo !== false) {
                        @unlink($uploadDir . '/' . $photo['file_name']);
                        $stmt = $pdo->prepare('DELETE FROM menu2_item1_photos WHERE id = :photo_id');
                        $stmt->execute(['photo_id' => (int) $photoId]);
                        $deletedPhotoFileNames[] = $photo['file_name'];
                    }
                }

                $savedFileNames = saveUploadedPhotosArray($photosField, $uploadDir);
                $stmt = $pdo->prepare(
                    'INSERT INTO menu2_item1_photos (item_id, file_name) VALUES (:item_id, :file_name)'
                );
                foreach ($savedFileNames as $fileName) {
                    $stmt->execute(['item_id' => $id, 'file_name' => $fileName]);
                }

                if ($oldRow !== false) {
                    $activityActor = currentActivityLogActor();
                    logActivity(
                        $pdo,
                        $activityActor['user_id'],
                        $activityActor['username'],
                        $activityActor['full_name'],
                        'update',
                        'menu2_item1',
                        $id,
                        'Mengubah data #' . $id . ': ' . $jobDescription . '.',
                        ['work_date' => $oldRow['work_date'], 'job_description' => $oldRow['job_description'], 'photos_deleted' => $deletedPhotoFileNames],
                        ['work_date' => $workDate, 'job_description' => $jobDescription, 'photos_added' => $savedFileNames]
                    );
                }

                $successMessage = 'Data berhasil diperbarui.';
            }
        }
    } elseif ($formAction === 'delete') {
        if (!canDeleteRecords()) {
            $errorMessage = 'Anda tidak memiliki izin untuk menghapus data ini.';
        } else {
            $id = postInt('id');

            if ($id === 0) {
                $errorMessage = 'Data tidak ditemukan.';
            } else {
                // Baris di tabel photos/signatures ikut terhapus otomatis (ON DELETE CASCADE).
                $photos     = getMenu2Item1Photos($pdo, $id);
                $signaturesForItem = fetchMenu2Item1SignaturesForItems($pdo, [$id]);
                $signatures        = [];
                if (isset($signaturesForItem[$id])) {
                    $signatures = $signaturesForItem[$id];
                }

                // Data lama diambil SEBELUM baris dihapus, untuk dicatat di Log Aktivitas.
                $stmt = $pdo->prepare('SELECT work_date, job_description FROM menu2_item1 WHERE id = :id LIMIT 1');
                $stmt->execute(['id' => $id]);
                $oldRow = $stmt->fetch();

                $stmt = $pdo->prepare('DELETE FROM menu2_item1 WHERE id = :id');
                $stmt->execute(['id' => $id]);

                foreach ($photos as $photo) {
                    @unlink($uploadDir . '/' . $photo['file_name']);
                }

                foreach ($signatures as $signature) {
                    @unlink($signatureUploadDir . '/' . $signature['file_name']);
                }

                if ($oldRow !== false) {
                    $deletedPhotoFileNames = [];
                    foreach ($photos as $photo) {
                        $deletedPhotoFileNames[] = $photo['file_name'];
                    }

                    $activityActor = currentActivityLogActor();
                    logActivity(
                        $pdo,
                        $activityActor['user_id'],
                        $activityActor['username'],
                        $activityActor['full_name'],
                        'delete',
                        'menu2_item1',
                        $id,
                        'Menghapus data #' . $id . ': ' . $oldRow['job_description'] . '.',
                        ['work_date' => $oldRow['work_date'], 'job_description' => $oldRow['job_description'], 'photos' => $deletedPhotoFileNames, 'signatures_count' => count($signatures)],
                        null
                    );
                }

                $successMessage = 'Data berhasil dihapus.';
            }
        }
    } elseif ($formAction === 'sign') {
        $id               = postInt('id');
        $signatureDataUri = postValue('signature_data', false);

        if ($id === 0 || $signatureDataUri === '') {
            $errorMessage = 'Tanda tangan tidak boleh kosong.';
        } else {
            // user_id SELALU dari session, bukan form (mencegah tanda tangan atas nama orang lain).
            $currentUserId = (int) $_SESSION['user_id'];

            $existingSignature = getMenu2Item1SignatureForUser($pdo, $id, $currentUserId);
            if ($existingSignature !== null) {
                $errorMessage = 'Anda sudah menandatangani data ini.';
            } else {
                $signatureFileName = saveMenu2Item1SignatureImage($signatureUploadDir, $signatureDataUri);

                if ($signatureFileName === null) {
                    $errorMessage = 'Tanda tangan tidak valid.';
                } else {
                    $stmt = $pdo->prepare(
                        'INSERT INTO menu2_item1_signatures (item_id, user_id, file_name) VALUES (:item_id, :user_id, :file_name)'
                    );
                    $stmt->execute([
                        'item_id'   => $id,
                        'user_id'   => $currentUserId,
                        'file_name' => $signatureFileName,
                    ]);
                    $newSignatureId = (int) $pdo->lastInsertId();

                    $activityActor = currentActivityLogActor();
                    logActivity(
                        $pdo,
                        $activityActor['user_id'],
                        $activityActor['username'],
                        $activityActor['full_name'],
                        'create',
                        'menu2_item1_signatures',
                        $newSignatureId,
                        'Menandatangani data #' . $id . '.',
                        null,
                        ['item_id' => $id, 'file_name' => $signatureFileName]
                    );

                    $successMessage = 'Tanda tangan berhasil disimpan.';
                }
            }
        }
    } elseif ($formAction === 'delete-sign') {
        $id = postInt('id');

        if ($id === 0) {
            $errorMessage = 'Data tidak ditemukan.';
        } else {
            // user_id SELALU dari session -- hanya bisa menghapus tanda tangan sendiri.
            $currentUserId = (int) $_SESSION['user_id'];

            // Data lama diambil SEBELUM dihapus, untuk dicatat di Log Aktivitas.
            $oldSignature = getMenu2Item1SignatureForUser($pdo, $id, $currentUserId);

            deleteMenu2Item1Signature($pdo, $signatureUploadDir, $id, $currentUserId);

            if ($oldSignature !== null) {
                $activityActor = currentActivityLogActor();
                logActivity(
                    $pdo,
                    $activityActor['user_id'],
                    $activityActor['username'],
                    $activityActor['full_name'],
                    'delete',
                    'menu2_item1_signatures',
                    (int) $oldSignature['id'],
                    'Menghapus tanda tangan pada data #' . $id . '.',
                    ['item_id' => $id, 'file_name' => $oldSignature['file_name']],
                    null
                );
            }

            $successMessage = 'Tanda tangan berhasil dihapus.';
        }
    }
}

// Fungsi filter ini dipakai lagi oleh Export Excel, agar datanya selalu sama persis.
$activeFilter      = parseDateRangeFilter();
$filterQueryString = dateRangeFilterQueryString($activeFilter);
$rows               = fetchMenu2Item1FilteredRows($pdo, $activeFilter);

// Diambil sekaligus untuk semua baris (bukan query satu-satu di dalam loop).
$signaturesByItemId = fetchMenu2Item1SignaturesForItems($pdo, array_column($rows, 'id'));

$formActionWithFilter = 'menu2-item1.php' . ($filterQueryString !== '' ? '?' . $filterQueryString : '');

$csrfToken = csrfToken();

$activeMenu = 'menu2-item1';
require __DIR__ . '/../../header.php';
?>

<link rel="stylesheet" href="/assets/extensions/sweetalert2/sweetalert2.min.css">
<link rel="stylesheet" href="/assets/extensions/flatpickr/flatpickr.min.css">
<link rel="stylesheet" href="/assets/extensions/filepond/filepond.css">
<link rel="stylesheet" href="/assets/extensions/filepond-plugin-image-preview/filepond-plugin-image-preview.css">
<link rel="stylesheet" href="/assets/extensions/glightbox/glightbox.min.css">

<div class="page-heading">
    <div class="page-title">
        <div class="row">
            <div class="col-12 col-lg-6 order-lg-1 order-last">
                <h3><?= htmlspecialchars($activeMenuLabel, ENT_QUOTES, 'UTF-8') ?> -> <?= htmlspecialchars($activeItemLabel, ENT_QUOTES, 'UTF-8') ?></h3>
                <p class="text-subtitle text-muted">Description of <?= htmlspecialchars($activeMenuLabel, ENT_QUOTES, 'UTF-8') ?>-<?= htmlspecialchars($activeItemLabel, ENT_QUOTES, 'UTF-8') ?>.</p>
            </div>
            <div class="col-12 col-lg-6 order-lg-2 order-first">
                <nav aria-label="breadcrumb" class="breadcrumb-header float-start float-lg-end">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="/index.php">Dashboard</a></li>
                        <li class="breadcrumb-item"><?= htmlspecialchars($activeMenuLabel, ENT_QUOTES, 'UTF-8') ?></li>
                        <li class="breadcrumb-item active" aria-current="page"><?= htmlspecialchars($activeItemLabel, ENT_QUOTES, 'UTF-8') ?></li>
                    </ol>
                </nav>
            </div>
        </div>
    </div>

</div>

<div class="page-content">
    <section class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-3">
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addModal">
                        <i class="bi bi-plus-lg me-1"></i>Tambah Data
                    </button>

                    <!-- Filter rentang tanggal (Tanggal pekerjaan) + Export Excel dikelompokkan
                         berdampingan -- hasil export selalu mengikuti filter yang aktif.
                         Flatpickr mode "range": pilih tanggal awal & akhir, filter otomatis
                         diterapkan. Dikirim melalui GET agar filter tetap terlihat di address
                         bar dan bisa langsung digunakan lagi oleh Export Excel & form
                         Tambah/Edit/Hapus. -->
                    <div class="d-flex flex-wrap align-items-center gap-2">
                        <form method="get" class="mb-0">
                            <div class="input-group input-group-sm">
                                <span class="input-group-text"><i class="bi bi-calendar-range"></i></span>
                                <input type="text" id="filter_date_range" class="form-control js-filter-date-range"
                                    placeholder="Pilih rentang tanggal" autocomplete="off">
<?php if ($filterQueryString !== ''): ?>
                                <a href="menu2-item1.php" class="btn btn-outline-secondary" title="Hapus filter">
                                    <i class="bi bi-x-lg"></i>
                                </a>
<?php endif; ?>
                            </div>
                            <input type="hidden" name="date_from" id="filter_date_from" value="<?= htmlspecialchars($activeFilter['date_from'], ENT_QUOTES, 'UTF-8') ?>">
                            <input type="hidden" name="date_to" id="filter_date_to" value="<?= htmlspecialchars($activeFilter['date_to'], ENT_QUOTES, 'UTF-8') ?>">
                        </form>
                        <a href="menu2-item1-export.php<?= $filterQueryString !== '' ? '?' . htmlspecialchars($filterQueryString, ENT_QUOTES, 'UTF-8') : '' ?>" class="btn btn-success btn-sm">
                            <i class="bi bi-file-earmark-excel me-1"></i>Export Excel
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table" id="table1">
                            <thead>
                                <tr>
                                    <th>No</th>
                                    <th>Hari</th>
                                    <th>Tanggal</th>
                                    <th>Pekerjaan</th>
                                    <th>Opsi</th>
                                </tr>
                            </thead>
                            <tbody>
<?php
    $rowNumber = 0;
?>
<?php foreach ($rows as $row): ?>
<?php
    $rowNumber = $rowNumber + 1;

    $workDateDisplay = formatDateDisplay($row['work_date']);
?>
                                <tr>
                                    <td><?= $rowNumber ?></td>
                                    <td><?= htmlspecialchars(indonesianDayName($row['work_date']), ENT_QUOTES, 'UTF-8') ?></td>
                                    <td><?= htmlspecialchars($workDateDisplay, ENT_QUOTES, 'UTF-8') ?></td>
                                    <td><?= htmlspecialchars($row['job_description'], ENT_QUOTES, 'UTF-8') ?></td>
                                    <td>
                                        <div class="d-flex gap-2">
                                            <button type="button" class="btn icon btn-secondary" data-bs-toggle="modal" data-bs-target="#viewModal<?= (int) $row['id'] ?>" title="Lihat">
                                                <i class="bi bi-eye"></i>
                                            </button>
                                            <button type="button" class="btn icon btn-primary" data-bs-toggle="modal" data-bs-target="#editModal<?= (int) $row['id'] ?>" title="Edit">
                                                <i class="bi bi-pencil"></i>
                                            </button>
<?php if (canDeleteRecords()): ?>
                                            <form method="post" class="d-inline js-delete-form" action="<?= htmlspecialchars($formActionWithFilter, ENT_QUOTES, 'UTF-8') ?>">
                                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                                                <input type="hidden" name="form_action" value="delete">
                                                <input type="hidden" name="id" value="<?= (int) $row['id'] ?>">
                                                <button type="submit" class="btn icon btn-danger" title="Hapus">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </form>
<?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
<?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>

<!-- Modal "Tambah Data", dibuka melalui tombol "Tambah Data" di atas tabel. -->
<div class="modal fade" id="addModal" tabindex="-1" aria-labelledby="addModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <form method="post" enctype="multipart/form-data" autocomplete="off" action="<?= htmlspecialchars($formActionWithFilter, ENT_QUOTES, 'UTF-8') ?>">
                <div class="modal-header">
                    <h5 class="modal-title" id="addModalLabel">Tambah Data</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                    <input type="hidden" name="form_action" value="add">
                    <div class="form-group mb-3">
                        <label for="add_work_date" class="form-label">Tanggal</label>
                        <input type="date" name="work_date" id="add_work_date" class="form-control js-work-date" required>
                    </div>
                    <div class="form-group mb-3">
                        <label for="add_job_description" class="form-label">Pekerjaan</label>
                        <textarea name="job_description" id="add_job_description" class="form-control" rows="3" required></textarea>
                    </div>
                    <div class="form-group mb-3">
                        <label class="form-label">Upload Foto</label>
                        <input type="file" name="photos[]" multiple class="js-photo-filepond">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary">Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal "Edit Data", satu modal per baris. Ditaruh di luar <table> (bukan
     di dalam <td>) agar tidak terganggu saat DataTables mengatur ulang
     baris untuk paginasi. -->
<?php foreach ($rows as $row): ?>
<?php
    $editPhotos = getMenu2Item1Photos($pdo, (int) $row['id']);

    // Tanda tangan milik user yang sedang login (jika ada), untuk tombol Sign.
    $editSignatures = [];
    if (isset($signaturesByItemId[$row['id']])) {
        $editSignatures = $signaturesByItemId[$row['id']];
    }

    $myEditSignature = null;
    foreach ($editSignatures as $signature) {
        if ((int) $signature['user_id'] === (int) $_SESSION['user_id']) {
            $myEditSignature = $signature;
            break;
        }
    }
?>
<div class="modal fade" id="editModal<?= (int) $row['id'] ?>" tabindex="-1" aria-labelledby="editModalLabel<?= (int) $row['id'] ?>" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <form method="post" enctype="multipart/form-data" autocomplete="off" class="js-edit-form-wrapper" action="<?= htmlspecialchars($formActionWithFilter, ENT_QUOTES, 'UTF-8') ?>">
                <div class="modal-header">
                    <h5 class="modal-title" id="editModalLabel<?= (int) $row['id'] ?>">Edit Data</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                    <input type="hidden" name="form_action" value="edit">
                    <input type="hidden" name="id" value="<?= (int) $row['id'] ?>">
                    <div class="form-group mb-3">
                        <label for="edit_work_date_<?= (int) $row['id'] ?>" class="form-label">Tanggal</label>
                        <input type="date" name="work_date" id="edit_work_date_<?= (int) $row['id'] ?>" class="form-control js-work-date" required
                            value="<?= htmlspecialchars($row['work_date'], ENT_QUOTES, 'UTF-8') ?>">
                    </div>
                    <div class="form-group mb-3">
                        <label for="edit_job_description_<?= (int) $row['id'] ?>" class="form-label">Pekerjaan</label>
                        <textarea name="job_description" id="edit_job_description_<?= (int) $row['id'] ?>" class="form-control" rows="3" required><?= htmlspecialchars($row['job_description'], ENT_QUOTES, 'UTF-8') ?></textarea>
                    </div>
<?php if (!empty($editPhotos)): ?>
                    <div class="form-group mb-3">
                        <label class="form-label">Foto Tersimpan</label>
                        <div class="row row-cols-3 g-2">
<?php foreach ($editPhotos as $photo): ?>
                            <div class="col js-photo-thumbnail">
                                <img src="<?= htmlspecialchars($uploadUrlBase . $photo['file_name'], ENT_QUOTES, 'UTF-8') ?>" class="img-fluid rounded mb-1" alt="Foto pekerjaan">
                                <div class="form-check">
                                    <input type="checkbox" name="delete_photo_ids[]" value="<?= (int) $photo['id'] ?>" class="form-check-input js-delete-photo-checkbox" id="delete_photo_<?= (int) $photo['id'] ?>">
                                    <label class="form-check-label small" for="delete_photo_<?= (int) $photo['id'] ?>">Hapus</label>
                                </div>
                            </div>
<?php endforeach; ?>
                        </div>
                    </div>
<?php endif; ?>
                    <div class="form-group mb-3">
                        <label class="form-label">Tambah Foto Baru</label>
                        <input type="file" name="photos[]" multiple class="js-photo-filepond">
                    </div>
                </div>
                <div class="modal-footer">
<?php if ($myEditSignature !== null): ?>
                    <!-- Tombol di sini, tetapi form yang disubmit ada DI LUAR <form> Edit
                         (lihat "deleteSignForm<id>" di bawah, dihubungkan melalui atribut
                         form="...") -- form tidak boleh bersarang di dalam form lain. Status
                         "Ditandatangani oleh ..." sudah ditampilkan di modal Lihat Data,
                         sehingga tidak diulang di sini. -->
                    <button type="submit" form="deleteSignForm<?= (int) $row['id'] ?>" class="btn btn-outline-danger me-auto js-delete-sign-btn">
                        <i class="bi bi-pen me-1"></i>Delete Sign
                    </button>
<?php else: ?>
                    <button type="button" class="btn btn-outline-primary me-auto js-sign-btn">
                        <i class="bi bi-pen me-1"></i>Sign
                    </button>
<?php endif; ?>
                    <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
                </div>
            </form>

<?php if ($myEditSignature !== null): ?>
            <!-- Form terpisah untuk tombol Hapus Tanda Tangan di atas, di luar <form>
                 Edit (form tidak boleh bersarang) -- tersembunyi (d-none), hanya
                 pembawa data, terhubung ke tombolnya melalui atribut form="...". -->
            <form method="post" id="deleteSignForm<?= (int) $row['id'] ?>" class="d-none js-delete-form" action="<?= htmlspecialchars($formActionWithFilter, ENT_QUOTES, 'UTF-8') ?>">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                <input type="hidden" name="form_action" value="delete-sign">
                <input type="hidden" name="id" value="<?= (int) $row['id'] ?>">
            </form>
<?php endif; ?>

<?php if ($myEditSignature === null): ?>
            <!-- Form terpisah untuk fitur Sign, di luar <form> Edit (form tidak boleh
                 bersarang) -- disembunyikan (d-none), ditampilkan saat tombol Sign
                 diklik. Hasil kanvas dikonversi menjadi PNG base64 dan diisikan ke
                 input "signature_data" sesaat sebelum submit (lihat <script> di bawah)
                 -- tetap dikirim melalui POST + reload biasa, bukan AJAX. -->
            <form method="post" class="js-sign-form d-none" action="<?= htmlspecialchars($formActionWithFilter, ENT_QUOTES, 'UTF-8') ?>">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                <input type="hidden" name="form_action" value="sign">
                <input type="hidden" name="id" value="<?= (int) $row['id'] ?>">
                <input type="hidden" name="signature_data" class="js-signature-data-input">
                <div class="modal-body">
                    <label class="form-label">Gambar tanda tangan di area berikut</label>
                    <canvas class="js-signature-canvas border rounded w-100" width="600" height="200" style="touch-action: none; cursor: crosshair;"></canvas>
                    <button type="button" class="btn btn-outline-secondary btn-sm mt-2 js-clear-signature">
                        <i class="bi bi-eraser me-1"></i>Hapus Coretan
                    </button>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary js-cancel-sign">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan Tanda Tangan</button>
                </div>
            </form>
<?php endif; ?>
        </div>
    </div>
</div>
<?php endforeach; ?>

<!-- Modal "Lihat Data", read-only -- satu modal per baris, di luar <table>
     dengan alasan yang sama seperti modal Edit di atas. -->
<?php foreach ($rows as $row): ?>
<?php
    $viewPhotos          = getMenu2Item1Photos($pdo, (int) $row['id']);
    $viewWorkDateDisplay  = formatDateDisplay($row['work_date']);
?>
<div class="modal fade" id="viewModal<?= (int) $row['id'] ?>" tabindex="-1" aria-labelledby="viewModalLabel<?= (int) $row['id'] ?>" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="viewModalLabel<?= (int) $row['id'] ?>">Detail Data</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="form-group mb-3">
                    <label class="form-label">Hari</label>
                    <p class="mb-0"><?= htmlspecialchars(indonesianDayName($row['work_date']), ENT_QUOTES, 'UTF-8') ?></p>
                </div>
                <div class="form-group mb-3">
                    <label class="form-label">Tanggal</label>
                    <p class="mb-0"><?= htmlspecialchars($viewWorkDateDisplay, ENT_QUOTES, 'UTF-8') ?></p>
                </div>
                <div class="form-group mb-3">
                    <label class="form-label">Pekerjaan</label>
                    <p class="mb-0"><?= nl2br(htmlspecialchars($row['job_description'], ENT_QUOTES, 'UTF-8')) ?></p>
                </div>
<?php if (!empty($viewPhotos)): ?>
                <div class="form-group mb-3">
                    <label class="form-label">Foto</label>
                    <div class="row row-cols-3 g-2">
<?php foreach ($viewPhotos as $photo): ?>
                        <div class="col">
                            <a href="<?= htmlspecialchars($uploadUrlBase . $photo['file_name'], ENT_QUOTES, 'UTF-8') ?>" class="glightbox" data-gallery="menu2item1-photos-<?= (int) $row['id'] ?>">
                                <img src="<?= htmlspecialchars($uploadUrlBase . $photo['file_name'], ENT_QUOTES, 'UTF-8') ?>" class="img-fluid rounded" alt="Foto pekerjaan">
                            </a>
                        </div>
<?php endforeach; ?>
                    </div>
                </div>
<?php else: ?>
                <p class="text-muted mb-3">Belum ada foto untuk data ini.</p>
<?php endif; ?>
<?php
    $viewSignatures = [];
    if (isset($signaturesByItemId[$row['id']])) {
        $viewSignatures = $signaturesByItemId[$row['id']];
    }
?>
<?php if (!empty($viewSignatures)): ?>
                <div class="form-group mb-0">
<?php foreach ($viewSignatures as $signature): ?>
                    <div class="mb-2">
                        <a href="#" class="js-view-signature-toggle" data-bs-toggle="collapse" data-bs-target="#viewSignature<?= (int) $row['id'] ?>_<?= (int) $signature['id'] ?>" role="button" aria-expanded="false">
                            <i class="bi bi-check-circle-fill text-success me-1"></i>Ditandatangani oleh <strong><?= htmlspecialchars($signature['full_name'], ENT_QUOTES, 'UTF-8') ?></strong>
                        </a>
                        <div class="collapse mt-2" id="viewSignature<?= (int) $row['id'] ?>_<?= (int) $signature['id'] ?>">
                            <img src="<?= htmlspecialchars($signatureUrlBase . $signature['file_name'], ENT_QUOTES, 'UTF-8') ?>" class="border rounded bg-white p-2" style="max-width: 300px;" alt="Tanda tangan <?= htmlspecialchars($signature['full_name'], ENT_QUOTES, 'UTF-8') ?>">
                        </div>
                    </div>
<?php endforeach; ?>
                </div>
<?php endif; ?>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary" onclick="window.print()">
                    <i class="bi bi-printer me-1"></i>Print
                </button>
            </div>
        </div>
    </div>
</div>
<?php endforeach; ?>

<style>
    /* window.print(): hanya .modal-body yang tercetak. */
    @media print {
        html, body {
            background: none !important;
        }
        body * {
            visibility: hidden;
        }
        .modal.show .modal-body,
        .modal.show .modal-body * {
            visibility: visible;
        }
        .modal.show {
            position: absolute;
            left: 0;
            top: 0;
            width: 100%;
            background: none !important;
        }
        .modal.show .modal-dialog {
            max-width: 100%;
            margin: 0;
        }
        .modal.show .modal-content {
            border: none;
            box-shadow: none;
            background: none !important;
        }
        .modal.show .modal-header,
        .modal.show .modal-footer {
            display: none !important;
        }
        .modal.show .modal-body {
            padding: 0;
            background: none !important;
        }
    }
</style>

<link rel="stylesheet" href="/assets/extensions/datatables.net-bs5/css/dataTables.bootstrap5.min.css">
<link rel="stylesheet" href="/assets/compiled/css/table-datatable-jquery.css">
<?php require_once __DIR__ . '/../../helpers/common-page-assets.php'; ?>

<script src="/assets/extensions/jquery/jquery.min.js"></script>
<script src="/assets/extensions/datatables.net/js/jquery.dataTables.min.js"></script>
<script src="/assets/extensions/datatables.net-bs5/js/dataTables.bootstrap5.min.js"></script>
<script src="/assets/extensions/flatpickr/flatpickr.min.js"></script>
<script src="/assets/extensions/sweetalert2/sweetalert2.min.js"></script>
<script src="/assets/extensions/filepond/filepond.js"></script>
<script src="/assets/extensions/filepond-plugin-image-preview/filepond-plugin-image-preview.min.js"></script>
<script src="/assets/extensions/filepond-plugin-file-validate-size/filepond-plugin-file-validate-size.min.js"></script>
<script src="/assets/extensions/filepond-plugin-file-validate-type/filepond-plugin-file-validate-type.min.js"></script>
<script src="/assets/extensions/filepond-plugin-image-exif-orientation/filepond-plugin-image-exif-orientation.min.js"></script>
<script src="/assets/extensions/filepond-plugin-image-resize/filepond-plugin-image-resize.min.js"></script>
<script src="/assets/extensions/glightbox/glightbox.min.js"></script>
<script>
    $(function () {
        $('#table1').DataTable({
            responsive: true,
            pageLength: 10,
            lengthMenu: [10, 25, 50, 100],
            // Kolom "No" & "Opsi" tidak perlu bisa diurutkan.
            columnDefs: [
                { orderable: false, targets: [0, 4] }
            ],
            // Jangan ganti ini dengan <tr> kosong manual di PHP -- error "_DT_CellIndex".
            language: {
                emptyTable: 'No data yet.'
            }
        });

        beautifyDataTableSearchBox();
    });
</script>
<script>
    var menu2Lightbox = GLightbox({
        selector: '.glightbox'
    });
</script>
<script>
    // dateFormat "Y-m-d" dikirim ke server; altFormat "d-m-Y" yang dilihat user.
    flatpickr('.js-work-date', {
        dateFormat: 'Y-m-d',
        altInput: true,
        altFormat: 'd-m-Y'
    });
</script>
<?php initDateRangeFilter($activeFilter['date_from'], $activeFilter['date_to']); ?>
<script>
    // storeAsFile: true -- file tetap terkirim lewat <form post> biasa, bukan AJAX.
    // imageResize* di sini cuma optimasi upload -- validasi final tetap di server.
    FilePond.registerPlugin(
        FilePondPluginImagePreview,
        FilePondPluginFileValidateSize,
        FilePondPluginFileValidateType,
        FilePondPluginImageExifOrientation,
        FilePondPluginImageResize
    );
    // Disimpan per .modal-content karena FilePond.find() tidak bisa dipakai lagi setelah init.
    var menu2FilePondInstances = new WeakMap();
    document.querySelectorAll('.js-photo-filepond').forEach(function (input) {
        // PENTING: diambil SEBELUM FilePond.create() -- sesudahnya closest() bisa null.
        var modalContent = input.closest('.modal-content');

        var pond = FilePond.create(input, {
            credits: null,
            allowMultiple: true,
            allowImagePreview: true,
            allowImageExifOrientation: true,
            allowImageResize: true,
            imageResizeTargetWidth: 2048,
            imageResizeTargetHeight: 2048,
            imageResizeMode: 'contain',
            imageResizeUpscale: false,
            acceptedFileTypes: ['image/jpeg', 'image/png', 'image/gif', 'image/webp'],
            maxFileSize: '10MB',
            storeAsFile: true
        });

        if (modalContent) {
            menu2FilePondInstances.set(modalContent, pond);
        }
    });
</script>
<script>
    document.querySelectorAll('.js-delete-photo-checkbox').forEach(function (checkbox) {
        checkbox.addEventListener('change', function () {
            checkbox.closest('.js-photo-thumbnail').classList.toggle('opacity-50', checkbox.checked);
        });
    });
</script>
<?php confirmDeleteForms('Hapus data ini?', 'Data yang sudah dihapus tidak bisa dikembalikan.'); ?>
<script>
    // Sign/Delete Sign submit form terpisah dari Edit -- ini mendeteksi perubahan
    // Edit yang belum disimpan supaya tidak hilang saat Sign/Delete Sign dipakai.
    function hasUnsavedEditChanges(modalContent) {
        var editForm = modalContent.querySelector('.js-edit-form-wrapper');
        if (!editForm) {
            return false;
        }

        var workDateInput = editForm.querySelector('.js-work-date');
        if (workDateInput && workDateInput.value !== workDateInput.defaultValue) {
            return true;
        }

        var jobDescriptionInput = editForm.querySelector('textarea[name="job_description"]');
        if (jobDescriptionInput && jobDescriptionInput.value !== jobDescriptionInput.defaultValue) {
            return true;
        }

        var hasCheckedDeletePhoto = false;
        editForm.querySelectorAll('.js-delete-photo-checkbox').forEach(function (checkbox) {
            if (checkbox.checked) {
                hasCheckedDeletePhoto = true;
            }
        });
        if (hasCheckedDeletePhoto) {
            return true;
        }

        var pond = menu2FilePondInstances.get(modalContent);
        if (pond && pond.getFiles().length > 0) {
            return true;
        }

        return false;
    }

    function warnUnsavedEditChanges(actionLabel) {
        Swal.fire({
            title: 'Ada perubahan yang belum disimpan',
            text: 'Simpan perubahan Anda (foto/tanggal/pekerjaan) terlebih dahulu dengan menekan "Simpan Perubahan" sebelum ' + actionLabel + '.',
            icon: 'warning'
        });
    }

    document.querySelectorAll('.js-sign-btn').forEach(function (button) {
        button.addEventListener('click', function () {
            var modalContent = button.closest('.modal-content');

            if (hasUnsavedEditChanges(modalContent)) {
                warnUnsavedEditChanges('melakukan Sign');
                return;
            }

            modalContent.querySelector('.js-edit-form-wrapper').classList.add('d-none');
            modalContent.querySelector('.js-sign-form').classList.remove('d-none');
        });
    });

    document.querySelectorAll('.js-cancel-sign').forEach(function (button) {
        button.addEventListener('click', function () {
            var modalContent = button.closest('.modal-content');
            modalContent.querySelector('.js-sign-form').classList.add('d-none');
            modalContent.querySelector('.js-edit-form-wrapper').classList.remove('d-none');
        });
    });

    // Modal ditutup saat form Sign terbuka -> kembalikan ke form Edit & kosongkan kanvas.
    document.querySelectorAll('.modal').forEach(function (modalEl) {
        modalEl.addEventListener('hidden.bs.modal', function () {
            var signForm = modalEl.querySelector('.js-sign-form');
            if (!signForm) {
                return;
            }

            signForm.classList.add('d-none');

            var editFormWrapper = modalEl.querySelector('.js-edit-form-wrapper');
            if (editFormWrapper) {
                editFormWrapper.classList.remove('d-none');
            }

            var canvas = signForm.querySelector('.js-signature-canvas');
            if (canvas) {
                canvas.getContext('2d').clearRect(0, 0, canvas.width, canvas.height);
                canvas.classList.remove('js-signature-has-content');
            }
        });
    });
</script>
<script>
    document.querySelectorAll('.js-signature-canvas').forEach(function (canvas) {
        var ctx = canvas.getContext('2d');
        ctx.lineWidth   = 2;
        ctx.lineCap     = 'round';
        ctx.lineJoin    = 'round';
        ctx.strokeStyle = '#000000';

        var isDrawing = false;
        var lastX = 0;
        var lastY = 0;

        // Diskalakan: ukuran asli kanvas (600x200) vs ukuran tampilnya di layar.
        function getCanvasPosition(event) {
            var rect   = canvas.getBoundingClientRect();
            var scaleX = canvas.width / rect.width;
            var scaleY = canvas.height / rect.height;

            return {
                x: (event.clientX - rect.left) * scaleX,
                y: (event.clientY - rect.top) * scaleY
            };
        }

        canvas.addEventListener('pointerdown', function (event) {
            isDrawing = true;
            canvas.setPointerCapture(event.pointerId);

            var position = getCanvasPosition(event);
            lastX = position.x;
            lastY = position.y;

            canvas.classList.add('js-signature-has-content');
        });

        canvas.addEventListener('pointermove', function (event) {
            if (!isDrawing) {
                return;
            }

            var position = getCanvasPosition(event);

            ctx.beginPath();
            ctx.moveTo(lastX, lastY);
            ctx.lineTo(position.x, position.y);
            ctx.stroke();

            lastX = position.x;
            lastY = position.y;
        });

        canvas.addEventListener('pointerup', function () {
            isDrawing = false;
        });

        canvas.addEventListener('pointerleave', function () {
            isDrawing = false;
        });
    });

    document.querySelectorAll('.js-clear-signature').forEach(function (button) {
        button.addEventListener('click', function () {
            var canvas = button.closest('.modal-body').querySelector('.js-signature-canvas');
            canvas.getContext('2d').clearRect(0, 0, canvas.width, canvas.height);
            canvas.classList.remove('js-signature-has-content');
        });
    });
</script>
<script>
    document.querySelectorAll('.js-sign-form').forEach(function (form) {
        form.addEventListener('submit', function (event) {
            var canvas = form.querySelector('.js-signature-canvas');

            if (!canvas.classList.contains('js-signature-has-content')) {
                event.preventDefault();

                Swal.fire({
                    title: 'Tanda tangan masih kosong',
                    text: 'Silakan gambar tanda tangan terlebih dahulu di area yang tersedia.',
                    icon: 'warning'
                });

                return;
            }

            form.querySelector('.js-signature-data-input').value = canvas.toDataURL('image/png');
        });
    });
</script>
<script>
    // Guard sama seperti tombol Sign, untuk form="deleteSignForm<id>".
    document.querySelectorAll('.js-delete-sign-btn').forEach(function (button) {
        button.addEventListener('click', function (event) {
            var modalContent = button.closest('.modal-content');

            if (hasUnsavedEditChanges(modalContent)) {
                event.preventDefault();
                warnUnsavedEditChanges('menghapus tanda tangan');
            }
        });
    });
</script>

<?php showFormResultToast($successMessage, $errorMessage); ?>

<?php require __DIR__ . '/../../footer.php'; ?>
