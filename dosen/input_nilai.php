<?php
/**
 * dosen/input_nilai.php
 * Menampilkan daftar mahasiswa yang BELUM memiliki nilai pada kelas-kelas
 * yang diampu dosen yang login, dengan form input nilai (modal).
 * Proses simpan ditangani oleh dosen/nilai_proses.php (action=create).
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';

requireRole('dosen');

$db = Database::getConnection();

$stmtDosen = $db->prepare('SELECT id FROM dosen WHERE user_id = :user_id');
$stmtDosen->execute([':user_id' => $_SESSION['user_id']]);
$dosenId = $stmtDosen->fetch()['id'] ?? 0;

// Ambil seluruh KRS pada kelas yang diampu dosen ini yang BELUM memiliki nilai
$stmt = $db->prepare('
    SELECT k.id AS krs_id, m.nim, m.nama, kl.nama_kelas, mk.kode_mk, mk.nama_mk
    FROM krs k
    JOIN mahasiswa m ON m.id = k.mahasiswa_id
    JOIN kelas kl ON kl.id = k.kelas_id
    JOIN mata_kuliah mk ON mk.id = kl.mata_kuliah_id
    LEFT JOIN nilai n ON n.krs_id = k.id
    WHERE kl.dosen_id = :dosen_id AND n.id IS NULL
    ORDER BY mk.nama_mk ASC, m.nama ASC
');
$stmt->execute([':dosen_id' => $dosenId]);
$belumDinilai = $stmt->fetchAll();

$pageTitle = 'Input Nilai';
require_once __DIR__ . '/../includes/header.php';
?>

<h5 class="fw-bold mb-3">Input Nilai</h5>
<p class="text-muted">Daftar mahasiswa pada kelas yang Anda ampu yang belum memiliki nilai.</p>

<div class="card card-stat">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th style="width: 40px;">#</th>
                        <th>NIM</th>
                        <th>Nama</th>
                        <th>Mata Kuliah</th>
                        <th>Kelas</th>
                        <th style="width: 120px;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($belumDinilai)): ?>
                        <tr>
                            <td colspan="6" class="text-center text-success py-3">
                                <i class="bi bi-check-circle-fill me-1"></i> Semua mahasiswa sudah dinilai. 🎉
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($belumDinilai as $i => $b): ?>
                            <tr>
                                <td><?= $i + 1 ?></td>
                                <td><?= htmlspecialchars($b['nim']) ?></td>
                                <td><?= htmlspecialchars($b['nama']) ?></td>
                                <td><?= htmlspecialchars($b['kode_mk'] . ' - ' . $b['nama_mk']) ?></td>
                                <td><span class="badge bg-primary"><?= htmlspecialchars($b['nama_kelas']) ?></span></td>
                                <td>
                                    <button
                                        type="button"
                                        class="btn btn-sm btn-primary"
                                        onclick='bukaModalInput(<?= json_encode($b, JSON_HEX_APOS | JSON_HEX_QUOT) ?>)'
                                    >
                                        <i class="bi bi-pencil-square"></i> Input
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

<!-- Modal Input Nilai -->
<div class="modal fade" id="modalInput" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form action="<?= BASE_URL ?>dosen/nilai_proses.php" method="POST" class="needs-validation" novalidate>
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Input Nilai</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                    <input type="hidden" name="action" value="create">
                    <input type="hidden" name="krs_id" id="input_krs_id" value="">

                    <div class="mb-3">
                        <label class="form-label">Mahasiswa</label>
                        <input type="text" id="input_nama_mhs" class="form-control" disabled>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Mata Kuliah</label>
                        <input type="text" id="input_nama_mk" class="form-control" disabled>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Nilai Angka (0-100)</label>
                        <input type="number" name="nilai_angka" id="input_nilai_angka" class="form-control" required min="0" max="100" step="1">
                        <div class="invalid-feedback">Nilai wajib diisi antara 0-100.</div>
                        <div class="form-text">Nilai huruf dan bobot IPK akan dihitung otomatis oleh sistem.</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan Nilai</button>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
function bukaModalInput(data) {
    document.getElementById('input_krs_id').value = data.krs_id;
    document.getElementById('input_nama_mhs').value = data.nim + ' - ' + data.nama;
    document.getElementById('input_nama_mk').value = data.kode_mk + ' - ' + data.nama_mk + ' (Kelas ' + data.nama_kelas + ')';
    document.getElementById('input_nilai_angka').value = '';

    var modal = new bootstrap.Modal(document.getElementById('modalInput'));
    modal.show();
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
