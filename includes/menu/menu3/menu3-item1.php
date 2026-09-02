<?php
require_once __DIR__ . '/../../auth.php';
require_once __DIR__ . '/../../helpers/format-helpers.php';
require_once __DIR__ . '/../../menu-config.php';
require_once __DIR__ . '/menu3-item1-filter.php';
require_once __DIR__ . '/menu3-item1-labels.php';

requireLogin();

$pdo = getDbConnection();

$itemKeys = array_keys($checklistItemLabels);

// Kalau Check dicentang, Keterangan SELALU dikosongkan, berapa pun yang terkirim.
function readChecklistInputFromPost(array $itemKeys): array
{
    $checklist = [];
    foreach ($itemKeys as $itemKey) {
        $checked = isset($_POST[$itemKey . '_check']);

        $note = '';
        if (!$checked) {
            $note = postValue($itemKey . '_note');
        }

        $checklist[$itemKey] = ['check' => $checked, 'note' => $note];
    }

    return $checklist;
}

// Daftar kolom checklist lengkap untuk SELECT "data lama" (Edit & Hapus) -- satu tempat saja.
function menu3Item1AllColumnsSql(array $itemKeys): string
{
    $columnsSql = 'check_date, unit, laporan, tindak_lanjut';
    foreach ($itemKeys as $itemKey) {
        $columnsSql = $columnsSql . ', ' . $itemKey . '_check, ' . $itemKey . '_note';
    }

    return $columnsSql;
}

