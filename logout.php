<?php
/**
 * ═══════════════════════════════════════════════════════════════════════════
 * PARI ADVENTURE - LOGOUT
 * Menghapus session dan redirect ke halaman login
 * ═══════════════════════════════════════════════════════════════════════════
 */

require_once __DIR__ . '/includes/auth.php';

// Memanggil fungsi logout dari auth.php untuk:
// 1. Menghapus semua session
// 2. Menghancurkan session cookie
// 3. Redirect ke BASE_URL/login.php (http://localhost/pari_adventure/login.php)
logout();

?>