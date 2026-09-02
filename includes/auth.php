<?php
// Helper autentikasi (login/logout) dan proteksi CSRF.

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/helpers/greeting-helper.php';
require_once __DIR__ . '/helpers/activity-log-helpers.php';
require_once __DIR__ . '/helpers/request-helpers.php';

function ensureSessionStarted(): void
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
}

function csrfToken(): string
{
    ensureSessionStarted();

    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

function verifyCsrfToken(?string $token): bool
{
    ensureSessionStarted();

    if (empty($_SESSION['csrf_token']) || empty($token)) {
        return false;
    }

    return hash_equals($_SESSION['csrf_token'], $token);
}

// Ambil token CSRF dari $_POST lalu langsung validasi -- dipakai di awal tiap form handler POST.
function submittedCsrfTokenIsValid(): bool
{
    return verifyCsrfToken(postValue('csrf_token', false));
}

function isLoggedIn(): bool
{
    ensureSessionStarted();
    return !empty($_SESSION['user_id']);
}

function isRoot(): bool
{
    ensureSessionStarted();
    return ($_SESSION['role'] ?? '') === 'root';
}

// Diperiksa di backend, bukan cuma disembunyikan tombolnya di UI.
function canDeleteRecords(): bool
{
    ensureSessionStarted();
    return ($_SESSION['role'] ?? '') !== 'member';
}

function requireLogin(): void
{
    if (!isLoggedIn()) {
        header('Location: /login.php');
        exit();
    }
}

// Menolak akses langsung lewat URL, bukan cuma menyembunyikan menu di sidebar.
function requireRoot(): void
{
    if (!isRoot()) {
        header('Location: /index.php');
        exit();
    }
}

// Mitigasi brute force sederhana berbasis session.
const MAX_LOGIN_ATTEMPTS = 5;
const LOGIN_LOCKOUT_SECONDS = 300; // 5 menit

function isLoginLocked(): bool
{
    ensureSessionStarted();

    if (empty($_SESSION['login_attempts']) || empty($_SESSION['login_locked_until'])) {
        return false;
    }

    if ($_SESSION['login_attempts'] >= MAX_LOGIN_ATTEMPTS && time() < $_SESSION['login_locked_until']) {
        return true;
    }

    return false;
}

function registerFailedLogin(): void
{
    ensureSessionStarted();

    if (!isset($_SESSION['login_attempts'])) {
        $_SESSION['login_attempts'] = 0;
    }

    $_SESSION['login_attempts'] = $_SESSION['login_attempts'] + 1;

    if ($_SESSION['login_attempts'] >= MAX_LOGIN_ATTEMPTS) {
        $_SESSION['login_locked_until'] = time() + LOGIN_LOCKOUT_SECONDS;
    }
}

function resetLoginAttempts(): void
{
    ensureSessionStarted();
    unset($_SESSION['login_attempts'], $_SESSION['login_locked_until']);
}

// Mengembalikan array user jika berhasil, atau null jika gagal.
function attemptLogin(string $username, string $password): ?array
{
    $pdo = getDbConnection();

    $stmt = $pdo->prepare(
        'SELECT id, username, password, full_name, role
         FROM users
         WHERE username = :username
         LIMIT 1'
    );
    $stmt->execute(['username' => $username]);
    $user = $stmt->fetch();

    if ($user === false) {
        return null;
    }

    if ($password !== $user['password']) {
        return null;
    }

    return $user;
}

function loginUser(array $user, bool $rememberMe = false): void
{
    ensureSessionStarted();

    // Regenerasi session ID untuk mencegah session fixation.
    session_regenerate_id(true);

    $_SESSION['user_id']    = $user['id'];
    $_SESSION['username']   = $user['username'];
    $_SESSION['full_name']  = $user['full_name'];
    $_SESSION['role']       = $user['role'];
    $_SESSION['login_time'] = time();

    // Ditampilkan sekali oleh footer.php pada halaman pertama setelah login,
    // lalu dihapus dari session (lihat footer.php).
    $_SESSION['login_toast_message'] = buildLoginGreetingText($user['full_name']);

    resetLoginAttempts();

    $pdo = getDbConnection();
    $stmt = $pdo->prepare('UPDATE users SET last_login_at = NOW() WHERE id = :id');
    $stmt->execute(['id' => $user['id']]);

    logActivity(
        $pdo,
        (int) $user['id'],
        $user['username'],
        $user['full_name'],
        'login',
        'auth',
        null,
        'Login berhasil.'
    );

    if ($rememberMe) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(),
            session_id(),
            [
                'expires'  => time() + (30 * 24 * 60 * 60),
                'path'     => $params['path'],
                'domain'   => $params['domain'],
                'secure'   => $params['secure'],
                'httponly' => true,
                'samesite' => 'Lax',
            ]
        );
    }
}

