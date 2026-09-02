<?php
require_once __DIR__ . '/../auth.php';
require_once __DIR__ . '/add-user-data.php';

requireLogin();
requireRoot();

$pdo = getDbConnection();

$successMessage = '';
$errorMessage   = '';

// Semua aksi (tambah/edit/hapus) dikirim POST ke halaman ini, dibedakan lewat "form_action".
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $formAction = postValue('form_action', false);

    if (!submittedCsrfTokenIsValid()) {
        $errorMessage = 'Invalid form session (CSRF). Please reload the page and try again.';
    } elseif ($formAction === 'add') {
        $role     = postValue('role');
        $username = postValue('username');
        $password = postValue('password', false);
        $fullName = postValue('full_name');

        if ($role === '' || $username === '' || $password === '' || $fullName === '') {
            $errorMessage = 'Semua field wajib diisi.';
        } else {
            $result = addUser($role, $username, $password, $fullName);

            if ($result === true) {
                $successMessage = 'User baru berhasil ditambahkan.';
            } else {
                $errorMessage = $result;
            }
        }
    } elseif ($formAction === 'edit') {
        $id          = postInt('id');
        $role        = postValue('role');
        $username    = postValue('username');
        $fullName    = postValue('full_name');
        $newPassword = postValue('password', false);

        if ($id === 0 || $role === '' || $username === '' || $fullName === '') {
            $errorMessage = 'Role, Username, dan Nama Lengkap wajib diisi.';
        } else {
            $result = updateUserByRoot($id, $role, $username, $fullName, $newPassword);

            if ($result === true) {
                $successMessage = 'Data user berhasil diperbarui.';
            } else {
                $errorMessage = $result;
            }
        }
    } elseif ($formAction === 'delete') {
        $id = postInt('id');

        if ($id === 0) {
            $errorMessage = 'Data tidak ditemukan.';
        } else {
            $result = deleteUserByRoot($id);

            if ($result === true) {
                $successMessage = 'User berhasil dihapus.';
            } else {
                $errorMessage = $result;
            }
        }
    }
}

$rows = fetchAllUsersForManage($pdo);

// Akun sendiri: tombol Hapus disembunyikan & Role dikunci (lihat auth.php).
$currentUserId = (int) $_SESSION['user_id'];

$csrfToken = csrfToken();

$activeMenu = '';
require __DIR__ . '/../header.php';
?>

<link rel="stylesheet" href="/assets/extensions/sweetalert2/sweetalert2.min.css">

<div class="page-heading">
    <div class="page-title">
        <div class="row">
            <div class="col-12 col-lg-6 order-lg-1 order-last">
                <h3>Manage User</h3>
                <p class="text-subtitle text-muted">Kelola akun pengguna yang bisa login ke aplikasi ini.</p>
            </div>
            <div class="col-12 col-lg-6 order-lg-2 order-first">
                <nav aria-label="breadcrumb" class="breadcrumb-header float-start float-lg-end">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="/index.php">Dashboard</a></li>
                        <li class="breadcrumb-item active" aria-current="page">Manage User</li>
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

                    <!-- Sengaja tanpa filter tanggal -- jumlah user biasanya sedikit. -->
                    <a href="add-user-export.php" class="btn btn-success btn-sm">
                        <i class="bi bi-file-earmark-excel me-1"></i>Export Excel
                    </a>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table" id="table1">
                            <thead>
                                <tr>
                                    <th>No</th>
                                    <th>Role</th>
                                    <th>Nama Lengkap</th>
                                    <th>Username</th>
                                    <th>Password</th>
                                    <th>Login Terakhir</th>
                                    <th>Dibuat</th>
                                    <th>Diperbarui</th>
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

    $isOwnAccountRow = ((int) $row['id'] === $currentUserId);
?>
                                <tr>
                                    <td><?= $rowNumber ?></td>
                                    <td><?= htmlspecialchars(ucfirst($row['role']), ENT_QUOTES, 'UTF-8') ?></td>
                                    <td><?= htmlspecialchars($row['full_name'], ENT_QUOTES, 'UTF-8') ?></td>
                                    <td><?= htmlspecialchars($row['username'], ENT_QUOTES, 'UTF-8') ?></td>
                                    <td>&bull;&bull;&bull;&bull;&bull;&bull;</td>
                                    <td><?= htmlspecialchars(formatDateTimeIndonesian($row['last_login_at']), ENT_QUOTES, 'UTF-8') ?></td>
                                    <td><?= htmlspecialchars(formatDateTimeIndonesian($row['created_at']), ENT_QUOTES, 'UTF-8') ?></td>
                                    <td><?= htmlspecialchars(formatDateTimeIndonesian($row['updated_at']), ENT_QUOTES, 'UTF-8') ?></td>
                                    <td>
                                        <div class="d-flex gap-2">
                                            <button type="button" class="btn icon btn-primary" data-bs-toggle="modal" data-bs-target="#editModal<?= (int) $row['id'] ?>" title="Edit">
                                                <i class="bi bi-pencil"></i>
                                            </button>