$successMessage = '';
$errorMessage   = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $formAction = postValue('form_action', false);

    if (!submittedCsrfTokenIsValid()) {
        $errorMessage = 'Invalid form session (CSRF). Please reload the page and try again.';
    } elseif ($formAction === 'add') {
        $checkDate = postValue('check_date');
        $unit      = postValue('unit');
        $laporan   = postValue('laporan');

        if ($checkDate === '' || !isset($unitLabels[$unit])) {
            $errorMessage = 'Tanggal dan Unit wajib diisi.';
        } else {
            $checklist = readChecklistInputFromPost($itemKeys);

            $stmt = $pdo->prepare(
                'INSERT INTO menu3_item1 (
                    check_date, checker_name, unit, laporan,
                    item1_check, item1_note, item2_check, item2_note,
                    item3_check, item3_note, item4_check, item4_note,
                    item5_check, item5_note, item6_check, item6_note,
                    item7_check, item7_note, item8_check, item8_note,
                    item9_check, item9_note, item10_check, item10_note
                ) VALUES (
                    :check_date, :checker_name, :unit, :laporan,
                    :item1_check, :item1_note, :item2_check, :item2_note,
                    :item3_check, :item3_note, :item4_check, :item4_note,
                    :item5_check, :item5_note, :item6_check, :item6_note,
                    :item7_check, :item7_note, :item8_check, :item8_note,
                    :item9_check, :item9_note, :item10_check, :item10_note
                )'
            );

            // Checker selalu dari akun yang sedang login, tidak ada input-nya di form.
            $params = [
                'check_date'   => $checkDate,
                'checker_name' => $_SESSION['full_name'],
                'unit'         => $unit,
                'laporan'      => $laporan,
            ];
            foreach ($itemKeys as $itemKey) {
                if ($checklist[$itemKey]['check']) {
                    $params[$itemKey . '_check'] = 1;
                } else {
                    $params[$itemKey . '_check'] = 0;
                }
                $params[$itemKey . '_note'] = $checklist[$itemKey]['note'];
            }
            $stmt->execute($params);
            $newRowId = (int) $pdo->lastInsertId();

            $activityActor = currentActivityLogActor();
            $unitDisplayForLog = menu3UnitLabel($unit, $unitLabels);
            logActivity(
                $pdo,
                $activityActor['user_id'],
                $activityActor['username'],
                $activityActor['full_name'],
                'create',
                'menu3_item1',
                $newRowId,
                'Menambahkan checklist #' . $newRowId . ': ' . $unitDisplayForLog . ' (' . $checkDate . ').',
                null,
                $params
            );

            $successMessage = 'Data baru berhasil ditambahkan.';
        }
    } elseif ($formAction === 'edit') {
        $id        = postInt('id');
        $checkDate = postValue('check_date');
        $unit      = postValue('unit');
        $laporan   = postValue('laporan');

        // Tindak Lanjut hanya ada di form Edit, tidak ada di form Tambah Data.
        $tindakLanjut = postValue('tindak_lanjut');

        if ($id === 0 || $checkDate === '' || !isset($unitLabels[$unit])) {
            $errorMessage = 'Tanggal dan Unit wajib diisi.';
        } else {
            $checklist = readChecklistInputFromPost($itemKeys);

            $stmt = $pdo->prepare('SELECT ' . menu3Item1AllColumnsSql($itemKeys) . ' FROM menu3_item1 WHERE id = :id LIMIT 1');
            $stmt->execute(['id' => $id]);
            $oldRow = $stmt->fetch();

            // Checker TIDAK diubah saat edit -- tetap orang yang pertama kali mengisi.
            $stmt = $pdo->prepare(
                'UPDATE menu3_item1 SET
                    check_date = :check_date, unit = :unit, laporan = :laporan, tindak_lanjut = :tindak_lanjut,
                    item1_check = :item1_check, item1_note = :item1_note,
                    item2_check = :item2_check, item2_note = :item2_note,
                    item3_check = :item3_check, item3_note = :item3_note,
                    item4_check = :item4_check, item4_note = :item4_note,
                    item5_check = :item5_check, item5_note = :item5_note,
                    item6_check = :item6_check, item6_note = :item6_note,
                    item7_check = :item7_check, item7_note = :item7_note,
                    item8_check = :item8_check, item8_note = :item8_note,
                    item9_check = :item9_check, item9_note = :item9_note,
                    item10_check = :item10_check, item10_note = :item10_note
                WHERE id = :id'
            );

            $params = [
                'check_date'    => $checkDate,
                'unit'          => $unit,
                'laporan'       => $laporan,
                'tindak_lanjut' => $tindakLanjut,
                'id'            => $id,
            ];
            foreach ($itemKeys as $itemKey) {
                if ($checklist[$itemKey]['check']) {
                    $params[$itemKey . '_check'] = 1;
                } else {
                    $params[$itemKey . '_check'] = 0;
                }
                $params[$itemKey . '_note'] = $checklist[$itemKey]['note'];
            }
            $stmt->execute($params);

            if ($oldRow !== false) {
                $newValuesForLog = $params;
                unset($newValuesForLog['id']);

                $activityActor = currentActivityLogActor();
                $unitDisplayForLog = menu3UnitLabel($unit, $unitLabels);
                logActivity(
                    $pdo,
                    $activityActor['user_id'],
                    $activityActor['username'],
                    $activityActor['full_name'],
                    'update',
                    'menu3_item1',
                    $id,
                    'Mengubah checklist #' . $id . ': ' . $unitDisplayForLog . ' (' . $checkDate . ').',
                    $oldRow,
                    $newValuesForLog
                );
            }

            $successMessage = 'Data berhasil diperbarui.';
        }
    } elseif ($formAction === 'delete') {
        if (!canDeleteRecords()) {
            $errorMessage = 'Anda tidak memiliki izin untuk menghapus data ini.';
        } else {
            $id = postInt('id');

            if ($id === 0) {
                $errorMessage = 'Data tidak ditemukan.';
            } else {
                $stmt = $pdo->prepare('SELECT ' . menu3Item1AllColumnsSql($itemKeys) . ' FROM menu3_item1 WHERE id = :id LIMIT 1');
                $stmt->execute(['id' => $id]);
                $oldRow = $stmt->fetch();

                $stmt = $pdo->prepare('DELETE FROM menu3_item1 WHERE id = :id');
                $stmt->execute(['id' => $id]);

                if ($oldRow !== false) {
                    $activityActor = currentActivityLogActor();
                    $unitDisplayForLog = menu3UnitLabel($oldRow['unit'], $unitLabels);
                    logActivity(
                        $pdo,
                        $activityActor['user_id'],
                        $activityActor['username'],
                        $activityActor['full_name'],
                        'delete',
                        'menu3_item1',
                        $id,
                        'Menghapus checklist #' . $id . ': ' . $unitDisplayForLog . ' (' . $oldRow['check_date'] . ').',
                        $oldRow,
                        null
                    );
                }

                $successMessage = 'Data berhasil dihapus.';
            }
        }
    }
}

