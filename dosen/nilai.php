<?php
/**
 * dosen/nilai.php
 * Menampilkan daftar nilai yang sudah diinput oleh dosen yang login,
 * dengan fitur Edit Nilai (mengubah nilai angka yang sudah tersimpan).
 * Proses update ditangani oleh dosen/nilai_proses.php (action=update).
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';

requireRole('dosen');

$db = Database::getConnection();

$stmtDosen = $db->prepare('SELECT id FROM dosen WHERE user_id = :user_id');
$stmtDosen->execute([':user_id' => $_SESSION['user_id']]);
$dosenId = $stmtDosen->fetch()['id'] ?? 0;

$stmt = $db->prepare('
    SELECT n.id AS nilai_id, n.krs_id, n.nilai_angka, n.nilai_huruf, n.bobot,
           m.nim, m.nama, kl.nama_kelas, mk.kode_mk, mk.nama_mk
    FROM nilai n
    JOIN krs k ON k.id = n.krs_id
    JOIN mahasiswa m ON m.id = k.mahasiswa_id
    JOIN kelas kl ON kl.id = k.kelas_id
    JOIN mata_kuliah mk ON mk.id = kl.mata_kuliah_id
    WHERE kl.dosen_id = :dosen_id
    ORDER BY mk.nama_mk ASC, m.nama ASC
');
$stmt->execute([':dosen_id' => $dosenId]);
$daftarNilai = $stmt->fetchAll();

$pageTitle = 'Daftar Nilai';
require_once __DIR__ . '/../includes/header.php';
?>

<h5 class="fw-bold mb-3">Daftar Nilai</h5>
<p class="text-muted">Nilai yang sudah diinput untuk mahasiswa pada kelas yang Anda ampu.</p>

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
                        <th>Nilai Angka</th>
                        <th>Nilai Huruf</th>
                        <th>Bobot</th>
                        <th style="width: 90px;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($daftarNilai)): ?>
                        <tr>
                            <td colspan="9" class="text-center text-muted py-3">Belum ada nilai yang diinput.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($daftarNilai as $i => $n): ?>
                            <tr>
                                <td><?= $i + 1 ?></td>
                                <td><?= htmlspecialchars($n['nim']) ?></td>
                                <td><?= htmlspecialchars($n['nama']) ?></td>
                                <td><?= htmlspecialchars($n['kode_mk'] . ' - ' . $n['nama_mk']) ?></td>
                                <td><span class="badge bg-primary"><?= htmlspecialchars($n['nama_kelas']) ?></span></td>
                                <td><?= htmlspecialchars((string) $n['nilai_angka']) ?></td>
                                <td><span class="badge bg-success"><?= htmlspecialchars($n['nilai_huruf']) ?></span></td>
                                <td><?= htmlspecialchars((string) $n['bobot']) ?></td>
                                <td>
                                    <button
                                        type="button"
                                        class="btn btn-sm btn-outline-warning"
                                        title="Edit Nilai"
                                        onclick='bukaModalEdit(<?= json_encode($n, JSON_HEX_APOS | JSON_HEX_QUOT) ?>)'
                                    >
                                        <i class="bi bi-pencil-square"></i>
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

<!-- Modal Edit Nilai -->
<div class="modal fade" id="modalEditNilai" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form action="<?= BASE_URL ?>dosen/nilai_proses.php" method="POST" class="needs-validation" novalidate>
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Nilai</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                    <input type="hidden" name="action" value="update">
                    <input type="hidden" name="nilai_id" id="edit_nilai_id" value="">

                    <div class="mb-3">
                        <label class="form-label">Mahasiswa</label>
                        <input type="text" id="edit_nama_mhs" class="form-control" disabled>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Mata Kuliah</label>
                        <input type="text" id="edit_nama_mk" class="form-control" disabled>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Nilai Angka (0-100)</label>
                        <input type="number" name="nilai_angka" id="edit_nilai_angka" class="form-control" required min="0" max="100" step="1">
                        <div class="invalid-feedback">Nilai wajib diisi antara 0-100.</div>
                        <div class="form-text">Nilai huruf dan bobot IPK akan dihitung ulang otomatis oleh sistem.</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
function bukaModalEdit(data) {
    document.getElementById('edit_nilai_id').value = data.nilai_id;
    document.getElementById('edit_nama_mhs').value = data.nim + ' - ' + data.nama;
    document.getElementById('edit_nama_mk').value = data.kode_mk + ' - ' + data.nama_mk + ' (Kelas ' + data.nama_kelas + ')';
    document.getElementById('edit_nilai_angka').value = data.nilai_angka;

    var modal = new bootstrap.Modal(document.getElementById('modalEditNilai'));
    modal.show();
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
