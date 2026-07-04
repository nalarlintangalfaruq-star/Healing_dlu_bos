<?php
/**
 * ═══════════════════════════════════════════════════════════════════════════
 * PARI ADVENTURE - KONFIGURASI SISTEM
 * Konfigurasi database, konstanta global, dan helper functions
 * ═══════════════════════════════════════════════════════════════════════════
 */

// ─── ENVIRONMENT & DEBUG ────────────────────────────────────────────────────
define('ENV_DEBUG', true); // Set ke false di production
if (ENV_DEBUG) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
}

// ─── KONSTANTA APLIKASI ─────────────────────────────────────────────────────
define('APP_NAME', 'Pari Adventure');
define('APP_VERSION', '1.0.0');
define('BASE_URL', 'http://localhost/pari_adventure'); // ✅ FIXED: Changed from pari-adventure to pari_adventure
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'pari_adventure');
define('DB_CHARSET', 'utf8mb4');
define('UPLOAD_PATH', $_SERVER['DOCUMENT_ROOT'] . '/pari_adventure/uploads/'); // ✅ ADDED: Path folder upload

// ─── SESSION CONFIG (HARUS SEBELUM session_start()) ─────────────────────────
// ⚠️ PENTING: Semua ini_set() HARUS sebelum session_start()
ini_set('session.cookie_secure', false); // Set true untuk HTTPS production
ini_set('session.cookie_httponly', true);
ini_set('session.cookie_samesite', 'Lax');
ini_set('session.gc_maxlifetime', 3600); // 1 jam

// Session dimulai SETELAH semua ini_set() selesai
@session_start();

// ═══════════════════════════════════════════════════════════════════════════
// DATABASE CONNECTION (Singleton Pattern with PDO)
// ═══════════════════════════════════════════════════════════════════════════

$_pdo = null;

function getDB() {
    global $_pdo;
    
    if ($_pdo !== null) {
        return $_pdo;
    }
    
    try {
        $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET;
        $_pdo = new PDO(
            $dsn,
            DB_USER,
            DB_PASS,
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]
        );
        return $_pdo;
    } catch (PDOException $e) {
        if (ENV_DEBUG) {
            die('Database Connection Error: ' . $e->getMessage());
        } else {
            die('Database connection failed. Please try again later.');
        }
    }
}

// ═══════════════════════════════════════════════════════════════════════════
// HELPER FUNCTIONS - Format & Utility
// ═══════════════════════════════════════════════════════════════════════════

/**
 * Format angka ke mata uang Rupiah
 * @param float $amount
 * @return string
 */
function formatRupiah($amount) {
    $amount = (float)$amount;
    return 'Rp ' . number_format($amount, 0, ',', '.');
}

/**
 * Generate kode booking unik dengan format PA-YYYYMMDD-XXXXXX
 * @return string
 */
function generateKodeBooking() {
    $date = date('Ymd');
    $random = strtoupper(bin2hex(random_bytes(3))); // 6 karakter hex random
    return 'PA-' . $date . '-' . $random;
}

/**
 * Sanitasi input string
 * @param string $input
 * @return string
 */
function sanitize($input) {
    $input = trim($input);
    $input = htmlspecialchars($input, ENT_QUOTES, 'UTF-8');
    $input = strip_tags($input);
    return $input;
}

/**
 * Set flash message (untuk redirect pages)
 * @param string $type (success, danger, warning, info)
 * @param string $message
 */
function setFlash($type, $message) {
    $_SESSION['flash'] = [
        'type' => $type,
        'message' => $message
    ];
}

/**
 * Get dan hapus flash message
 * @return array|null
 */
function getFlash() {
    if (!isset($_SESSION['flash'])) {
        return null;
    }
    $flash = $_SESSION['flash'];
    unset($_SESSION['flash']);
    return $flash;
}

/**
 * Redirect ke URL dengan exit
 * @param string $url
 */
function redirect($url) {
    header('Location: ' . $url);
    exit;
}

/**
 * Cek apakah user adalah admin
 * @return bool
 */
function isAdminLoggedIn() {
    return isset($_SESSION['admin_id']) && $_SESSION['user_type'] === 'admin';
}

/**
 * Cek apakah user adalah regular user
 * @return bool
 */
function isUserLoggedIn() {
    return isset($_SESSION['user_id']) && $_SESSION['user_type'] === 'user';
}

/**
 * Ambil data user saat ini dari session
 * @return array|null
 */
function getCurrentUser() {
    if (!isUserLoggedIn()) {
        return null;
    }
    $db = getDB();
    $stmt = $db->prepare('SELECT * FROM users WHERE id = ? LIMIT 1');
    $stmt->execute([(int)$_SESSION['user_id']]);
    return $stmt->fetch();
}

/**
 * Ambil data admin saat ini dari session
 * @return array|null
 */
function getCurrentAdmin() {
    if (!isAdminLoggedIn()) {
        return null;
    }
    $db = getDB();
    $stmt = $db->prepare('SELECT * FROM admins WHERE id = ? LIMIT 1');
    $stmt->execute([(int)$_SESSION['admin_id']]);
    return $stmt->fetch();
}

/**
 * Format tanggal ke format Indonesia
 * @param string $date (format Y-m-d)
 * @return string
 */
function formatTanggalID($date) {
    $months = [
        '01' => 'Januari', '02' => 'Februari', '03' => 'Maret', '04' => 'April',
        '05' => 'Mei', '06' => 'Juni', '07' => 'Juli', '08' => 'Agustus',
        '09' => 'September', '10' => 'Oktober', '11' => 'November', '12' => 'Desember'
    ];
    $d = explode('-', $date);
    return $d[2] . ' ' . $months[$d[1]] . ' ' . $d[0];
}

/**
 * Hitung selisih hari dari tanggal tertentu
 * @param string $date (format Y-m-d)
 * @return int
 */
function hitungHariLagi($date) {
    $today = new DateTime(date('Y-m-d'));
    $trip = new DateTime($date);
    $diff = $trip->diff($today);
    return (int)$diff->days;
}

/**
 * Cek apakah file upload valid
 * @param array $file $_FILES['field']
 * @param array $allowed_ext (misal: ['jpg', 'png', 'pdf'])
 * @param int $max_size (dalam bytes)
 * @return array ['valid' => bool, 'message' => string]
 */
function validateFileUpload($file, $allowed_ext = ['jpg', 'png', 'pdf'], $max_size = 3145728) {
    if (!isset($file['tmp_name']) || $file['error'] !== UPLOAD_ERR_OK) {
        return ['valid' => false, 'message' => 'File upload error.'];
    }
    
    if ($file['size'] > $max_size) {
        return ['valid' => false, 'message' => 'File size exceeds maximum allowed (' . round($max_size/1024/1024, 1) . ' MB).'];
    }
    
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, $allowed_ext)) {
        return ['valid' => false, 'message' => 'File type not allowed. Allowed: ' . implode(', ', $allowed_ext)];
    }
    
    return ['valid' => true, 'message' => 'File valid'];
}

/**
 * Generate random string untuk password reset token, verifikasi email, dll
 * @param int $length
 * @return string
 */
function generateToken($length = 32) {
    return bin2hex(random_bytes($length / 2));
}

?>