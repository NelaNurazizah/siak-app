<?php
/**
 * includes/auth.php
 * Kumpulan fungsi bantuan untuk autentikasi dan proteksi halaman.
 *
 * Dipakai di setiap halaman admin/dosen/mahasiswa dengan cara:
 *   require_once __DIR__ . '/../includes/auth.php';
 *   requireRole('admin'); // hanya admin yang boleh akses halaman ini
 */

/**
 * Mengecek apakah user sudah login (ada session user_id).
 * Jika belum login, redirect ke halaman login.
 */
function requireLogin(): void
{
    if (empty($_SESSION['user_id'])) {
        header('Location: ' . BASE_URL . 'login.php');
        exit;
    }
}

/**
 * Mengecek apakah user sudah login DAN memiliki role yang sesuai.
 * Jika belum login -> redirect ke login.
 * Jika login tapi role tidak sesuai -> redirect ke dashboard sesuai role-nya sendiri.
 *
 * @param string|array $allowedRoles Role yang diizinkan mengakses halaman ini.
 */
function requireRole($allowedRoles): void
{
    requireLogin();

    if (is_string($allowedRoles)) {
        $allowedRoles = [$allowedRoles];
    }

    if (!in_array($_SESSION['role'], $allowedRoles, true)) {
        redirectToDashboard($_SESSION['role']);
    }
}

/**
 * Redirect user ke dashboard sesuai role masing-masing.
 * Dipakai saat user mencoba mengakses halaman role lain,
 * atau saat user yang sudah login membuka login.php lagi.
 */
function redirectToDashboard(string $role): void
{
    switch ($role) {
        case 'admin':
            header('Location: ' . BASE_URL . 'admin/dashboard.php');
            break;
        case 'dosen':
            header('Location: ' . BASE_URL . 'dosen/dashboard.php');
            break;
        case 'mahasiswa':
            header('Location: ' . BASE_URL . 'mahasiswa/dashboard.php');
            break;
        default:
            header('Location: ' . BASE_URL . 'login.php');
    }
    exit;
}

/**
 * Mengambil nama lengkap user yang sedang login dari session.
 */
function currentUserName(): string
{
    return $_SESSION['nama'] ?? 'Pengguna';
}

/**
 * Mengecek kecocokan CSRF token dari form dengan yang ada di session.
 */
function verifyCsrfToken(?string $token): bool
{
    if (empty($token) || empty($_SESSION['csrf_token'])) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $token);
}
