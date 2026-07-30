<?php
/**
 * dosen/mahasiswa.php
 * Menampilkan daftar mahasiswa peserta suatu kelas (?kelas_id=..).
 * Memverifikasi bahwa kelas tersebut benar-benar diampu oleh dosen yang login
 * (mencegah akses ke kelas dosen lain / IDOR).
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

requireRole('dosen');

$db = Database::getConnection();

$stmtDosen = $db->prepare('SELECT id FROM dosen WHERE user_id = :user_id');
$stmtDosen->execute([':user_id' => $_SESSION['user_id']]);
$dosenId = $stmtDosen->fetch()['id'] ?? 0;

$kelasId = (int) ($_GET['kelas_id'] ?? 0);

// Pastikan kelas yang diminta memang milik dosen ini
$stmtKelas = $db->prepare('
    SELECT k.id, k.nama_kelas, mk.kode_mk, mk.nama_mk
    FROM kelas k
    JOIN mata_kuliah mk ON mk.id = k.mata_kuliah_id
    WHERE k.id = :kelas_id AND k.dosen_id = :dosen_id
');
$stmtKelas->execute([':kelas_id' => $kelasId, ':dosen_id' => $dosenId]);
$kelas = $stmtKelas->fetch();

if (!$kelas) {
    setFlash('danger', 'Kelas tidak ditemukan atau bukan kelas yang Anda ampu.');
    redirectTo('dosen/mata_kuliah.php');
}

// Ambil daftar mahasiswa peserta kelas ini beserta status nilainya
$stmtMhs = $db->prepare('
    SELECT m.nim, m.nama, m.jenis_kelamin, k.id AS krs_id, n.nilai_huruf
    FROM krs k
    JOIN mahasiswa m ON m.id = k.mahasiswa_id
    LEFT JOIN nilai n ON n.krs_id = k.id
    WHERE k.kelas_id = :kelas_id
    ORDER BY m.nama ASC
');
$stmtMhs->execute([':kelas_id' => $kelasId]);
$daftarMahasiswa = $stmtMhs->fetchAll();

$pageTitle = 'Mahasiswa - ' . $kelas['nama_mk'];
require_once __DIR__ . '/../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h5 class="fw-bold mb-1">Mahasiswa Kelas <?= htmlspecialchars($kelas['nama_kelas']) ?></h5>
        <p class="text-muted mb-0"><?= htmlspecialchars($kelas['kode_mk'] . ' - ' . $kelas['nama_mk']) ?></p>
    </div>
    <a href="<?= BASE_URL ?>dosen/mata_kuliah.php" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left me-1"></i> Kembali
    </a>
</div>

<div class="card card-stat">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th style="width: 40px;">#</th>
                        <th>NIM</th>
                        <th>Nama</th>
                        <th>Jenis Kelamin</th>
                        <th>Status Nilai</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($daftarMahasiswa)): ?>
                        <tr>
                            <td colspan="5" class="text-center text-muted py-3">Belum ada mahasiswa yang mengambil kelas ini.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($daftarMahasiswa as $i => $m): ?>
                            <tr>
                                <td><?= $i + 1 ?></td>
                                <td><?= htmlspecialchars($m['nim']) ?></td>
                                <td><?= htmlspecialchars($m['nama']) ?></td>
                                <td><?= $m['jenis_kelamin'] === 'L' ? 'Laki-laki' : 'Perempuan' ?></td>
                                <td>
                                    <?php if ($m['nilai_huruf']): ?>
                                        <span class="badge bg-success">Sudah Dinilai (<?= htmlspecialchars($m['nilai_huruf']) ?>)</span>
                                    <?php else: ?>
                                        <span class="badge bg-warning text-dark">Belum Dinilai</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
