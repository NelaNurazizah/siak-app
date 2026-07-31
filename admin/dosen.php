<?php
/**
 * admin/dosen.php
 * Halaman CRUD Dosen untuk Admin.
 * Menampilkan daftar dosen serta form tambah/edit dalam bentuk modal.
 * Proses simpan/update/hapus ditangani oleh admin/dosen_proses.php.
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
    $whereClause = ' WHERE d.nidn LIKE :keyword OR d.nama LIKE :keyword';
    $params[':keyword'] = '%' . $keyword . '%';
}

$sqlCount = "SELECT COUNT(*) AS total FROM dosen d JOIN users u ON u.id = d.user_id" . $whereClause;
$stmtCount = $db->prepare($sqlCount);
$stmtCount->execute($params);
$totalData = (int) $stmtCount->fetch()['total'];
$totalHalaman = (int) ceil($totalData / $perHalaman);

$sql = "
    SELECT d.id, d.nidn, d.nama, d.jenis_kelamin, d.no_hp, d.alamat, u.username
    FROM dosen d
    JOIN users u ON u.id = d.user_id
" . $whereClause . ' ORDER BY d.nama ASC LIMIT :limit OFFSET :offset';

$stmt = $db->prepare($sql);
foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value);
}
$stmt->bindValue(':limit', $perHalaman, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$daftarDosen = $stmt->fetchAll();

$pageTitle = 'Data Dosen';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
    <h5 class="fw-bold mb-0">Data Dosen</h5>
    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalDosen" onclick="bukaModalTambah()">
        <i class="bi bi-plus-lg me-1"></i> Tambah Dosen
    </button>
</div>

<form action="" method="GET" class="mb-3">
    <div class="input-group" style="max-width: 350px;">
        <span class="input-group-text bg-white"><i class="bi bi-search"></i></span>
        <input type="text" name="q" class="form-control" placeholder="Cari NIDN atau nama..." value="<?= htmlspecialchars($keyword) ?>">
        <?php if ($keyword !== ''): ?>
            <a href="<?= BASE_URL ?>admin/dosen.php" class="btn btn-outline-secondary">
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
                        <th>NIDN</th>
                        <th>Nama</th>
                        <th>Jenis Kelamin</th>
                        <th>No. HP</th>
                        <th style="width: 120px;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($daftarDosen)): ?>
                        <tr>
                            <td colspan="6" class="text-center text-muted py-3">Belum ada data dosen.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($daftarDosen as $i => $d): ?>
                            <tr>
                                <td><?= $offset + $i + 1 ?></td>
                                <td><?= htmlspecialchars($d['nidn']) ?></td>
                                <td><?= htmlspecialchars($d['nama']) ?></td>
                                <td><?= $d['jenis_kelamin'] === 'L' ? 'Laki-laki' : 'Perempuan' ?></td>
                                <td><?= htmlspecialchars($d['no_hp'] ?? '-') ?></td>
                                <td>
                                    <button
                                        type="button"
                                        class="btn btn-sm btn-outline-warning"
                                        title="Edit"
                                        onclick='bukaModalEdit(<?= json_encode($d, JSON_HEX_APOS | JSON_HEX_QUOT) ?>)'
                                    >
                                        <i class="bi bi-pencil-square"></i>
                                    </button>
                                    <button
                                        type="button"
                                        class="btn btn-sm btn-outline-danger"
                                        title="Hapus"
                                        onclick="konfirmasiHapus(<?= (int) $d['id'] ?>, '<?= htmlspecialchars(addslashes($d['nama'])) ?>')"
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
        <?= renderPagination($halaman, $totalHalaman, 'admin/dosen.php', $keyword !== '' ? ['q' => $keyword] : []) ?>
    </div>
    <?php endif; ?>
</div>

<!-- Modal Tambah/Edit Dosen -->
<div class="modal fade" id="modalDosen" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form action="<?= BASE_URL ?>admin/dosen_proses.php" method="POST" class="needs-validation" novalidate>
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalDosenLabel">Tambah Dosen</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                    <input type="hidden" name="action" id="formAction" value="create">
                    <input type="hidden" name="id" id="dosen_id" value="">

                    <div class="mb-3">
                        <label class="form-label">NIDN</label>
                        <input type="text" name="nidn" id="dosen_nidn" class="form-control" required maxlength="20">
                        <div class="invalid-feedback">NIDN wajib diisi.</div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Nama Lengkap</label>
                        <input type="text" name="nama" id="dosen_nama" class="form-control" required maxlength="100">
                        <div class="invalid-feedback">Nama wajib diisi.</div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Jenis Kelamin</label>
                        <select name="jenis_kelamin" id="dosen_jk" class="form-select" required>
                            <option value="L">Laki-laki</option>
                            <option value="P">Perempuan</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">No. HP</label>
                        <input type="text" name="no_hp" id="dosen_hp" class="form-control" maxlength="20">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Alamat</label>
                        <textarea name="alamat" id="dosen_alamat" class="form-control" rows="2"></textarea>
                    </div>

                    <div id="infoPasswordDefault" class="alert alert-info small mb-0">
                        <i class="bi bi-info-circle me-1"></i>
                        Password default untuk dosen baru: <strong>password123</strong> (NIDN sebagai username).
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
<form id="formHapus" action="<?= BASE_URL ?>admin/dosen_proses.php" method="POST" class="d-none">
    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
    <input type="hidden" name="action" value="delete">
    <input type="hidden" name="id" id="hapus_id" value="">
</form>

<script>
function bukaModalTambah() {
    document.getElementById('modalDosenLabel').innerText = 'Tambah Dosen';
    document.getElementById('formAction').value = 'create';
    document.getElementById('dosen_id').value = '';
    document.getElementById('dosen_nidn').value = '';
    document.getElementById('dosen_nama').value = '';
    document.getElementById('dosen_jk').value = 'L';
    document.getElementById('dosen_hp').value = '';
    document.getElementById('dosen_alamat').value = '';
    document.getElementById('infoPasswordDefault').classList.remove('d-none');
}

function bukaModalEdit(data) {
    document.getElementById('modalDosenLabel').innerText = 'Edit Dosen';
    document.getElementById('formAction').value = 'update';
    document.getElementById('dosen_id').value = data.id;
    document.getElementById('dosen_nidn').value = data.nidn;
    document.getElementById('dosen_nama').value = data.nama;
    document.getElementById('dosen_jk').value = data.jenis_kelamin;
    document.getElementById('dosen_hp').value = data.no_hp ?? '';
    document.getElementById('dosen_alamat').value = data.alamat ?? '';
    document.getElementById('infoPasswordDefault').classList.add('d-none');

    var modal = new bootstrap.Modal(document.getElementById('modalDosen'));
    modal.show();
}

function konfirmasiHapus(id, nama) {
    if (confirm('Yakin ingin menghapus data dosen "' + nama + '"? Kelas yang diampu juga akan terhapus.')) {
        document.getElementById('hapus_id').value = id;
        document.getElementById('formHapus').submit();
    }
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