// Fungsi filter ini dipakai lagi oleh Export Excel, agar datanya selalu sama persis.
$activeFilter      = parseDateRangeFilter();
$filterQueryString = dateRangeFilterQueryString($activeFilter);
$rows               = fetchMenu3Item1FilteredRows($pdo, $activeFilter);

$formActionWithFilter = 'menu3-item1.php';
if ($filterQueryString !== '') {
    $formActionWithFilter = $formActionWithFilter . '?' . $filterQueryString;
}

$exportUrl = 'menu3-item1-export.php';
if ($filterQueryString !== '') {
    $exportUrl = $exportUrl . '?' . htmlspecialchars($filterQueryString, ENT_QUOTES, 'UTF-8');
}

$csrfToken = csrfToken();

$activeMenu = 'menu3-item1';
require __DIR__ . '/../../header.php';
?>

<link rel="stylesheet" href="/assets/extensions/sweetalert2/sweetalert2.min.css">
<link rel="stylesheet" href="/assets/extensions/flatpickr/flatpickr.min.css">
<link rel="stylesheet" href="/assets/extensions/choices.js/public/assets/styles/choices.css">

<div class="page-heading">
    <div class="page-title">
        <div class="row">
            <div class="col-12 col-lg-6 order-lg-1 order-last">
                <h3><?= htmlspecialchars($activeMenuLabel, ENT_QUOTES, 'UTF-8') ?> -> <?= htmlspecialchars($activeItemLabel, ENT_QUOTES, 'UTF-8') ?></h3>
                <p class="text-subtitle text-muted">Checklist pekerjaan harian per unit.</p>
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

                    <!-- Filter rentang tanggal (Tanggal checklist) + Export Excel -- pola yang sama
                         digunakan di seluruh tabel aplikasi ini. Flatpickr mode "range": pilih
                         tanggal awal & akhir, filter otomatis diterapkan. Dikirim melalui GET agar
                         filter tetap terlihat di address bar dan bisa langsung digunakan lagi oleh
                         Export Excel & form Tambah/Edit/Hapus. -->
                    <div class="d-flex flex-wrap align-items-center gap-2">
                        <form method="get" class="mb-0">
                            <div class="input-group input-group-sm">
                                <span class="input-group-text"><i class="bi bi-calendar-range"></i></span>
                                <input type="text" id="filter_date_range" class="form-control js-filter-date-range"
                                    placeholder="Pilih rentang tanggal" autocomplete="off">
<?php if ($filterQueryString !== ''): ?>
                                <a href="menu3-item1.php" class="btn btn-outline-secondary" title="Hapus filter">
                                    <i class="bi bi-x-lg"></i>
                                </a>
<?php endif; ?>
                            </div>
                            <input type="hidden" name="date_from" id="filter_date_from" value="<?= htmlspecialchars($activeFilter['date_from'], ENT_QUOTES, 'UTF-8') ?>">
                            <input type="hidden" name="date_to" id="filter_date_to" value="<?= htmlspecialchars($activeFilter['date_to'], ENT_QUOTES, 'UTF-8') ?>">
                        </form>
                        <a href="<?= $exportUrl ?>" class="btn btn-success btn-sm">
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
                                    <th>Unit</th>
                                    <th>Status</th>
                                    <th>Laporan</th>
                                    <th>Tindak Lanjut</th>
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

    $checkDateDisplay = formatDateDisplay($row['check_date']);
    $unitDisplay      = menu3UnitLabel($row['unit'], $unitLabels);

    // Status dihitung otomatis dari jumlah item checklist yang tidak tercentang,
    // tidak disimpan sebagai kolom di database.
    $kendalaCount     = countMenu3Item1Kendala($row, $itemKeys);
    $kendalaStatus    = menu3Item1KendalaStatus($kendalaCount);
    $statusBadgeClass = $kendalaStatus['badge_class'];
    $statusBadgeText  = $kendalaStatus['text'];

    $laporanDisplay = $row['laporan'];
    if ($laporanDisplay === '') {
        $laporanDisplay = '-';
    }

    $tindakLanjutDisplay = $row['tindak_lanjut'];
    if ($tindakLanjutDisplay === '') {
        $tindakLanjutDisplay = '-';
    }
