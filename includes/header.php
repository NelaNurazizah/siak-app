<?php
/**
 * includes/header.php
 * Bagian <head> + navbar atas, dipakai di semua halaman dashboard
 * (admin, dosen, mahasiswa).
 *
 * Variabel yang bisa di-set sebelum include file ini:
 * - $pageTitle (string) -> judul halaman, default "Dashboard"
 */

if (!defined('APP_INIT')) {
    die('Akses langsung ke file ini tidak diizinkan.');
}

$pageTitle = $pageTitle ?? 'Dashboard';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?> - <?= htmlspecialchars(APP_NAME) ?></title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <link href="<?= BASE_URL ?>assets/css/style.css" rel="stylesheet">
</head>
<body>
<div class="d-flex">
    <?php require_once __DIR__ . '/sidebar.php'; ?>

    <div class="flex-grow-1">
        <!-- Navbar atas -->
        <nav class="navbar navbar-expand-lg navbar-light bg-white border-bottom shadow-sm px-3">
            <button class="btn btn-outline-secondary d-lg-none me-2" type="button" id="sidebarToggle">
                <i class="bi bi-list"></i>
            </button>
            <span class="navbar-brand fw-semibold mb-0"><?= htmlspecialchars($pageTitle) ?></span>

            <div class="ms-auto dropdown">
                <button class="btn btn-light d-flex align-items-center border" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="bi bi-person-circle me-2"></i>
                    <span><?= htmlspecialchars(currentUserName()) ?></span>
                </button>
                <ul class="dropdown-menu dropdown-menu-end">
                    <li><span class="dropdown-item-text small text-muted">Role: <?= htmlspecialchars(ucfirst($_SESSION['role'])) ?></span></li>
                    <li><hr class="dropdown-divider"></li>
                    <?php if ($_SESSION['role'] === 'mahasiswa'): ?>
                    <li><a class="dropdown-item" href="<?= BASE_URL ?>mahasiswa/ganti_password.php"><i class="bi bi-key me-2"></i>Ganti Password</a></li>
                    <?php endif; ?>
                    <li><a class="dropdown-item text-danger" href="<?= BASE_URL ?>logout.php"><i class="bi bi-box-arrow-right me-2"></i>Logout</a></li>
                </ul>
            </div>
        </nav>

        <main class="p-4">
