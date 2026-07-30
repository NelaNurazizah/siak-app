<?php
/**
 * includes/sidebar.php
 * Sidebar navigasi kiri, menu berbeda tergantung role user yang login.
 */

if (!defined('APP_INIT')) {
    die('Akses langsung ke file ini tidak diizinkan.');
}

$role = $_SESSION['role'] ?? '';
$currentPage = basename($_SERVER['PHP_SELF']);

/**
 * Helper kecil untuk menandai menu yang sedang aktif.
 */
function menuActive(string $page, string $currentPage): string
{
    return $page === $currentPage ? 'active' : '';
}
?>
<div class="sidebar text-white p-3 d-none d-lg-block" style="width: 260px; min-height: 100vh;" id="sidebarMenu">
    <div class="d-flex align-items-center mb-4">
        <i class="bi bi-mortarboard-fill fs-3 me-2"></i>
        <div>
            <div class="fw-bold"><?= htmlspecialchars(APP_NAME) ?></div>
            <small class="text-white-50">Panel <?= htmlspecialchars(ucfirst($role)) ?></small>
        </div>
    </div>

    <ul class="nav nav-pills flex-column gap-1">
        <?php if ($role === 'admin'): ?>
            <li class="nav-item">
                <a class="nav-link <?= menuActive('dashboard.php', $currentPage) ?>" href="<?= BASE_URL ?>admin/dashboard.php">
                    <i class="bi bi-speedometer2 me-2"></i>Dashboard
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= menuActive('mahasiswa.php', $currentPage) ?>" href="<?= BASE_URL ?>admin/mahasiswa.php">
                    <i class="bi bi-people-fill me-2"></i>Data Mahasiswa
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= menuActive('dosen.php', $currentPage) ?>" href="<?= BASE_URL ?>admin/dosen.php">
                    <i class="bi bi-person-badge-fill me-2"></i>Data Dosen
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= menuActive('mata_kuliah.php', $currentPage) ?>" href="<?= BASE_URL ?>admin/mata_kuliah.php">
                    <i class="bi bi-journal-bookmark-fill me-2"></i>Mata Kuliah
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= menuActive('kelas.php', $currentPage) ?>" href="<?= BASE_URL ?>admin/kelas.php">
                    <i class="bi bi-door-open-fill me-2"></i>Kelas
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= menuActive('tahun_akademik.php', $currentPage) ?>" href="<?= BASE_URL ?>admin/tahun_akademik.php">
                    <i class="bi bi-calendar3 me-2"></i>Tahun Akademik
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= menuActive('user.php', $currentPage) ?>" href="<?= BASE_URL ?>admin/user.php">
                    <i class="bi bi-person-gear me-2"></i>Kelola User
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= menuActive('krs.php', $currentPage) ?>" href="<?= BASE_URL ?>admin/krs.php">
                    <i class="bi bi-card-checklist me-2"></i>Data KRS
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= menuActive('nilai.php', $currentPage) ?>" href="<?= BASE_URL ?>admin/nilai.php">
                    <i class="bi bi-clipboard-data-fill me-2"></i>Data Nilai
                </a>
            </li>

        <?php elseif ($role === 'dosen'): ?>
            <li class="nav-item">
                <a class="nav-link <?= menuActive('dashboard.php', $currentPage) ?>" href="<?= BASE_URL ?>dosen/dashboard.php">
                    <i class="bi bi-speedometer2 me-2"></i>Dashboard
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= menuActive('mata_kuliah.php', $currentPage) ?>" href="<?= BASE_URL ?>dosen/mata_kuliah.php">
                    <i class="bi bi-journal-bookmark-fill me-2"></i>Mata Kuliah Diampu
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= menuActive('mahasiswa.php', $currentPage) ?>" href="<?= BASE_URL ?>dosen/mahasiswa.php">
                    <i class="bi bi-people-fill me-2"></i>Mahasiswa
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= menuActive('input_nilai.php', $currentPage) ?>" href="<?= BASE_URL ?>dosen/input_nilai.php">
                    <i class="bi bi-pencil-square me-2"></i>Input Nilai
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= menuActive('nilai.php', $currentPage) ?>" href="<?= BASE_URL ?>dosen/nilai.php">
                    <i class="bi bi-clipboard-data-fill me-2"></i>Daftar Nilai
                </a>
            </li>

        <?php elseif ($role === 'mahasiswa'): ?>
            <li class="nav-item">
                <a class="nav-link <?= menuActive('dashboard.php', $currentPage) ?>" href="<?= BASE_URL ?>mahasiswa/dashboard.php">
                    <i class="bi bi-speedometer2 me-2"></i>Dashboard
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= menuActive('isi_krs.php', $currentPage) ?>" href="<?= BASE_URL ?>mahasiswa/isi_krs.php">
                    <i class="bi bi-pencil-square me-2"></i>Isi KRS
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= menuActive('krs.php', $currentPage) ?>" href="<?= BASE_URL ?>mahasiswa/krs.php">
                    <i class="bi bi-card-checklist me-2"></i>KRS Saya
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= menuActive('nilai.php', $currentPage) ?>" href="<?= BASE_URL ?>mahasiswa/nilai.php">
                    <i class="bi bi-clipboard-data-fill me-2"></i>Nilai Saya
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= menuActive('ganti_password.php', $currentPage) ?>" href="<?= BASE_URL ?>mahasiswa/ganti_password.php">
                    <i class="bi bi-key-fill me-2"></i>Ganti Password
                </a>
            </li>
        <?php endif; ?>

        <li class="nav-item mt-3 border-top pt-2">
            <a class="nav-link text-danger-emphasis" href="<?= BASE_URL ?>logout.php">
                <i class="bi bi-box-arrow-right me-2"></i>Logout
            </a>
        </li>
    </ul>
</div>
