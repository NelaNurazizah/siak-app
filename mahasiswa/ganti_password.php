<?php
/**
 * mahasiswa/ganti_password.php
 * Halaman bagi mahasiswa untuk mengubah password akun mereka sendiri.
 * Proses ditangani oleh mahasiswa/ganti_password_proses.php.
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';

requireRole('mahasiswa');

$pageTitle = 'Ganti Password';
require_once __DIR__ . '/../includes/header.php';
?>

<h5 class="fw-bold mb-3">Ganti Password</h5>

<div class="row">
    <div class="col-md-6">
        <div class="card card-stat">
            <div class="card-body">
                <form action="<?= BASE_URL ?>mahasiswa/ganti_password_proses.php" method="POST" class="needs-validation" novalidate>
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">

                    <div class="mb-3">
                        <label class="form-label">Password Lama</label>
                        <input type="password" name="password_lama" class="form-control" required>
                        <div class="invalid-feedback">Password lama wajib diisi.</div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Password Baru</label>
                        <input type="password" name="password_baru" id="password_baru" class="form-control" required minlength="6">
                        <div class="invalid-feedback">Password baru minimal 6 karakter.</div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Konfirmasi Password Baru</label>
                        <input type="password" name="konfirmasi_password" id="konfirmasi_password" class="form-control" required minlength="6">
                        <div class="invalid-feedback">Konfirmasi password wajib diisi dan harus sama.</div>
                    </div>

                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-key-fill me-1"></i> Simpan Password Baru
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
// Validasi tambahan: pastikan konfirmasi password sama persis dengan password baru
(function () {
    const form = document.querySelector('form.needs-validation');
    const passwordBaru = document.getElementById('password_baru');
    const konfirmasi = document.getElementById('konfirmasi_password');

    function cekKecocokan() {
        if (passwordBaru.value !== konfirmasi.value) {
            konfirmasi.setCustomValidity('Password tidak cocok');
        } else {
            konfirmasi.setCustomValidity('');
        }
    }

    passwordBaru.addEventListener('input', cekKecocokan);
    konfirmasi.addEventListener('input', cekKecocokan);
})();
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
