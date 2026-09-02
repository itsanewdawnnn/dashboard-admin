<?php
require_once __DIR__ . '/../auth.php';
require_once __DIR__ . '/../helpers/format-helpers.php';
require_once __DIR__ . '/activity-log-filter.php';
require_once __DIR__ . '/../menu/menu3/menu3-item1-labels.php';

requireLogin();
requireRoot();

$pdo = getDbConnection();

$activeFilter      = parseDateRangeFilter();
$filterQueryString = dateRangeFilterQueryString($activeFilter);
$rows               = fetchActivityLogFilteredRows($pdo, $activeFilter);

$activeMenu = 'activity-log';
require __DIR__ . '/../header.php';
?>

<link rel="stylesheet" href="/assets/extensions/flatpickr/flatpickr.min.css">
<link rel="stylesheet" href="/assets/extensions/glightbox/glightbox.min.css">

<div class="page-heading">
    <div class="page-title">
        <div class="row">
            <div class="col-12 col-lg-6 order-lg-1 order-last">
                <h3>Log Aktivitas</h3>
                <p class="text-subtitle text-muted">Catatan otomatis setiap login, logout, dan perubahan data di aplikasi ini.</p>
            </div>
            <div class="col-12 col-lg-6 order-lg-2 order-first">
                <nav aria-label="breadcrumb" class="breadcrumb-header float-start float-lg-end">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="/index.php">Dashboard</a></li>
                        <li class="breadcrumb-item active" aria-current="page">Log Aktivitas</li>
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
                <div class="card-header d-flex flex-wrap justify-content-end align-items-center gap-3">
                    <!-- Tidak ada tombol "Tambah Data" -- Log Aktivitas dicatat otomatis. -->
                    <div class="d-flex flex-wrap align-items-center gap-2">
                        <form method="get" class="mb-0">
                            <div class="input-group input-group-sm">
                                <span class="input-group-text"><i class="bi bi-calendar-range"></i></span>
                                <input type="text" id="filter_date_range" class="form-control js-filter-date-range"
                                    placeholder="Pilih rentang tanggal" autocomplete="off">
<?php if ($filterQueryString !== ''): ?>
                                <a href="activity-log.php" class="btn btn-outline-secondary" title="Hapus filter">
                                    <i class="bi bi-x-lg"></i>
                                </a>
<?php endif; ?>
                            </div>
                            <input type="hidden" name="date_from" id="filter_date_from" value="<?= htmlspecialchars($activeFilter['date_from'], ENT_QUOTES, 'UTF-8') ?>">
                            <input type="hidden" name="date_to" id="filter_date_to" value="<?= htmlspecialchars($activeFilter['date_to'], ENT_QUOTES, 'UTF-8') ?>">
                        </form>
                        <a href="activity-log-export.php<?= $filterQueryString !== '' ? '?' . htmlspecialchars($filterQueryString, ENT_QUOTES, 'UTF-8') : '' ?>" class="btn btn-success btn-sm">
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
                                    <th>Waktu</th>
                                    <th>User</th>
                                    <th>Aksi</th>
                                    <th>Modul</th>
                                    <th>Deskripsi</th>
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

    $createdAtDisplay = formatDateTimeIndonesian($row['created_at']);

    $hasDetail = false;
    if ($row['old_values'] !== null || $row['new_values'] !== null) {
        $hasDetail = true;
    }
?>
                                <tr>
                                    <td><?= $rowNumber ?></td>
                                    <td><?= htmlspecialchars($createdAtDisplay, ENT_QUOTES, 'UTF-8') ?></td>
                                    <td><?= htmlspecialchars($row['full_name'], ENT_QUOTES, 'UTF-8') ?> (<?= htmlspecialchars($row['username'], ENT_QUOTES, 'UTF-8') ?>)</td>
                                    <td><span class="badge bg-<?= activityLogActionTypeBadgeColor($row['action_type']) ?>"><?= htmlspecialchars(activityLogActionTypeLabel($row['action_type']), ENT_QUOTES, 'UTF-8') ?></span></td>
                                    <td><?= htmlspecialchars(activityLogModuleLabel($row['module']), ENT_QUOTES, 'UTF-8') ?></td>
                                    <td><?= htmlspecialchars($row['description'], ENT_QUOTES, 'UTF-8') ?></td>
                                    <td class="text-center">
