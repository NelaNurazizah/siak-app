<?php
/**
 * admin/mata_kuliah_proses.php
 * Menangani proses Create, Update, dan Delete data mata kuliah.
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

requireRole('admin');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirectTo('admin/mata_kuliah.php');
}

if (!verifyCsrfToken($_POST['csrf_token'] ?? null)) {
    setFlash('danger', 'Token keamanan tidak valid. Silakan coba lagi.');
    redirectTo('admin/mata_kuliah.php');
}

$action = $_POST['action'] ?? '';
$db = Database::getConnection();

if ($action === 'create') {
    $kodeMk   = cleanInput($_POST['kode_mk'] ?? '');
    $namaMk   = cleanInput($_POST['nama_mk'] ?? '');
    $sks      = (int) ($_POST['sks'] ?? 0);
    $semester = (int) ($_POST['semester'] ?? 0);

    if ($kodeMk === '' || $namaMk === '' || $sks < 1 || $semester < 1) {
        setFlash('danger', 'Semua field wajib diisi dengan benar.');
        redirectTo('admin/mata_kuliah.php');
    }

    if (!isValidKode($kodeMk)) {
        setFlash('danger', 'Format kode mata kuliah tidak valid. Gunakan huruf/angka, 3-20 karakter.');
        redirectTo('admin/mata_kuliah.php');
    }

    if ($sks > 6 || $semester > 8) {
        setFlash('danger', 'SKS maksimal 6 dan semester maksimal 8.');
        redirectTo('admin/mata_kuliah.php');
    }

    try {
        $cek = $db->prepare('SELECT id FROM mata_kuliah WHERE kode_mk = :kode');
        $cek->execute([':kode' => $kodeMk]);
        if ($cek->fetch()) {
            setFlash('danger', 'Kode mata kuliah tersebut sudah digunakan.');
            redirectTo('admin/mata_kuliah.php');
        }

        $stmt = $db->prepare('
            INSERT INTO mata_kuliah (kode_mk, nama_mk, sks, semester)
            VALUES (:kode_mk, :nama_mk, :sks, :semester)
        ');
        $stmt->execute([
            ':kode_mk'  => $kodeMk,
            ':nama_mk'  => $namaMk,
            ':sks'      => $sks,
            ':semester' => $semester,
        ]);

        setFlash('success', 'Mata kuliah berhasil ditambahkan.');
    } catch (PDOException $e) {
        error_log('Tambah Mata Kuliah Error: ' . $e->getMessage());
        setFlash('danger', 'Terjadi kesalahan saat menyimpan data.');
    }
} elseif ($action === 'update') {
    $id       = (int) ($_POST['id'] ?? 0);
    $kodeMk   = cleanInput($_POST['kode_mk'] ?? '');
    $namaMk   = cleanInput($_POST['nama_mk'] ?? '');
    $sks      = (int) ($_POST['sks'] ?? 0);
    $semester = (int) ($_POST['semester'] ?? 0);

    if ($id === 0 || $kodeMk === '' || $namaMk === '' || $sks < 1 || $semester < 1) {
        setFlash('danger', 'Semua field wajib diisi dengan benar.');
        redirectTo('admin/mata_kuliah.php');
    }

    if (!isValidKode($kodeMk)) {
        setFlash('danger', 'Format kode mata kuliah tidak valid. Gunakan huruf/angka, 3-20 karakter.');
        redirectTo('admin/mata_kuliah.php');
    }

    if ($sks > 6 || $semester > 8) {
        setFlash('danger', 'SKS maksimal 6 dan semester maksimal 8.');
        redirectTo('admin/mata_kuliah.php');
    }

    try {
        $cek = $db->prepare('SELECT id FROM mata_kuliah WHERE kode_mk = :kode AND id != :id');
        $cek->execute([':kode' => $kodeMk, ':id' => $id]);
        if ($cek->fetch()) {
            setFlash('danger', 'Kode mata kuliah tersebut sudah dipakai mata kuliah lain.');
            redirectTo('admin/mata_kuliah.php');
        }

        $stmt = $db->prepare('
            UPDATE mata_kuliah
            SET kode_mk = :kode_mk, nama_mk = :nama_mk, sks = :sks, semester = :semester
            WHERE id = :id
        ');
        $stmt->execute([
            ':kode_mk'  => $kodeMk,
            ':nama_mk'  => $namaMk,
            ':sks'      => $sks,
            ':semester' => $semester,
            ':id'       => $id,
        ]);

        setFlash('success', 'Mata kuliah berhasil diperbarui.');
    } catch (PDOException $e) {
        error_log('Update Mata Kuliah Error: ' . $e->getMessage());
        setFlash('danger', 'Terjadi kesalahan saat memperbarui data.');
    }
} elseif ($action === 'delete') {
    $id = (int) ($_POST['id'] ?? 0);

    if ($id === 0) {
        setFlash('danger', 'Data tidak valid.');
        redirectTo('admin/mata_kuliah.php');
    }

    try {
        $stmt = $db->prepare('DELETE FROM mata_kuliah WHERE id = :id');
        $stmt->execute([':id' => $id]);

        setFlash('success', 'Mata kuliah berhasil dihapus.');
    } catch (PDOException $e) {
        error_log('Hapus Mata Kuliah Error: ' . $e->getMessage());
        setFlash('danger', 'Terjadi kesalahan saat menghapus data.');
    }
} else {
    setFlash('danger', 'Aksi tidak dikenali.');
}

redirectTo('admin/mata_kuliah.php');
