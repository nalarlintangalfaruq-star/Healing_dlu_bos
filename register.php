<?php
require_once 'includes/auth.php';

if (isUserLoggedIn()) redirect(BASE_URL . '/user/dashboard.php');

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
             header("Location: login.php");
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
    position: relative; z-index: 2; text-align: center; margin-bottom: 2.5rem;
  }

  .mascot-bounce {
    display: inline-block;
    animation: mascotBounce 2s ease-in-out infinite;
  }
  @keyframes mascotBounce {
    0%,100% { transform: translateY(0) rotate(-3deg); }
    50%     { transform: translateY(-20px) rotate(3deg); }
  }

  .mascot-blob {
    width: 160px; height: 160px; border-radius: 60% 40% 55% 45% / 50% 60% 40% 50%;
    background: linear-gradient(135deg, var(--yellow) 0%, var(--orange) 100%);
    display: flex; align-items: center; justify-content: center;
    font-size: 70px; margin: 0 auto;
    border: 4px solid rgba(255,255,255,0.4);
    box-shadow: 0 0 40px rgba(255,211,61,.5), 0 8px 0 rgba(0,0,0,0.3);
    animation: blobMorph 5s ease-in-out infinite;
  }
  @keyframes blobMorph {
    0%,100% { border-radius: 60% 40% 55% 45% / 50% 60% 40% 50%; }
    33%     { border-radius: 40% 60% 45% 55% / 60% 40% 60% 40%; }
    66%     { border-radius: 55% 45% 60% 40% / 40% 55% 45% 60%; }
  }

  .mascot-shadow {
    width: 100px; height: 20px; background: rgba(0,0,0,.35);
    border-radius: 50%; margin: 8px auto 0; filter: blur(8px);
    animation: shadowPulse 2s ease-in-out infinite;
  }
  @keyframes shadowPulse {
    0%,100% { transform: scaleX(1);   opacity:.35; }
    50%     { transform: scaleX(.7);  opacity:.15; }
  }

  .left-title {
    font-family: 'Fredoka One', cursive;
    font-size: 2.8rem; line-height: 1.1;
    color: var(--white);
    text-shadow: 3px 3px 0 var(--dark), 5px 5px 0 rgba(0,0,0,.3);
    margin-bottom: 1rem; position: relative; z-index: 2;
    animation: titleWobble 4s ease-in-out infinite;
  }
  @keyframes titleWobble {
    0%,100% { transform: rotate(-1deg); }
    50%     { transform: rotate(1deg); }
  }
  .left-title span { color: var(--yellow); }

  .left-sub {
    font-size: 1rem; color: rgba(255,255,255,.75);
    font-weight: 600; line-height: 1.6;
    position: relative; z-index: 2; max-width: 300px;
  }

  .feature-pills {
    margin-top: 2rem; display: flex; flex-direction: column; gap: .75rem;
    position: relative; z-index: 2; width: 100%; max-width: 320px;
  }

  .pill {
    display: flex; align-items: center; gap: .75rem;
    background: rgba(255,255,255,.08);
    border: 1.5px solid rgba(255,255,255,.15);
    border-radius: 50px; padding: .6rem 1.1rem;
    color: var(--white); font-weight: 700; font-size: .9rem;
    backdrop-filter: blur(6px);
    animation: pillSlide .6s cubic-bezier(.34,1.56,.64,1) both;
    transition: transform .2s, background .2s;
  }
  .pill:hover { transform: translateX(6px) scale(1.03); background: rgba(255,255,255,.14); }
  .pill:nth-child(1) { animation-delay: .1s; }
  .pill:nth-child(2) { animation-delay: .2s; }
  .pill:nth-child(3) { animation-delay: .3s; }
  .pill:nth-child(4) { animation-delay: .4s; }
  @keyframes pillSlide {
    from { opacity:0; transform: translateX(-30px); }
    to   { opacity:1; transform: translateX(0); }
  }

  .pill-icon {
    width: 32px; height: 32px; border-radius: 50%;
    display: flex; align-items: center; justify-content: center;
    font-size: 16px; flex-shrink: 0;
  }
  .pill-icon.y { background: var(--yellow); }
  .pill-icon.p { background: var(--pink); }
  .pill-icon.b { background: var(--blue); }
  .pill-icon.g { background: var(--green); }

  /* ── RIGHT PANEL (FORM) ── */
  .panel-right {
    display: flex; align-items: center; justify-content: center;
    padding: 2.5rem 2rem;
    overflow-y: auto;
  }

  .form-card {
    width: 100%; max-width: 440px;
    background: var(--card-bg);
    border-radius: 24px;
    border: 2px solid rgba(155,89,245,.4);
    padding: 2.5rem 2.2rem;
    box-shadow: 0 0 60px rgba(155,89,245,.25), 0 20px 40px rgba(0,0,0,.5);
    animation: cardDrop .7s cubic-bezier(.34,1.56,.64,1) both;
  }
  @keyframes cardDrop {
    from { opacity:0; transform: translateY(-40px) scale(.95); }
    to   { opacity:1; transform: translateY(0) scale(1); }
  }

  .form-logo {
    display: flex; align-items: center; gap: 10px; margin-bottom: 1.5rem;
    text-decoration: none;
  }
  .logo-icon {
    width: 44px; height: 44px; border-radius: 12px;
    background: linear-gradient(135deg, var(--blue), var(--purple));
    display: flex; align-items: center; justify-content: center;
    font-size: 22px;
    box-shadow: 0 4px 0 rgba(0,0,0,.35);
    animation: logoSpin 6s linear infinite;
  }
  @keyframes logoSpin {
    0%,100% { transform: rotate(-5deg); }
    50%     { transform: rotate(5deg); }
  }
  .logo-name {
    font-family: 'Fredoka One', cursive;
    font-size: 1.3rem; color: var(--white);
    text-shadow: 2px 2px 0 rgba(0,0,0,.4);
  }

  .form-title {
    font-family: 'Fredoka One', cursive;
    font-size: 2rem; color: var(--white);
    text-shadow: 2px 2px 0 rgba(0,0,0,.4);
    margin-bottom: .3rem;
  }

  .form-sub {
    font-size: .9rem; color: rgba(255,255,255,.55);
    margin-bottom: 1.5rem; font-weight: 600;
  }
  .form-sub a {
    color: var(--yellow); text-decoration: none; font-weight: 800;
    transition: color .2s;
  }
  .form-sub a:hover { color: var(--orange); }

  /* ── ALERT ── */
  .alert {
    border-radius: 14px; padding: .9rem 1.1rem;
    font-weight: 700; font-size: .9rem; margin-bottom: 1.2rem;
    border: 2px solid; display: flex; align-items: center; gap: .5rem;
    animation: alertShake .5s cubic-bezier(.36,.07,.19,.97) both;
  }
  @keyframes alertShake {
    10%,90% { transform: translateX(-2px); }
    20%,80% { transform: translateX(4px); }
    30%,50%,70% { transform: translateX(-4px); }
    40%,60% { transform: translateX(4px); }
  }
  .alert-danger {
    background: rgba(255,75,75,.12);
    border-color: rgba(255,75,75,.4);
    color: #FF8A8A;
  }

  /* ── FORM GROUP ── */
  .fg { margin-bottom: 1rem; }

  .fg label {
    display: block; font-size: .82rem; font-weight: 800;
    color: rgba(255,255,255,.65); letter-spacing: .04em;
    text-transform: uppercase; margin-bottom: .4rem;
  }

  .input-wrap {
    position: relative; display: flex; align-items: center;
  }

  .input-icon {
    position: absolute; left: 12px; font-size: 17px;
    pointer-events: none; z-index: 2;
  }

  .input-wrap input {
    width: 100%; padding: .75rem .9rem .75rem 2.6rem;
    background: var(--input-bg);
    border: 2px solid rgba(155,89,245,.3);
    border-radius: 14px;
    color: var(--white); font-family: 'Nunito', sans-serif;
    font-size: .95rem; font-weight: 600;
    outline: none; transition: border-color .25s, box-shadow .25s, transform .15s;
  }
  .input-wrap input::placeholder { color: rgba(255,255,255,.3); }
  .input-wrap input:focus {
    border-color: var(--purple);
    box-shadow: 0 0 0 3px rgba(155,89,245,.3);
    transform: scale(1.01);
  }
  .input-wrap input:focus + .input-ring {
    opacity: 1; transform: scale(1);
  }

  /* strength / match hint */
  .hint { margin-top: 5px; font-size: .78rem; font-weight: 700; min-height: 1.1em; }

  /* ── CHECKBOX ── */
  .agree-row {
    display: flex; align-items: flex-start; gap: 10px; margin-bottom: 1.3rem;
  }
  .agree-row input[type="checkbox"] {
    width: 20px; height: 20px; flex-shrink: 0; margin-top: 2px;
    accent-color: var(--purple); cursor: pointer;
  }
  .agree-row label {
    font-size: .82rem; color: rgba(255,255,255,.55); cursor: pointer;
    line-height: 1.6; font-weight: 600;
  }
  .agree-row label a { color: var(--blue); text-decoration: none; font-weight: 800; }
  .agree-row label a:hover { color: var(--green); }

  /* ── SUBMIT BUTTON ── */
  .btn-submit {
    width: 100%; padding: .9rem 1.5rem;
    background: linear-gradient(135deg, var(--yellow) 0%, var(--orange) 100%);
    border: none; border-radius: 16px;
    font-family: 'Fredoka One', cursive;
    font-size: 1.25rem; color: var(--dark);
    cursor: pointer; position: relative; overflow: hidden;
    box-shadow: 0 6px 0 rgba(0,0,0,.35), 0 0 30px rgba(255,211,61,.3);
    transition: transform .15s, box-shadow .15s;
    letter-spacing: .02em;
  }
  .btn-submit::before {
    content:''; position:absolute; inset:0;
    background: rgba(255,255,255,.15);
    transform: translateX(-100%) skewX(-15deg);
    transition: transform .4s;
  }
  .btn-submit:hover::before { transform: translateX(120%) skewX(-15deg); }
  .btn-submit:hover {
    transform: translateY(-3px);
    box-shadow: 0 9px 0 rgba(0,0,0,.35), 0 0 40px rgba(255,211,61,.45);
  }
  .btn-submit:active {
    transform: translateY(3px);
    box-shadow: 0 2px 0 rgba(0,0,0,.35);
  }

  /* ── BOTTOM LINK ── */
  .bottom-link {
    text-align: center; margin-top: 1.2rem;
    font-size: .88rem; color: rgba(255,255,255,.45); font-weight: 600;
  }
  .bottom-link a {
    color: var(--pink); font-weight: 800; text-decoration: none;
    transition: color .2s;
  }
  .bottom-link a:hover { color: var(--yellow); }

  /* ── CONFETTI BURST (on submit) ── */
  .confetti-piece {
    position: fixed; width: 10px; height: 10px;
    border-radius: 2px; pointer-events: none; z-index: 9999;
    animation: confettiFall 1.2s ease-in forwards;
  }
  @keyframes confettiFall {
    0%   { opacity:1; transform: translate(0,0) rotate(0deg) scale(1); }
    100% { opacity:0; transform: translate(var(--tx),var(--ty)) rotate(720deg) scale(.3); }
  }

  /* ── RESPONSIVE ── */
  @media (max-width: 768px) {
    .page-wrapper { grid-template-columns: 1fr; }
    .panel-left { display: none; }
    .panel-right { padding: 1.5rem 1rem; align-items: flex-start; padding-top: 3rem; }
  }

  /* ── SCORE TAG (decoration) ── */
  .score-tag {
    position: fixed; top: 1.5rem; right: 1.5rem; z-index: 20;
    background: linear-gradient(135deg,var(--yellow),var(--orange));
    color: var(--dark); font-family:'Fredoka One',cursive;
    font-size: 1rem; padding: .4rem 1rem; border-radius: 50px;
    box-shadow: 0 4px 0 rgba(0,0,0,.3);
    animation: scoreFloat 3s ease-in-out infinite;
    pointer-events: none;
  }
  @keyframes scoreFloat {
    0%,100% { transform: translateY(0) rotate(-2deg); }
    50%     { transform: translateY(-6px) rotate(2deg); }
  }
