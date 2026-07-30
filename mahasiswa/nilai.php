<?php
/**
 * mahasiswa/nilai.php
 * Menampilkan transkrip nilai mahasiswa: dikelompokkan per semester
 * lengkap dengan Indeks Prestasi (IP) semester, serta IPK kumulatif
 * di bagian atas halaman.
 *
 * Tahap 21 - Hitung IPK: menambahkan perhitungan IP per semester dan
 * IPK kumulatif otomatis berdasarkan total SKS dan bobot nilai.
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
    ORDER BY ta.tahun ASC, ta.semester ASC, mk.nama_mk ASC
');
$stmt->execute([':mahasiswa_id' => $mahasiswaId]);
$semuaNilai = $stmt->fetchAll();

/**
 * Mengelompokkan daftar nilai per tahun akademik, dan menghitung
 * Indeks Prestasi (IP) untuk masing-masing semester.
 */
$nilaiPerSemester = [];
foreach ($semuaNilai as $row) {
    $key = $row['tahun'] . ' - ' . $row['semester'];
    if (!isset($nilaiPerSemester[$key])) {
        $nilaiPerSemester[$key] = [
            'tahun'    => $row['tahun'],
            'semester' => $row['semester'],
            'items'    => [],
            'sks'      => 0,
            'mutu'     => 0,
        ];
    }
    $nilaiPerSemester[$key]['items'][] = $row;
    $nilaiPerSemester[$key]['sks']  += (int) $row['sks'];
    $nilaiPerSemester[$key]['mutu'] += $row['sks'] * $row['bobot'];
}

// Hitung IP per semester serta IPK kumulatif secara progresif (menumpuk dari semester paling awal)
$totalSksKumulatif  = 0;
$totalMutuKumulatif = 0;
foreach ($nilaiPerSemester as $key => $semester) {
    $nilaiPerSemester[$key]['ip_semester'] = $semester['sks'] > 0
        ? round($semester['mutu'] / $semester['sks'], 2)
        : 0.00;

    $totalSksKumulatif  += $semester['sks'];
    $totalMutuKumulatif += $semester['mutu'];

    $nilaiPerSemester[$key]['ipk_sampai_semester_ini'] = $totalSksKumulatif > 0
        ? round($totalMutuKumulatif / $totalSksKumulatif, 2)
        : 0.00;
}

$ipkKumulatif = $totalSksKumulatif > 0 ? round($totalMutuKumulatif / $totalSksKumulatif, 2) : 0.00;

// Tampilkan dari semester terbaru ke terlama
$nilaiPerSemesterUrut = array_reverse($nilaiPerSemester, true);

$pageTitle = 'Nilai & IPK Saya';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="fw-bold mb-0">Transkrip Nilai</h5>
    <div class="text-end">
        <div class="text-muted small">IPK Kumulatif</div>
        <div class="fs-3 fw-bold text-primary"><?= number_format($ipkKumulatif, 2) ?></div>
        <div class="text-muted small">Total <?= (int) $totalSksKumulatif ?> SKS</div>
    </div>
</div>

<?php if (empty($nilaiPerSemesterUrut)): ?>
    <div class="card card-stat">
        <div class="card-body text-center text-muted py-4">
            Belum ada nilai yang tercatat.
        </div>
    </div>
<?php else: ?>
    <?php foreach ($nilaiPerSemesterUrut as $semester): ?>
        <div class="card card-stat mb-3">
            <div class="card-header bg-white d-flex flex-wrap justify-content-between align-items-center gap-2">
                <span class="fw-semibold">
                    <i class="bi bi-calendar3 me-1"></i>
                    <?= htmlspecialchars($semester['tahun'] . ' - Semester ' . $semester['semester']) ?>
                </span>
                <div>
                    <span class="badge bg-secondary me-1"><?= (int) $semester['sks'] ?> SKS</span>
                    <span class="badge bg-info text-dark me-1">IP Semester: <?= number_format($semester['ip_semester'], 2) ?></span>
                    <span class="badge bg-primary">IPK s.d. semester ini: <?= number_format($semester['ipk_sampai_semester_ini'], 2) ?></span>
                </div>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Kode</th>
                                <th>Mata Kuliah</th>
                                <th>SKS</th>
                                <th>Nilai Angka</th>
                                <th>Nilai Huruf</th>
                                <th>Bobot</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($semester['items'] as $item): ?>
                                <tr>
                                    <td><?= htmlspecialchars($item['kode_mk']) ?></td>
                                    <td><?= htmlspecialchars($item['nama_mk']) ?></td>
                                    <td><?= (int) $item['sks'] ?></td>
                                    <td><?= htmlspecialchars((string) $item['nilai_angka']) ?></td>
                                    <td><span class="badge bg-success"><?= htmlspecialchars($item['nilai_huruf']) ?></span></td>
                                    <td><?= htmlspecialchars((string) $item['bobot']) ?></td>
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
