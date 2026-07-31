<?php
/**
 * mahasiswa/ganti_password_proses.php
 * Menangani proses ganti password oleh mahasiswa yang login.
 *
 * Validasi:
 * - Password lama harus cocok dengan password tersimpan (password_verify).
 * - Password baru minimal 6 karakter.
 * - Konfirmasi password baru harus sama persis dengan password baru.
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

requireRole('mahasiswa');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirectTo('mahasiswa/ganti_password.php');
}

if (!verifyCsrfToken($_POST['csrf_token'] ?? null)) {
    setFlash('danger', 'Token keamanan tidak valid. Silakan coba lagi.');
    redirectTo('mahasiswa/ganti_password.php');
}

$passwordLama  = $_POST['password_lama'] ?? '';
$passwordBaru  = $_POST['password_baru'] ?? '';
$konfirmasi    = $_POST['konfirmasi_password'] ?? '';

if ($passwordLama === '' || $passwordBaru === '' || $konfirmasi === '') {
    setFlash('danger', 'Semua field wajib diisi.');
    redirectTo('mahasiswa/ganti_password.php');
}

if (strlen($passwordBaru) < 6) {
    setFlash('danger', 'Password baru minimal 6 karakter.');
    redirectTo('mahasiswa/ganti_password.php');
}

if ($passwordBaru !== $konfirmasi) {
    setFlash('danger', 'Konfirmasi password baru tidak cocok.');
    redirectTo('mahasiswa/ganti_password.php');
}

$db = Database::getConnection();

try {
    $stmt = $db->prepare('SELECT password FROM users WHERE id = :id');
    $stmt->execute([':id' => $_SESSION['user_id']]);
    $user = $stmt->fetch();

    if (!$user || !password_verify($passwordLama, $user['password'])) {
        setFlash('danger', 'Password lama yang Anda masukkan salah.');
        redirectTo('mahasiswa/ganti_password.php');
    }

    $hashBaru = password_hash($passwordBaru, PASSWORD_DEFAULT);
    $stmtUpdate = $db->prepare('UPDATE users SET password = :password WHERE id = :id');
    $stmtUpdate->execute([':password' => $hashBaru, ':id' => $_SESSION['user_id']]);

    setFlash('success', 'Password berhasil diubah. Gunakan password baru saat login berikutnya.');
} catch (PDOException $e) {
    error_log('Ganti Password Error: ' . $e->getMessage());
    setFlash('danger', 'Terjadi kesalahan saat mengubah password.');
}

redirectTo('mahasiswa/ganti_password.php');