</style>
</head>
<body>

<div class="bg-scene">
  <div class="blob blob-1"></div>
  <div class="blob blob-2"></div>
  <div class="blob blob-3"></div>
</div>

<div id="floaters"></div>

<div class="score-tag">🏆 JOIN NOW!</div>

<div class="page-wrapper">

  <div class="panel-left">
    <div class="panel-left-bg"></div>

    <div class="mascot-area">
      <div class="mascot-bounce">
        <div class="mascot-blob">🌊</div>
      </div>
      <div class="mascot-shadow"></div>
    </div>

    <h2 class="left-title">Gabung ke<br><span>Pari Adventure!</span></h2>
    <p class="left-sub">Daftar sekarang dan nikmati kemudahan booking trip seru ke Pulau Pari!</p>

    <div class="feature-pills">
      <div class="pill">
        <div class="pill-icon y">🆓</div>
        <span>Pendaftaran 100% Gratis</span>
      </div>
      <div class="pill">
        <div class="pill-icon p">🔒</div>
        <span>Data Aman & Terlindungi</span>
      </div>
      <div class="pill">
        <div class="pill-icon b">📱</div>
        <span>Akses Mudah di Mana Saja</span>
      </div>
      <div class="pill">
        <div class="pill-icon g">💰</div>
        <span>Penawaran Eksklusif Member</span>
      </div>
    </div>
  </div>

  <div class="panel-right">
    <div class="form-card">

      <a href="index.php" class="form-logo">
        <div class="logo-icon">🐋</div>
        <div class="logo-name">Pari Adventure</div>
      </a>

      <h1 class="form-title">Buat Akun Baru</h1>
      <p class="form-sub">Sudah punya akun? <a href="login.php">Masuk di sini</a></p>

      <?php if ($error): ?>
        <div class="alert alert-danger">⚠️ <?= htmlspecialchars($error) ?></div>
      <?php endif; ?>

      <form method="POST" action="register.php" id="regForm" autocomplete="off">

        <input type="text"     name="prevent_autofill_name"  style="display:none;" tabindex="-1" aria-hidden="true">
        <input type="email"    name="prevent_autofill_email" style="display:none;" tabindex="-1" aria-hidden="true">
        <input type="password" name="prevent_autofill_pass"  style="display:none;" tabindex="-1" aria-hidden="true">

        <div class="fg">
          <label>Nama Lengkap <span style="color:var(--pink)">*</span></label>
          <div class="input-wrap">
            <span class="input-icon">👤</span>
            <input type="text" name="nama" placeholder="Masukkan nama lengkap Anda"
                   value="<?= htmlspecialchars($_POST['nama'] ?? '') ?>" required
                   autocomplete="off" readonly onfocus="this.removeAttribute('readonly')">
          </div>
        </div>

        <div class="fg">
          <label>Alamat Email <span style="color:var(--pink)">*</span></label>
          <div class="input-wrap">
            <span class="input-icon">📧</span>
            <input type="email" name="email" placeholder="nama@email.com"
                   value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required
                   autocomplete="off" readonly onfocus="this.removeAttribute('readonly')">
          </div>
        </div>

        <div class="fg">
          <label>Nomor WhatsApp</label>
          <div class="input-wrap">
            <span class="input-icon">📱</span>
            <input type="tel" name="telepon" placeholder="08xxxxxxxxxx"
                   value="<?= htmlspecialchars($_POST['telepon'] ?? '') ?>"
                   autocomplete="off" readonly onfocus="this.removeAttribute('readonly')">
          </div>
        </div>

        <div class="fg">
          <label>Password <span style="color:var(--pink)">*</span></label>
          <div class="input-wrap">
            <span class="input-icon">🔒</span>
            <!-- FIX: tambah data-lpignore & data-form-type untuk blokir autofill password manager -->
            <input type="password" name="password" id="pass1"
                   placeholder="Minimal 8 karakter" required minlength="8"
                   autocomplete="new-password"
                   data-lpignore="true"
                   data-form-type="other"
                   readonly onfocus="this.removeAttribute('readonly')">
          </div>
          <div id="passStrength" class="hint"></div>
        </div>

        <div class="fg">
          <label>Konfirmasi Password <span style="color:var(--pink)">*</span></label>
          <div class="input-wrap">
            <span class="input-icon">🔒</span>
            <!-- FIX: tambah data-lpignore & data-form-type untuk blokir autofill password manager -->
            <input type="password" name="confirm_password" id="pass2"
                   placeholder="Ulangi password Anda" required
                   autocomplete="new-password"
                   data-lpignore="true"
                   data-form-type="other"
                   readonly onfocus="this.removeAttribute('readonly')">
          </div>
          <div id="passMatch" class="hint"></div>
        </div>

        <div class="agree-row">
          <input type="checkbox" id="agree" required>
          <label for="agree">
            Saya menyetujui <a href="#">Syarat &amp; Ketentuan</a> serta
            <a href="#">Kebijakan Privasi</a> Pari Adventure.
          </label>
        </div>

        <button type="submit" class="btn-submit" id="submitBtn">
          ✅ Daftar Sekarang
        </button>
      </form>

      <div class="bottom-link">
        Sudah punya akun? <a href="login.php">Masuk sekarang</a>
      </div>
    </div>
  </div>
