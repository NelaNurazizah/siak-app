<?php
/**
 * mahasiswa/krs_proses.php
 * Menangani proses pengambilan (create) dan pembatalan (delete) KRS oleh mahasiswa.
 *
 * Validasi penting:
 * - Kelas harus berada pada tahun akademik yang sedang aktif.
 * - Mahasiswa tidak boleh mengambil kelas yang sama dua kali.
 * - Kuota kelas tidak boleh terlampaui (dicek ulang di server, tidak hanya di UI).
 * - Mahasiswa hanya bisa membatalkan KRS miliknya sendiri.
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

requireRole('mahasiswa');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirectTo('mahasiswa/isi_krs.php');
}

if (!verifyCsrfToken($_POST['csrf_token'] ?? null)) {
    setFlash('danger', 'Token keamanan tidak valid. Silakan coba lagi.');
    redirectTo('mahasiswa/isi_krs.php');
}

$db = Database::getConnection();

$stmtMhs = $db->prepare('SELECT id FROM mahasiswa WHERE user_id = :user_id');
$stmtMhs->execute([':user_id' => $_SESSION['user_id']]);
$mahasiswaId = $stmtMhs->fetch()['id'] ?? 0;

$action = $_POST['action'] ?? '';

if ($action === 'create') {
    $kelasId = (int) ($_POST['kelas_id'] ?? 0);

    if ($kelasId === 0) {
        setFlash('danger', 'Kelas tidak valid.');
        redirectTo('mahasiswa/isi_krs.php');
    }

    try {
        $db->beginTransaction();

        // Ambil data kelas beserta tahun akademiknya, dan kunci baris untuk mencegah race condition kuota
        $stmtKelas = $db->prepare('
            SELECT k.id, k.kuota, k.tahun_akademik_id, ta.status
            FROM kelas k
            JOIN tahun_akademik ta ON ta.id = k.tahun_akademik_id
            WHERE k.id = :kelas_id
            FOR UPDATE
        ');
        $stmtKelas->execute([':kelas_id' => $kelasId]);
        $kelas = $stmtKelas->fetch();

        if (!$kelas || $kelas['status'] !== 'aktif') {
            $db->rollBack();
            setFlash('danger', 'Kelas tidak tersedia pada tahun akademik yang aktif.');
            redirectTo('mahasiswa/isi_krs.php');
        }

        // Cek apakah mahasiswa sudah mengambil kelas ini
        $stmtCek = $db->prepare('SELECT id FROM krs WHERE mahasiswa_id = :mahasiswa_id AND kelas_id = :kelas_id');
        $stmtCek->execute([':mahasiswa_id' => $mahasiswaId, ':kelas_id' => $kelasId]);
        if ($stmtCek->fetch()) {
            $db->rollBack();
            setFlash('danger', 'Anda sudah mengambil kelas ini sebelumnya.');
            redirectTo('mahasiswa/isi_krs.php');
        }

        // Cek kuota kelas
        $stmtJumlah = $db->prepare('SELECT COUNT(*) AS total FROM krs WHERE kelas_id = :kelas_id');
        $stmtJumlah->execute([':kelas_id' => $kelasId]);
        $jumlahPeserta = (int) $stmtJumlah->fetch()['total'];

        if ($jumlahPeserta >= (int) $kelas['kuota']) {
            $db->rollBack();
            setFlash('danger', 'Kuota kelas ini sudah penuh.');
            redirectTo('mahasiswa/isi_krs.php');
        }

        $stmtInsert = $db->prepare('
            INSERT INTO krs (mahasiswa_id, kelas_id, tahun_akademik_id, tanggal_input, status)
            VALUES (:mahasiswa_id, :kelas_id, :ta_id, CURDATE(), "disetujui")
        ');
        $stmtInsert->execute([
            ':mahasiswa_id' => $mahasiswaId,
            ':kelas_id'     => $kelasId,
            ':ta_id'        => $kelas['tahun_akademik_id'],
        ]);

        $db->commit();
        setFlash('success', 'Kelas berhasil ditambahkan ke KRS Anda.');
    } catch (PDOException $e) {
        $db->rollBack();
        error_log('Isi KRS Error: ' . $e->getMessage());
        setFlash('danger', 'Terjadi kesalahan saat menyimpan KRS.');
    }
} elseif ($action === 'delete') {
    $krsId = (int) ($_POST['krs_id'] ?? 0);

    if ($krsId === 0) {
        setFlash('danger', 'Data tidak valid.');
        redirectTo('mahasiswa/isi_krs.php');
    }

    try {
        // Pastikan KRS ini benar milik mahasiswa yang login, dan belum memiliki nilai
        $stmtCek = $db->prepare('
            SELECT k.id, (SELECT COUNT(*) FROM nilai WHERE krs_id = k.id) AS ada_nilai
            FROM krs k
            WHERE k.id = :krs_id AND k.mahasiswa_id = :mahasiswa_id
        ');
        $stmtCek->execute([':krs_id' => $krsId, ':mahasiswa_id' => $mahasiswaId]);
        $krs = $stmtCek->fetch();

        if (!$krs) {
            setFlash('danger', 'Data KRS tidak ditemukan atau bukan milik Anda.');
            redirectTo('mahasiswa/isi_krs.php');
        }

        if ((int) $krs['ada_nilai'] > 0) {
            setFlash('danger', 'Kelas ini tidak dapat dibatalkan karena sudah memiliki nilai.');
            redirectTo('mahasiswa/isi_krs.php');
        }

        $stmtHapus = $db->prepare('DELETE FROM krs WHERE id = :id');
        $stmtHapus->execute([':id' => $krsId]);

        setFlash('success', 'Kelas berhasil dibatalkan dari KRS Anda.');
    } catch (PDOException $e) {
        error_log('Batalkan KRS Error: ' . $e->getMessage());
        setFlash('danger', 'Terjadi kesalahan saat membatalkan KRS.');
    }
} else {
    setFlash('danger', 'Aksi tidak dikenali.');
}

redirectTo('mahasiswa/isi_krs.php');