?>
                                <tr>
                                    <td><?= $rowNumber ?></td>
                                    <td><?= htmlspecialchars(indonesianDayName($row['check_date']), ENT_QUOTES, 'UTF-8') ?></td>
                                    <td><?= htmlspecialchars($checkDateDisplay, ENT_QUOTES, 'UTF-8') ?></td>
                                    <td><?= htmlspecialchars($unitDisplay, ENT_QUOTES, 'UTF-8') ?></td>
                                    <td><span class="badge <?= $statusBadgeClass ?>"><?= htmlspecialchars($statusBadgeText, ENT_QUOTES, 'UTF-8') ?></span></td>
                                    <td><?= htmlspecialchars($laporanDisplay, ENT_QUOTES, 'UTF-8') ?></td>
                                    <td><?= htmlspecialchars($tindakLanjutDisplay, ENT_QUOTES, 'UTF-8') ?></td>
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

<?php
// $existingChecklist kosong untuk form Tambah, diisi dari data baris untuk form Edit.
function renderChecklistFormRows(array $checklistItemLabels, string $idPrefix, array $existingChecklist = []): void
{
    foreach ($checklistItemLabels as $itemKey => $itemLabel) {
        $checked = false;
        $note    = '';
        if (isset($existingChecklist[$itemKey])) {
            $checked = (bool) $existingChecklist[$itemKey]['check'];
            $note    = $existingChecklist[$itemKey]['note'];
        }

        $checkboxId = $idPrefix . '_' . $itemKey . '_check';
        ?>
                            <tr class="js-checklist-row">
                                <td class="text-center">
                                    <input type="checkbox" class="form-check-input js-checklist-check" name="<?= htmlspecialchars($itemKey, ENT_QUOTES, 'UTF-8') ?>_check" id="<?= htmlspecialchars($checkboxId, ENT_QUOTES, 'UTF-8') ?>"<?php if ($checked) { echo ' checked'; } ?>>
                                </td>
                                <td><label for="<?= htmlspecialchars($checkboxId, ENT_QUOTES, 'UTF-8') ?>" class="mb-0"><?= htmlspecialchars($itemLabel, ENT_QUOTES, 'UTF-8') ?></label></td>
                                <td>
                                    <input type="text" class="form-control form-control-sm js-checklist-note" name="<?= htmlspecialchars($itemKey, ENT_QUOTES, 'UTF-8') ?>_note" maxlength="255" placeholder="Keterangan (opsional)"<?php if ($checked) { echo ' disabled'; } ?> value="<?= htmlspecialchars($note, ENT_QUOTES, 'UTF-8') ?>">
                                </td>
                            </tr>
        <?php
    }
}
?>

<!-- Modal "Tambah Data", dibuka melalui tombol "Tambah Data" di atas tabel. -->
<div class="modal fade" id="addModal" tabindex="-1" aria-labelledby="addModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <form method="post" autocomplete="off" action="<?= htmlspecialchars($formActionWithFilter, ENT_QUOTES, 'UTF-8') ?>">
                <div class="modal-header">
                    <h5 class="modal-title" id="addModalLabel">Tambah Data</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                    <input type="hidden" name="form_action" value="add">
                    <div class="form-group mb-3">
                        <label for="add_check_date" class="form-label">Tanggal</label>
                        <input type="date" name="check_date" id="add_check_date" class="form-control js-check-date" required>
                    </div>
                    <div class="form-group mb-3">
                        <label for="add_unit" class="form-label">Unit</label>
                        <select name="unit" id="add_unit" class="form-select js-unit-choices" required>
                            <option value="" disabled selected>-- Pilih Unit --</option>
