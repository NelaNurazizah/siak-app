<?php
/**
 * mahasiswa/dashboard.php
 * Dashboard utama untuk role Mahasiswa.
 * Menampilkan profil singkat, ringkasan IPK sementara, total SKS lulus,
 * dan status pengisian KRS pada tahun akademik aktif.
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';

requireRole('mahasiswa');

$db = Database::getConnection();

$stmtMhs = $db->prepare('SELECT id, nim, nama, angkatan FROM mahasiswa WHERE user_id = :user_id');
$stmtMhs->execute([':user_id' => $_SESSION['user_id']]);
$mahasiswa = $stmtMhs->fetch();
$mahasiswaId = $mahasiswa['id'] ?? 0;

// Tahun akademik yang sedang aktif
$tahunAktif = $db->query("SELECT id, tahun, semester FROM tahun_akademik WHERE status = 'aktif' LIMIT 1")->fetch();

// Hitung IPK sementara & total SKS yang sudah dinilai (seluruh semester)
$stmtIpk = $db->prepare('
    SELECT
        COALESCE(SUM(mk.sks * n.bobot), 0) AS total_mutu,
        COALESCE(SUM(mk.sks), 0) AS total_sks
    FROM krs k
    JOIN kelas kl ON kl.id = k.kelas_id
    JOIN mata_kuliah mk ON mk.id = kl.mata_kuliah_id
    JOIN nilai n ON n.krs_id = k.id
    WHERE k.mahasiswa_id = :mahasiswa_id
');
$stmtIpk->execute([':mahasiswa_id' => $mahasiswaId]);
$hasilIpk = $stmtIpk->fetch();
$totalSks = (int) $hasilIpk['total_sks'];
$ipk = $totalSks > 0 ? round($hasilIpk['total_mutu'] / $totalSks, 2) : 0.00;

// Status KRS pada tahun akademik aktif
$jumlahKrsAktif = 0;
if ($tahunAktif) {
    $stmtKrsAktif = $db->prepare('SELECT COUNT(*) AS total FROM krs WHERE mahasiswa_id = :mahasiswa_id AND tahun_akademik_id = :ta_id');
    $stmtKrsAktif->execute([':mahasiswa_id' => $mahasiswaId, ':ta_id' => $tahunAktif['id']]);
    $jumlahKrsAktif = (int) $stmtKrsAktif->fetch()['total'];
}

$pageTitle = 'Dashboard Mahasiswa';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="mb-4">
    <h4 class="fw-bold mb-1">Selamat datang, <?= htmlspecialchars(currentUserName()) ?> 👋</h4>
    <p class="text-muted mb-0">
        NIM: <?= htmlspecialchars($mahasiswa['nim'] ?? '-') ?> &bull; Angkatan: <?= htmlspecialchars((string) ($mahasiswa['angkatan'] ?? '-')) ?>
    </p>
</div>

<div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
        <div class="card card-stat h-100">
            <div class="card-body d-flex align-items-center justify-content-between">
                <div>
                    <div class="text-muted small">IPK Sementara</div>
                    <div class="fs-4 fw-bold text-primary"><?= number_format($ipk, 2) ?></div>
                </div>
                <i class="bi bi-graph-up-arrow fs-1 text-primary opacity-25"></i>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card card-stat h-100">
            <div class="card-body d-flex align-items-center justify-content-between">
                <div>
                    <div class="text-muted small">Total SKS Lulus</div>
                    <div class="fs-4 fw-bold"><?= $totalSks ?></div>
                </div>
                <i class="bi bi-journal-check fs-1 text-success opacity-25"></i>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card card-stat h-100">
            <div class="card-body d-flex align-items-center justify-content-between">
                <div>
                    <div class="text-muted small">KRS Tahun Aktif</div>
                    <div class="fs-4 fw-bold"><?= $jumlahKrsAktif ?></div>
                </div>
                <i class="bi bi-card-checklist fs-1 text-warning opacity-25"></i>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card card-stat h-100">
            <div class="card-body">
                <div class="text-muted small mb-1">Tahun Akademik Aktif</div>
                <div class="fw-bold">
                    <?= $tahunAktif ? htmlspecialchars($tahunAktif['tahun'] . ' - ' . $tahunAktif['semester']) : 'Belum diatur' ?>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card card-stat">
    <div class="card-body">
        <?php if ($tahunAktif && $jumlahKrsAktif === 0): ?>
            <div class="alert alert-warning mb-0 d-flex align-items-center">
                <i class="bi bi-exclamation-triangle-fill me-2 fs-5"></i>
                <div>
                    Anda belum mengisi KRS untuk tahun akademik <strong><?= htmlspecialchars($tahunAktif['tahun'] . ' - ' . $tahunAktif['semester']) ?></strong>.
                    <a href="<?= BASE_URL ?>mahasiswa/isi_krs.php" class="alert-link">Isi KRS sekarang</a>.
                </div>
            </div>
        <?php elseif ($tahunAktif): ?>
            <div class="alert alert-success mb-0 d-flex align-items-center">
                <i class="bi bi-check-circle-fill me-2 fs-5"></i>
                <div>
                    Anda sudah mengambil <strong><?= $jumlahKrsAktif ?></strong> mata kuliah pada tahun akademik aktif.
                    <a href="<?= BASE_URL ?>mahasiswa/krs.php" class="alert-link">Lihat KRS</a>.
                </div>
            </div>
        <?php else: ?>
            <div class="alert alert-secondary mb-0">
                Belum ada tahun akademik yang diatur sebagai aktif. Silakan hubungi Admin.
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
