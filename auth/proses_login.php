<?php
/**
 * auth/proses_login.php
 * Menangani proses autentikasi ketika form login.php di-submit.
 *
 * Alur:
 * 1. Pastikan request adalah POST.
 * 2. Validasi CSRF token.
 * 3. Validasi input username & password tidak kosong.
 * 4. Cari user di database berdasarkan username (prepared statement).
 * 5. Verifikasi password menggunakan password_verify().
 * 6. Jika valid: buat session, regenerate session id, redirect ke dashboard sesuai role.
 * 7. Jika tidak valid: simpan pesan error ke session, redirect kembali ke login.php.
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';

// Hanya boleh diakses melalui method POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . BASE_URL . 'login.php');
    exit;
}

// Validasi CSRF token
if (!verifyCsrfToken($_POST['csrf_token'] ?? null)) {
    $_SESSION['login_error'] = 'Token keamanan tidak valid. Silakan coba lagi.';
    header('Location: ' . BASE_URL . 'login.php');
    exit;
}

$username = trim($_POST['username'] ?? '');
$password = $_POST['password'] ?? '';

// Validasi input dasar
if ($username === '' || $password === '') {
    $_SESSION['login_error'] = 'Username dan password wajib diisi.';
    header('Location: ' . BASE_URL . 'login.php');
    exit;
}

try {
    $db = Database::getConnection();

    // Ambil data user berdasarkan username (prepared statement mencegah SQL Injection)
    $stmt = $db->prepare('SELECT id, username, password, role FROM users WHERE username = :username LIMIT 1');
    $stmt->execute([':username' => $username]);
    $user = $stmt->fetch();

    // Verifikasi password dengan password_verify()
    if (!$user || !password_verify($password, $user['password'])) {
        $_SESSION['login_error'] = 'Username atau password salah.';
        header('Location: ' . BASE_URL . 'login.php');
        exit;
    }

    // Ambil nama lengkap sesuai role dari tabel profil masing-masing
    $nama = ambilNamaUser($db, (int) $user['id'], $user['role']);

    // Regenerate session ID untuk mencegah session fixation attack
    session_regenerate_id(true);

    // Simpan data user ke session
    $_SESSION['user_id']  = $user['id'];
    $_SESSION['username'] = $user['username'];
    $_SESSION['role']     = $user['role'];
    $_SESSION['nama']     = $nama;

    // Buat ulang CSRF token setelah login berhasil
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

    // Redirect ke dashboard sesuai role
    redirectToDashboard($user['role']);
} catch (PDOException $e) {
    error_log('Login Error: ' . $e->getMessage());
    $_SESSION['login_error'] = 'Terjadi kesalahan pada server. Silakan coba lagi nanti.';
    header('Location: ' . BASE_URL . 'login.php');
    exit;
}

/**
 * Mengambil nama lengkap user berdasarkan role dari tabel profil terkait
 * (admin, dosen, atau mahasiswa).
 */
function ambilNamaUser(PDO $db, int $userId, string $role): string
{
    $table = match ($role) {
        'admin' => 'admin',
        'dosen' => 'dosen',
        'mahasiswa' => 'mahasiswa',
        default => null,
    };

    if ($table === null) {
        return 'Pengguna';
    }

    $stmt = $db->prepare("SELECT nama FROM `$table` WHERE user_id = :user_id LIMIT 1");
    $stmt->execute([':user_id' => $userId]);
    $row = $stmt->fetch();

    return $row['nama'] ?? 'Pengguna';
}