<?php foreach ($unitLabels as $unitKey => $unitLabel): ?>
                            <option value="<?= htmlspecialchars($unitKey, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($unitLabel, ENT_QUOTES, 'UTF-8') ?></option>
<?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group mb-3">
                        <label class="form-label">Checklist Item</label>
                        <div class="table-responsive">
                            <table class="table table-sm table-bordered align-middle mb-0 js-checklist-table">
                                <thead>
                                    <tr>
                                        <th style="width: 60px;" class="text-center">Check</th>
                                        <th>Item</th>
                                        <th>Keterangan</th>
                                    </tr>
                                </thead>
                                <tbody>
<?php renderChecklistFormRows($checklistItemLabels, 'add'); ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="form-group mb-0">
                        <label for="add_laporan" class="form-label">Laporan</label>
                        <input type="text" name="laporan" id="add_laporan" class="form-control" maxlength="255" placeholder="Laporan (opsional)">
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
    $editChecklist = [];
    foreach ($itemKeys as $itemKey) {
        $editChecklist[$itemKey] = [
            'check' => (bool) $row[$itemKey . '_check'],
            'note'  => $row[$itemKey . '_note'],
        ];
    }
?>
<div class="modal fade" id="editModal<?= (int) $row['id'] ?>" tabindex="-1" aria-labelledby="editModalLabel<?= (int) $row['id'] ?>" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <form method="post" autocomplete="off" action="<?= htmlspecialchars($formActionWithFilter, ENT_QUOTES, 'UTF-8') ?>">
                <div class="modal-header">
                    <h5 class="modal-title" id="editModalLabel<?= (int) $row['id'] ?>">Edit Data</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                    <input type="hidden" name="form_action" value="edit">
                    <input type="hidden" name="id" value="<?= (int) $row['id'] ?>">
                    <div class="form-group mb-3">
                        <label for="edit_check_date_<?= (int) $row['id'] ?>" class="form-label">Tanggal</label>
                        <input type="date" name="check_date" id="edit_check_date_<?= (int) $row['id'] ?>" class="form-control js-check-date" required
                            value="<?= htmlspecialchars($row['check_date'], ENT_QUOTES, 'UTF-8') ?>">
                    </div>
                    <div class="form-group mb-3">
                        <label for="edit_unit_<?= (int) $row['id'] ?>" class="form-label">Unit</label>
                        <select name="unit" id="edit_unit_<?= (int) $row['id'] ?>" class="form-select js-unit-choices" required>
<?php foreach ($unitLabels as $unitKey => $unitLabel): ?>
                            <option value="<?= htmlspecialchars($unitKey, ENT_QUOTES, 'UTF-8') ?>"<?php if ($row['unit'] === $unitKey) { echo ' selected'; } ?>><?= htmlspecialchars($unitLabel, ENT_QUOTES, 'UTF-8') ?></option>
<?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group mb-3">
                        <label class="form-label">Checklist Item</label>
                        <div class="table-responsive">
                            <table class="table table-sm table-bordered align-middle mb-0 js-checklist-table">
                                <thead>
                                    <tr>
                                        <th style="width: 60px;" class="text-center">Check</th>
                                        <th>Item</th>
                                        <th>Keterangan</th>
                                    </tr>
                                </thead>
                                <tbody>
<?php renderChecklistFormRows($checklistItemLabels, 'edit' . (int) $row['id'], $editChecklist); ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="form-group mb-3">
                        <label for="edit_laporan_<?= (int) $row['id'] ?>" class="form-label">Laporan</label>
                        <input type="text" name="laporan" id="edit_laporan_<?= (int) $row['id'] ?>" class="form-control" maxlength="255" placeholder="Laporan (opsional)"
                            value="<?= htmlspecialchars($row['laporan'], ENT_QUOTES, 'UTF-8') ?>">
                    </div>
                    <div class="form-group mb-0">
                        <label for="edit_tindak_lanjut_<?= (int) $row['id'] ?>" class="form-label">Tindak Lanjut</label>
                        <input type="text" name="tindak_lanjut" id="edit_tindak_lanjut_<?= (int) $row['id'] ?>" class="form-control" maxlength="255" placeholder="Tindak Lanjut (opsional)"
                            value="<?= htmlspecialchars($row['tindak_lanjut'], ENT_QUOTES, 'UTF-8') ?>">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endforeach; ?>

