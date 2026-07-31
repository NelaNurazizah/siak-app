<?php
/**
 * admin/kelas.php
 * Halaman CRUD Kelas untuk Admin.
 * Kelas merupakan relasi antara Mata Kuliah, Dosen, dan Tahun Akademik.
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
    $whereClause = ' WHERE mk.nama_mk LIKE :keyword OR mk.kode_mk LIKE :keyword OR d.nama LIKE :keyword OR k.nama_kelas LIKE :keyword';
    $params[':keyword'] = '%' . $keyword . '%';
}

$sqlCount = "
    SELECT COUNT(*) AS total
    FROM kelas k
    JOIN mata_kuliah mk ON mk.id = k.mata_kuliah_id
    JOIN dosen d ON d.id = k.dosen_id
    JOIN tahun_akademik ta ON ta.id = k.tahun_akademik_id
" . $whereClause;
$stmtCount = $db->prepare($sqlCount);
$stmtCount->execute($params);
$totalData = (int) $stmtCount->fetch()['total'];
$totalHalaman = (int) ceil($totalData / $perHalaman);

$sql = "
    SELECT k.id, k.nama_kelas, k.hari, k.jam, k.ruang, k.kuota,
           mk.id AS mata_kuliah_id, mk.kode_mk, mk.nama_mk,
           d.id AS dosen_id, d.nama AS nama_dosen,
           ta.id AS tahun_akademik_id, ta.tahun, ta.semester
    FROM kelas k
    JOIN mata_kuliah mk ON mk.id = k.mata_kuliah_id
    JOIN dosen d ON d.id = k.dosen_id
    JOIN tahun_akademik ta ON ta.id = k.tahun_akademik_id
" . $whereClause . ' ORDER BY ta.tahun DESC, mk.nama_mk ASC LIMIT :limit OFFSET :offset';

$stmt = $db->prepare($sql);
foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value);
}
$stmt->bindValue(':limit', $perHalaman, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$daftarKelas = $stmt->fetchAll();

// Data untuk dropdown form
$daftarMatkul = $db->query('SELECT id, kode_mk, nama_mk FROM mata_kuliah ORDER BY nama_mk ASC')->fetchAll();
$daftarDosen  = $db->query('SELECT id, nama FROM dosen ORDER BY nama ASC')->fetchAll();
$daftarTahun  = $db->query("SELECT id, tahun, semester FROM tahun_akademik ORDER BY tahun DESC")->fetchAll();

$pageTitle = 'Data Kelas';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
    <h5 class="fw-bold mb-0">Data Kelas</h5>
    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalKelas" onclick="bukaModalTambah()">
        <i class="bi bi-plus-lg me-1"></i> Tambah Kelas
    </button>
</div>

<form action="" method="GET" class="mb-3">
    <div class="input-group" style="max-width: 350px;">
        <span class="input-group-text bg-white"><i class="bi bi-search"></i></span>
        <input type="text" name="q" class="form-control" placeholder="Cari mata kuliah, dosen, atau kelas..." value="<?= htmlspecialchars($keyword) ?>">
        <?php if ($keyword !== ''): ?>
            <a href="<?= BASE_URL ?>admin/kelas.php" class="btn btn-outline-secondary">
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
                        <th>Kelas</th>
                        <th>Mata Kuliah</th>
                        <th>Dosen</th>
                        <th>Tahun Akademik</th>
                        <th>Jadwal</th>
                        <th>Kuota</th>
                        <th style="width: 120px;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($daftarKelas)): ?>
                        <tr>
                            <td colspan="8" class="text-center text-muted py-3">Belum ada data kelas.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($daftarKelas as $i => $k): ?>
                            <tr>
                                <td><?= $offset + $i + 1 ?></td>
                                <td><span class="badge bg-primary"><?= htmlspecialchars($k['nama_kelas']) ?></span></td>
                                <td><?= htmlspecialchars($k['kode_mk'] . ' - ' . $k['nama_mk']) ?></td>
                                <td><?= htmlspecialchars($k['nama_dosen']) ?></td>
                                <td><?= htmlspecialchars($k['tahun'] . ' (' . $k['semester'] . ')') ?></td>
                                <td><?= htmlspecialchars(($k['hari'] ?? '-') . ' ' . ($k['jam'] ?? '')) ?><br><small class="text-muted"><?= htmlspecialchars($k['ruang'] ?? '-') ?></small></td>
                                <td><?= (int) $k['kuota'] ?></td>
                                <td>
                                    <button
                                        type="button"
                                        class="btn btn-sm btn-outline-warning"
                                        title="Edit"
                                        onclick='bukaModalEdit(<?= json_encode($k, JSON_HEX_APOS | JSON_HEX_QUOT) ?>)'
                                    >
                                        <i class="bi bi-pencil-square"></i>
                                    </button>
                                    <button
                                        type="button"
                                        class="btn btn-sm btn-outline-danger"
                                        title="Hapus"
                                        onclick="konfirmasiHapus(<?= (int) $k['id'] ?>, '<?= htmlspecialchars(addslashes($k['nama_kelas'] . ' - ' . $k['nama_mk'])) ?>')"
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
        <?= renderPagination($halaman, $totalHalaman, 'admin/kelas.php', $keyword !== '' ? ['q' => $keyword] : []) ?>
    </div>
    <?php endif; ?>
</div>

<!-- Modal Tambah/Edit Kelas -->
<div class="modal fade" id="modalKelas" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form action="<?= BASE_URL ?>admin/kelas_proses.php" method="POST" class="needs-validation" novalidate>
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalKelasLabel">Tambah Kelas</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                    <input type="hidden" name="action" id="formAction" value="create">
                    <input type="hidden" name="id" id="kelas_id" value="">

                    <div class="mb-3">
                        <label class="form-label">Mata Kuliah</label>
                        <select name="mata_kuliah_id" id="kelas_matkul" class="form-select" required>
                            <option value="">-- Pilih Mata Kuliah --</option>
                            <?php foreach ($daftarMatkul as $mk): ?>
                                <option value="<?= (int) $mk['id'] ?>"><?= htmlspecialchars($mk['kode_mk'] . ' - ' . $mk['nama_mk']) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <div class="invalid-feedback">Mata kuliah wajib dipilih.</div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Dosen Pengampu</label>
                        <select name="dosen_id" id="kelas_dosen" class="form-select" required>
                            <option value="">-- Pilih Dosen --</option>
                            <?php foreach ($daftarDosen as $d): ?>
                                <option value="<?= (int) $d['id'] ?>"><?= htmlspecialchars($d['nama']) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <div class="invalid-feedback">Dosen wajib dipilih.</div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Tahun Akademik</label>
                        <select name="tahun_akademik_id" id="kelas_tahun" class="form-select" required>
                            <option value="">-- Pilih Tahun Akademik --</option>
                            <?php foreach ($daftarTahun as $ta): ?>
                                <option value="<?= (int) $ta['id'] ?>"><?= htmlspecialchars($ta['tahun'] . ' - ' . $ta['semester']) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <div class="invalid-feedback">Tahun akademik wajib dipilih.</div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Nama Kelas</label>
                        <input type="text" name="nama_kelas" id="kelas_nama" class="form-control" required maxlength="10" placeholder="Contoh: A">
                        <div class="invalid-feedback">Nama kelas wajib diisi.</div>
                    </div>

                    <div class="row">
                        <div class="col-6 mb-3">
                            <label class="form-label">Hari</label>
                            <select name="hari" id="kelas_hari" class="form-select">
                                <option value="Senin">Senin</option>
                                <option value="Selasa">Selasa</option>
                                <option value="Rabu">Rabu</option>
                                <option value="Kamis">Kamis</option>
                                <option value="Jumat">Jumat</option>
                                <option value="Sabtu">Sabtu</option>
                            </select>
                        </div>
                        <div class="col-6 mb-3">
                            <label class="form-label">Jam</label>
                            <input type="text" name="jam" id="kelas_jam" class="form-control" placeholder="08:00-10:30" pattern="([01]\d|2[0-3]):[0-5]\d-([01]\d|2[0-3]):[0-5]\d" title="Format: 08:00-10:30">
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-6 mb-3">
                            <label class="form-label">Ruang</label>
                            <input type="text" name="ruang" id="kelas_ruang" class="form-control" placeholder="R101">
                        </div>
                        <div class="col-6 mb-3">
                            <label class="form-label">Kuota</label>
                            <input type="number" name="kuota" id="kelas_kuota" class="form-control" required min="1" max="100" value="30">
                            <div class="invalid-feedback">Kuota wajib diisi.</div>
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
<form id="formHapus" action="<?= BASE_URL ?>admin/kelas_proses.php" method="POST" class="d-none">
    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
    <input type="hidden" name="action" value="delete">
    <input type="hidden" name="id" id="hapus_id" value="">
</form>

<script>
function bukaModalTambah() {
    document.getElementById('modalKelasLabel').innerText = 'Tambah Kelas';
    document.getElementById('formAction').value = 'create';
    document.getElementById('kelas_id').value = '';
    document.getElementById('kelas_matkul').value = '';
    document.getElementById('kelas_dosen').value = '';
    document.getElementById('kelas_tahun').value = '';
    document.getElementById('kelas_nama').value = '';
    document.getElementById('kelas_hari').value = 'Senin';
    document.getElementById('kelas_jam').value = '';
    document.getElementById('kelas_ruang').value = '';
    document.getElementById('kelas_kuota').value = '30';
}

function bukaModalEdit(data) {
    document.getElementById('modalKelasLabel').innerText = 'Edit Kelas';
    document.getElementById('formAction').value = 'update';
    document.getElementById('kelas_id').value = data.id;
    document.getElementById('kelas_matkul').value = data.mata_kuliah_id;
    document.getElementById('kelas_dosen').value = data.dosen_id;
    document.getElementById('kelas_tahun').value = data.tahun_akademik_id;
    document.getElementById('kelas_nama').value = data.nama_kelas;
    document.getElementById('kelas_hari').value = data.hari ?? 'Senin';
    document.getElementById('kelas_jam').value = data.jam ?? '';
    document.getElementById('kelas_ruang').value = data.ruang ?? '';
    document.getElementById('kelas_kuota').value = data.kuota;

    var modal = new bootstrap.Modal(document.getElementById('modalKelas'));
    modal.show();
}

function konfirmasiHapus(id, nama) {
    if (confirm('Yakin ingin menghapus kelas "' + nama + '"? Data KRS dan nilai terkait juga akan terhapus.')) {
        document.getElementById('hapus_id').value = id;
        document.getElementById('formHapus').submit();
    }
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
