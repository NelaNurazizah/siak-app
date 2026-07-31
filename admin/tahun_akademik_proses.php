<?php
/**
 * admin/tahun_akademik_proses.php
 * Menangani proses Create, Update, dan Delete data tahun akademik.
 *
 * Aturan bisnis: hanya boleh ada 1 tahun akademik berstatus "aktif".
 * Jika user mengaktifkan satu tahun akademik, semua yang lain
 * otomatis diubah menjadi "nonaktif".
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

requireRole('admin');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirectTo('admin/tahun_akademik.php');
}

if (!verifyCsrfToken($_POST['csrf_token'] ?? null)) {
    setFlash('danger', 'Token keamanan tidak valid. Silakan coba lagi.');
    redirectTo('admin/tahun_akademik.php');
}

$action = $_POST['action'] ?? '';
$db = Database::getConnection();

if ($action === 'create') {
    $tahun    = cleanInput($_POST['tahun'] ?? '');
    $semester = $_POST['semester'] ?? 'Ganjil';
    $status   = $_POST['status'] ?? 'nonaktif';

    if ($tahun === '' || !in_array($semester, ['Ganjil', 'Genap'], true)) {
        setFlash('danger', 'Tahun dan semester wajib diisi dengan benar.');
        redirectTo('admin/tahun_akademik.php');
    }

    if (!isValidTahunAkademik($tahun)) {
        setFlash('danger', 'Format tahun tidak valid. Gunakan format seperti 2025/2026.');
        redirectTo('admin/tahun_akademik.php');
    }
    if (!in_array($status, ['aktif', 'nonaktif'], true)) {
        $status = 'nonaktif';
    }

    try {
        $db->beginTransaction();

        $cek = $db->prepare('SELECT id FROM tahun_akademik WHERE tahun = :tahun AND semester = :semester');
        $cek->execute([':tahun' => $tahun, ':semester' => $semester]);
        if ($cek->fetch()) {
            $db->rollBack();
            setFlash('danger', 'Tahun akademik dengan tahun dan semester tersebut sudah ada.');
            redirectTo('admin/tahun_akademik.php');
        }

        if ($status === 'aktif') {
            $db->exec("UPDATE tahun_akademik SET status = 'nonaktif'");
        }

        $stmt = $db->prepare('INSERT INTO tahun_akademik (tahun, semester, status) VALUES (:tahun, :semester, :status)');
        $stmt->execute([
            ':tahun'    => $tahun,
            ':semester' => $semester,
            ':status'   => $status,
        ]);

        $db->commit();
        setFlash('success', 'Tahun akademik berhasil ditambahkan.');
    } catch (PDOException $e) {
        $db->rollBack();
        error_log('Tambah Tahun Akademik Error: ' . $e->getMessage());
        setFlash('danger', 'Terjadi kesalahan saat menyimpan data.');
    }
} elseif ($action === 'update') {
    $id       = (int) ($_POST['id'] ?? 0);
    $tahun    = cleanInput($_POST['tahun'] ?? '');
    $semester = $_POST['semester'] ?? 'Ganjil';
    $status   = $_POST['status'] ?? 'nonaktif';

    if ($id === 0 || $tahun === '' || !in_array($semester, ['Ganjil', 'Genap'], true)) {
        setFlash('danger', 'Tahun dan semester wajib diisi dengan benar.');
        redirectTo('admin/tahun_akademik.php');
    }

    if (!isValidTahunAkademik($tahun)) {
        setFlash('danger', 'Format tahun tidak valid. Gunakan format seperti 2025/2026.');
        redirectTo('admin/tahun_akademik.php');
    }
    if (!in_array($status, ['aktif', 'nonaktif'], true)) {
        $status = 'nonaktif';
    }

    try {
        $db->beginTransaction();

        $cek = $db->prepare('SELECT id FROM tahun_akademik WHERE tahun = :tahun AND semester = :semester AND id != :id');
        $cek->execute([':tahun' => $tahun, ':semester' => $semester, ':id' => $id]);
        if ($cek->fetch()) {
            $db->rollBack();
            setFlash('danger', 'Tahun akademik dengan tahun dan semester tersebut sudah ada.');
            redirectTo('admin/tahun_akademik.php');
        }

        if ($status === 'aktif') {
            $db->exec("UPDATE tahun_akademik SET status = 'nonaktif' WHERE id != " . (int) $id);
        }

        $stmt = $db->prepare('UPDATE tahun_akademik SET tahun = :tahun, semester = :semester, status = :status WHERE id = :id');
        $stmt->execute([
            ':tahun'    => $tahun,
            ':semester' => $semester,
            ':status'   => $status,
            ':id'       => $id,
        ]);

        $db->commit();
        setFlash('success', 'Tahun akademik berhasil diperbarui.');
    } catch (PDOException $e) {
        $db->rollBack();
        error_log('Update Tahun Akademik Error: ' . $e->getMessage());
        setFlash('danger', 'Terjadi kesalahan saat memperbarui data.');
    }
} elseif ($action === 'delete') {
    $id = (int) ($_POST['id'] ?? 0);

    if ($id === 0) {
        setFlash('danger', 'Data tidak valid.');
        redirectTo('admin/tahun_akademik.php');
    }

    try {
        $stmt = $db->prepare('DELETE FROM tahun_akademik WHERE id = :id');
        $stmt->execute([':id' => $id]);

        setFlash('success', 'Tahun akademik berhasil dihapus.');
    } catch (PDOException $e) {
        error_log('Hapus Tahun Akademik Error: ' . $e->getMessage());
        setFlash('danger', 'Terjadi kesalahan saat menghapus data.');
    }
} else {
    setFlash('danger', 'Aksi tidak dikenali.');
}

redirectTo('admin/tahun_akademik.php');
