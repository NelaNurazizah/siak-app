<?php
/**
 * admin/user.php
 * Halaman Kelola User untuk Admin.
 *
 * Menampilkan seluruh akun (admin, dosen, mahasiswa) dalam satu tabel.
 * - Akun dosen & mahasiswa dibuat otomatis lewat modul CRUD Dosen/Mahasiswa,
 *   jadi di halaman ini hanya bisa Reset Password atau Hapus untuk mereka.
 * - Akun Admin baru BISA dibuat langsung dari halaman ini.
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
    $whereClause = ' WHERE u.username LIKE :keyword OR a.nama LIKE :keyword OR d.nama LIKE :keyword OR m.nama LIKE :keyword';
    $params[':keyword'] = '%' . $keyword . '%';
}

$sqlCount = "
    SELECT COUNT(*) AS total
    FROM users u
    LEFT JOIN admin a ON a.user_id = u.id
    LEFT JOIN dosen d ON d.user_id = u.id
    LEFT JOIN mahasiswa m ON m.user_id = u.id
" . $whereClause;
$stmtCount = $db->prepare($sqlCount);
$stmtCount->execute($params);
$totalData = (int) $stmtCount->fetch()['total'];
$totalHalaman = (int) ceil($totalData / $perHalaman);

$sql = "
    SELECT u.id, u.username, u.role, u.created_at,
           COALESCE(a.nama, d.nama, m.nama) AS nama
    FROM users u
    LEFT JOIN admin a ON a.user_id = u.id
    LEFT JOIN dosen d ON d.user_id = u.id
    LEFT JOIN mahasiswa m ON m.user_id = u.id
" . $whereClause . " ORDER BY FIELD(u.role, 'admin', 'dosen', 'mahasiswa'), nama ASC LIMIT :limit OFFSET :offset";

$stmt = $db->prepare($sql);
foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value);
}
$stmt->bindValue(':limit', $perHalaman, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$daftarUser = $stmt->fetchAll();

$pageTitle = 'Kelola User';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
    <h5 class="fw-bold mb-0">Kelola User</h5>
    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalUser">
        <i class="bi bi-plus-lg me-1"></i> Tambah Admin
    </button>
</div>

<form action="" method="GET" class="mb-3">
    <div class="input-group" style="max-width: 350px;">
        <span class="input-group-text bg-white"><i class="bi bi-search"></i></span>
        <input type="text" name="q" class="form-control" placeholder="Cari username atau nama..." value="<?= htmlspecialchars($keyword) ?>">
        <?php if ($keyword !== ''): ?>
            <a href="<?= BASE_URL ?>admin/user.php" class="btn btn-outline-secondary">
                <i class="bi bi-x-lg"></i>
            </a>
        <?php endif; ?>
        <button type="submit" class="btn btn-outline-primary">Cari</button>
    </div>
</form>

<div class="alert alert-info small">
    <i class="bi bi-info-circle me-1"></i>
    Akun untuk Dosen dan Mahasiswa dibuat otomatis melalui menu <strong>Data Dosen</strong> dan <strong>Data Mahasiswa</strong>.
    Di halaman ini Anda hanya bisa menambah akun <strong>Admin</strong> baru, mereset password, atau menghapus akun.
</div>

<div class="card card-stat">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th style="width: 40px;">#</th>
                        <th>Username</th>
                        <th>Nama</th>
                        <th>Role</th>
                        <th>Dibuat Pada</th>
                        <th style="width: 180px;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($daftarUser as $i => $u): ?>
                        <tr>
                            <td><?= $offset + $i + 1 ?></td>
                            <td><?= htmlspecialchars($u['username']) ?></td>
                            <td><?= htmlspecialchars($u['nama'] ?? '-') ?></td>
                            <td>
                                <?php
                                $badgeClass = match ($u['role']) {
                                    'admin' => 'bg-danger',
                                    'dosen' => 'bg-success',
                                    'mahasiswa' => 'bg-primary',
                                    default => 'bg-secondary',
                                };
                                ?>
                                <span class="badge <?= $badgeClass ?>"><?= htmlspecialchars(ucfirst($u['role'])) ?></span>
                            </td>
                            <td><?= htmlspecialchars(date('d M Y', strtotime($u['created_at']))) ?></td>
                            <td>
                                <form action="<?= BASE_URL ?>admin/user_proses.php" method="POST" class="d-inline" onsubmit="return confirm('Reset password akun ini ke password123?');">
                                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                                    <input type="hidden" name="action" value="reset_password">
                                    <input type="hidden" name="id" value="<?= (int) $u['id'] ?>">
                                    <button type="submit" class="btn btn-sm btn-outline-secondary" title="Reset Password">
                                        <i class="bi bi-key"></i>
                                    </button>
                                </form>

                                <?php if (!($u['role'] === 'admin' && (int) $u['id'] === (int) $_SESSION['user_id'])): ?>
                                <form action="<?= BASE_URL ?>admin/user_proses.php" method="POST" class="d-inline" onsubmit="return confirm('Yakin ingin menghapus akun \'<?= htmlspecialchars(addslashes($u['username'])) ?>\'?');">
                                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?= (int) $u['id'] ?>">
                                    <button type="submit" class="btn btn-sm btn-outline-danger" title="Hapus">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php if ($totalHalaman > 1): ?>
    <div class="card-footer bg-white">
        <?= renderPagination($halaman, $totalHalaman, 'admin/user.php', $keyword !== '' ? ['q' => $keyword] : []) ?>
    </div>
    <?php endif; ?>
</div>

<!-- Modal Tambah Admin -->
<div class="modal fade" id="modalUser" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form action="<?= BASE_URL ?>admin/user_proses.php" method="POST" class="needs-validation" novalidate>
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Tambah Akun Admin</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                    <input type="hidden" name="action" value="create_admin">

                    <div class="mb-3">
                        <label class="form-label">Nama Lengkap</label>
                        <input type="text" name="nama" class="form-control" required maxlength="100">
                        <div class="invalid-feedback">Nama wajib diisi.</div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Username</label>
                        <input type="text" name="username" class="form-control" required maxlength="50">
                        <div class="invalid-feedback">Username wajib diisi.</div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Password</label>
                        <input type="password" name="password" class="form-control" required minlength="6">
                        <div class="invalid-feedback">Password minimal 6 karakter.</div>
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

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
