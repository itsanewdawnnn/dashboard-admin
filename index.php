<?php
require_once __DIR__ . '/includes/auth.php';

requireLogin();

$activeMenu = 'dashboard';
require __DIR__ . '/includes/header.php';
?>

<div class="page-heading">
    <h3>Dashboard</h3>
    <p class="text-subtitle text-muted">Ringkasan kondisi operasional hari ini.</p>
</div>
<div class="page-content">
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
