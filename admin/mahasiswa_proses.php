<?php
/**
 * admin/mahasiswa_proses.php
 * Menangani proses Create, Update, dan Delete data mahasiswa.
 *
 * Setiap mahasiswa memiliki satu akun di tabel `users` (role = mahasiswa).
 * - create: insert ke `users` lalu ke `mahasiswa` (dalam satu transaction).
 * - update: update data di `mahasiswa`, dan sinkronkan `username` di `users` jika NIM berubah.
 * - delete: hapus dari `users` -> otomatis terhapus dari `mahasiswa` (ON DELETE CASCADE).
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

requireRole('admin');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirectTo('admin/mahasiswa.php');
}

if (!verifyCsrfToken($_POST['csrf_token'] ?? null)) {
    setFlash('danger', 'Token keamanan tidak valid. Silakan coba lagi.');
    redirectTo('admin/mahasiswa.php');
}

$action = $_POST['action'] ?? '';
$db = Database::getConnection();

if ($action === 'create') {
    $nim       = cleanInput($_POST['nim'] ?? '');
    $nama      = cleanInput($_POST['nama'] ?? '');
    $jk        = $_POST['jenis_kelamin'] ?? 'L';
    $angkatan  = (int) ($_POST['angkatan'] ?? 0);
    $noHp      = cleanInput($_POST['no_hp'] ?? '');
    $alamat    = cleanInput($_POST['alamat'] ?? '');

    if ($nim === '' || $nama === '' || $angkatan === 0) {
        setFlash('danger', 'NIM, Nama, dan Angkatan wajib diisi.');
        redirectTo('admin/mahasiswa.php');
    }

    if (!in_array($jk, ['L', 'P'], true)) {
        $jk = 'L';
    }

    try {
        $db->beginTransaction();

        // Cek apakah NIM sudah dipakai
        $cek = $db->prepare('SELECT id FROM mahasiswa WHERE nim = :nim');
        $cek->execute([':nim' => $nim]);
        if ($cek->fetch()) {
            $db->rollBack();
            setFlash('danger', 'NIM tersebut sudah terdaftar.');
            redirectTo('admin/mahasiswa.php');
        }

        // Buat akun user baru untuk mahasiswa (password default: password123)
        $defaultPassword = password_hash('password123', PASSWORD_DEFAULT);
        $stmtUser = $db->prepare('INSERT INTO users (username, password, role) VALUES (:username, :password, "mahasiswa")');
        $stmtUser->execute([
            ':username' => $nim,
            ':password' => $defaultPassword,
        ]);
        $userId = (int) $db->lastInsertId();

        // Simpan data profil mahasiswa
        $stmtMhs = $db->prepare('
            INSERT INTO mahasiswa (user_id, nim, nama, jenis_kelamin, angkatan, no_hp, alamat)
            VALUES (:user_id, :nim, :nama, :jk, :angkatan, :no_hp, :alamat)
        ');
        $stmtMhs->execute([
            ':user_id'  => $userId,
            ':nim'      => $nim,
            ':nama'     => $nama,
            ':jk'       => $jk,
            ':angkatan' => $angkatan,
            ':no_hp'    => $noHp ?: null,
            ':alamat'   => $alamat ?: null,
        ]);

        $db->commit();
        setFlash('success', 'Data mahasiswa berhasil ditambahkan.');
    } catch (PDOException $e) {
        $db->rollBack();
        error_log('Tambah Mahasiswa Error: ' . $e->getMessage());
        setFlash('danger', 'Terjadi kesalahan saat menyimpan data.');
    }
} elseif ($action === 'update') {
    $id        = (int) ($_POST['id'] ?? 0);
    $nim       = cleanInput($_POST['nim'] ?? '');
    $nama      = cleanInput($_POST['nama'] ?? '');
    $jk        = $_POST['jenis_kelamin'] ?? 'L';
    $angkatan  = (int) ($_POST['angkatan'] ?? 0);
    $noHp      = cleanInput($_POST['no_hp'] ?? '');
    $alamat    = cleanInput($_POST['alamat'] ?? '');

    if ($id === 0 || $nim === '' || $nama === '' || $angkatan === 0) {
        setFlash('danger', 'Data tidak lengkap.');
        redirectTo('admin/mahasiswa.php');
    }

    if (!in_array($jk, ['L', 'P'], true)) {
        $jk = 'L';
    }

    try {
        $db->beginTransaction();

        // Ambil user_id dan NIM lama untuk mahasiswa yang diedit
        $stmtCari = $db->prepare('SELECT user_id, nim FROM mahasiswa WHERE id = :id');
        $stmtCari->execute([':id' => $id]);
        $mhsLama = $stmtCari->fetch();

        if (!$mhsLama) {
            $db->rollBack();
            setFlash('danger', 'Data mahasiswa tidak ditemukan.');
            redirectTo('admin/mahasiswa.php');
        }

        // Jika NIM diubah, pastikan NIM baru belum dipakai mahasiswa lain
        if ($nim !== $mhsLama['nim']) {
            $cek = $db->prepare('SELECT id FROM mahasiswa WHERE nim = :nim AND id != :id');
            $cek->execute([':nim' => $nim, ':id' => $id]);
            if ($cek->fetch()) {
                $db->rollBack();
                setFlash('danger', 'NIM tersebut sudah dipakai mahasiswa lain.');
                redirectTo('admin/mahasiswa.php');
            }

            // Sinkronkan username di tabel users karena NIM dipakai sebagai username
            $stmtUser = $db->prepare('UPDATE users SET username = :username WHERE id = :user_id');
            $stmtUser->execute([':username' => $nim, ':user_id' => $mhsLama['user_id']]);
        }

        $stmtUpdate = $db->prepare('
            UPDATE mahasiswa
            SET nim = :nim, nama = :nama, jenis_kelamin = :jk, angkatan = :angkatan, no_hp = :no_hp, alamat = :alamat
            WHERE id = :id
        ');
        $stmtUpdate->execute([
            ':nim'      => $nim,
            ':nama'     => $nama,
            ':jk'       => $jk,
            ':angkatan' => $angkatan,
            ':no_hp'    => $noHp ?: null,
            ':alamat'   => $alamat ?: null,
            ':id'       => $id,
        ]);

        $db->commit();
        setFlash('success', 'Data mahasiswa berhasil diperbarui.');
    } catch (PDOException $e) {
        $db->rollBack();
        error_log('Update Mahasiswa Error: ' . $e->getMessage());
        setFlash('danger', 'Terjadi kesalahan saat memperbarui data.');
    }
} elseif ($action === 'delete') {
    $id = (int) ($_POST['id'] ?? 0);

    if ($id === 0) {
        setFlash('danger', 'Data tidak valid.');
        redirectTo('admin/mahasiswa.php');
    }

    try {
        // Hapus user terkait -> mahasiswa, krs, dan nilai ikut terhapus otomatis (ON DELETE CASCADE)
        $stmtCari = $db->prepare('SELECT user_id FROM mahasiswa WHERE id = :id');
        $stmtCari->execute([':id' => $id]);
        $row = $stmtCari->fetch();

        if (!$row) {
            setFlash('danger', 'Data mahasiswa tidak ditemukan.');
            redirectTo('admin/mahasiswa.php');
        }

        $stmtHapus = $db->prepare('DELETE FROM users WHERE id = :user_id');
        $stmtHapus->execute([':user_id' => $row['user_id']]);

        setFlash('success', 'Data mahasiswa berhasil dihapus.');
    } catch (PDOException $e) {
        error_log('Hapus Mahasiswa Error: ' . $e->getMessage());
        setFlash('danger', 'Terjadi kesalahan saat menghapus data.');
    }
} else {
    setFlash('danger', 'Aksi tidak dikenali.');
}

redirectTo('admin/mahasiswa.php');
