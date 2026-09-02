<?php
require_once __DIR__ . '/../auth.php';

requireLogin();

$successMessage = '';
$errorMessage   = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!submittedCsrfTokenIsValid()) {
        $errorMessage = 'Invalid form session (CSRF). Please reload the page and try again.';
    } else {
        $fullName    = postValue('full_name');
        $username    = postValue('username');
        $newPassword = postValue('password', false);

        if ($username === '' || $fullName === '') {
            $errorMessage = 'Username and full name are required.';
        } else {
            $result = updateUserProfile((int) $_SESSION['user_id'], $username, $fullName, $newPassword);

            if ($result === true) {
                $successMessage = 'Profile updated successfully.';
            } else {
                $errorMessage = $result;
            }
        }
    }
}

$currentUser = getUserById((int) $_SESSION['user_id']);
$csrfToken   = csrfToken();

$activeMenu = '';
require __DIR__ . '/../header.php';
?>

<div class="page-heading">
    <div class="page-title">
        <div class="row">
            <div class="col-12 col-lg-6 order-lg-1 order-last">
                <h3>Account Profile</h3>
                <p class="text-subtitle text-muted">A page where you can change your profile information</p>
            </div>
            <div class="col-12 col-lg-6 order-lg-2 order-first">
                <nav aria-label="breadcrumb" class="breadcrumb-header float-start float-lg-end">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="/index.php">Dashboard</a></li>
                        <li class="breadcrumb-item active" aria-current="page">Profile</li>
                    </ol>
                </nav>
            </div>
        </div>
    </div>


    <section class="section">
        <div class="row">
            <div class="col-12 col-lg-4">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex justify-content-center align-items-center flex-column">
                            <div class="avatar avatar-2xl bg-primary">
                                <span class="avatar-content"><?= htmlspecialchars(userInitials($currentUser['full_name']), ENT_QUOTES, 'UTF-8') ?></span>
                            </div>

                            <h3 class="mt-3"><?= htmlspecialchars($currentUser['full_name'], ENT_QUOTES, 'UTF-8') ?></h3>
                            <p class="text-small text-muted"><?= htmlspecialchars($currentUser['role'], ENT_QUOTES, 'UTF-8') ?></p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-12 col-lg-8">
                <div class="card">
                    <div class="card-body">
                        <form action="profile.php" method="post" autocomplete="off">
                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">

                            <div class="form-group">
                                <label for="full_name" class="form-label">Full Name</label>
                                <input type="text" name="full_name" id="full_name" class="form-control"
                                    placeholder="Your Full Name"
                                    value="<?= htmlspecialchars($currentUser['full_name'], ENT_QUOTES, 'UTF-8') ?>">
                            </div>
                            <div class="form-group">
                                <label for="username" class="form-label">Username</label>
                                <input type="text" name="username" id="username" class="form-control"
                                    placeholder="Your Username"
                                    pattern="[a-z0-9]+" title="Huruf kecil dan angka saja, tanpa spasi atau karakter khusus."
                                    value="<?= htmlspecialchars($currentUser['username'], ENT_QUOTES, 'UTF-8') ?>">
                                <div class="form-text">Huruf kecil dan angka saja, tanpa spasi (contoh: kahfi, admin01).</div>
                            </div>
                            <div class="form-group">
                                <label for="password" class="form-label">New Password</label>
                                <div class="password-input-wrapper">
                                    <input type="password" name="password" id="password" class="form-control"
                                        placeholder="Leave blank to keep your current password">
                                    <button type="button" class="password-toggle-btn" data-target="password" aria-label="Show password">
                                        <i class="bi bi-eye"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="form-group">
                                <button type="submit" class="btn btn-primary">Save Changes</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>

<?php require_once __DIR__ . '/../helpers/common-page-assets.php'; ?>

<?php showFormResultToast($successMessage, $errorMessage); ?>

<?php require __DIR__ . '/../footer.php'; ?>