function logoutUser(): void
{
    ensureSessionStarted();

    // Data pelaku diambil SEBELUM session dikosongkan di bawah.
    $actor = currentActivityLogActor();
    if ($actor['user_id'] !== null) {
        $pdo = getDbConnection();
        logActivity(
            $pdo,
            $actor['user_id'],
            $actor['username'],
            $actor['full_name'],
            'logout',
            'auth',
            null,
            'Logout.'
        );
    }

    $_SESSION = [];

    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            time() - 42000,
            $params['path'],
            $params['domain'],
            $params['secure'],
            $params['httponly']
        );
    }

    session_destroy();
}

// Inisial dua huruf untuk badge avatar sidebar (contoh: "Muhammad Kahfi" -> "MK").
function userInitials(string $fullName): string
{
    $words = preg_split('/\s+/', trim($fullName), -1, PREG_SPLIT_NO_EMPTY);
    if (count($words) === 0) {
        return '?';
    }
    if (count($words) === 1) {
        return mb_strtoupper(mb_substr($words[0], 0, 2));
    }
    $first = mb_substr($words[0], 0, 1);
    $last  = mb_substr($words[count($words) - 1], 0, 1);
    return mb_strtoupper($first . $last);
}

function getUserById(int $id): ?array
{
    $pdo = getDbConnection();

    $stmt = $pdo->prepare('SELECT id, username, full_name, role FROM users WHERE id = :id LIMIT 1');
    $stmt->execute(['id' => $id]);
    $user = $stmt->fetch();

    if ($user === false) {
        return null;
    }

    return $user;
}

// Password hanya diupdate jika $newPassword diisi (tidak kosong).
function updateUserProfile(int $id, string $username, string $fullName, string $newPassword = ''): string|true
{
    if (!isValidUsernameFormat($username)) {
        return USERNAME_FORMAT_ERROR;
    }

    $pdo = getDbConnection();

    $stmt = $pdo->prepare('SELECT id FROM users WHERE username = :username AND id != :id LIMIT 1');
    $stmt->execute(['username' => $username, 'id' => $id]);
    if ($stmt->fetch() !== false) {
        return 'Username is already taken by another account.';
    }

    // Data lama diambil SEBELUM diubah, untuk dicatat di Log Aktivitas.
    $stmt = $pdo->prepare('SELECT username, full_name FROM users WHERE id = :id LIMIT 1');
    $stmt->execute(['id' => $id]);
    $oldUserRow = $stmt->fetch();

    $oldUsername = '';
    $oldFullName = '';
    if ($oldUserRow !== false) {
        $oldUsername = $oldUserRow['username'];
        $oldFullName = $oldUserRow['full_name'];
    }

    $passwordChanged = $newPassword !== '';

    if ($passwordChanged) {
        $stmt = $pdo->prepare(
            'UPDATE users SET username = :username, full_name = :full_name, password = :password WHERE id = :id'
        );
        $stmt->execute([
            'username'  => $username,
            'full_name' => $fullName,
            'password'  => $newPassword,
            'id'        => $id,
        ]);
    } else {
        $stmt = $pdo->prepare(
            'UPDATE users SET username = :username, full_name = :full_name WHERE id = :id'
        );
        $stmt->execute([
            'username'  => $username,
            'full_name' => $fullName,
            'id'        => $id,
        ]);
    }

    $activityDescription = 'Mengubah profil sendiri.';
    if ($passwordChanged) {
        $activityDescription = 'Mengubah profil sendiri (termasuk password).';
    }

    logActivity(
        $pdo,
        $id,
        $oldUsername,
        $oldFullName,
        'update',
        'users',
        $id,
        $activityDescription,
        ['username' => $oldUsername, 'full_name' => $oldFullName],
        ['username' => $username, 'full_name' => $fullName]
    );

    // Sinkronkan session dengan data terbaru.
    ensureSessionStarted();
    $_SESSION['username']  = $username;
    $_SESSION['full_name'] = $fullName;

    return true;
}