<?php if (!$isOwnAccountRow): ?>
                                            <form method="post" class="d-inline js-delete-form" action="add-user.php">
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
            <form method="post" autocomplete="off" action="add-user.php">
                <div class="modal-header">
                    <h5 class="modal-title" id="addModalLabel">Tambah Data</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                    <input type="hidden" name="form_action" value="add">
                    <div class="form-group mb-3">
                        <label for="add_role" class="form-label">Role</label>
                        <select name="role" id="add_role" class="form-select">
                            <option value="member" selected>Member</option>
                            <option value="admin">Admin</option>
                            <option value="root">Root</option>
                        </select>
                    </div>
                    <div class="form-group mb-3">
                        <label for="add_username" class="form-label">Username</label>
                        <input type="text" name="username" id="add_username" class="form-control" required
                            pattern="[a-z0-9]+" title="Huruf kecil dan angka saja, tanpa spasi atau karakter khusus.">
                        <div class="form-text">Huruf kecil dan angka saja, tanpa spasi (contoh: kahfi, admin01).</div>
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
                    <div class="form-group mb-3">
                        <label for="add_full_name" class="form-label">Nama Lengkap</label>
                        <input type="text" name="full_name" id="add_full_name" class="form-control" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary">Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Satu modal per baris, di luar <table> agar tidak terganggu DataTables. -->
<?php foreach ($rows as $row): ?>
<?php
    $isOwnAccountRow = ((int) $row['id'] === $currentUserId);
?>
<div class="modal fade" id="editModal<?= (int) $row['id'] ?>" tabindex="-1" aria-labelledby="editModalLabel<?= (int) $row['id'] ?>" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <form method="post" autocomplete="off" action="add-user.php">
                <div class="modal-header">
                    <h5 class="modal-title" id="editModalLabel<?= (int) $row['id'] ?>">Edit Data</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                    <input type="hidden" name="form_action" value="edit">
                    <input type="hidden" name="id" value="<?= (int) $row['id'] ?>">
                    <div class="form-group mb-3">
                        <label for="edit_role_<?= (int) $row['id'] ?>" class="form-label">Role</label>
<?php if ($isOwnAccountRow): ?>
                        <!-- Dikunci; nilai tetap dikirim via input hidden karena <select disabled> tidak ter-submit. -->
                        <select id="edit_role_<?= (int) $row['id'] ?>" class="form-select" disabled>
                            <option value="member" <?= $row['role'] === 'member' ? 'selected' : '' ?>>Member</option>
                            <option value="admin" <?= $row['role'] === 'admin' ? 'selected' : '' ?>>Admin</option>
                            <option value="root" <?= $row['role'] === 'root' ? 'selected' : '' ?>>Root</option>
                        </select>
                        <input type="hidden" name="role" value="<?= htmlspecialchars($row['role'], ENT_QUOTES, 'UTF-8') ?>">
                        <div class="form-text">Anda tidak bisa mengubah role akun Anda sendiri.</div>
<?php else: ?>
                        <select name="role" id="edit_role_<?= (int) $row['id'] ?>" class="form-select">
                            <option value="member" <?= $row['role'] === 'member' ? 'selected' : '' ?>>Member</option>
                            <option value="admin" <?= $row['role'] === 'admin' ? 'selected' : '' ?>>Admin</option>
                            <option value="root" <?= $row['role'] === 'root' ? 'selected' : '' ?>>Root</option>
                        </select>
<?php endif; ?>
                    </div>
                    <div class="form-group mb-3">
                        <label for="edit_username_<?= (int) $row['id'] ?>" class="form-label">Username</label>
                        <input type="text" name="username" id="edit_username_<?= (int) $row['id'] ?>" class="form-control" required
                            pattern="[a-z0-9]+" title="Huruf kecil dan angka saja, tanpa spasi atau karakter khusus."
                            value="<?= htmlspecialchars($row['username'], ENT_QUOTES, 'UTF-8') ?>">
                        <div class="form-text">Huruf kecil dan angka saja, tanpa spasi (contoh: kahfi, admin01).</div>
                    </div>
                    <div class="form-group mb-3">
                        <label for="edit_password_<?= (int) $row['id'] ?>" class="form-label">Password</label>
                        <div class="password-input-wrapper">
                            <input type="password" name="password" id="edit_password_<?= (int) $row['id'] ?>" class="form-control"
                                placeholder="Kosongkan jika tidak ingin mengubah password">
                            <button type="button" class="password-toggle-btn" data-target="edit_password_<?= (int) $row['id'] ?>" aria-label="Show password">
                                <i class="bi bi-eye"></i>
                            </button>
                        </div>
                    </div>
                    <div class="form-group mb-3">
                        <label for="edit_full_name_<?= (int) $row['id'] ?>" class="form-label">Nama Lengkap</label>
                        <input type="text" name="full_name" id="edit_full_name_<?= (int) $row['id'] ?>" class="form-control" required
                            value="<?= htmlspecialchars($row['full_name'], ENT_QUOTES, 'UTF-8') ?>">
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
<?php require_once __DIR__ . '/../helpers/common-page-assets.php'; ?>

<script src="/assets/extensions/jquery/jquery.min.js"></script>
<script src="/assets/extensions/datatables.net/js/jquery.dataTables.min.js"></script>
<script src="/assets/extensions/datatables.net-bs5/js/dataTables.bootstrap5.min.js"></script>
<script src="/assets/extensions/sweetalert2/sweetalert2.min.js"></script>
<script>
    $(function () {
        $('#table1').DataTable({
            responsive: true,
            pageLength: 10,
            lengthMenu: [10, 25, 50, 100],
            // Kolom "No" dan "Opsi" tidak perlu bisa diurutkan -- isinya bukan data.
            columnDefs: [
                { orderable: false, targets: [0, 8] }
            ],
            // Jangan ganti ini dengan <tr> kosong manual di PHP -- error "_DT_CellIndex".
            language: {
                emptyTable: 'No data yet.'
            }
        });

        beautifyDataTableSearchBox();
    });
</script>
<?php confirmDeleteForms('Hapus user ini?', 'User yang sudah dihapus tidak bisa dikembalikan.'); ?>
<?php showFormResultToast($successMessage, $errorMessage); ?>

<?php require __DIR__ . '/../footer.php'; ?>