</div>

<script>
/* ── CLEAR AUTOFILL on load (browser override) ── */
window.addEventListener('load', () => {
  setTimeout(() => {
    document.querySelectorAll('input[type="email"], input[type="password"]').forEach(el => {
      if (el.value && !el.dataset.userTyped) el.value = '';
    });
  }, 100);
  /* FIX: force-clear password fields saat halaman selesai dimuat */
  setTimeout(() => {
    document.querySelectorAll('input[type="password"]').forEach(el => {
      if (!el.dataset.userTyped) el.value = '';
    });
  }, 500);
});
document.querySelectorAll('input').forEach(el => {
  el.addEventListener('input', () => { el.dataset.userTyped = '1'; });
});

/* FIX: clear password field jika browser mengisi otomatis saat focus */
document.querySelectorAll('input[type="password"]').forEach(el => {
  el.addEventListener('focus', function () {
    if (!this.dataset.userTyped) this.value = '';
  });
});

/* ── PASSWORD LOGIC (unchanged) ── */
const pass1 = document.getElementById('pass1');
const pass2 = document.getElementById('pass2');

pass1.addEventListener('input', () => {
  const v = pass1.value;
  const el = document.getElementById('passStrength');
  if (!v) { el.textContent = ''; return; }
  if (v.length < 6)      { el.textContent = '🔴 Terlalu pendek'; el.style.color = '#FF6B6B'; }
  else if (v.length < 8) { el.textContent = '🟡 Lumayan';         el.style.color = '#FFD93D'; }
  else if (/[A-Z]/.test(v) && /[0-9]/.test(v)) { el.textContent = '🟢 Password kuat!'; el.style.color = '#4ECDC4'; }
  else                   { el.textContent = '🔵 Cukup';            el.style.color = '#3EC6FF'; }
});