// Format username: huruf kecil (a-z) dan angka (0-9) saja, tanpa spasi/simbol.
function isValidUsernameFormat(string $username): bool
{
    return preg_match('/^[a-z0-9]+$/', $username) === 1;
}

const USERNAME_FORMAT_ERROR = 'Username hanya boleh huruf kecil (a-z) dan angka (0-9), tanpa spasi atau karakter khusus.';

// Dicek ketat di addUser() agar role lain tidak "disusupkan" lewat POST.
const ALLOWED_USER_ROLES = ['root', 'admin', 'member'];

function addUser(string $role, string $username, string $password, string $fullName): string|true
{
    if (!in_array($role, ALLOWED_USER_ROLES, true)) {
        return 'Role tidak valid.';
    }

    if (!isValidUsernameFormat($username)) {
        return USERNAME_FORMAT_ERROR;
    }

    $pdo = getDbConnection();

    $stmt = $pdo->prepare('SELECT id FROM users WHERE username = :username LIMIT 1');
    $stmt->execute(['username' => $username]);
    if ($stmt->fetch() !== false) {
        return 'Username sudah dipakai, silakan pilih username lain.';
    }

    $stmt = $pdo->prepare(
        'INSERT INTO users (role, username, password, full_name) VALUES (:role, :username, :password, :full_name)'
    );
    $stmt->execute([
        'role'      => $role,
        'username'  => $username,
        'password'  => $password,
        'full_name' => $fullName,
    ]);
    $newUserId = (int) $pdo->lastInsertId();

    $actor = currentActivityLogActor();
    logActivity(
        $pdo,
        $actor['user_id'],
        $actor['username'],
        $actor['full_name'],
        'create',
        'users',
        $newUserId,
        'Menambahkan user baru: ' . $username . ' (' . $role . ').',
        null,
        ['role' => $role, 'username' => $username, 'full_name' => $fullName]
    );

    return true;
}

function isAllowedUserRole(string $role): bool
{
    $roleIsValid = false;
    foreach (ALLOWED_USER_ROLES as $allowedRole) {
        if ($role === $allowedRole) {
            $roleIsValid = true;
        }
    }

    return $roleIsValid;
}

