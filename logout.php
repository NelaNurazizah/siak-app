<?php
/**
 * logout.php
 * Menghancurkan session pengguna dan mengarahkan kembali ke halaman login.
 */

require_once __DIR__ . '/config/config.php';

// Kosongkan semua data session
$_SESSION = [];

// Hapus cookie session dari browser jika ada
if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params['path'],
        $params['domain'],
        $params['secure'],
        $params['httponly']
    );
}

// Hancurkan session di server
session_destroy();

header('Location: ' . BASE_URL . 'login.php');
exit;
