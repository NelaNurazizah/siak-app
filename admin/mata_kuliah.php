<?php
/**
 * admin/mata_kuliah.php
 * Halaman CRUD Mata Kuliah untuk Admin.
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
    $whereClause = ' WHERE kode_mk LIKE :keyword OR nama_mk LIKE :keyword';
    $params[':keyword'] = '%' . $keyword . '%';
}

$stmtCount = $db->prepare('SELECT COUNT(*) AS total FROM mata_kuliah' . $whereClause);
$stmtCount->execute($params);
$totalData = (int) $stmtCount->fetch()['total'];
$totalHalaman = (int) ceil($totalData / $perHalaman);

$sql = 'SELECT id, kode_mk, nama_mk, sks, semester FROM mata_kuliah' . $whereClause . ' ORDER BY semester ASC, nama_mk ASC LIMIT :limit OFFSET :offset';

$stmt = $db->prepare($sql);
foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value);
}
$stmt->bindValue(':limit', $perHalaman, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$daftarMatkul = $stmt->fetchAll();

$pageTitle = 'Data Mata Kuliah';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
    <h5 class="fw-bold mb-0">Data Mata Kuliah</h5>
    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalMatkul" onclick="bukaModalTambah()">
        <i class="bi bi-plus-lg me-1"></i> Tambah Mata Kuliah
    </button>
</div>

<form action="" method="GET" class="mb-3">
    <div class="input-group" style="max-width: 350px;">
        <span class="input-group-text bg-white"><i class="bi bi-search"></i></span>
        <input type="text" name="q" class="form-control" placeholder="Cari kode atau nama MK..." value="<?= htmlspecialchars($keyword) ?>">
        <?php if ($keyword !== ''): ?>
            <a href="<?= BASE_URL ?>admin/mata_kuliah.php" class="btn btn-outline-secondary">
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
                        <th>Kode MK</th>
                        <th>Nama Mata Kuliah</th>
                        <th>SKS</th>
                        <th>Semester</th>
                        <th style="width: 120px;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($daftarMatkul)): ?>
                        <tr>
                            <td colspan="6" class="text-center text-muted py-3">Belum ada data mata kuliah.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($daftarMatkul as $i => $mk): ?>
                            <tr>
                                <td><?= $offset + $i + 1 ?></td>
                                <td><span class="badge bg-secondary"><?= htmlspecialchars($mk['kode_mk']) ?></span></td>
                                <td><?= htmlspecialchars($mk['nama_mk']) ?></td>
                                <td><?= (int) $mk['sks'] ?></td>
                                <td><?= (int) $mk['semester'] ?></td>
                                <td>
                                    <button
                                        type="button"
                                        class="btn btn-sm btn-outline-warning"
                                        title="Edit"
                                        onclick='bukaModalEdit(<?= json_encode($mk, JSON_HEX_APOS | JSON_HEX_QUOT) ?>)'
                                    >
                                        <i class="bi bi-pencil-square"></i>
                                    </button>
                                    <button
                                        type="button"
                                        class="btn btn-sm btn-outline-danger"
                                        title="Hapus"
                                        onclick="konfirmasiHapus(<?= (int) $mk['id'] ?>, '<?= htmlspecialchars(addslashes($mk['nama_mk'])) ?>')"
                                    >
                                        <i class="bi bi-trash"></i>
                                    </button>
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
        <?= renderPagination($halaman, $totalHalaman, 'admin/mata_kuliah.php', $keyword !== '' ? ['q' => $keyword] : []) ?>
    </div>
    <?php endif; ?>
</div>

<!-- Modal Tambah/Edit Mata Kuliah -->
<div class="modal fade" id="modalMatkul" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form action="<?= BASE_URL ?>admin/mata_kuliah_proses.php" method="POST" class="needs-validation" novalidate>
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalMatkulLabel">Tambah Mata Kuliah</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                    <input type="hidden" name="action" id="formAction" value="create">
                    <input type="hidden" name="id" id="matkul_id" value="">

                    <div class="mb-3">
                        <label class="form-label">Kode Mata Kuliah</label>
                        <input type="text" name="kode_mk" id="matkul_kode" class="form-control" required maxlength="20">
                        <div class="invalid-feedback">Kode mata kuliah wajib diisi.</div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Nama Mata Kuliah</label>
                        <input type="text" name="nama_mk" id="matkul_nama" class="form-control" required maxlength="150">
                        <div class="invalid-feedback">Nama mata kuliah wajib diisi.</div>
                    </div>

                    <div class="row">
                        <div class="col-6 mb-3">
                            <label class="form-label">SKS</label>
                            <input type="number" name="sks" id="matkul_sks" class="form-control" required min="1" max="6">
                            <div class="invalid-feedback">SKS wajib diisi (1-6).</div>
                        </div>
                        <div class="col-6 mb-3">
                            <label class="form-label">Semester</label>
                            <input type="number" name="semester" id="matkul_semester" class="form-control" required min="1" max="8">
                            <div class="invalid-feedback">Semester wajib diisi (1-8).</div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan</button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Form tersembunyi untuk aksi hapus -->
<form id="formHapus" action="<?= BASE_URL ?>admin/mata_kuliah_proses.php" method="POST" class="d-none">
    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
    <input type="hidden" name="action" value="delete">
    <input type="hidden" name="id" id="hapus_id" value="">
</form>

<script>
function bukaModalTambah() {
    document.getElementById('modalMatkulLabel').innerText = 'Tambah Mata Kuliah';
    document.getElementById('formAction').value = 'create';
    document.getElementById('matkul_id').value = '';
    document.getElementById('matkul_kode').value = '';
    document.getElementById('matkul_nama').value = '';
    document.getElementById('matkul_sks').value = '';
    document.getElementById('matkul_semester').value = '';
}

function bukaModalEdit(data) {
    document.getElementById('modalMatkulLabel').innerText = 'Edit Mata Kuliah';
    document.getElementById('formAction').value = 'update';
    document.getElementById('matkul_id').value = data.id;
    document.getElementById('matkul_kode').value = data.kode_mk;
    document.getElementById('matkul_nama').value = data.nama_mk;
    document.getElementById('matkul_sks').value = data.sks;
    document.getElementById('matkul_semester').value = data.semester;

    var modal = new bootstrap.Modal(document.getElementById('modalMatkul'));
    modal.show();
}

function konfirmasiHapus(id, nama) {
    if (confirm('Yakin ingin menghapus mata kuliah "' + nama + '"? Kelas terkait juga akan terhapus.')) {
        document.getElementById('hapus_id').value = id;
        document.getElementById('formHapus').submit();
    }
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
