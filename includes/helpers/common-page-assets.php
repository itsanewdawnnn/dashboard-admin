<?php
// Aset (CSS/JS) yang dipakai bersama halaman tabel (Menu 1/2/3, Manajemen User,
// Log Aktivitas) & profile.php -- di-require SEKALI di titik yang sama seperti
// dulu blok <style>/<script> manual berada. Bagian statis langsung tercetak;
// bagian yang butuh nilai per-halaman (konfirmasi hapus, filter tanggal) lewat
// fungsi print di bawah, dipanggil di titik yang sama seperti skrip lama.
?>
<style>
    /* Password show/hide toggle -- dipakai di profile.php, add-user.php, menu1-item1.php. */
    .password-input-wrapper {
        position: relative;
    }
    .password-input-wrapper .form-control {
        padding-right: 2.75rem;
    }
    .password-toggle-btn {
        position: absolute;
        top: 50%;
        right: .75rem;
        transform: translateY(-50%);
        display: flex;
        align-items: center;
        justify-content: center;
        width: 2rem;
        height: 2rem;
        background: none;
        border: none;
        border-radius: 50%;
        padding: 0;
        line-height: 1;
        color: #a6a8aa;
        cursor: pointer;
        transition: background-color .15s ease, color .15s ease;
    }
    .password-toggle-btn:hover,
    .password-toggle-btn:focus-visible {
        color: #5a8dee;
        background-color: rgba(90, 141, 238, .1);
    }
    .password-toggle-btn i {
        font-size: 1.1rem;
    }
</style>
<style>
    /* Show entries/Search bawaan DataTables dipaksa flex row agar tetap sebaris. */
    #table1_wrapper .row:first-child {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        row-gap: .5rem;
    }
    #table1_wrapper .row:first-child > div {
        flex: 0 0 auto;
        width: auto;
        max-width: 100%;
    }
    #table1_wrapper .row:first-child > div:last-child {
        margin-left: auto;
    }
</style>
<script>
    // Search box bawaan DataTables diganti tampilannya jadi input group + ikon.
    // Dipanggil sesudah $('#table1').DataTable({...}) di tiap halaman.
    function beautifyDataTableSearchBox() {
        var $searchInput = $('#table1_filter input').detach();
        $searchInput.attr('type', 'text');
        $searchInput.attr('placeholder', 'Cari data...');
        $searchInput.attr('title', 'Search');
        $('#table1_filter').empty().append(
            $('<div class="input-group input-group-sm"></div>')
                .append('<span class="input-group-text"><i class="bi bi-search"></i></span>')
                .append($searchInput)
        );
    }
</script>
<script>
    // Satu listener menangani semua toggle show/hide password (data-target).
    document.addEventListener('click', function (event) {
        const toggleButton = event.target.closest('.password-toggle-btn');
        if (!toggleButton) {
            return;
        }

        const input = document.getElementById(toggleButton.dataset.target);
        const icon = toggleButton.querySelector('i');
        const isHidden = input.getAttribute('type') === 'password';

        if (isHidden) {
            input.setAttribute('type', 'text');
            icon.classList.remove('bi-eye');
            icon.classList.add('bi-eye-slash');
        } else {
            input.setAttribute('type', 'password');
            icon.classList.remove('bi-eye-slash');
            icon.classList.add('bi-eye');
        }
    });
</script>
<?php
// Konfirmasi SweetAlert sebelum form ".js-delete-form" benar-benar disubmit.
// $confirmTitle/$confirmText beda-beda kalimatnya per halaman (mis. "Hapus user ini?").
function confirmDeleteForms(string $confirmTitle, string $confirmText): void
{
    ?>
<script>
    document.querySelectorAll('.js-delete-form').forEach(function (form) {
        form.addEventListener('submit', function (event) {
            event.preventDefault();

            Swal.fire({
                title: <?= json_encode($confirmTitle) ?>,
                text: <?= json_encode($confirmText) ?>,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Ya, hapus',
                cancelButtonText: 'Batal'
            }).then(function (result) {
                if (result.isConfirmed) {
                    form.submit();
                }
            });
        });
    });
</script>
    <?php
}

// Notifikasi hasil aksi (Tambah/Edit/Hapus/dll) -- pakai Toastify, PERSIS seperti
// mekanisme toast sapaan login (footer.php) supaya seragam. CSS/JS toastify dimuat
// di sini juga (bukan lewat footer.php) karena footer.php tidak tahu soal
// $successMessage/$errorMessage per-halaman.
function showFormResultToast(string $successMessage, string $errorMessage): void
{
    $toastText  = '';
    $toastColor = '#4fbe87';

    if ($successMessage !== '') {
        $toastText  = $successMessage;
        $toastColor = '#4fbe87';
    } elseif ($errorMessage !== '') {
        $toastText  = $errorMessage;
        $toastColor = '#dc3545';
    } else {
        return;
    }
    ?>
<link rel="stylesheet" href="/assets/extensions/toastify-js/src/toastify.css">
<script src="/assets/extensions/toastify-js/src/toastify.js"></script>
<script>
    Toastify({
        text: <?= json_encode($toastText) ?>,
        duration: 5000,
        close: true,
        gravity: 'top',
        position: 'right',
        backgroundColor: <?= json_encode($toastColor) ?>,
    }).showToast();
</script>
    <?php
}

// Flatpickr mode "range" untuk filter tanggal (.js-filter-date-range), disinkronkan
// ke 2 input hidden (id="filter_date_from"/"filter_date_to") lalu auto-submit form.
// $dateFromValue/$dateToValue: nilai filter aktif saat ini (format "Y-m-d" atau '').
function initDateRangeFilter(string $dateFromValue, string $dateToValue): void
{
    ?>
<script>
    var filterDateFrom = <?= json_encode($dateFromValue) ?>;
    var filterDateTo   = <?= json_encode($dateToValue) ?>;
    var filterDefaultDate = [];
    if (filterDateFrom !== '' && filterDateTo !== '') {
        filterDefaultDate = [filterDateFrom, filterDateTo];
    } else if (filterDateFrom !== '') {
        filterDefaultDate = [filterDateFrom];
    }

    flatpickr('.js-filter-date-range', {
        mode: 'range',
        dateFormat: 'Y-m-d',
        altInput: true,
        altInputClass: 'form-control',
        altFormat: 'd-m-Y',
        locale: { rangeSeparator: ' s/d ' },
        defaultDate: filterDefaultDate,
        onChange: function (selectedDates, dateStr, instance) {
            if (selectedDates.length === 2) {
                document.getElementById('filter_date_from').value = instance.formatDate(selectedDates[0], 'Y-m-d');
                document.getElementById('filter_date_to').value = instance.formatDate(selectedDates[1], 'Y-m-d');
                document.getElementById('filter_date_from').closest('form').submit();
            } else if (selectedDates.length === 0) {
                document.getElementById('filter_date_from').value = '';
                document.getElementById('filter_date_to').value = '';
            }
        }
    });
</script>
    <?php
}
