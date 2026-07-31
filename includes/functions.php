<?php
/**
 * includes/functions.php
 * Kumpulan fungsi bantuan umum yang dipakai di berbagai halaman,
 * terutama untuk modul CRUD (flash message, redirect, dsb).
 */

/**
 * Menyimpan pesan flash ke session untuk ditampilkan sekali
 * setelah redirect (pola Post-Redirect-Get).
 *
 * @param string $type    'success' | 'danger' | 'warning' | 'info'
 * @param string $message Isi pesan yang ditampilkan ke user
 */
function setFlash(string $type, string $message): void
{
    $_SESSION['flash'] = [
        'type'    => $type,
        'message' => $message,
    ];
}

/**
 * Mengambil pesan flash dari session (jika ada), lalu menghapusnya
 * agar tidak muncul lagi pada request berikutnya.
 */
function getFlash(): ?array
{
    if (empty($_SESSION['flash'])) {
        return null;
    }

    $flash = $_SESSION['flash'];
    unset($_SESSION['flash']);

    return $flash;
}

/**
 * Redirect ke URL relatif terhadap BASE_URL, lalu hentikan eksekusi.
 */
function redirectTo(string $path): void
{
    header('Location: ' . BASE_URL . $path);
    exit;
}

/**
 * Membersihkan string input dari whitespace berlebih.
 * (Escaping output tetap dilakukan terpisah dengan htmlspecialchars()
 * saat menampilkan data ke HTML.)
 */
function cleanInput(string $value): string
{
    return trim($value);
}

/**
 * Menghasilkan HTML navigasi pagination Bootstrap.
 * Mempertahankan parameter pencarian (?q=) yang sedang aktif.
 *
 * @param int    $halamanSaatIni Halaman yang sedang aktif (mulai dari 1)
 * @param int    $totalHalaman   Total jumlah halaman
 * @param string $baseUrl        URL halaman (relatif terhadap BASE_URL), contoh: 'admin/mahasiswa.php'
 * @param array  $queryParams    Parameter query tambahan yang perlu dipertahankan (contoh: ['q' => 'budi'])
 */
function renderPagination(int $halamanSaatIni, int $totalHalaman, string $baseUrl, array $queryParams = []): string
{
    if ($totalHalaman <= 1) {
        return '';
    }

    $buatUrl = function (int $page) use ($baseUrl, $queryParams): string {
        $queryParams['page'] = $page;
        return BASE_URL . $baseUrl . '?' . http_build_query($queryParams);
    };

    $html = '<nav aria-label="Navigasi halaman"><ul class="pagination justify-content-center mb-0">';

    // Tombol "Sebelumnya"
    $disabledPrev = $halamanSaatIni <= 1 ? 'disabled' : '';
    $html .= '<li class="page-item ' . $disabledPrev . '">
        <a class="page-link" href="' . htmlspecialchars($buatUrl(max(1, $halamanSaatIni - 1))) . '">&laquo;</a>
    </li>';

    for ($i = 1; $i <= $totalHalaman; $i++) {
        $active = $i === $halamanSaatIni ? 'active' : '';
        $html .= '<li class="page-item ' . $active . '">
            <a class="page-link" href="' . htmlspecialchars($buatUrl($i)) . '">' . $i . '</a>
        </li>';
    }

    // Tombol "Berikutnya"
    $disabledNext = $halamanSaatIni >= $totalHalaman ? 'disabled' : '';
    $html .= '<li class="page-item ' . $disabledNext . '">
        <a class="page-link" href="' . htmlspecialchars($buatUrl(min($totalHalaman, $halamanSaatIni + 1))) . '">&raquo;</a>
    </li>';

    $html .= '</ul></nav>';

    return $html;
}

/**
 * Mengonversi nilai angka (0-100) menjadi nilai huruf dan bobot IPK,
 * sesuai skala penilaian yang ditetapkan pada proyek ini:
 *   A = 4.00 (85-100)   A- = 3.75 (80-84)   B+ = 3.50 (75-79)
 *   B = 3.00 (70-74)    B- = 2.75 (65-69)   C+ = 2.50 (60-64)
 *   C = 2.00 (55-59)    D  = 1.00 (40-54)   E  = 0.00 (0-39)
 *
 * @return array{0: string, 1: float} [nilai_huruf, bobot]
 */
function hitungNilaiHuruf(float $angka): array
{
    return match (true) {
        $angka >= 85 => ['A', 4.00],
        $angka >= 80 => ['A-', 3.75],
        $angka >= 75 => ['B+', 3.50],
        $angka >= 70 => ['B', 3.00],
        $angka >= 65 => ['B-', 2.75],
        $angka >= 60 => ['C+', 2.50],
        $angka >= 55 => ['C', 2.00],
        $angka >= 40 => ['D', 1.00],
        default => ['E', 0.00],
    };
}
