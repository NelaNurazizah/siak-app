<?php
/**
 * mahasiswa/krs.php
 * Menampilkan riwayat KRS mahasiswa di seluruh tahun akademik (bukan hanya
 * yang aktif), dikelompokkan per tahun akademik. Halaman ini bersifat
 * read-only (untuk mengubah/mengambil KRS, lihat mahasiswa/isi_krs.php).
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';

requireRole('mahasiswa');

$db = Database::getConnection();

$stmtMhs = $db->prepare('SELECT id FROM mahasiswa WHERE user_id = :user_id');
$stmtMhs->execute([':user_id' => $_SESSION['user_id']]);
$mahasiswaId = $stmtMhs->fetch()['id'] ?? 0;

$stmt = $db->prepare('
    SELECT ta.id AS ta_id, ta.tahun, ta.semester,
           mk.kode_mk, mk.nama_mk, mk.sks, kl.nama_kelas, d.nama AS nama_dosen,
           n.nilai_huruf
    FROM krs k
    JOIN kelas kl ON kl.id = k.kelas_id
    JOIN mata_kuliah mk ON mk.id = kl.mata_kuliah_id
    JOIN dosen d ON d.id = kl.dosen_id
    JOIN tahun_akademik ta ON ta.id = k.tahun_akademik_id
    LEFT JOIN nilai n ON n.krs_id = k.id
    WHERE k.mahasiswa_id = :mahasiswa_id
    ORDER BY ta.tahun DESC, ta.semester ASC, mk.nama_mk ASC
');
$stmt->execute([':mahasiswa_id' => $mahasiswaId]);
$semuaKrs = $stmt->fetchAll();

// Kelompokkan data per tahun akademik agar mudah ditampilkan per semester
$krsPerTahun = [];
foreach ($semuaKrs as $row) {
    $key = $row['tahun'] . ' - ' . $row['semester'];
    $krsPerTahun[$key]['info'] = ['tahun' => $row['tahun'], 'semester' => $row['semester']];
    $krsPerTahun[$key]['items'][] = $row;
}

$pageTitle = 'KRS Saya';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="fw-bold mb-0">Riwayat KRS</h5>
    <a href="<?= BASE_URL ?>mahasiswa/isi_krs.php" class="btn btn-primary btn-sm">
        <i class="bi bi-pencil-square me-1"></i> Isi/Ubah KRS
    </a>
</div>

<?php if (empty($krsPerTahun)): ?>
    <div class="card card-stat">
        <div class="card-body text-center text-muted py-4">
            Anda belum memiliki data KRS pada semester manapun.
        </div>
    </div>
<?php else: ?>
    <?php foreach ($krsPerTahun as $kelompok): ?>
        <?php $totalSksSemester = array_sum(array_column($kelompok['items'], 'sks')); ?>
        <div class="card card-stat mb-3">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <span class="fw-semibold">
                    <i class="bi bi-calendar3 me-1"></i>
                    <?= htmlspecialchars($kelompok['info']['tahun'] . ' - Semester ' . $kelompok['info']['semester']) ?>
                </span>
                <span class="badge bg-secondary"><?= (int) $totalSksSemester ?> SKS</span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Kode</th>
                                <th>Mata Kuliah</th>
                                <th>Kelas</th>
                                <th>Dosen</th>
                                <th>SKS</th>
                                <th>Nilai</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($kelompok['items'] as $item): ?>
                                <tr>
                                    <td><?= htmlspecialchars($item['kode_mk']) ?></td>
                                    <td><?= htmlspecialchars($item['nama_mk']) ?></td>
                                    <td><span class="badge bg-primary"><?= htmlspecialchars($item['nama_kelas']) ?></span></td>
                                    <td><?= htmlspecialchars($item['nama_dosen']) ?></td>
                                    <td><?= (int) $item['sks'] ?></td>
                                    <td>
                                        <?php if ($item['nilai_huruf']): ?>
                                            <span class="badge bg-success"><?= htmlspecialchars($item['nilai_huruf']) ?></span>
                                        <?php else: ?>
                                            <span class="badge bg-warning text-dark">Belum Ada</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
