<?php
/**
 * dosen/mata_kuliah.php
 * Menampilkan daftar mata kuliah (kelas) yang diampu oleh dosen yang login.
 * Setiap baris memiliki tombol untuk melihat daftar mahasiswa pesertanya.
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';

requireRole('dosen');

$db = Database::getConnection();

$stmtDosen = $db->prepare('SELECT id FROM dosen WHERE user_id = :user_id');
$stmtDosen->execute([':user_id' => $_SESSION['user_id']]);
$dosenId = $stmtDosen->fetch()['id'] ?? 0;

$stmt = $db->prepare('
    SELECT k.id, k.nama_kelas, k.hari, k.jam, k.ruang, k.kuota,
           mk.kode_mk, mk.nama_mk, mk.sks,
           ta.tahun, ta.semester,
           (SELECT COUNT(*) FROM krs WHERE kelas_id = k.id) AS jumlah_peserta
    FROM kelas k
    JOIN mata_kuliah mk ON mk.id = k.mata_kuliah_id
    JOIN tahun_akademik ta ON ta.id = k.tahun_akademik_id
    WHERE k.dosen_id = :dosen_id
    ORDER BY ta.tahun DESC, mk.nama_mk ASC
');
$stmt->execute([':dosen_id' => $dosenId]);
$daftarKelas = $stmt->fetchAll();

$pageTitle = 'Mata Kuliah Diampu';
require_once __DIR__ . '/../includes/header.php';
?>

<h5 class="fw-bold mb-3">Mata Kuliah yang Diampu</h5>

<div class="row g-3">
    <?php if (empty($daftarKelas)): ?>
        <div class="col-12">
            <div class="card card-stat">
                <div class="card-body text-center text-muted py-4">
                    Anda belum ditugaskan mengampu kelas apapun. Silakan hubungi Admin.
                </div>
            </div>
        </div>
    <?php else: ?>
        <?php foreach ($daftarKelas as $k): ?>
            <div class="col-md-6 col-xl-4">
                <div class="card card-stat h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <span class="badge bg-secondary"><?= htmlspecialchars($k['kode_mk']) ?></span>
                            <span class="badge bg-primary">Kelas <?= htmlspecialchars($k['nama_kelas']) ?></span>
                        </div>
                        <h6 class="fw-bold"><?= htmlspecialchars($k['nama_mk']) ?></h6>
                        <p class="text-muted small mb-2">
                            <i class="bi bi-clock me-1"></i><?= htmlspecialchars(($k['hari'] ?? '-') . ' ' . ($k['jam'] ?? '')) ?><br>
                            <i class="bi bi-geo-alt me-1"></i><?= htmlspecialchars($k['ruang'] ?? '-') ?><br>
                            <i class="bi bi-calendar3 me-1"></i><?= htmlspecialchars($k['tahun'] . ' - ' . $k['semester']) ?><br>
                            <i class="bi bi-award me-1"></i><?= (int) $k['sks'] ?> SKS
                        </p>
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="small text-muted"><?= (int) $k['jumlah_peserta'] ?> / <?= (int) $k['kuota'] ?> mahasiswa</span>
                            <a href="<?= BASE_URL ?>dosen/mahasiswa.php?kelas_id=<?= (int) $k['id'] ?>" class="btn btn-sm btn-outline-primary">
                                <i class="bi bi-people me-1"></i> Lihat Mahasiswa
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
