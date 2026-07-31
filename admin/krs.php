<?php
/**
 * admin/krs.php
 * Halaman bagi Admin untuk melihat SELURUH data KRS di sistem (read-only),
 * lintas mahasiswa dan tahun akademik. Dilengkapi pencarian dan pagination,
 * konsisten dengan halaman listing Admin lainnya.
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
    $whereClause = ' WHERE m.nim LIKE :keyword1 OR m.nama LIKE :keyword2 OR mk.nama_mk LIKE :keyword3';
    $params[':keyword1'] = '%' . $keyword . '%';
    $params[':keyword2'] = '%' . $keyword . '%';
    $params[':keyword3'] = '%' . $keyword . '%';
}

$sqlCount = "
    SELECT COUNT(*) AS total
    FROM krs k
    JOIN mahasiswa m ON m.id = k.mahasiswa_id
    JOIN kelas kl ON kl.id = k.kelas_id
    JOIN mata_kuliah mk ON mk.id = kl.mata_kuliah_id
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
    SELECT k.id, k.tanggal_input, k.status,
           m.nim, m.nama AS nama_mahasiswa,
           mk.kode_mk, mk.nama_mk, kl.nama_kelas,
           d.nama AS nama_dosen, ta.tahun, ta.semester,
           n.nilai_huruf
    FROM krs k
    JOIN mahasiswa m ON m.id = k.mahasiswa_id
    JOIN kelas kl ON kl.id = k.kelas_id
    JOIN mata_kuliah mk ON mk.id = kl.mata_kuliah_id
    JOIN dosen d ON d.id = kl.dosen_id
    JOIN tahun_akademik ta ON ta.id = k.tahun_akademik_id
    LEFT JOIN nilai n ON n.krs_id = k.id
" . $whereClause . ' ORDER BY k.id DESC LIMIT :limit OFFSET :offset';

$stmt = $db->prepare($sql);
foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value);
}
$stmt->bindValue(':limit', $perHalaman, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$daftarKrs = $stmt->fetchAll();

$pageTitle = 'Data KRS';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
    <h5 class="fw-bold mb-0">Data KRS (Seluruh Mahasiswa)</h5>
</div>

<form action="" method="GET" class="mb-3">
    <div class="input-group" style="max-width: 400px;">
        <span class="input-group-text bg-white"><i class="bi bi-search"></i></span>
        <input type="text" name="q" class="form-control" placeholder="Cari NIM, nama mahasiswa, atau mata kuliah..." value="<?= htmlspecialchars($keyword) ?>">
        <?php if ($keyword !== ''): ?>
            <a href="<?= BASE_URL ?>admin/krs.php" class="btn btn-outline-secondary">
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
                        <th>Kelas</th>
                        <th>Dosen</th>
                        <th>Tahun Akademik</th>
                        <th>Status</th>
                        <th>Nilai</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($daftarKrs)): ?>
                        <tr>
                            <td colspan="9" class="text-center text-muted py-3">Belum ada data KRS.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($daftarKrs as $i => $k): ?>
                            <tr>
                                <td><?= $offset + $i + 1 ?></td>
                                <td><?= htmlspecialchars($k['nim']) ?></td>
                                <td><?= htmlspecialchars($k['nama_mahasiswa']) ?></td>
                                <td><?= htmlspecialchars($k['kode_mk'] . ' - ' . $k['nama_mk']) ?></td>
                                <td><span class="badge bg-primary"><?= htmlspecialchars($k['nama_kelas']) ?></span></td>
                                <td><?= htmlspecialchars($k['nama_dosen']) ?></td>
                                <td><?= htmlspecialchars($k['tahun'] . ' - ' . $k['semester']) ?></td>
                                <td>
                                    <?php
                                    $statusBadge = match ($k['status']) {
                                        'disetujui' => 'bg-success',
                                        'ditolak' => 'bg-danger',
                                        default => 'bg-warning text-dark',
                                    };
                                    ?>
                                    <span class="badge <?= $statusBadge ?>"><?= htmlspecialchars(ucfirst($k['status'])) ?></span>
                                </td>
                                <td>
                                    <?php if ($k['nilai_huruf']): ?>
                                        <span class="badge bg-success"><?= htmlspecialchars($k['nilai_huruf']) ?></span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">Belum Ada</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php if ($totalHalaman > 1): ?>
    <div class="card-footer bg-white">
        <?= renderPagination($halaman, $totalHalaman, 'admin/krs.php', $keyword !== '' ? ['q' => $keyword] : []) ?>
    </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
