<?php
/**
 * mahasiswa/isi_krs.php
 * Halaman bagi mahasiswa untuk mengisi (mengambil) kelas pada tahun akademik
 * yang sedang aktif. Menampilkan kelas yang belum diambil beserta sisa kuota,
 * dan kelas yang sudah diambil (dengan opsi batalkan).
 * Proses ditangani oleh mahasiswa/krs_proses.php.
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';

requireRole('mahasiswa');

$db = Database::getConnection();

$stmtMhs = $db->prepare('SELECT id FROM mahasiswa WHERE user_id = :user_id');
$stmtMhs->execute([':user_id' => $_SESSION['user_id']]);
$mahasiswaId = $stmtMhs->fetch()['id'] ?? 0;

$tahunAktif = $db->query("SELECT id, tahun, semester FROM tahun_akademik WHERE status = 'aktif' LIMIT 1")->fetch();

$kelasTersedia = [];
$kelasDiambil = [];

if ($tahunAktif) {
    // Kelas yang tersedia pada tahun akademik aktif dan BELUM diambil mahasiswa ini
    $stmtTersedia = $db->prepare('
        SELECT k.id, k.nama_kelas, k.hari, k.jam, k.ruang, k.kuota,
               mk.kode_mk, mk.nama_mk, mk.sks, d.nama AS nama_dosen,
               (SELECT COUNT(*) FROM krs WHERE kelas_id = k.id) AS jumlah_peserta
        FROM kelas k
        JOIN mata_kuliah mk ON mk.id = k.mata_kuliah_id
        JOIN dosen d ON d.id = k.dosen_id
        WHERE k.tahun_akademik_id = :ta_id
          AND k.id NOT IN (
              SELECT kelas_id FROM krs WHERE mahasiswa_id = :mahasiswa_id
          )
        ORDER BY mk.nama_mk ASC
    ');
    $stmtTersedia->execute([':ta_id' => $tahunAktif['id'], ':mahasiswa_id' => $mahasiswaId]);
    $kelasTersedia = $stmtTersedia->fetchAll();

    // Kelas yang sudah diambil mahasiswa pada tahun akademik aktif
    $stmtDiambil = $db->prepare('
        SELECT k.id AS krs_id, kl.nama_kelas, mk.kode_mk, mk.nama_mk, mk.sks, d.nama AS nama_dosen
        FROM krs k
        JOIN kelas kl ON kl.id = k.kelas_id
        JOIN mata_kuliah mk ON mk.id = kl.mata_kuliah_id
        JOIN dosen d ON d.id = kl.dosen_id
        WHERE k.mahasiswa_id = :mahasiswa_id AND k.tahun_akademik_id = :ta_id
        ORDER BY mk.nama_mk ASC
    ');
    $stmtDiambil->execute([':mahasiswa_id' => $mahasiswaId, ':ta_id' => $tahunAktif['id']]);
    $kelasDiambil = $stmtDiambil->fetchAll();
}

$totalSksDiambil = array_sum(array_column($kelasDiambil, 'sks'));

$pageTitle = 'Isi KRS';
require_once __DIR__ . '/../includes/header.php';
?>

<h5 class="fw-bold mb-3">Isi KRS</h5>

<?php if (!$tahunAktif): ?>
    <div class="alert alert-warning">Belum ada tahun akademik yang aktif. Silakan hubungi Admin.</div>
<?php else: ?>

    <div class="alert alert-info d-flex justify-content-between align-items-center">
        <span>Tahun Akademik: <strong><?= htmlspecialchars($tahunAktif['tahun'] . ' - ' . $tahunAktif['semester']) ?></strong></span>
        <span>Total SKS Diambil: <strong><?= (int) $totalSksDiambil ?></strong> SKS</span>
    </div>

    <h6 class="fw-semibold mt-4 mb-2">Kelas yang Sudah Diambil</h6>
    <div class="card card-stat mb-4">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Mata Kuliah</th>
                            <th>Kelas</th>
                            <th>Dosen</th>
                            <th>SKS</th>
                            <th style="width: 100px;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($kelasDiambil)): ?>
                            <tr>
                                <td colspan="5" class="text-center text-muted py-3">Belum ada kelas yang diambil.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($kelasDiambil as $kd): ?>
                                <tr>
                                    <td><?= htmlspecialchars($kd['kode_mk'] . ' - ' . $kd['nama_mk']) ?></td>
                                    <td><span class="badge bg-primary"><?= htmlspecialchars($kd['nama_kelas']) ?></span></td>
                                    <td><?= htmlspecialchars($kd['nama_dosen']) ?></td>
                                    <td><?= (int) $kd['sks'] ?></td>
                                    <td>
                                        <form action="<?= BASE_URL ?>mahasiswa/krs_proses.php" method="POST" onsubmit="return confirm('Batalkan mata kuliah ini dari KRS Anda?');">
                                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="krs_id" value="<?= (int) $kd['krs_id'] ?>">
                                            <button type="submit" class="btn btn-sm btn-outline-danger">
                                                <i class="bi bi-x-circle"></i> Batalkan
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <h6 class="fw-semibold mb-2">Kelas Tersedia</h6>
    <div class="card card-stat">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Mata Kuliah</th>
                            <th>Kelas</th>
                            <th>Dosen</th>
                            <th>Jadwal</th>
                            <th>SKS</th>
                            <th>Kuota</th>
                            <th style="width: 100px;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($kelasTersedia)): ?>
                            <tr>
                                <td colspan="7" class="text-center text-muted py-3">Tidak ada kelas tersedia untuk diambil.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($kelasTersedia as $kt): ?>
                                <?php $penuh = (int) $kt['jumlah_peserta'] >= (int) $kt['kuota']; ?>
                                <tr>
                                    <td><?= htmlspecialchars($kt['kode_mk'] . ' - ' . $kt['nama_mk']) ?></td>
                                    <td><span class="badge bg-primary"><?= htmlspecialchars($kt['nama_kelas']) ?></span></td>
                                    <td><?= htmlspecialchars($kt['nama_dosen']) ?></td>
                                    <td><?= htmlspecialchars(($kt['hari'] ?? '-') . ' ' . ($kt['jam'] ?? '')) ?></td>
                                    <td><?= (int) $kt['sks'] ?></td>
                                    <td>
                                        <?= (int) $kt['jumlah_peserta'] ?> / <?= (int) $kt['kuota'] ?>
                                        <?php if ($penuh): ?>
                                            <span class="badge bg-danger">Penuh</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <form action="<?= BASE_URL ?>mahasiswa/krs_proses.php" method="POST">
                                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                                            <input type="hidden" name="action" value="create">
                                            <input type="hidden" name="kelas_id" value="<?= (int) $kt['id'] ?>">
                                            <button type="submit" class="btn btn-sm btn-primary" <?= $penuh ? 'disabled' : '' ?>>
                                                <i class="bi bi-plus-circle"></i> Ambil
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

<?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
