<?php
/**
 * mahasiswa/nilai.php
 * Menampilkan seluruh nilai mahasiswa yang login, diurutkan dari
 * tahun akademik terbaru. Perhitungan IPK kumulatif secara detail
 * akan ditambahkan pada Tahap 21 - Hitung IPK.
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
    SELECT ta.tahun, ta.semester, mk.kode_mk, mk.nama_mk, mk.sks,
           n.nilai_angka, n.nilai_huruf, n.bobot
    FROM nilai n
    JOIN krs k ON k.id = n.krs_id
    JOIN kelas kl ON kl.id = k.kelas_id
    JOIN mata_kuliah mk ON mk.id = kl.mata_kuliah_id
    JOIN tahun_akademik ta ON ta.id = k.tahun_akademik_id
    WHERE k.mahasiswa_id = :mahasiswa_id
    ORDER BY ta.tahun DESC, ta.semester ASC, mk.nama_mk ASC
');
$stmt->execute([':mahasiswa_id' => $mahasiswaId]);
$daftarNilai = $stmt->fetchAll();

$totalSks  = array_sum(array_column($daftarNilai, 'sks'));
$totalMutu = 0;
foreach ($daftarNilai as $n) {
    $totalMutu += $n['sks'] * $n['bobot'];
}
$ipk = $totalSks > 0 ? round($totalMutu / $totalSks, 2) : 0.00;

$pageTitle = 'Nilai Saya';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="fw-bold mb-0">Nilai Saya</h5>
    <div class="text-end">
        <div class="text-muted small">IPK Kumulatif</div>
        <div class="fs-4 fw-bold text-primary"><?= number_format($ipk, 2) ?></div>
    </div>
</div>

<div class="card card-stat">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Tahun Akademik</th>
                        <th>Kode</th>
                        <th>Mata Kuliah</th>
                        <th>SKS</th>
                        <th>Nilai Angka</th>
                        <th>Nilai Huruf</th>
                        <th>Bobot</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($daftarNilai)): ?>
                        <tr>
                            <td colspan="7" class="text-center text-muted py-3">Belum ada nilai yang tercatat.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($daftarNilai as $n): ?>
                            <tr>
                                <td><?= htmlspecialchars($n['tahun'] . ' - ' . $n['semester']) ?></td>
                                <td><?= htmlspecialchars($n['kode_mk']) ?></td>
                                <td><?= htmlspecialchars($n['nama_mk']) ?></td>
                                <td><?= (int) $n['sks'] ?></td>
                                <td><?= htmlspecialchars((string) $n['nilai_angka']) ?></td>
                                <td><span class="badge bg-success"><?= htmlspecialchars($n['nilai_huruf']) ?></span></td>
                                <td><?= htmlspecialchars((string) $n['bobot']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
                <?php if (!empty($daftarNilai)): ?>
                <tfoot>
                    <tr class="table-light fw-semibold">
                        <td colspan="3" class="text-end">Total</td>
                        <td><?= (int) $totalSks ?></td>
                        <td colspan="3">IPK: <?= number_format($ipk, 2) ?></td>
                    </tr>
                </tfoot>
                <?php endif; ?>
            </table>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
