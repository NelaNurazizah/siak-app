<?php
/**
 * config/database.php
 *
 * Menyediakan koneksi ke database MySQL menggunakan PDO.
 * Menggunakan pola Singleton agar koneksi hanya dibuat satu kali
 * per request, kemudian dipakai ulang oleh seluruh halaman.
 *
 * Cara pakai di file lain:
 *   require_once __DIR__ . '/../config/database.php';
 *   $db = Database::getConnection();
 *   $stmt = $db->prepare("SELECT * FROM users WHERE username = :username");
 *   $stmt->execute([':username' => $username]);
 */

class Database
{
    // Ganti sesuai konfigurasi MySQL di XAMPP masing-masing
    private static string $host = 'localhost';
    private static string $dbname = 'db_sia';
    private static string $username = 'root';
    private static string $password = '';
    private static string $charset = 'utf8mb4';

    private static ?PDO $connection = null;

    /**
     * Mengambil instance koneksi PDO (singleton).
     * Jika koneksi belum ada, akan dibuat terlebih dahulu.
     */
    public static function getConnection(): PDO
    {
        if (self::$connection === null) {
            $dsn = "mysql:host=" . self::$host . ";dbname=" . self::$dbname . ";charset=" . self::$charset;

            $options = [
                // Lempar exception jika terjadi error query, bukan silent fail
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                // Kembalikan hasil query sebagai associative array
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                // Gunakan native prepared statement (bukan emulasi) untuk keamanan maksimal
                PDO::ATTR_EMULATE_PREPARES => false,
            ];

            try {
                self::$connection = new PDO($dsn, self::$username, self::$password, $options);
            } catch (PDOException $e) {
                // Jangan tampilkan detail koneksi (host/username/password) ke user
                error_log('Database Connection Error: ' . $e->getMessage());
                die('Koneksi ke database gagal. Silakan hubungi administrator atau periksa konfigurasi database Anda.');
            }
        }

        return self::$connection;
    }

    /**
     * Mencegah instance class ini dibuat langsung (new Database()).
     */
    private function __construct()
    {
    }

    /**
     * Mencegah class ini di-clone.
     */
    private function __clone()
    {
    }
}