pass2.addEventListener('input', () => {
  const el = document.getElementById('passMatch');
  if (!pass2.value) { el.textContent = ''; return; }
  if (pass1.value === pass2.value) { el.textContent = '✅ Password cocok';     el.style.color = '#4ECDC4'; }
  else                             { el.textContent = '❌ Password tidak cocok'; el.style.color = '#FF6B6B'; }
});

/* ── CONFETTI on submit ── */
document.getElementById('regForm').addEventListener('submit', (e) => {
  const colors = ['#FFD93D','#FF6B35','#FF4FA1','#9B59F5','#3EC6FF','#4ECDC4'];
  for (let i = 0; i < 40; i++) {
    const p = document.createElement('div');
    p.className = 'confetti-piece';
    const btn = document.getElementById('submitBtn');
    const r = btn.getBoundingClientRect();
    p.style.left = (r.left + r.width/2 + (Math.random()-0.5)*80) + 'px';
    p.style.top  = (r.top + (Math.random()-0.5)*30) + 'px';
    p.style.background = colors[Math.floor(Math.random()*colors.length)];
    p.style.setProperty('--tx', (Math.random()-0.5)*200 + 'px');
    p.style.setProperty('--ty', -(Math.random()*200+80) + 'px');
    p.style.animationDuration = (.8+Math.random()*.8) + 's';
    document.body.appendChild(p);
    setTimeout(()=>p.remove(), 1600);
  }
});