<?php if ($hasDetail): ?>
                                        <button type="button" class="btn icon btn-secondary" data-bs-toggle="modal" data-bs-target="#detailModal<?= (int) $row['id'] ?>" title="Lihat Detail">
                                            <i class="bi bi-eye"></i>
                                        </button>
<?php else: ?>
                                        <!-- Login/logout tidak punya old_values/new_values untuk ditampilkan. -->
                                        -
<?php endif; ?>
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

<!-- Modal "Lihat Detail" per baris, di luar <table> agar tidak terganggu DataTables. -->
<?php foreach ($rows as $row): ?>
<?php
    if ($row['old_values'] === null && $row['new_values'] === null) {
        continue;
    }

    $oldValuesDecoded = null;
    if ($row['old_values'] !== null) {
        $oldValuesDecoded = json_decode($row['old_values'], true);
    }

    $newValuesDecoded = null;
    if ($row['new_values'] !== null) {
        $newValuesDecoded = json_decode($row['new_values'], true);
    }
?>
<div class="modal fade" id="detailModal<?= (int) $row['id'] ?>" tabindex="-1" aria-labelledby="detailModalLabel<?= (int) $row['id'] ?>" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="detailModalLabel<?= (int) $row['id'] ?>">Detail Log Aktivitas</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p class="mb-3"><?= htmlspecialchars($row['description'], ENT_QUOTES, 'UTF-8') ?></p>
                <div class="row">
                    <div class="col-12 col-md-6 mb-3 mb-md-0">
                        <h6>Sebelum</h6>
<?php if ($oldValuesDecoded === null): ?>
                        <p class="text-muted text-sm">(tidak ada data sebelum -- data baru)</p>
<?php else: ?>
                        <table class="table table-sm table-bordered">
<?php foreach ($oldValuesDecoded as $columnKey => $columnValue): ?>
                            <tr>
                                <th class="text-nowrap"><?= htmlspecialchars(activityLogColumnLabel((string) $columnKey), ENT_QUOTES, 'UTF-8') ?></th>
                                <td><?= activityLogCellValueHtml($row['module'], (string) $columnKey, $columnValue, $unitLabels, 'log' . (int) $row['id'] . '-sebelum') ?></td>
                            </tr>
<?php endforeach; ?>
                        </table>
<?php endif; ?>
                    </div>
                    <div class="col-12 col-md-6">
                        <h6>Sesudah</h6>
<?php if ($newValuesDecoded === null): ?>
                        <p class="text-muted text-sm">(tidak ada data sesudah -- data dihapus)</p>
<?php else: ?>
                        <table class="table table-sm table-bordered">
<?php foreach ($newValuesDecoded as $columnKey => $columnValue): ?>
                            <tr>
                                <th class="text-nowrap"><?= htmlspecialchars(activityLogColumnLabel((string) $columnKey), ENT_QUOTES, 'UTF-8') ?></th>
                                <td><?= activityLogCellValueHtml($row['module'], (string) $columnKey, $columnValue, $unitLabels, 'log' . (int) $row['id'] . '-sesudah') ?></td>
                            </tr>
<?php endforeach; ?>
                        </table>
<?php endif; ?>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
            </div>
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
<script src="/assets/extensions/flatpickr/flatpickr.min.js"></script>
<script src="/assets/extensions/glightbox/glightbox.min.js"></script>
<script>
    $(function () {
        $('#table1').DataTable({
            responsive: true,
            pageLength: 10,
            lengthMenu: [10, 25, 50, 100],
            columnDefs: [
                { orderable: false, targets: [0, 6] }
            ],
            order: [],
            // Jangan ganti ini dengan <tr> kosong manual di PHP -- error "_DT_CellIndex".
            language: {
                emptyTable: 'No data yet.'
            }
        });

        beautifyDataTableSearchBox();
    });
</script>
<script>
    // data-gallery dibuat unik per baris & sisi Sebelum/Sesudah (lihat
    // activityLogCellValueHtml()), jadi navigasi next/prev tidak tercampur.
    var activityLogLightbox = GLightbox({
        selector: '.glightbox'
    });
</script>
<?php initDateRangeFilter($activeFilter['date_from'], $activeFilter['date_to']); ?>

<?php require __DIR__ . '/../footer.php'; ?>
