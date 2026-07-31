<?php
/**
 * admin/kelas_proses.php
 * Menangani proses Create, Update, dan Delete data kelas.
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

requireRole('admin');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirectTo('admin/kelas.php');
}

if (!verifyCsrfToken($_POST['csrf_token'] ?? null)) {
    setFlash('danger', 'Token keamanan tidak valid. Silakan coba lagi.');
    redirectTo('admin/kelas.php');
}

$action = $_POST['action'] ?? '';
$db = Database::getConnection();

/**
 * Mengambil dan memvalidasi input form kelas dari $_POST.
 * Mengembalikan array data jika valid, atau null jika ada yang kosong/tidak valid.
 */
function ambilInputKelas(): ?array
{
    $data = [
        'mata_kuliah_id'    => (int) ($_POST['mata_kuliah_id'] ?? 0),
        'dosen_id'          => (int) ($_POST['dosen_id'] ?? 0),
        'tahun_akademik_id' => (int) ($_POST['tahun_akademik_id'] ?? 0),
        'nama_kelas'        => cleanInput($_POST['nama_kelas'] ?? ''),
        'hari'              => cleanInput($_POST['hari'] ?? ''),
        'jam'               => cleanInput($_POST['jam'] ?? ''),
        'ruang'             => cleanInput($_POST['ruang'] ?? ''),
        'kuota'             => (int) ($_POST['kuota'] ?? 0),
    ];

    if (
        $data['mata_kuliah_id'] === 0 ||
        $data['dosen_id'] === 0 ||
        $data['tahun_akademik_id'] === 0 ||
        $data['nama_kelas'] === '' ||
        $data['kuota'] < 1
    ) {
        return null;
    }

    $hariValid = ['', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
    if (!in_array($data['hari'], $hariValid, true)) {
        return null;
    }

    if (!isValidJam($data['jam'])) {
        return null;
    }

    return $data;
}

if ($action === 'create') {
    $data = ambilInputKelas();

    if ($data === null) {
        setFlash('danger', 'Semua field wajib diisi dengan benar.');
        redirectTo('admin/kelas.php');
    }

    try {
        $stmt = $db->prepare('
            INSERT INTO kelas (mata_kuliah_id, dosen_id, tahun_akademik_id, nama_kelas, hari, jam, ruang, kuota)
            VALUES (:mata_kuliah_id, :dosen_id, :tahun_akademik_id, :nama_kelas, :hari, :jam, :ruang, :kuota)
        ');
        $stmt->execute([
            ':mata_kuliah_id'    => $data['mata_kuliah_id'],
            ':dosen_id'          => $data['dosen_id'],
            ':tahun_akademik_id' => $data['tahun_akademik_id'],
            ':nama_kelas'        => $data['nama_kelas'],
            ':hari'              => $data['hari'] ?: null,
            ':jam'               => $data['jam'] ?: null,
            ':ruang'             => $data['ruang'] ?: null,
            ':kuota'             => $data['kuota'],
        ]);

        setFlash('success', 'Kelas berhasil ditambahkan.');
    } catch (PDOException $e) {
        error_log('Tambah Kelas Error: ' . $e->getMessage());
        setFlash('danger', 'Terjadi kesalahan saat menyimpan data. Pastikan mata kuliah, dosen, dan tahun akademik valid.');
    }
} elseif ($action === 'update') {
    $id   = (int) ($_POST['id'] ?? 0);
    $data = ambilInputKelas();

    if ($id === 0 || $data === null) {
        setFlash('danger', 'Semua field wajib diisi dengan benar.');
        redirectTo('admin/kelas.php');
    }

    try {
        $stmt = $db->prepare('
            UPDATE kelas
            SET mata_kuliah_id = :mata_kuliah_id, dosen_id = :dosen_id, tahun_akademik_id = :tahun_akademik_id,
                nama_kelas = :nama_kelas, hari = :hari, jam = :jam, ruang = :ruang, kuota = :kuota
            WHERE id = :id
        ');
        $stmt->execute([
            ':mata_kuliah_id'    => $data['mata_kuliah_id'],
            ':dosen_id'          => $data['dosen_id'],
            ':tahun_akademik_id' => $data['tahun_akademik_id'],
            ':nama_kelas'        => $data['nama_kelas'],
            ':hari'              => $data['hari'] ?: null,
            ':jam'               => $data['jam'] ?: null,
            ':ruang'             => $data['ruang'] ?: null,
            ':kuota'             => $data['kuota'],
            ':id'                => $id,
        ]);

        setFlash('success', 'Kelas berhasil diperbarui.');
    } catch (PDOException $e) {
        error_log('Update Kelas Error: ' . $e->getMessage());
        setFlash('danger', 'Terjadi kesalahan saat memperbarui data.');
    }
} elseif ($action === 'delete') {
    $id = (int) ($_POST['id'] ?? 0);

    if ($id === 0) {
        setFlash('danger', 'Data tidak valid.');
        redirectTo('admin/kelas.php');
    }

    try {
        $stmt = $db->prepare('DELETE FROM kelas WHERE id = :id');
        $stmt->execute([':id' => $id]);

        setFlash('success', 'Kelas berhasil dihapus.');
    } catch (PDOException $e) {
        error_log('Hapus Kelas Error: ' . $e->getMessage());
        setFlash('danger', 'Terjadi kesalahan saat menghapus data.');
    }
} else {
    setFlash('danger', 'Aksi tidak dikenali.');
}

redirectTo('admin/kelas.php');