<!-- Modal "Lihat Data", read-only -- satu modal per baris, di luar <table>
     dengan alasan yang sama seperti modal Edit di atas. -->
<?php foreach ($rows as $row): ?>
<?php
    $viewCheckDateDisplay = formatDateDisplay($row['check_date']);
    $viewUnitDisplay      = menu3UnitLabel($row['unit'], $unitLabels);

    $viewKendalaCount     = countMenu3Item1Kendala($row, $itemKeys);
    $viewKendalaStatus    = menu3Item1KendalaStatus($viewKendalaCount);
    $viewStatusBadgeClass = $viewKendalaStatus['badge_class'];
    $viewStatusBadgeText  = $viewKendalaStatus['text'];

    $viewLaporanDisplay = $row['laporan'];
    if ($viewLaporanDisplay === '') {
        $viewLaporanDisplay = '-';
    }

    $viewTindakLanjutDisplay = $row['tindak_lanjut'];
    if ($viewTindakLanjutDisplay === '') {
        $viewTindakLanjutDisplay = '-';
    }
?>
<div class="modal fade" id="viewModal<?= (int) $row['id'] ?>" tabindex="-1" aria-labelledby="viewModalLabel<?= (int) $row['id'] ?>" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="viewModalLabel<?= (int) $row['id'] ?>">Detail Data</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="form-group mb-3">
                    <label class="form-label">Hari</label>
                    <p class="mb-0"><?= htmlspecialchars(indonesianDayName($row['check_date']), ENT_QUOTES, 'UTF-8') ?></p>
                </div>
                <div class="form-group mb-3">
                    <label class="form-label">Tanggal</label>
                    <p class="mb-0"><?= htmlspecialchars($viewCheckDateDisplay, ENT_QUOTES, 'UTF-8') ?></p>
                </div>
                <div class="form-group mb-3">
                    <label class="form-label">Unit</label>
                    <p class="mb-0"><?= htmlspecialchars($viewUnitDisplay, ENT_QUOTES, 'UTF-8') ?></p>
                </div>
                <div class="form-group mb-3">
                    <label class="form-label">Status</label>
                    <p class="mb-0"><span class="badge <?= $viewStatusBadgeClass ?>"><?= htmlspecialchars($viewStatusBadgeText, ENT_QUOTES, 'UTF-8') ?></span></p>
                </div>
                <div class="form-group mb-3">
                    <label class="form-label">Checker</label>
                    <p class="mb-3"><?= htmlspecialchars($row['checker_name'], ENT_QUOTES, 'UTF-8') ?></p>
                    <div class="table-responsive">
                        <table class="table table-sm table-bordered align-middle mb-0 js-checklist-table">
                            <thead>
                                <tr>
                                    <th style="width: 60px;" class="text-center">Check</th>
                                    <th>Item</th>
                                    <th>Keterangan</th>
                                </tr>
                            </thead>
                            <tbody>
<?php foreach ($checklistItemLabels as $itemKey => $itemLabel): ?>
<?php
    $itemNoteDisplay = $row[$itemKey . '_note'];
    if ($itemNoteDisplay === '') {
        $itemNoteDisplay = '-';
    }
?>
                                <tr>
                                    <td class="text-center">
<?php if ((bool) $row[$itemKey . '_check']): ?>
                                        <i class="bi bi-check-lg text-success"></i>
<?php else: ?>
                                        <i class="bi bi-x-lg text-danger"></i>
<?php endif; ?>
                                    </td>
                                    <td><?= htmlspecialchars($itemLabel, ENT_QUOTES, 'UTF-8') ?></td>
                                    <td><?= htmlspecialchars($itemNoteDisplay, ENT_QUOTES, 'UTF-8') ?></td>
                                </tr>
