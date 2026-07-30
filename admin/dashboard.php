<?php
/**
 * admin/dashboard.php
 * Dashboard utama untuk role Admin.
 * Menampilkan ringkasan statistik: jumlah mahasiswa, dosen,
 * mata kuliah, kelas, KRS, dan nilai.
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';

// Hanya admin yang boleh mengakses halaman ini
requireRole('admin');

$db = Database::getConnection();

// Ambil statistik jumlah data untuk setiap entitas
$stats = [
    'mahasiswa'      => (int) $db->query('SELECT COUNT(*) AS total FROM mahasiswa')->fetch()['total'],
    'dosen'          => (int) $db->query('SELECT COUNT(*) AS total FROM dosen')->fetch()['total'],
    'mata_kuliah'    => (int) $db->query('SELECT COUNT(*) AS total FROM mata_kuliah')->fetch()['total'],
    'kelas'          => (int) $db->query('SELECT COUNT(*) AS total FROM kelas')->fetch()['total'],
    'krs'            => (int) $db->query('SELECT COUNT(*) AS total FROM krs')->fetch()['total'],
    'nilai'          => (int) $db->query('SELECT COUNT(*) AS total FROM nilai')->fetch()['total'],
];

// Ambil tahun akademik yang sedang aktif
$stmtTahun = $db->query("SELECT tahun, semester FROM tahun_akademik WHERE status = 'aktif' LIMIT 1");
$tahunAktif = $stmtTahun->fetch();

// Ambil 5 aktivitas KRS terbaru (join ke mahasiswa & kelas/mata kuliah)
$stmtAktivitas = $db->query("
    SELECT m.nama AS nama_mahasiswa, mk.nama_mk, k.tanggal_input
    FROM krs k
    JOIN mahasiswa m ON m.id = k.mahasiswa_id
    JOIN kelas kl ON kl.id = k.kelas_id
    JOIN mata_kuliah mk ON mk.id = kl.mata_kuliah_id
    ORDER BY k.id DESC
    LIMIT 5
");
$aktivitasTerbaru = $stmtAktivitas->fetchAll();

$pageTitle = 'Dashboard Admin';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-1">Selamat datang, <?= htmlspecialchars(currentUserName()) ?> 👋</h4>
        <p class="text-muted mb-0">
            Tahun Akademik Aktif:
            <span class="badge bg-primary">
                <?= $tahunAktif ? htmlspecialchars($tahunAktif['tahun'] . ' - ' . $tahunAktif['semester']) : 'Belum diatur' ?>
            </span>
        </p>
    </div>
</div>

<!-- Card Statistik -->
<div class="row g-3 mb-4">
    <div class="col-6 col-md-4 col-xl-2">
        <div class="card card-stat h-100">
            <div class="card-body">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <div class="text-muted small">Mahasiswa</div>
                        <div class="fs-4 fw-bold"><?= $stats['mahasiswa'] ?></div>
                    </div>
                    <i class="bi bi-people-fill fs-1 text-primary opacity-25"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-4 col-xl-2">
        <div class="card card-stat h-100">
            <div class="card-body">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <div class="text-muted small">Dosen</div>
                        <div class="fs-4 fw-bold"><?= $stats['dosen'] ?></div>
                    </div>
                    <i class="bi bi-person-badge-fill fs-1 text-success opacity-25"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-4 col-xl-2">
        <div class="card card-stat h-100">
            <div class="card-body">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <div class="text-muted small">Mata Kuliah</div>
                        <div class="fs-4 fw-bold"><?= $stats['mata_kuliah'] ?></div>
                    </div>
                    <i class="bi bi-journal-bookmark-fill fs-1 text-warning opacity-25"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-4 col-xl-2">
        <div class="card card-stat h-100">
            <div class="card-body">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <div class="text-muted small">Kelas</div>
                        <div class="fs-4 fw-bold"><?= $stats['kelas'] ?></div>
                    </div>
                    <i class="bi bi-door-open-fill fs-1 text-info opacity-25"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-4 col-xl-2">
        <div class="card card-stat h-100">
            <div class="card-body">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <div class="text-muted small">Data KRS</div>
                        <div class="fs-4 fw-bold"><?= $stats['krs'] ?></div>
                    </div>
                    <i class="bi bi-card-checklist fs-1 text-danger opacity-25"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-4 col-xl-2">
        <div class="card card-stat h-100">
            <div class="card-body">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <div class="text-muted small">Data Nilai</div>
                        <div class="fs-4 fw-bold"><?= $stats['nilai'] ?></div>
                    </div>
                    <i class="bi bi-clipboard-data-fill fs-1 text-secondary opacity-25"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Tabel Aktivitas Terbaru -->
<div class="card card-stat">
    <div class="card-header bg-white fw-semibold">
        <i class="bi bi-clock-history me-1"></i> Aktivitas KRS Terbaru
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0 align-middle">
                <thead class="table-light">
                    <tr>
                        <th>Mahasiswa</th>
                        <th>Mata Kuliah</th>
                        <th>Tanggal Input</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($aktivitasTerbaru)): ?>
                        <tr>
                            <td colspan="3" class="text-center text-muted py-3">Belum ada data KRS.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($aktivitasTerbaru as $item): ?>
                            <tr>
                                <td><?= htmlspecialchars($item['nama_mahasiswa']) ?></td>
                                <td><?= htmlspecialchars($item['nama_mk']) ?></td>
                                <td><?= htmlspecialchars(date('d M Y', strtotime($item['tanggal_input']))) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
