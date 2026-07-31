<?php
/**
 * admin/tahun_akademik.php
 * Halaman CRUD Tahun Akademik untuk Admin.
 * Hanya boleh ada 1 tahun akademik berstatus "aktif" pada satu waktu.
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';

requireRole('admin');

$db = Database::getConnection();

$stmt = $db->query('SELECT id, tahun, semester, status FROM tahun_akademik ORDER BY tahun DESC, semester ASC');
$daftarTahun = $stmt->fetchAll();

$pageTitle = 'Tahun Akademik';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="fw-bold mb-0">Data Tahun Akademik</h5>
    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalTahun" onclick="bukaModalTambah()">
        <i class="bi bi-plus-lg me-1"></i> Tambah Tahun Akademik
    </button>
</div>

<div class="card card-stat">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th style="width: 40px;">#</th>
                        <th>Tahun</th>
                        <th>Semester</th>
                        <th>Status</th>
                        <th style="width: 120px;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($daftarTahun)): ?>
                        <tr>
                            <td colspan="5" class="text-center text-muted py-3">Belum ada data tahun akademik.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($daftarTahun as $i => $t): ?>
                            <tr>
                                <td><?= $i + 1 ?></td>
                                <td><?= htmlspecialchars($t['tahun']) ?></td>
                                <td><?= htmlspecialchars($t['semester']) ?></td>
                                <td>
                                    <?php if ($t['status'] === 'aktif'): ?>
                                        <span class="badge bg-success">Aktif</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">Nonaktif</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <button
                                        type="button"
                                        class="btn btn-sm btn-outline-warning"
                                        title="Edit"
                                        onclick='bukaModalEdit(<?= json_encode($t, JSON_HEX_APOS | JSON_HEX_QUOT) ?>)'
                                    >
                                        <i class="bi bi-pencil-square"></i>
                                    </button>
                                    <button
                                        type="button"
                                        class="btn btn-sm btn-outline-danger"
                                        title="Hapus"
                                        onclick="konfirmasiHapus(<?= (int) $t['id'] ?>, '<?= htmlspecialchars(addslashes($t['tahun'] . ' - ' . $t['semester'])) ?>')"
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
</div>

<!-- Modal Tambah/Edit Tahun Akademik -->
<div class="modal fade" id="modalTahun" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form action="<?= BASE_URL ?>admin/tahun_akademik_proses.php" method="POST" class="needs-validation" novalidate>
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalTahunLabel">Tambah Tahun Akademik</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                    <input type="hidden" name="action" id="formAction" value="create">
                    <input type="hidden" name="id" id="tahun_id" value="">

                    <div class="mb-3">
                        <label class="form-label">Tahun</label>
                        <input type="text" name="tahun" id="tahun_tahun" class="form-control" required maxlength="20" placeholder="Contoh: 2025/2026" pattern="\d{4}/\d{4}">
                        <div class="invalid-feedback">Format tahun harus seperti 2025/2026.</div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Semester</label>
                        <select name="semester" id="tahun_semester" class="form-select" required>
                            <option value="Ganjil">Ganjil</option>
                            <option value="Genap">Genap</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Status</label>
                        <select name="status" id="tahun_status" class="form-select" required>
                            <option value="nonaktif">Nonaktif</option>
                            <option value="aktif">Aktif</option>
                        </select>
                        <div class="form-text">Jika dipilih "Aktif", tahun akademik lain otomatis dijadikan nonaktif.</div>
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
<form id="formHapus" action="<?= BASE_URL ?>admin/tahun_akademik_proses.php" method="POST" class="d-none">
    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
    <input type="hidden" name="action" value="delete">
    <input type="hidden" name="id" id="hapus_id" value="">
</form>

<script>
function bukaModalTambah() {
    document.getElementById('modalTahunLabel').innerText = 'Tambah Tahun Akademik';
    document.getElementById('formAction').value = 'create';
    document.getElementById('tahun_id').value = '';
    document.getElementById('tahun_tahun').value = '';
    document.getElementById('tahun_semester').value = 'Ganjil';
    document.getElementById('tahun_status').value = 'nonaktif';
}

function bukaModalEdit(data) {
    document.getElementById('modalTahunLabel').innerText = 'Edit Tahun Akademik';
    document.getElementById('formAction').value = 'update';
    document.getElementById('tahun_id').value = data.id;
    document.getElementById('tahun_tahun').value = data.tahun;
    document.getElementById('tahun_semester').value = data.semester;
    document.getElementById('tahun_status').value = data.status;

    var modal = new bootstrap.Modal(document.getElementById('modalTahun'));
    modal.show();
}

function konfirmasiHapus(id, label) {
    if (confirm('Yakin ingin menghapus tahun akademik "' + label + '"? Kelas dan KRS terkait juga akan terhapus.')) {
        document.getElementById('hapus_id').value = id;
        document.getElementById('formHapus').submit();
    }
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
