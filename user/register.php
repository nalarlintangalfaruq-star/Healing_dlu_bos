<?php
require_once 'includes/auth.php';

if (isUserLoggedIn()) redirect('user/dashboard.php');

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama     = htmlspecialchars(trim($_POST['nama'] ?? ''));
    $email    = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    $telepon  = htmlspecialchars(trim($_POST['telepon'] ?? ''));
    $password = $_POST['password'] ?? '';
    $confirm  = $_POST['confirm_password'] ?? '';

    if (empty($nama) || empty($email) || empty($password)) {
        $error = 'Nama, email, dan password wajib diisi.';
    } elseif (strlen($password) < 8) {
        $error = 'Password minimal 8 karakter.';
    } elseif ($password !== $confirm) {
        $error = 'Konfirmasi password tidak cocok.';
    } else {
        // ======================================================================
        // PERBAIKAN: Langsung simpan ke database tanpa fungsi registerUser()
        // ======================================================================
        try {
            $db = getDB(); // Mengambil koneksi database dari auth.php

            // 1. Cek apakah email sudah terdaftar sebelumnya
            $stmt = $db->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->execute([$email]);
            
            if ($stmt->fetch()) {
                $error = 'Email sudah terdaftar. Silakan gunakan email lain atau langsung Masuk.';
            } else {
                // 2. Enkripsi password agar aman di database
                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

                // 3. Masukkan data user baru ke tabel
                $stmt = $db->prepare("INSERT INTO users (nama, email, password, no_telepon, status) VALUES (?, ?, ?, ?, 'aktif')");
                $stmt->execute([$nama, $email, $hashedPassword, $telepon]);

                // 4. Beri notifikasi sukses dan pindahkan ke halaman login
                setFlash('success', 'Pendaftaran akun berhasil! Silakan masuk.');
                redirect('login.php');
                exit;
            }
        } catch (PDOException $e) {
            $error = 'Terjadi kesalahan database: ' . $e->getMessage();
        }
        // ======================================================================
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Daftar Akun - Pari Adventure</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Fredoka+One&family=Nunito:wght@400;600;700;800;900&display=swap" rel="stylesheet">
<style>
  :root {
    --yellow:  #FFD93D;
    --orange:  #FF6B35;
    --pink:    #FF4FA1;
    --purple:  #9B59F5;
    --blue:    #3EC6FF;
    --green:   #4ECDC4;
    --dark:    #1A0533;
    --card-bg: #2D0D5E;
    --input-bg:#3D1580;
    --white:   #FFFFFF;
    --shadow-color: rgba(0,0,0,0.5);
  }

  *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

  body {
    font-family: 'Nunito', sans-serif;
    background: var(--dark);
    min-height: 100vh;
    overflow-x: hidden;
    position: relative;
  }

  /* ── ANIMATED BACKGROUND ── */
  .bg-scene {
    position: fixed; inset: 0; z-index: 0; overflow: hidden;
    background: radial-gradient(ellipse at 20% 50%, #2D0060 0%, #0A001A 60%);
  }

  .blob {
    position: absolute; border-radius: 50%; filter: blur(60px); opacity: 0.35;
    animation: blobFloat 8s ease-in-out infinite alternate;
  }
  .blob-1 { width:400px; height:400px; background:var(--purple); top:-100px; left:-80px; animation-delay:0s; }
  .blob-2 { width:300px; height:300px; background:var(--pink);   bottom:-80px; right:-60px; animation-delay:2s; }
  .blob-3 { width:250px; height:250px; background:var(--blue);   top:40%; left:60%; animation-delay:4s; }
  @keyframes blobFloat {
    from { transform: translate(0,0) scale(1); }
    to   { transform: translate(30px,40px) scale(1.15); }
  }

  /* ── FLOATING BLOBS (game elements) ── */
  .floater {
    position: fixed; border-radius: 50%; pointer-events: none; z-index: 1;
    animation: floaterUp linear infinite;
  }
  @keyframes floaterUp {
    0%   { transform: translateY(110vh) rotate(0deg)   scale(0.8); opacity:0; }
    10%  { opacity:.7; }
    90%  { opacity:.5; }
    100% { transform: translateY(-15vh)  rotate(360deg) scale(1.2); opacity:0; }
  }

  /* ── STARS ── */
  .star {
    position: fixed; pointer-events: none; z-index: 1; font-size: 20px;
    animation: starTwinkle 3s ease-in-out infinite;
  }
  @keyframes starTwinkle {
    0%,100% { opacity:.2; transform:scale(1); }
    50%      { opacity:1;  transform:scale(1.4) rotate(20deg); }
  }

  /* ── MAIN LAYOUT ── */
  .page-wrapper {
    position: relative; z-index: 10;
    min-height: 100vh;
    display: grid;
    grid-template-columns: 1fr 1fr;
  }

  /* ── LEFT PANEL ── */
  .panel-left {
    display: flex; flex-direction: column; align-items: center; justify-content: center;
    padding: 3rem 2.5rem;
    position: relative; overflow: hidden;
  }

  .panel-left-bg {
    position:absolute; inset:0; z-index:0;
    background: linear-gradient(160deg, rgba(155,89,245,.25) 0%, rgba(255,75,161,.15) 100%);
    border-right: 2px solid rgba(255,255,255,0.06);
  }

  .mascot-area {
    position: relative; z-index: 2; text-align: center;
  }

  .mascot-title {
    font-family: 'Fredoka One', cursive; font-size: 48px; color: var(--white);
    text-shadow: 4px 4px 0 var(--pink), 8px 8px 0 var(--purple);
    margin-bottom: 20px; line-height: 1.1;
  }

  .mascot-emoji {
    font-size: 120px; margin: 30px 0; animation: bounce 2s infinite;
  }
  @keyframes bounce {
    0%, 100% { transform: translateY(0); }
    50% { transform: translateY(-20px); }
  }

  .mascot-text {
    font-size: 16px; color: rgba(255,255,255,0.8); max-width: 300px;
    line-height: 1.6; margin: 20px 0;
  }

  /* ── RIGHT PANEL ── */
  .panel-right {
    display: flex; flex-direction: column; align-items: center; justify-content: center;
    padding: 3rem 2.5rem;
    position: relative;
  }

  .panel-right-bg {
    position: absolute; inset: 0; z-index: 0;
    background: linear-gradient(160deg, rgba(62,198,255,.15) 0%, rgba(78,205,196,.1) 100%);
  }

  .form-container {
    position: relative; z-index: 2; width: 100%; max-width: 360px;
  }

  .form-title {
    font-family: 'Fredoka One', cursive; font-size: 32px;
    color: var(--white); text-shadow: 2px 2px 0 var(--pink);
    margin-bottom: 8px;
  }

  .form-subtitle {
    font-size: 14px; color: rgba(255,255,255,0.7);
    margin-bottom: 28px;
  }

  .form-group {
    margin-bottom: 20px;
  }

  .form-group label {
    display: block; font-size: 12px; font-weight: 700;
    color: rgba(255,255,255,0.9); text-transform: uppercase;
    letter-spacing: 0.5px; margin-bottom: 8px;
  }

  .form-group input {
    width: 100%; padding: 14px 16px; background: var(--input-bg);
    border: 2px solid rgba(255,255,255,0.1); border-radius: 12px;
    font-size: 14px; color: var(--white); font-family: 'Nunito', sans-serif;
    outline: none; transition: all .3s;
  }

  .form-group input::placeholder {
    color: rgba(255,255,255,0.5);
  }

  .form-group input:focus {
    border-color: var(--blue); background: rgba(62,198,255,0.1);
    box-shadow: 0 0 0 3px rgba(62,198,255,0.2);
  }

  .form-check {
    display: flex; align-items: center; font-size: 14px;
    color: rgba(255,255,255,0.8); margin-bottom: 20px;
  }

  .form-check input {
    width: 20px; height: 20px; margin-right: 8px;
    cursor: pointer;
  }

  .form-check a {
    color: var(--blue); text-decoration: none;
  }

  .form-check a:hover {
    text-decoration: underline;
  }

  .btn-submit {
    width: 100%; padding: 16px; background: linear-gradient(135deg, var(--blue), var(--green));
    color: var(--dark); border: none; border-radius: 12px;
    font-family: 'Fredoka One', cursive; font-size: 16px; font-weight: 800;
    cursor: pointer; transition: all .3s; margin-bottom: 16px;
    box-shadow: 0 8px 0 var(--shadow-color);
  }

  .btn-submit:hover {
    transform: translateY(-2px);
    box-shadow: 0 10px 0 var(--shadow-color);
  }

  .btn-submit:active {
    transform: translateY(4px);
    box-shadow: 0 3px 0 var(--shadow-color);
  }

  .form-footer {
    text-align: center; font-size: 14px; color: rgba(255,255,255,0.8);
  }

  .form-footer a {
    color: var(--blue); text-decoration: none; font-weight: 700;
  }

  .form-footer a:hover {
    text-decoration: underline;
  }

  .alert {
    padding: 14px 16px; background: rgba(255, 107, 107, 0.2);
    border: 2px solid #ff6b6b; border-radius: 10px;
    color: #ff9999; font-size: 14px; margin-bottom: 20px;
  }

  .alert-success {
    background: rgba(76, 205, 140, 0.2);
    border-color: #4ccd8c;
    color: #7dd9b1;
  }

  @media (max-width: 768px) {
    .page-wrapper { grid-template-columns: 1fr; }
    .panel-left { display: none; }
    .panel-right { padding: 2rem 1.5rem; }
    .form-container { max-width: 100%; }
  }
</style>
</head>
<body>
<div class="bg-scene">
  <div class="blob blob-1"></div>
  <div class="blob blob-2"></div>
  <div class="blob blob-3"></div>
</div>

<div class="page-wrapper">

  <!-- ═══════════════════════════════════════════════════════════════ -->
  <!-- LEFT: MASCOT & BRANDING                                        -->
  <!-- ═══════════════════════════════════════════════════════════════ -->
  <div class="panel-left">
    <div class="mascot-area">
      <div class="mascot-title">Selamat Datang di<br>Pari Adventure! 🏝️</div>
      <div class="mascot-emoji">🏖️</div>
      <div class="mascot-text">
        Bergabunglah dengan ribuan petualang dan temukan pengalaman tak terlupakan di Pulau Pari yang eksotis!
      </div>
    </div>
  </div>

  <!-- ═══════════════════════════════════════════════════════════════ -->
  <!-- RIGHT: FORM REGISTER                                           -->
  <!-- ═══════════════════════════════════════════════════════════════ -->
  <div class="panel-right">
    <div class="form-container">
      <div class="form-title">Daftar</div>
      <div class="form-subtitle">Buat akun baru untuk memulai petualangan</div>

      <?php if ($error): ?>
      <div class="alert"><?= htmlspecialchars($error) ?></div>
      <?php endif; ?>

      <form method="POST" action="register.php">

        <div class="form-group">
          <label>Nama Lengkap *</label>
          <input type="text" name="nama" placeholder="Nama Anda" required
                 value="<?= htmlspecialchars($_POST['nama'] ?? '') ?>">
        </div>

        <div class="form-group">
          <label>Email *</label>
          <input type="email" name="email" placeholder="email@example.com" required
                 value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
        </div>

        <div class="form-group">
          <label>Nomor Telepon</label>
          <input type="tel" name="telepon" placeholder="+62 8xx xxxx xxxx"
                 value="<?= htmlspecialchars($_POST['telepon'] ?? '') ?>">
        </div>

        <div class="form-group">
          <label>Password *</label>
          <input type="password" name="password" placeholder="Min. 8 karakter" required>
        </div>

        <div class="form-group">
          <label>Konfirmasi Password *</label>
          <input type="password" name="confirm_password" placeholder="Ketik ulang password" required>
        </div>

        <div class="form-check">
          <input type="checkbox" id="terms" required>
          <label for="terms" style="margin-bottom:0;">
            Saya setuju dengan <a href="#">Syarat & Ketentuan</a>
          </label>
        </div>

        <button type="submit" class="btn-submit">🚀 Daftar Sekarang</button>

      </form>

      <div class="form-footer">
        Sudah punya akun? <a href="login.php">Masuk di sini</a>
      </div>

    </div>
  </div>

</div>

</body>
</html>