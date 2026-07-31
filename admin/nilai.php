<?php
/**
 * admin/nilai.php
 * Halaman bagi Admin untuk melihat SELURUH data nilai di sistem (read-only),
 * lintas mahasiswa, dosen, dan tahun akademik. Dilengkapi pencarian dan
 * pagination, konsisten dengan halaman listing Admin lainnya.
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

requireRole('admin');

$db = Database::getConnection();

$keyword = cleanInput($_GET['q'] ?? '');

$perHalaman = 10;
$halaman = max(1, (int) ($_GET['page'] ?? 1));
$offset = ($halaman - 1) * $perHalaman;

$whereClause = '';
$params = [];
if ($keyword !== '') {
    $whereClause = ' WHERE m.nim LIKE :keyword1 OR m.nama LIKE :keyword2 OR mk.nama_mk LIKE :keyword3 OR d.nama LIKE :keyword4';
    $params[':keyword1'] = '%' . $keyword . '%';
    $params[':keyword2'] = '%' . $keyword . '%';
    $params[':keyword3'] = '%' . $keyword . '%';
    $params[':keyword4'] = '%' . $keyword . '%';
}

$sqlCount = "
    SELECT COUNT(*) AS total
    FROM nilai n
    JOIN krs k ON k.id = n.krs_id
    JOIN mahasiswa m ON m.id = k.mahasiswa_id
    JOIN kelas kl ON kl.id = k.kelas_id
    JOIN mata_kuliah mk ON mk.id = kl.mata_kuliah_id
    JOIN dosen d ON d.id = kl.dosen_id
" . $whereClause;
$stmtCount = $db->prepare($sqlCount);
$stmtCount->execute($params);
$totalData = (int) $stmtCount->fetch()['total'];
$totalHalaman = (int) ceil($totalData / $perHalaman);

if ($totalHalaman > 0 && $halaman > $totalHalaman) {
    $halaman = $totalHalaman;
    $offset = ($halaman - 1) * $perHalaman;
}

$sql = "
    SELECT n.id, n.nilai_angka, n.nilai_huruf, n.bobot,
           m.nim, m.nama AS nama_mahasiswa,
           mk.kode_mk, mk.nama_mk, mk.sks, kl.nama_kelas,
           d.nama AS nama_dosen, ta.tahun, ta.semester
    FROM nilai n
    JOIN krs k ON k.id = n.krs_id
    JOIN mahasiswa m ON m.id = k.mahasiswa_id
    JOIN kelas kl ON kl.id = k.kelas_id
    JOIN mata_kuliah mk ON mk.id = kl.mata_kuliah_id
    JOIN dosen d ON d.id = kl.dosen_id
    JOIN tahun_akademik ta ON ta.id = k.tahun_akademik_id
" . $whereClause . ' ORDER BY n.id DESC LIMIT :limit OFFSET :offset';

$stmt = $db->prepare($sql);
foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value);
}
$stmt->bindValue(':limit', $perHalaman, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$daftarNilai = $stmt->fetchAll();

$pageTitle = 'Data Nilai';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
    <h5 class="fw-bold mb-0">Data Nilai (Seluruh Mahasiswa)</h5>
</div>

<form action="" method="GET" class="mb-3">
    <div class="input-group" style="max-width: 400px;">
        <span class="input-group-text bg-white"><i class="bi bi-search"></i></span>
        <input type="text" name="q" class="form-control" placeholder="Cari NIM, nama, mata kuliah, atau dosen..." value="<?= htmlspecialchars($keyword) ?>">
        <?php if ($keyword !== ''): ?>
            <a href="<?= BASE_URL ?>admin/nilai.php" class="btn btn-outline-secondary">
                <i class="bi bi-x-lg"></i>
            </a>
        <?php endif; ?>
        <button type="submit" class="btn btn-outline-primary">Cari</button>
    </div>
</form>

<div class="card card-stat">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th style="width: 40px;">#</th>
                        <th>NIM</th>
                        <th>Mahasiswa</th>
                        <th>Mata Kuliah</th>
                        <th>Dosen</th>
                        <th>Tahun Akademik</th>
                        <th>Nilai Angka</th>
                        <th>Nilai Huruf</th>
                        <th>Bobot</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($daftarNilai)): ?>
                        <tr>
                            <td colspan="9" class="text-center text-muted py-3">Belum ada data nilai.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($daftarNilai as $i => $n): ?>
                            <tr>
                                <td><?= $offset + $i + 1 ?></td>
                                <td><?= htmlspecialchars($n['nim']) ?></td>
                                <td><?= htmlspecialchars($n['nama_mahasiswa']) ?></td>
                                <td><?= htmlspecialchars($n['kode_mk'] . ' - ' . $n['nama_mk']) ?></td>
                                <td><?= htmlspecialchars($n['nama_dosen']) ?></td>
                                <td><?= htmlspecialchars($n['tahun'] . ' - ' . $n['semester']) ?></td>
                                <td><?= htmlspecialchars((string) $n['nilai_angka']) ?></td>
                                <td><span class="badge bg-success"><?= htmlspecialchars($n['nilai_huruf']) ?></span></td>
                                <td><?= htmlspecialchars((string) $n['bobot']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php if ($totalHalaman > 1): ?>
    <div class="card-footer bg-white">
        <?= renderPagination($halaman, $totalHalaman, 'admin/nilai.php', $keyword !== '' ? ['q' => $keyword] : []) ?>
    </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
