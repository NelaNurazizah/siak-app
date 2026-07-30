<?php
/**
 * dosen/nilai_proses.php
 * Menangani proses input nilai (create) dan edit nilai (update) oleh dosen.
 *
 * Keamanan: setiap request diverifikasi bahwa krs_id/nilai_id yang dikirim
 * benar-benar berada pada kelas yang diampu oleh dosen yang login,
 * agar seorang dosen tidak bisa menilai atau mengubah nilai mahasiswa
 * di kelas dosen lain.
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

requireRole('dosen');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirectTo('dosen/input_nilai.php');
}

if (!verifyCsrfToken($_POST['csrf_token'] ?? null)) {
    setFlash('danger', 'Token keamanan tidak valid. Silakan coba lagi.');
    redirectTo('dosen/input_nilai.php');
}

$db = Database::getConnection();

$stmtDosen = $db->prepare('SELECT id FROM dosen WHERE user_id = :user_id');
$stmtDosen->execute([':user_id' => $_SESSION['user_id']]);
$dosenId = $stmtDosen->fetch()['id'] ?? 0;

$action = $_POST['action'] ?? '';

if ($action === 'create') {
    $krsId      = (int) ($_POST['krs_id'] ?? 0);
    $nilaiAngka = $_POST['nilai_angka'] ?? '';

    if ($krsId === 0 || $nilaiAngka === '' || !is_numeric($nilaiAngka) || $nilaiAngka < 0 || $nilaiAngka > 100) {
        setFlash('danger', 'Nilai wajib diisi dengan angka antara 0-100.');
        redirectTo('dosen/input_nilai.php');
    }

    $nilaiAngka = round((float) $nilaiAngka, 2);

    try {
        // Pastikan krs_id ini benar milik kelas yang diampu dosen yang login
        $stmtCek = $db->prepare('
            SELECT k.id
            FROM krs k
            JOIN kelas kl ON kl.id = k.kelas_id
            WHERE k.id = :krs_id AND kl.dosen_id = :dosen_id
        ');
        $stmtCek->execute([':krs_id' => $krsId, ':dosen_id' => $dosenId]);
        if (!$stmtCek->fetch()) {
            setFlash('danger', 'Data KRS tidak ditemukan atau bukan mahasiswa di kelas yang Anda ampu.');
            redirectTo('dosen/input_nilai.php');
        }

        // Cek apakah nilai untuk KRS ini sudah pernah diinput sebelumnya
        $stmtCekNilai = $db->prepare('SELECT id FROM nilai WHERE krs_id = :krs_id');
        $stmtCekNilai->execute([':krs_id' => $krsId]);
        if ($stmtCekNilai->fetch()) {
            setFlash('danger', 'Nilai untuk mahasiswa ini sudah pernah diinput. Silakan gunakan menu Edit Nilai.');
            redirectTo('dosen/input_nilai.php');
        }

        [$nilaiHuruf, $bobot] = hitungNilaiHuruf($nilaiAngka);

        $stmt = $db->prepare('
            INSERT INTO nilai (krs_id, nilai_angka, nilai_huruf, bobot)
            VALUES (:krs_id, :nilai_angka, :nilai_huruf, :bobot)
        ');
        $stmt->execute([
            ':krs_id'      => $krsId,
            ':nilai_angka' => $nilaiAngka,
            ':nilai_huruf' => $nilaiHuruf,
            ':bobot'       => $bobot,
        ]);

        setFlash('success', "Nilai berhasil disimpan ($nilaiHuruf).");
    } catch (PDOException $e) {
        error_log('Input Nilai Error: ' . $e->getMessage());
        setFlash('danger', 'Terjadi kesalahan saat menyimpan nilai.');
    }

    redirectTo('dosen/input_nilai.php');
}

if ($action === 'update') {
    $nilaiId    = (int) ($_POST['nilai_id'] ?? 0);
    $nilaiAngka = $_POST['nilai_angka'] ?? '';

    if ($nilaiId === 0 || $nilaiAngka === '' || !is_numeric($nilaiAngka) || $nilaiAngka < 0 || $nilaiAngka > 100) {
        setFlash('danger', 'Nilai wajib diisi dengan angka antara 0-100.');
        redirectTo('dosen/nilai.php');
    }

    $nilaiAngka = round((float) $nilaiAngka, 2);

    try {
        // Pastikan baris nilai ini milik mahasiswa di kelas yang diampu dosen yang login
        $stmtCek = $db->prepare('
            SELECT n.id
            FROM nilai n
            JOIN krs k ON k.id = n.krs_id
            JOIN kelas kl ON kl.id = k.kelas_id
            WHERE n.id = :nilai_id AND kl.dosen_id = :dosen_id
        ');
        $stmtCek->execute([':nilai_id' => $nilaiId, ':dosen_id' => $dosenId]);
        if (!$stmtCek->fetch()) {
            setFlash('danger', 'Data nilai tidak ditemukan atau bukan wewenang Anda.');
            redirectTo('dosen/nilai.php');
        }

        [$nilaiHuruf, $bobot] = hitungNilaiHuruf($nilaiAngka);

        $stmt = $db->prepare('
            UPDATE nilai
            SET nilai_angka = :nilai_angka, nilai_huruf = :nilai_huruf, bobot = :bobot
            WHERE id = :id
        ');
        $stmt->execute([
            ':nilai_angka' => $nilaiAngka,
            ':nilai_huruf' => $nilaiHuruf,
            ':bobot'       => $bobot,
            ':id'          => $nilaiId,
        ]);

        setFlash('success', "Nilai berhasil diperbarui ($nilaiHuruf).");
    } catch (PDOException $e) {
        error_log('Update Nilai Error: ' . $e->getMessage());
        setFlash('danger', 'Terjadi kesalahan saat memperbarui nilai.');
    }

    redirectTo('dosen/nilai.php');
}

setFlash('danger', 'Aksi tidak dikenali.');
redirectTo('dosen/input_nilai.php');
