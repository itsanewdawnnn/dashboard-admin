<?php
require_once __DIR__ . '/../../auth.php';
require_once __DIR__ . '/../../helpers/format-helpers.php';
require_once __DIR__ . '/../../menu-config.php';
require_once __DIR__ . '/menu1-item1-filter.php';

requireLogin();

$pdo = getDbConnection();

$successMessage = '';
$errorMessage   = '';

// Semua aksi (tambah/edit/hapus) dikirim POST ke halaman ini, dibedakan lewat "form_action".
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $formAction = postValue('form_action', false);

    if (!submittedCsrfTokenIsValid()) {
        $errorMessage = 'Invalid form session (CSRF). Please reload the page and try again.';
    } elseif ($formAction === 'add') {
        $fullName   = postValue('full_name');
        $activeDate = postValue('active_date');
        $password   = postValue('password', false);

        if ($fullName === '' || $activeDate === '' || $password === '') {
            $errorMessage = 'Nama Lengkap, tanggal aktif, dan password wajib diisi.';
        } else {
            $stmt = $pdo->prepare(
                'INSERT INTO menu1_item1 (full_name, active_date, password) VALUES (:full_name, :active_date, :password)'
            );
            $stmt->execute([
                'full_name'   => $fullName,
                'active_date' => $activeDate,
                'password'    => $password,
            ]);
            $newRowId = (int) $pdo->lastInsertId();

            $activityActor = currentActivityLogActor();
            logActivity(
                $pdo,
                $activityActor['user_id'],
                $activityActor['username'],
                $activityActor['full_name'],
                'create',
                'menu1_item1',
                $newRowId,
                'Menambahkan data #' . $newRowId . ': ' . $fullName . '.',
                null,
                ['full_name' => $fullName, 'active_date' => $activeDate, 'password' => $password]
            );

            $successMessage = 'Data baru berhasil ditambahkan.';
        }
    } elseif ($formAction === 'edit') {
        $id         = postInt('id');
        $fullName   = postValue('full_name');
        $activeDate = postValue('active_date');
        $password   = postValue('password', false);

        if ($id === 0 || $fullName === '' || $activeDate === '' || $password === '') {
            $errorMessage = 'Nama Lengkap, tanggal aktif, dan password wajib diisi.';
        } else {
            // Data lama diambil SEBELUM diubah, untuk dicatat di Log Aktivitas.
            $stmt = $pdo->prepare('SELECT full_name, active_date, password FROM menu1_item1 WHERE id = :id LIMIT 1');
            $stmt->execute(['id' => $id]);
            $oldRow = $stmt->fetch();

            $stmt = $pdo->prepare(
                'UPDATE menu1_item1 SET full_name = :full_name, active_date = :active_date, password = :password WHERE id = :id'
            );
            $stmt->execute([
                'full_name'   => $fullName,
                'active_date' => $activeDate,
                'password'    => $password,
                'id'          => $id,
            ]);

            if ($oldRow !== false) {
                $activityActor = currentActivityLogActor();
                logActivity(
                    $pdo,
                    $activityActor['user_id'],
                    $activityActor['username'],
                    $activityActor['full_name'],
                    'update',
                    'menu1_item1',
                    $id,
                    'Mengubah data #' . $id . ': ' . $fullName . '.',
                    ['full_name' => $oldRow['full_name'], 'active_date' => $oldRow['active_date'], 'password' => $oldRow['password']],
                    ['full_name' => $fullName, 'active_date' => $activeDate, 'password' => $password]
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
                // Data lama diambil SEBELUM baris dihapus, untuk dicatat di Log Aktivitas.
                $stmt = $pdo->prepare('SELECT full_name, active_date, password FROM menu1_item1 WHERE id = :id LIMIT 1');
                $stmt->execute(['id' => $id]);
                $oldRow = $stmt->fetch();

                $stmt = $pdo->prepare('DELETE FROM menu1_item1 WHERE id = :id');
                $stmt->execute(['id' => $id]);

                if ($oldRow !== false) {
                    $activityActor = currentActivityLogActor();
                    logActivity(
                        $pdo,
                        $activityActor['user_id'],
                        $activityActor['username'],
                        $activityActor['full_name'],
                        'delete',
                        'menu1_item1',
                        $id,
                        'Menghapus data #' . $id . ': ' . $oldRow['full_name'] . '.',
                        ['full_name' => $oldRow['full_name'], 'active_date' => $oldRow['active_date'], 'password' => $oldRow['password']],
                        null
                    );
                }

                $successMessage = 'Data berhasil dihapus.';
            }
        }
    }
}

$activeFilter          = parseDateRangeFilter();
$filterQueryString     = dateRangeFilterQueryString($activeFilter);
$rows                  = fetchMenu1Item1FilteredRows($pdo, $activeFilter);
$formActionWithFilter  = 'menu1-item1.php' . ($filterQueryString !== '' ? '?' . $filterQueryString : '');

$csrfToken = csrfToken();

$activeMenu = 'menu1-item1';
require __DIR__ . '/../../header.php';
?>

<link rel="stylesheet" href="/assets/extensions/sweetalert2/sweetalert2.min.css">
<link rel="stylesheet" href="/assets/extensions/flatpickr/flatpickr.min.css">

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

                    <!-- Filter rentang tanggal (Tgl Aktif) + Export Excel -- pola sama di
                         seluruh tabel aplikasi ini. Dikirim via GET agar tetap terlihat di
                         address bar dan terbawa ke Export Excel & form
                         Tambah/Edit/Hapus. -->
                    <div class="d-flex flex-wrap align-items-center gap-2">
                        <form method="get" class="mb-0">
                            <div class="input-group input-group-sm">
                                <span class="input-group-text"><i class="bi bi-calendar-range"></i></span>
                                <input type="text" id="filter_date_range" class="form-control js-filter-date-range"
                                    placeholder="Pilih rentang tanggal" autocomplete="off">
