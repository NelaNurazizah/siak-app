<?php
/**
 * admin/user_proses.php
 * Menangani proses Tambah Akun Admin, Reset Password, dan Hapus User.
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

requireRole('admin');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirectTo('admin/user.php');
}

if (!verifyCsrfToken($_POST['csrf_token'] ?? null)) {
    setFlash('danger', 'Token keamanan tidak valid. Silakan coba lagi.');
    redirectTo('admin/user.php');
}

$action = $_POST['action'] ?? '';
$db = Database::getConnection();

if ($action === 'create_admin') {
    $nama     = cleanInput($_POST['nama'] ?? '');
    $username = cleanInput($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($nama === '' || $username === '' || strlen($password) < 6) {
        setFlash('danger', 'Nama, username wajib diisi, dan password minimal 6 karakter.');
        redirectTo('admin/user.php');
    }

    try {
        $db->beginTransaction();

        $cek = $db->prepare('SELECT id FROM users WHERE username = :username');
        $cek->execute([':username' => $username]);
        if ($cek->fetch()) {
            $db->rollBack();
            setFlash('danger', 'Username tersebut sudah digunakan.');
            redirectTo('admin/user.php');
        }

        $hash = password_hash($password, PASSWORD_DEFAULT);
        $stmtUser = $db->prepare('INSERT INTO users (username, password, role) VALUES (:username, :password, "admin")');
        $stmtUser->execute([':username' => $username, ':password' => $hash]);
        $userId = (int) $db->lastInsertId();

        $stmtAdmin = $db->prepare('INSERT INTO admin (user_id, nama) VALUES (:user_id, :nama)');
        $stmtAdmin->execute([':user_id' => $userId, ':nama' => $nama]);

        $db->commit();
        setFlash('success', 'Akun admin baru berhasil ditambahkan.');
    } catch (PDOException $e) {
        $db->rollBack();
        error_log('Tambah Admin Error: ' . $e->getMessage());
        setFlash('danger', 'Terjadi kesalahan saat menyimpan data.');
    }
} elseif ($action === 'reset_password') {
    $id = (int) ($_POST['id'] ?? 0);

    if ($id === 0) {
        setFlash('danger', 'Data tidak valid.');
        redirectTo('admin/user.php');
    }

    try {
        $hash = password_hash('password123', PASSWORD_DEFAULT);
        $stmt = $db->prepare('UPDATE users SET password = :password WHERE id = :id');
        $stmt->execute([':password' => $hash, ':id' => $id]);

        setFlash('success', 'Password berhasil direset menjadi "password123".');
    } catch (PDOException $e) {
        error_log('Reset Password Error: ' . $e->getMessage());
        setFlash('danger', 'Terjadi kesalahan saat mereset password.');
    }
} elseif ($action === 'delete') {
    $id = (int) ($_POST['id'] ?? 0);

    if ($id === 0) {
        setFlash('danger', 'Data tidak valid.');
        redirectTo('admin/user.php');
    }

    if ($id === (int) $_SESSION['user_id']) {
        setFlash('danger', 'Anda tidak dapat menghapus akun Anda sendiri.');
        redirectTo('admin/user.php');
    }

    try {
        // Cegah penghapusan admin terakhir agar sistem tidak kehilangan akses admin
        $stmtCekRole = $db->prepare('SELECT role FROM users WHERE id = :id');
        $stmtCekRole->execute([':id' => $id]);
        $target = $stmtCekRole->fetch();

        if (!$target) {
            setFlash('danger', 'User tidak ditemukan.');
            redirectTo('admin/user.php');
        }

        if ($target['role'] === 'admin') {
            $jumlahAdmin = (int) $db->query("SELECT COUNT(*) AS total FROM users WHERE role = 'admin'")->fetch()['total'];
            if ($jumlahAdmin <= 1) {
                setFlash('danger', 'Tidak dapat menghapus admin terakhir yang tersisa.');
                redirectTo('admin/user.php');
            }
        }

        // Menghapus user akan otomatis menghapus data profil terkait (admin/dosen/mahasiswa) via CASCADE
        $stmt = $db->prepare('DELETE FROM users WHERE id = :id');
        $stmt->execute([':id' => $id]);

        setFlash('success', 'Akun berhasil dihapus.');
    } catch (PDOException $e) {
        error_log('Hapus User Error: ' . $e->getMessage());
        setFlash('danger', 'Terjadi kesalahan saat menghapus akun.');
    }
} else {
    setFlash('danger', 'Aksi tidak dikenali.');
}

redirectTo('admin/user.php');