/* ── FLOATING BLOBS ── */
const blobColors = ['#9B59F5','#FF4FA1','#3EC6FF','#4ECDC4','#FFD93D','#FF6B35'];
const container  = document.getElementById('floaters');
function spawnBlob() {
  const el = document.createElement('div');
  el.className = 'floater';
  const size = 20 + Math.random()*50;
  el.style.cssText = [
    `width:${size}px`, `height:${size}px`,
    `left:${Math.random()*100}vw`,
    `background:${blobColors[Math.floor(Math.random()*blobColors.length)]}`,
    `opacity:${0.15+Math.random()*.25}`,
    `animation-duration:${6+Math.random()*10}s`,
    `animation-delay:${Math.random()*5}s`,
    `filter:blur(${2+Math.random()*4}px)`
  ].join(';');
  container.appendChild(el);
  el.addEventListener('animationend', () => el.remove());
}
for(let i=0;i<12;i++) setTimeout(spawnBlob, i*600);
setInterval(spawnBlob, 1800);

/* ── FLOATING STARS ── */
const starEmojis = ['⭐','✨','💫','🌟'];
for(let i=0;i<10;i++){
  const s = document.createElement('div');
  s.className = 'star';
  s.textContent = starEmojis[Math.floor(Math.random()*starEmojis.length)];
  s.style.left = Math.random()*95 + 'vw';
  s.style.top  = Math.random()*90 + 'vh';
  s.style.animationDelay = (Math.random()*3) + 's';
  s.style.animationDuration = (2+Math.random()*2) + 's';
  document.body.appendChild(s);
}

/* ── INPUT FOCUS SHAKE ON WRONG ── */
document.querySelectorAll('input[required]').forEach(inp => {
  inp.addEventListener('invalid', () => {
    inp.style.animation = 'none';
    requestAnimationFrame(()=>{
      inp.style.animation = 'alertShake .4s ease both';
      inp.style.borderColor = '#FF6B6B';
      setTimeout(()=>{ inp.style.borderColor=''; inp.style.animation=''; }, 600);
    });
  });
});
</script>
</body>
</html>