<?php if ($filterQueryString !== ''): ?>
                                <a href="menu1-item1.php" class="btn btn-outline-secondary" title="Hapus filter">
                                    <i class="bi bi-x-lg"></i>
                                </a>
<?php endif; ?>
                            </div>
                            <input type="hidden" name="date_from" id="filter_date_from" value="<?= htmlspecialchars($activeFilter['date_from'], ENT_QUOTES, 'UTF-8') ?>">
                            <input type="hidden" name="date_to" id="filter_date_to" value="<?= htmlspecialchars($activeFilter['date_to'], ENT_QUOTES, 'UTF-8') ?>">
                        </form>
                        <a href="menu1-item1-export.php<?= $filterQueryString !== '' ? '?' . htmlspecialchars($filterQueryString, ENT_QUOTES, 'UTF-8') : '' ?>" class="btn btn-success btn-sm">
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
                                    <th>Nama Lengkap</th>
                                    <th>Tgl Aktif</th>
                                    <th>Password</th>
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

    $activeDateDisplay = formatDateDisplay($row['active_date']);
?>
                                <tr>
                                    <td><?= $rowNumber ?></td>
                                    <td><?= htmlspecialchars($row['full_name'], ENT_QUOTES, 'UTF-8') ?></td>
                                    <td><?= htmlspecialchars($activeDateDisplay, ENT_QUOTES, 'UTF-8') ?></td>
                                    <td>&bull;&bull;&bull;&bull;&bull;&bull;</td>
                                    <td>
                                        <div class="d-flex gap-2">
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

<div class="modal fade" id="addModal" tabindex="-1" aria-labelledby="addModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
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
                        <label for="add_full_name" class="form-label">Nama Lengkap</label>
                        <input type="text" name="full_name" id="add_full_name" class="form-control" required>
                    </div>
                    <div class="form-group mb-3">
                        <label for="add_active_date" class="form-label">Tgl Aktif</label>
                        <input type="date" name="active_date" id="add_active_date" class="form-control js-active-date" required>
                    </div>
                    <div class="form-group mb-3">
                        <label for="add_password" class="form-label">Password</label>
                        <div class="password-input-wrapper">
                            <input type="password" name="password" id="add_password" class="form-control" required>
                            <button type="button" class="password-toggle-btn" data-target="add_password" aria-label="Show password">
                                <i class="bi bi-eye"></i>
                            </button>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary">Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal "Edit Data" per baris, di luar <table> agar tidak terganggu DataTables. -->
<?php foreach ($rows as $row): ?>
<div class="modal fade" id="editModal<?= (int) $row['id'] ?>" tabindex="-1" aria-labelledby="editModalLabel<?= (int) $row['id'] ?>" aria-hidden="true">
    <div class="modal-dialog" role="document">
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
                        <label for="edit_full_name_<?= (int) $row['id'] ?>" class="form-label">Nama Lengkap</label>
                        <input type="text" name="full_name" id="edit_full_name_<?= (int) $row['id'] ?>" class="form-control" required
                            value="<?= htmlspecialchars($row['full_name'], ENT_QUOTES, 'UTF-8') ?>">
                    </div>
                    <div class="form-group mb-3">
                        <label for="edit_active_date_<?= (int) $row['id'] ?>" class="form-label">Tgl Aktif</label>
                        <input type="date" name="active_date" id="edit_active_date_<?= (int) $row['id'] ?>" class="form-control js-active-date" required
                            value="<?= htmlspecialchars($row['active_date'], ENT_QUOTES, 'UTF-8') ?>">
                    </div>
                    <div class="form-group mb-3">
                        <label for="edit_password_<?= (int) $row['id'] ?>" class="form-label">Password</label>
                        <div class="password-input-wrapper">
                            <input type="password" name="password" id="edit_password_<?= (int) $row['id'] ?>" class="form-control" required
                                value="<?= htmlspecialchars($row['password'], ENT_QUOTES, 'UTF-8') ?>">
                            <button type="button" class="password-toggle-btn" data-target="edit_password_<?= (int) $row['id'] ?>" aria-label="Show password">
                                <i class="bi bi-eye"></i>
                            </button>
                        </div>
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

<link rel="stylesheet" href="/assets/extensions/datatables.net-bs5/css/dataTables.bootstrap5.min.css">
<link rel="stylesheet" href="/assets/compiled/css/table-datatable-jquery.css">
<?php require_once __DIR__ . '/../../helpers/common-page-assets.php'; ?>

<script src="/assets/extensions/jquery/jquery.min.js"></script>
<script src="/assets/extensions/datatables.net/js/jquery.dataTables.min.js"></script>
<script src="/assets/extensions/datatables.net-bs5/js/dataTables.bootstrap5.min.js"></script>
<script src="/assets/extensions/flatpickr/flatpickr.min.js"></script>
<script src="/assets/extensions/sweetalert2/sweetalert2.min.js"></script>
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
    // dateFormat "Y-m-d" dikirim ke server; altFormat "d-m-Y" yang dilihat user.
    flatpickr('.js-active-date', {
        dateFormat: 'Y-m-d',
        altInput: true,
        altFormat: 'd-m-Y'
    });
</script>
<?php initDateRangeFilter($activeFilter['date_from'], $activeFilter['date_to']); ?>
<?php confirmDeleteForms('Hapus data ini?', 'Data yang sudah dihapus tidak bisa dikembalikan.'); ?>
<?php showFormResultToast($successMessage, $errorMessage); ?>

<?php require __DIR__ . '/../../footer.php'; ?>
