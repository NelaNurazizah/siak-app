<?php
/**
 * dosen/dashboard.php
 * Dashboard utama untuk role Dosen.
 * Menampilkan ringkasan: jumlah kelas yang diampu, jumlah mahasiswa,
 * dan progres input nilai pada tahun akademik yang sedang aktif.
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';

requireRole('dosen');

$db = Database::getConnection();

// Ambil id dosen berdasarkan user_id yang sedang login
$stmtDosen = $db->prepare('SELECT id, nidn FROM dosen WHERE user_id = :user_id');
$stmtDosen->execute([':user_id' => $_SESSION['user_id']]);
$dosen = $stmtDosen->fetch();
$dosenId = $dosen['id'] ?? 0;

// Jumlah kelas yang diampu
$stmtKelas = $db->prepare('SELECT COUNT(*) AS total FROM kelas WHERE dosen_id = :dosen_id');
$stmtKelas->execute([':dosen_id' => $dosenId]);
$totalKelas = (int) $stmtKelas->fetch()['total'];

// Optimasi: jumlah mahasiswa unik dan progres nilai sama-sama berasal dari
// tabel krs+kelas dengan filter dosen_id yang sama, jadi digabung jadi 1 query
// (sebelumnya 2 query terpisah).
$stmtRingkasan = $db->prepare('
    SELECT
        COUNT(DISTINCT k.mahasiswa_id) AS total_mahasiswa,
        COUNT(k.id) AS total_krs,
        SUM(CASE WHEN n.id IS NOT NULL THEN 1 ELSE 0 END) AS sudah_dinilai
    FROM krs k
    JOIN kelas kl ON kl.id = k.kelas_id
    LEFT JOIN nilai n ON n.krs_id = k.id
    WHERE kl.dosen_id = :dosen_id
');
$stmtRingkasan->execute([':dosen_id' => $dosenId]);
$ringkasan = $stmtRingkasan->fetch();

$totalMahasiswa = (int) ($ringkasan['total_mahasiswa'] ?? 0);
$totalKrs = (int) ($ringkasan['total_krs'] ?? 0);
$sudahDinilai = (int) ($ringkasan['sudah_dinilai'] ?? 0);
$belumDinilai = $totalKrs - $sudahDinilai;
$persenProgres = $totalKrs > 0 ? round(($sudahDinilai / $totalKrs) * 100) : 0;

// Daftar kelas yang diampu beserta jumlah peserta
$stmtDaftarKelas = $db->prepare('
    SELECT k.id, k.nama_kelas, k.hari, k.jam, mk.kode_mk, mk.nama_mk,
           (SELECT COUNT(*) FROM krs WHERE kelas_id = k.id) AS jumlah_peserta
    FROM kelas k
    JOIN mata_kuliah mk ON mk.id = k.mata_kuliah_id
    WHERE k.dosen_id = :dosen_id
    ORDER BY mk.nama_mk ASC
');
$stmtDaftarKelas->execute([':dosen_id' => $dosenId]);
$daftarKelas = $stmtDaftarKelas->fetchAll();

$pageTitle = 'Dashboard Dosen';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="mb-4">
    <h4 class="fw-bold mb-1">Selamat datang, <?= htmlspecialchars(currentUserName()) ?> 👋</h4>
    <p class="text-muted mb-0">NIDN: <?= htmlspecialchars($dosen['nidn'] ?? '-') ?></p>
</div>

<div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
        <div class="card card-stat h-100">
            <div class="card-body d-flex align-items-center justify-content-between">
                <div>
                    <div class="text-muted small">Kelas Diampu</div>
                    <div class="fs-4 fw-bold"><?= $totalKelas ?></div>
                </div>
                <i class="bi bi-door-open-fill fs-1 text-primary opacity-25"></i>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card card-stat h-100">
            <div class="card-body d-flex align-items-center justify-content-between">
                <div>
                    <div class="text-muted small">Total Mahasiswa</div>
                    <div class="fs-4 fw-bold"><?= $totalMahasiswa ?></div>
                </div>
                <i class="bi bi-people-fill fs-1 text-success opacity-25"></i>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card card-stat h-100">
            <div class="card-body d-flex align-items-center justify-content-between">
                <div>
                    <div class="text-muted small">Sudah Dinilai</div>
                    <div class="fs-4 fw-bold text-success"><?= $sudahDinilai ?></div>
                </div>
                <i class="bi bi-check-circle-fill fs-1 text-success opacity-25"></i>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card card-stat h-100">
            <div class="card-body d-flex align-items-center justify-content-between">
                <div>
                    <div class="text-muted small">Belum Dinilai</div>
                    <div class="fs-4 fw-bold text-danger"><?= $belumDinilai ?></div>
                </div>
                <i class="bi bi-exclamation-circle-fill fs-1 text-danger opacity-25"></i>
            </div>
        </div>
    </div>
</div>

<div class="card card-stat mb-4">
    <div class="card-body">
        <div class="d-flex justify-content-between mb-1">
            <span class="fw-semibold">Progres Input Nilai</span>
            <span class="text-muted"><?= $sudahDinilai ?> / <?= $totalKrs ?> (<?= $persenProgres ?>%)</span>
        </div>
        <div class="progress" style="height: 10px;">
            <div class="progress-bar bg-success" role="progressbar" style="width: <?= $persenProgres ?>%;" aria-valuenow="<?= $persenProgres ?>" aria-valuemin="0" aria-valuemax="100"></div>
        </div>
    </div>
</div>

<div class="card card-stat">
    <div class="card-header bg-white fw-semibold">
        <i class="bi bi-journal-bookmark-fill me-1"></i> Kelas yang Diampu
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0 align-middle">
                <thead class="table-light">
                    <tr>
                        <th>Mata Kuliah</th>
                        <th>Kelas</th>
                        <th>Jadwal</th>
                        <th>Jumlah Peserta</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($daftarKelas)): ?>
                        <tr>
                            <td colspan="4" class="text-center text-muted py-3">Anda belum mengampu kelas apapun.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($daftarKelas as $k): ?>
                            <tr>
                                <td><?= htmlspecialchars($k['kode_mk'] . ' - ' . $k['nama_mk']) ?></td>
                                <td><span class="badge bg-primary"><?= htmlspecialchars($k['nama_kelas']) ?></span></td>
                                <td><?= htmlspecialchars(($k['hari'] ?? '-') . ' ' . ($k['jam'] ?? '')) ?></td>
                                <td><?= (int) $k['jumlah_peserta'] ?> mahasiswa</td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
