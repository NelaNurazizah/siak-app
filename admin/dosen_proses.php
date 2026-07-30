<?php
/**
 * admin/dosen_proses.php
 * Menangani proses Create, Update, dan Delete data dosen.
 *
 * Setiap dosen memiliki satu akun di tabel `users` (role = dosen).
 * - create: insert ke `users` lalu ke `dosen` (dalam satu transaction).
 * - update: update data di `dosen`, dan sinkronkan `username` di `users` jika NIDN berubah.
 * - delete: hapus dari `users` -> otomatis terhapus dari `dosen` (ON DELETE CASCADE),
 *           yang juga akan menghapus kelas yang diampu (ON DELETE CASCADE pada tabel kelas).
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

requireRole('admin');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirectTo('admin/dosen.php');
}

if (!verifyCsrfToken($_POST['csrf_token'] ?? null)) {
    setFlash('danger', 'Token keamanan tidak valid. Silakan coba lagi.');
    redirectTo('admin/dosen.php');
}

$action = $_POST['action'] ?? '';
$db = Database::getConnection();

if ($action === 'create') {
    $nidn   = cleanInput($_POST['nidn'] ?? '');
    $nama   = cleanInput($_POST['nama'] ?? '');
    $jk     = $_POST['jenis_kelamin'] ?? 'L';
    $noHp   = cleanInput($_POST['no_hp'] ?? '');
    $alamat = cleanInput($_POST['alamat'] ?? '');

    if ($nidn === '' || $nama === '') {
        setFlash('danger', 'NIDN dan Nama wajib diisi.');
        redirectTo('admin/dosen.php');
    }

    if (!in_array($jk, ['L', 'P'], true)) {
        $jk = 'L';
    }

    try {
        $db->beginTransaction();

        $cek = $db->prepare('SELECT id FROM dosen WHERE nidn = :nidn');
        $cek->execute([':nidn' => $nidn]);
        if ($cek->fetch()) {
            $db->rollBack();
            setFlash('danger', 'NIDN tersebut sudah terdaftar.');
            redirectTo('admin/dosen.php');
        }

        $defaultPassword = password_hash('password123', PASSWORD_DEFAULT);
        $stmtUser = $db->prepare('INSERT INTO users (username, password, role) VALUES (:username, :password, "dosen")');
        $stmtUser->execute([
            ':username' => $nidn,
            ':password' => $defaultPassword,
        ]);
        $userId = (int) $db->lastInsertId();

        $stmtDosen = $db->prepare('
            INSERT INTO dosen (user_id, nidn, nama, jenis_kelamin, no_hp, alamat)
            VALUES (:user_id, :nidn, :nama, :jk, :no_hp, :alamat)
        ');
        $stmtDosen->execute([
            ':user_id' => $userId,
            ':nidn'    => $nidn,
            ':nama'    => $nama,
            ':jk'      => $jk,
            ':no_hp'   => $noHp ?: null,
            ':alamat'  => $alamat ?: null,
        ]);

        $db->commit();
        setFlash('success', 'Data dosen berhasil ditambahkan.');
    } catch (PDOException $e) {
        $db->rollBack();
        error_log('Tambah Dosen Error: ' . $e->getMessage());
        setFlash('danger', 'Terjadi kesalahan saat menyimpan data.');
    }
} elseif ($action === 'update') {
    $id     = (int) ($_POST['id'] ?? 0);
    $nidn   = cleanInput($_POST['nidn'] ?? '');
    $nama   = cleanInput($_POST['nama'] ?? '');
    $jk     = $_POST['jenis_kelamin'] ?? 'L';
    $noHp   = cleanInput($_POST['no_hp'] ?? '');
    $alamat = cleanInput($_POST['alamat'] ?? '');

    if ($id === 0 || $nidn === '' || $nama === '') {
        setFlash('danger', 'Data tidak lengkap.');
        redirectTo('admin/dosen.php');
    }

    if (!in_array($jk, ['L', 'P'], true)) {
        $jk = 'L';
    }

    try {
        $db->beginTransaction();

        $stmtCari = $db->prepare('SELECT user_id, nidn FROM dosen WHERE id = :id');
        $stmtCari->execute([':id' => $id]);
        $dosenLama = $stmtCari->fetch();

        if (!$dosenLama) {
            $db->rollBack();
            setFlash('danger', 'Data dosen tidak ditemukan.');
            redirectTo('admin/dosen.php');
        }

        if ($nidn !== $dosenLama['nidn']) {
            $cek = $db->prepare('SELECT id FROM dosen WHERE nidn = :nidn AND id != :id');
            $cek->execute([':nidn' => $nidn, ':id' => $id]);
            if ($cek->fetch()) {
                $db->rollBack();
                setFlash('danger', 'NIDN tersebut sudah dipakai dosen lain.');
                redirectTo('admin/dosen.php');
            }

            $stmtUser = $db->prepare('UPDATE users SET username = :username WHERE id = :user_id');
            $stmtUser->execute([':username' => $nidn, ':user_id' => $dosenLama['user_id']]);
        }

        $stmtUpdate = $db->prepare('
            UPDATE dosen
            SET nidn = :nidn, nama = :nama, jenis_kelamin = :jk, no_hp = :no_hp, alamat = :alamat
            WHERE id = :id
        ');
        $stmtUpdate->execute([
            ':nidn'   => $nidn,
            ':nama'   => $nama,
            ':jk'     => $jk,
            ':no_hp'  => $noHp ?: null,
            ':alamat' => $alamat ?: null,
            ':id'     => $id,
        ]);

        $db->commit();
        setFlash('success', 'Data dosen berhasil diperbarui.');
    } catch (PDOException $e) {
        $db->rollBack();
        error_log('Update Dosen Error: ' . $e->getMessage());
        setFlash('danger', 'Terjadi kesalahan saat memperbarui data.');
    }
} elseif ($action === 'delete') {
    $id = (int) ($_POST['id'] ?? 0);

    if ($id === 0) {
        setFlash('danger', 'Data tidak valid.');
        redirectTo('admin/dosen.php');
    }

    try {
        $stmtCari = $db->prepare('SELECT user_id FROM dosen WHERE id = :id');
        $stmtCari->execute([':id' => $id]);
        $row = $stmtCari->fetch();

        if (!$row) {
            setFlash('danger', 'Data dosen tidak ditemukan.');
            redirectTo('admin/dosen.php');
        }

        $stmtHapus = $db->prepare('DELETE FROM users WHERE id = :user_id');
        $stmtHapus->execute([':user_id' => $row['user_id']]);

        setFlash('success', 'Data dosen berhasil dihapus.');
    } catch (PDOException $e) {
        error_log('Hapus Dosen Error: ' . $e->getMessage());
        setFlash('danger', 'Terjadi kesalahan saat menghapus data.');
    }
} else {
    setFlash('danger', 'Aksi tidak dikenali.');
}

redirectTo('admin/dosen.php');
