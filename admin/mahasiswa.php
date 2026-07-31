<?php
/**
 * admin/mahasiswa.php
 * Halaman CRUD Mahasiswa untuk Admin.
 * Menampilkan daftar mahasiswa serta form tambah/edit dalam bentuk modal.
 * Proses simpan/update/hapus ditangani oleh admin/mahasiswa_proses.php.
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

requireRole('admin');

$db = Database::getConnection();

// Ambil kata kunci pencarian dari query string (?q=...)
$keyword = cleanInput($_GET['q'] ?? '');

// Pengaturan pagination
$perHalaman = 10;
$halaman = max(1, (int) ($_GET['page'] ?? 1));
$offset = ($halaman - 1) * $perHalaman;

$whereClause = '';
$params = [];
if ($keyword !== '') {
    $whereClause = ' WHERE m.nim LIKE :keyword OR m.nama LIKE :keyword';
    $params[':keyword'] = '%' . $keyword . '%';
}

// Hitung total data (untuk keperluan pagination)
$sqlCount = "SELECT COUNT(*) AS total FROM mahasiswa m JOIN users u ON u.id = m.user_id" . $whereClause;
$stmtCount = $db->prepare($sqlCount);
$stmtCount->execute($params);
$totalData = (int) $stmtCount->fetch()['total'];
$totalHalaman = (int) ceil($totalData / $perHalaman);

// Ambil data mahasiswa beserta username-nya, difilter oleh kata kunci pencarian (NIM/Nama) jika ada
$sql = "
    SELECT m.id, m.nim, m.nama, m.jenis_kelamin, m.angkatan, m.no_hp, m.alamat, u.username
    FROM mahasiswa m
    JOIN users u ON u.id = m.user_id
" . $whereClause . ' ORDER BY m.nama ASC LIMIT :limit OFFSET :offset';

$stmt = $db->prepare($sql);
foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value);
}
$stmt->bindValue(':limit', $perHalaman, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$daftarMahasiswa = $stmt->fetchAll();

$pageTitle = 'Data Mahasiswa';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
    <h5 class="fw-bold mb-0">Data Mahasiswa</h5>
    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalMahasiswa" onclick="bukaModalTambah()">
        <i class="bi bi-plus-lg me-1"></i> Tambah Mahasiswa
    </button>
</div>

<form action="" method="GET" class="mb-3">
    <div class="input-group" style="max-width: 350px;">
        <span class="input-group-text bg-white"><i class="bi bi-search"></i></span>
        <input type="text" name="q" class="form-control" placeholder="Cari NIM atau nama..." value="<?= htmlspecialchars($keyword) ?>">
        <?php if ($keyword !== ''): ?>
            <a href="<?= BASE_URL ?>admin/mahasiswa.php" class="btn btn-outline-secondary">
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
                        <th>Nama</th>
                        <th>Jenis Kelamin</th>
                        <th>Angkatan</th>
                        <th>No. HP</th>
                        <th style="width: 120px;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($daftarMahasiswa)): ?>
                        <tr>
                            <td colspan="7" class="text-center text-muted py-3">Belum ada data mahasiswa.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($daftarMahasiswa as $i => $m): ?>
                            <tr>
                                <td><?= $offset + $i + 1 ?></td>
                                <td><?= htmlspecialchars($m['nim']) ?></td>
                                <td><?= htmlspecialchars($m['nama']) ?></td>
                                <td><?= $m['jenis_kelamin'] === 'L' ? 'Laki-laki' : 'Perempuan' ?></td>
                                <td><?= htmlspecialchars((string) $m['angkatan']) ?></td>
                                <td><?= htmlspecialchars($m['no_hp'] ?? '-') ?></td>
                                <td>
                                    <button
                                        type="button"
                                        class="btn btn-sm btn-outline-warning"
                                        title="Edit"
                                        onclick='bukaModalEdit(<?= json_encode($m, JSON_HEX_APOS | JSON_HEX_QUOT) ?>)'
                                    >
                                        <i class="bi bi-pencil-square"></i>
                                    </button>
                                    <button
                                        type="button"
                                        class="btn btn-sm btn-outline-danger"
                                        title="Hapus"
                                        onclick="konfirmasiHapus(<?= (int) $m['id'] ?>, '<?= htmlspecialchars(addslashes($m['nama'])) ?>')"
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
        <?= renderPagination($halaman, $totalHalaman, 'admin/mahasiswa.php', $keyword !== '' ? ['q' => $keyword] : []) ?>
    </div>
    <?php endif; ?>
</div>

<!-- Modal Tambah/Edit Mahasiswa -->
<div class="modal fade" id="modalMahasiswa" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form action="<?= BASE_URL ?>admin/mahasiswa_proses.php" method="POST" class="needs-validation" novalidate>
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalMahasiswaLabel">Tambah Mahasiswa</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                    <input type="hidden" name="action" id="formAction" value="create">
                    <input type="hidden" name="id" id="mahasiswa_id" value="">

                    <div class="mb-3">
                        <label class="form-label">NIM</label>
                        <input type="text" name="nim" id="mahasiswa_nim" class="form-control" required maxlength="20">
                        <div class="invalid-feedback">NIM wajib diisi.</div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Nama Lengkap</label>
                        <input type="text" name="nama" id="mahasiswa_nama" class="form-control" required maxlength="100">
                        <div class="invalid-feedback">Nama wajib diisi.</div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Jenis Kelamin</label>
                        <select name="jenis_kelamin" id="mahasiswa_jk" class="form-select" required>
                            <option value="L">Laki-laki</option>
                            <option value="P">Perempuan</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Angkatan</label>
                        <input type="number" name="angkatan" id="mahasiswa_angkatan" class="form-control" required min="2000" max="2100">
                        <div class="invalid-feedback">Angkatan wajib diisi.</div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">No. HP</label>
                        <input type="text" name="no_hp" id="mahasiswa_hp" class="form-control" maxlength="20">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Alamat</label>
                        <textarea name="alamat" id="mahasiswa_alamat" class="form-control" rows="2"></textarea>
                    </div>

                    <div id="infoPasswordDefault" class="alert alert-info small mb-0">
                        <i class="bi bi-info-circle me-1"></i>
                        Password default untuk mahasiswa baru: <strong>password123</strong> (NIM sebagai username).
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
<form id="formHapus" action="<?= BASE_URL ?>admin/mahasiswa_proses.php" method="POST" class="d-none">
    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
    <input type="hidden" name="action" value="delete">
    <input type="hidden" name="id" id="hapus_id" value="">
</form>

<script>
function bukaModalTambah() {
    document.getElementById('modalMahasiswaLabel').innerText = 'Tambah Mahasiswa';
    document.getElementById('formAction').value = 'create';
    document.getElementById('mahasiswa_id').value = '';
    document.getElementById('mahasiswa_nim').value = '';
    document.getElementById('mahasiswa_nim').removeAttribute('readonly');
    document.getElementById('mahasiswa_nama').value = '';
    document.getElementById('mahasiswa_jk').value = 'L';
    document.getElementById('mahasiswa_angkatan').value = '';
    document.getElementById('mahasiswa_hp').value = '';
    document.getElementById('mahasiswa_alamat').value = '';
    document.getElementById('infoPasswordDefault').classList.remove('d-none');
}

function bukaModalEdit(data) {
    document.getElementById('modalMahasiswaLabel').innerText = 'Edit Mahasiswa';
    document.getElementById('formAction').value = 'update';
    document.getElementById('mahasiswa_id').value = data.id;
    document.getElementById('mahasiswa_nim').value = data.nim;
    document.getElementById('mahasiswa_nama').value = data.nama;
    document.getElementById('mahasiswa_jk').value = data.jenis_kelamin;
    document.getElementById('mahasiswa_angkatan').value = data.angkatan;
    document.getElementById('mahasiswa_hp').value = data.no_hp ?? '';
    document.getElementById('mahasiswa_alamat').value = data.alamat ?? '';
    document.getElementById('infoPasswordDefault').classList.add('d-none');

    var modal = new bootstrap.Modal(document.getElementById('modalMahasiswa'));
    modal.show();
}

function konfirmasiHapus(id, nama) {
    if (confirm('Yakin ingin menghapus data mahasiswa "' + nama + '"? Data KRS dan nilai terkait juga akan terhapus.')) {
        document.getElementById('hapus_id').value = id;
        document.getElementById('formHapus').submit();
    }
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
