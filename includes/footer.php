<?php
/**
 * includes/footer.php
 * Penutup halaman dashboard: tutup <main>, script bundle, dsb.
 */

if (!defined('APP_INIT')) {
    die('Akses langsung ke file ini tidak diizinkan.');
}
?>
        </main>

        <footer class="text-center text-muted small py-3 border-top bg-white">
            &copy; <?= date('Y') ?> <?= htmlspecialchars(APP_NAME) ?>. Seluruh hak cipta dilindungi.
        </footer>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= BASE_URL ?>assets/js/main.js"></script>
</body>
</html>