<?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="form-group mb-3">
                    <label class="form-label">Laporan</label>
                    <p class="mb-0"><?= htmlspecialchars($viewLaporanDisplay, ENT_QUOTES, 'UTF-8') ?></p>
                </div>
                <div class="form-group mb-0">
                    <label class="form-label">Tindak Lanjut</label>
                    <p class="mb-0"><?= htmlspecialchars($viewTindakLanjutDisplay, ENT_QUOTES, 'UTF-8') ?></p>
                </div>
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

<style>
    .js-checklist-check {
        width: 1.15rem;
        height: 1.15rem;
    }

    @media (max-width: 575.98px) {
        .js-checklist-table thead {
            display: none;
        }
        .js-checklist-table, .js-checklist-table tbody {
            display: block;
            width: 100%;
        }
        .js-checklist-table tr {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            column-gap: .6rem;
            border: 1px solid var(--bs-border-color, #dee2e6);
            border-radius: .5rem;
            padding: .6rem .75rem;
            margin-bottom: .5rem;
        }
        .js-checklist-table td {
            display: block;
            border: none !important;
            padding: 0;
        }
        .js-checklist-table td:nth-child(1) {
            flex: 0 0 auto;
        }
        .js-checklist-table td:nth-child(2) {
            flex: 1 1 auto;
        }
        .js-checklist-table td:nth-child(3) {
            flex: 1 1 100%;
            margin-top: .4rem;
        }
        .js-checklist-table .js-checklist-note {
            font-size: 16px;
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
<script src="/assets/extensions/choices.js/public/assets/scripts/choices.js"></script>
<script src="/assets/extensions/sweetalert2/sweetalert2.min.js"></script>
<script>
    $(function () {
        $('#table1').DataTable({
            responsive: true,
            pageLength: 10,
            lengthMenu: [10, 25, 50, 100],
            // Status berisi teks campur angka -- urutan alfabet tidak berguna, jadi tidak sortable.
            columnDefs: [
                { orderable: false, targets: [0, 4, 7] }
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
    // WeakMap mencegah init dobel -- Choices.js error kalau elemen yang sama
    // di-init lebih dari sekali tanpa di-destroy dulu.
    var menu3UnitChoicesInstances = new WeakMap();

    function initUnitChoicesDropdown(selectElement) {
        if (menu3UnitChoicesInstances.has(selectElement)) {
            return;
        }

        var choicesInstance = new Choices(selectElement, {
            searchEnabled: true,
            shouldSort: false,
            itemSelectText: ''
        });

        menu3UnitChoicesInstances.set(selectElement, choicesInstance);
    }

    // Ditunda sampai modal ditampilkan -- Choices.js salah hitung lebar elemen kalau
    // diinisialisasi saat modalnya masih tersembunyi.
    document.querySelectorAll('.modal').forEach(function (modalEl) {
        modalEl.addEventListener('show.bs.modal', function () {
            var unitSelect = modalEl.querySelector('.js-unit-choices');
            if (unitSelect) {
                initUnitChoicesDropdown(unitSelect);
            }
        });
    });
</script>
<script>
    // dateFormat "Y-m-d" dikirim ke server; altFormat "d-m-Y" yang dilihat user.
    flatpickr('.js-check-date', {
        dateFormat: 'Y-m-d',
        altInput: true,
        altFormat: 'd-m-Y'
    });
</script>
<?php initDateRangeFilter($activeFilter['date_from'], $activeFilter['date_to']); ?>
<script>
    // Check tercentang -> Keterangan dikosongkan & dinonaktifkan.
    document.querySelectorAll('.js-checklist-check').forEach(function (checkbox) {
        function syncNoteState() {
            var noteInput = checkbox.closest('.js-checklist-row').querySelector('.js-checklist-note');
            if (!noteInput) {
                return;
            }

            noteInput.disabled = checkbox.checked;
            if (checkbox.checked) {
                noteInput.value = '';
            }
        }

        checkbox.addEventListener('change', syncNoteState);
        syncNoteState();
    });
</script>
<?php confirmDeleteForms('Hapus data ini?', 'Data yang sudah dihapus tidak bisa dikembalikan.'); ?>
<?php showFormResultToast($successMessage, $errorMessage); ?>
<?php require __DIR__ . '/../../footer.php'; ?>
