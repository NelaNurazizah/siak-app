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
