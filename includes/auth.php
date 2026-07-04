<?php
/**
 * ═══════════════════════════════════════════════════════════════════════════
 * PARI ADVENTURE - AUTHENTICATION SYSTEM
 * Login, Register, Logout, dan Session Management
 * ═══════════════════════════════════════════════════════════════════════════
 */

require_once __DIR__ . '/config.php';

// ═══════════════════════════════════════════════════════════════════════════
// LOGIN USER (Regular Customer)
// ═══════════════════════════════════════════════════════════════════════════

/**
 * Login user berdasarkan email dan password
 * @param string $email
 * @param string $password
 * @return array ['success' => bool, 'message' => string, 'user' => array|null]
 */
function loginUser($email, $password) {
    try {
        $db = getDB();
        $email = sanitize($email);
        
        $stmt = $db->prepare('SELECT id, nama, email, password, status FROM users WHERE email = ? LIMIT 1');
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        
        if (!$user) {
            return [
                'success' => false,
                'message' => 'Email atau password salah.'
            ];
        }
        
        if (!password_verify($password, $user['password'])) {
            return [
                'success' => false,
                'message' => 'Email atau password salah.'
            ];
        }
        
        if ($user['status'] !== 'aktif') {
            return [
                'success' => false,
                'message' => 'Akun Anda tidak aktif. Hubungi admin.'
            ];
        }
        
        // Set session untuk user
        $_SESSION['user_id'] = (int)$user['id'];
        $_SESSION['user_name'] = $user['nama'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['user_type'] = 'user';
        
        return [
            'success' => true,
            'message' => 'Login berhasil! Selamat datang, ' . $user['nama'],
            'user' => $user
        ];
        
    } catch (Exception $e) {
        return [
            'success' => false,
            'message' => 'Terjadi kesalahan: ' . $e->getMessage()
        ];
    }
}

// ═══════════════════════════════════════════════════════════════════════════
// REGISTER USER (Membuat Akun Baru)
// ═══════════════════════════════════════════════════════════════════════════

/**
 * Register akun user baru
 * @param array $data (nama, email, password, confirm_password, no_telepon, alamat)
 * @return array ['success' => bool, 'message' => string]
 */
function registerUser($data) {
    try {
        $db = getDB();
        
        // Sanitasi input
        $nama = sanitize($data['nama'] ?? '');
        $email = filter_var($data['email'] ?? '', FILTER_SANITIZE_EMAIL);
        $password = $data['password'] ?? '';
        $confirm = $data['confirm_password'] ?? '';
        $telepon = sanitize($data['no_telepon'] ?? '');
        $alamat = sanitize($data['alamat'] ?? '');
        
        // Validasi
        if (empty($nama) || empty($email) || empty($password) || empty($confirm)) {
            return ['success' => false, 'message' => 'Semua field harus diisi.'];
        }
        
        if (strlen($password) < 8) {
            return ['success' => false, 'message' => 'Password minimal 8 karakter.'];
        }
        
        if ($password !== $confirm) {
            return ['success' => false, 'message' => 'Password tidak cocok.'];
        }
        
        // Cek email duplicate
        $stmt = $db->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            return ['success' => false, 'message' => 'Email sudah terdaftar. Gunakan email lain.'];
        }
        
        // Hash password
        $hashed = password_hash($password, PASSWORD_DEFAULT);
        
        // Insert ke database
        $stmt = $db->prepare("
            INSERT INTO users (nama, email, password, no_telepon, alamat, status, email_verified)
            VALUES (?, ?, ?, ?, ?, 'aktif', '0000-00-00 00:00:00')
        ");
        $stmt->execute([$nama, $email, $hashed, $telepon, $alamat]);
        
        return [
            'success' => true,
            'message' => 'Akun berhasil dibuat! Silakan login dengan email dan password Anda.'
        ];
        
    } catch (Exception $e) {
        return [
            'success' => false,
            'message' => 'Terjadi kesalahan: ' . $e->getMessage()
        ];
    }
}

// ═══════════════════════════════════════════════════════════════════════════
// LOGIN ADMIN (Akun Admin Terpisah)
// ═══════════════════════════════════════════════════════════════════════════

/**
 * Login admin menggunakan tabel admins yang terpisah
 * @param string $email
 * @param string $password
 * @return array ['success' => bool, 'message' => string, 'admin' => array|null]
 */
function loginAdmin($email, $password) {
    try {
        $db = getDB();
        $email = sanitize($email);
        
        // Query ke tabel ADMINS (terpisah dari users)
        $stmt = $db->prepare('SELECT id, nama, email, password, role FROM admins WHERE email = ? LIMIT 1');
        $stmt->execute([$email]);
        $admin = $stmt->fetch();
        
        if (!$admin) {
            return [
                'success' => false,
                'message' => 'Email atau password admin salah.'
            ];
        }
        
        if (!password_verify($password, $admin['password'])) {
            return [
                'success' => false,
                'message' => 'Email atau password admin salah.'
            ];
        }
        
        // Set session untuk admin
        $_SESSION['admin_id'] = (int)$admin['id'];
        $_SESSION['admin_name'] = $admin['nama'];
        $_SESSION['admin_email'] = $admin['email'];
        $_SESSION['admin_role'] = $admin['role'];
        $_SESSION['user_type'] = 'admin';
        
        return [
            'success' => true,
            'message' => 'Login admin berhasil! Selamat datang, ' . $admin['nama'],
            'admin' => $admin
        ];
        
    } catch (Exception $e) {
        return [
            'success' => false,
            'message' => 'Terjadi kesalahan: ' . $e->getMessage()
        ];
    }
}

// ═══════════════════════════════════════════════════════════════════════════
// LOGOUT
// ═══════════════════════════════════════════════════════════════════════════

/**
 * Logout dan hapus session
 */
function logout() {
    session_unset();
    session_destroy();
    redirect(BASE_URL . '/login.php');
}

// ═══════════════════════════════════════════════════════════════════════════
// MIDDLEWARE - PROTECT ROUTES
// ═══════════════════════════════════════════════════════════════════════════

/**
 * Middleware: Proteksi halaman user (hanya user yang sudah login)
 * Jika belum login, redirect ke login.php
 */
function requireUserLogin() {
    if (!isUserLoggedIn()) {
        setFlash('danger', '⚠️ Silakan login terlebih dahulu.');
        redirect(BASE_URL . '/login.php');
    }
}

/**
 * Middleware: Proteksi halaman admin (hanya admin yang sudah login)
 * Jika belum login admin, redirect ke admin/login.php
 */
function requireAdminLogin() {
    if (!isAdminLoggedIn()) {
        setFlash('danger', '⚠️ Akses ditolak. Silakan login sebagai admin.');
        redirect(BASE_URL . '/admin/login.php');
    }
}

/**
 * Middleware: Cegah user yang sudah login membuka halaman login/register
 * (redirect ke dashboard jika sudah login)
 */
function redirectIfLoggedIn() {
    if (isUserLoggedIn()) {
        redirect(BASE_URL . '/user/dashboard.php');
    }
    if (isAdminLoggedIn()) {
        redirect(BASE_URL . '/admin/dashboard.php');
    }
}

?>