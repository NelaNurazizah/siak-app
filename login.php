<?php
require_once __DIR__ . '/config/config.php';

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$errorMessage = $_SESSION['login_error'] ?? null;
unset($_SESSION['login_error']);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Login - <?= htmlspecialchars(APP_NAME) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body class="login-page">
    <div class="login-card card">
        <div class="card-header-custom">
            <div class="icon-circle"><i class="bi bi-mortarboard-fill"></i></div>
            <h4 class="fw-bold mb-1"><?= htmlspecialchars(APP_NAME) ?></h4>
            <p class="text-muted small mb-0">Silakan masuk menggunakan akun Anda</p>
        </div>
        <div class="card-body">
            <?php if ($errorMessage): ?>
                <div class="alert alert-danger d-flex align-items-center" role="alert">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i>
                    <div><?= htmlspecialchars($errorMessage) ?></div>
                </div>
            <?php endif; ?>
            <form action="auth/proses_login.php" method="POST" class="needs-validation" novalidate>
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                <div class="mb-3">
                    <label for="username" class="form-label">Username</label>
                    <div class="input-group">
                        <span class="input-group-text bg-white"><i class="bi bi-person-fill"></i></span>
                        <input type="text" class="form-control" id="username" name="username" placeholder="Masukkan username" required autofocus>
                        <div class="invalid-feedback">Username wajib diisi.</div>
                    </div>
                </div>
                <div class="mb-3">
                    <label for="password" class="form-label">Password</label>
                    <div class="input-group">
                        <span class="input-group-text bg-white"><i class="bi bi-lock-fill"></i></span>
                        <input type="password" class="form-control" id="password" name="password" placeholder="Masukkan password" required minlength="6">
                        <span class="input-group-text bg-white toggle-password"><i class="bi bi-eye"></i></span>
                        <div class="invalid-feedback">Password minimal 6 karakter.</div>
                    </div>
                </div>
                <div class="d-grid mt-4">
                    <button type="submit" class="btn btn-primary btn-login text-white">
                        <i class="bi bi-box-arrow-in-right me-1"></i> Masuk
                    </button>
                </div>
            </form>
            <p class="text-center text-muted small mt-4 mb-0">&copy; <?= date('Y') ?> <?= htmlspecialchars(APP_NAME) ?></p>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/login.js"></script>
</body>
</html>