// Root tidak bisa mengubah role akunnya sendiri lewat fungsi ini (mencegah
// kehilangan akses ke halaman Manage User secara tidak sengaja).
function updateUserByRoot(int $id, string $role, string $username, string $fullName, string $newPassword = ''): string|true
{
    if (!isAllowedUserRole($role)) {
        return 'Role tidak valid.';
    }

    if (!isValidUsernameFormat($username)) {
        return USERNAME_FORMAT_ERROR;
    }

    ensureSessionStarted();
    $currentUserId = (int) $_SESSION['user_id'];

    if ($id === $currentUserId) {
        $role = $_SESSION['role'];
    }

    $pdo = getDbConnection();

    $stmt = $pdo->prepare('SELECT id FROM users WHERE username = :username AND id != :id LIMIT 1');
    $stmt->execute(['username' => $username, 'id' => $id]);
    if ($stmt->fetch() !== false) {
        return 'Username sudah dipakai user lain.';
    }

    // Data lama diambil SEBELUM diubah, untuk dicatat di Log Aktivitas.
    $stmt = $pdo->prepare('SELECT role, username, full_name FROM users WHERE id = :id LIMIT 1');
    $stmt->execute(['id' => $id]);
    $oldUserRow = $stmt->fetch();

    $oldRole     = '';
    $oldUsername = '';
    $oldFullName = '';
    if ($oldUserRow !== false) {
        $oldRole     = $oldUserRow['role'];
        $oldUsername = $oldUserRow['username'];
        $oldFullName = $oldUserRow['full_name'];
    }

    $passwordChanged = $newPassword !== '';

    if ($passwordChanged) {
        $stmt = $pdo->prepare(
            'UPDATE users SET role = :role, username = :username, full_name = :full_name, password = :password WHERE id = :id'
        );
        $stmt->execute([
            'role'      => $role,
            'username'  => $username,
            'full_name' => $fullName,
            'password'  => $newPassword,
            'id'        => $id,
        ]);
    } else {
        $stmt = $pdo->prepare(
            'UPDATE users SET role = :role, username = :username, full_name = :full_name WHERE id = :id'
        );
        $stmt->execute([
            'role'      => $role,
            'username'  => $username,
            'full_name' => $fullName,
            'id'        => $id,
        ]);
    }

    $activityDescription = 'Mengubah data user: ' . $username . '.';
    if ($passwordChanged) {
        $activityDescription = 'Mengubah data user: ' . $username . ' (termasuk password).';
    }

    $actor = currentActivityLogActor();
    logActivity(
        $pdo,
        $actor['user_id'],
        $actor['username'],
        $actor['full_name'],
        'update',
        'users',
        $id,
        $activityDescription,
        ['role' => $oldRole, 'username' => $oldUsername, 'full_name' => $oldFullName],
        ['role' => $role, 'username' => $username, 'full_name' => $fullName]
    );

    // Root mengedit akunnya sendiri -> sinkronkan session juga.
    if ($id === $currentUserId) {
        $_SESSION['username']  = $username;
        $_SESSION['full_name'] = $fullName;
    }

    return true;
}

// Root tidak boleh menghapus akunnya sendiri (satu-satunya tempat menghapus user).
function deleteUserByRoot(int $id): string|true
{
    ensureSessionStarted();
    $currentUserId = (int) $_SESSION['user_id'];

    if ($id === $currentUserId) {
        return 'Anda tidak bisa menghapus akun Anda sendiri.';
    }

    $pdo = getDbConnection();

    // Data lama diambil SEBELUM baris dihapus, untuk dicatat di Log Aktivitas.
    $stmt = $pdo->prepare('SELECT role, username, full_name FROM users WHERE id = :id LIMIT 1');
    $stmt->execute(['id' => $id]);
    $oldUserRow = $stmt->fetch();

    $oldRole     = '';
    $oldUsername = '';
    $oldFullName = '';
    if ($oldUserRow !== false) {
        $oldRole     = $oldUserRow['role'];
        $oldUsername = $oldUserRow['username'];
        $oldFullName = $oldUserRow['full_name'];
    }

    $stmt = $pdo->prepare('DELETE FROM users WHERE id = :id');
    $stmt->execute(['id' => $id]);

    $actor = currentActivityLogActor();
    logActivity(
        $pdo,
        $actor['user_id'],
        $actor['username'],
        $actor['full_name'],
        'delete',
        'users',
        $id,
        'Menghapus user: ' . $oldUsername . '.',
        ['role' => $oldRole, 'username' => $oldUsername, 'full_name' => $oldFullName],
        null
    );

    return true;
}
