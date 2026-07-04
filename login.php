<?php
// ============================================================
// TIDAK ADA PERUBAHAN PADA BAGIAN PHP / LOGIKA
// ============================================================
require_once 'includes/auth.php';

if (isUserLoggedIn()) {
    header("Location: user/dashboard.php");
    exit;
}

$error   = '';
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        $error = "Email dan password wajib diisi!";
    } else {
        $result = loginUser($email, $password);
        if ($result['success']) {
            header("Location: user/dashboard.php");
            exit;
        } else {
            $error = $result['message'];
        }
    }
}

$flash = getFlash();
if ($flash && $flash['type'] === 'success') $success = $flash['message'];
if ($flash && $flash['type'] === 'warning') $error   = $flash['message'];
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Masuk - Pari Adventure</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Lilita+One&family=Fredoka+One&family=Nunito:wght@700;800;900&display=swap" rel="stylesheet">

<style>
/* ============================================================
   STUMBLE GUYS LOGIN THEME
   ============================================================ */
:root {
  --sg-purple:  #7c3aed;
  --sg-purple2: #5b21b6;
  --sg-purple3: #a78bfa;
  --sg-pink:    #ec4899;
  --sg-orange:  #f97316;
  --sg-orange2: #fb923c;
  --sg-yellow:  #fbbf24;
  --sg-yellow2: #fef08a;
  --sg-cyan:    #06b6d4;
  --sg-cyan2:   #67e8f9;
  --sg-green:   #22c55e;
  --sg-red:     #ef4444;
  --sg-white:   #ffffff;
  --sg-bg:      #1e0a3c;
  --sg-bg2:     #2d1458;
  --sg-bg3:     #3b1a72;
  --stroke:     -2px 2px 0 #2d1458, 2px 2px 0 #2d1458,
                2px -2px 0 #2d1458, -2px -2px 0 #2d1458;
  --font-title: 'Lilita One', cursive;
  --font-label: 'Fredoka One', cursive;
  --font-body:  'Nunito', sans-serif;
}

*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
html, body { height: 100%; }
body {
  font-family: var(--font-body);
  background: var(--sg-bg);
  color: var(--sg-white);
  overflow: hidden;
  position: relative;
}

/* ---- BG Canvas ---- */
#bgCanvas {
  position: fixed; inset: 0;
  pointer-events: none; z-index: 0;
}

/* ---- Page layout ---- */
.auth-page {
  display: flex;
  min-height: 100vh;
  position: relative; z-index: 1;
}

/* ============================================================
   LEFT PANEL — decorative side
   ============================================================ */
.auth-left {
  flex: 1;
  background: linear-gradient(160deg, var(--sg-purple) 0%, var(--sg-bg) 100%);
  display: flex; align-items: center; justify-content: center;
  padding: 48px 40px;
  position: relative; overflow: hidden;
  border-right: 4px solid var(--sg-yellow);
}

/* Animated blobs */
.blob {
  position: absolute; border-radius: 50%;
  filter: blur(55px); pointer-events: none;
  animation: blobFloat 6s ease-in-out infinite;
}
.blob-1 { width:320px;height:320px;background:rgba(249,115,22,.3);  top:-80px;left:-60px;  animation-delay:0s; }
.blob-2 { width:260px;height:260px;background:rgba(6,182,212,.2);   bottom:-60px;right:-40px; animation-delay:2s; }
.blob-3 { width:180px;height:180px;background:rgba(236,72,153,.25); top:40%;left:30%;animation-delay:4s; }
@keyframes blobFloat {
  0%,100%{ transform:translateY(0)  scale(1); }
  50%     { transform:translateY(-24px) scale(1.06); }
}

/* Floating shapes */
.fshape {
  position: absolute; pointer-events: none;
  animation: fshapeSpin linear infinite;
  filter: drop-shadow(0 4px 8px rgba(0,0,0,.35));
  user-select: none;
}
@keyframes fshapeSpin {
  0%  { transform: rotate(0deg)   translateY(0); }
  50% { transform: rotate(180deg) translateY(-18px); }
  100%{ transform: rotate(360deg) translateY(0); }
}

.auth-left-content { position: relative; z-index: 2; text-align: center; max-width: 380px; }

.left-mascot {
  font-size: 88px; display: block; margin-bottom: 10px;
  animation: mascotBounce 2.5s ease-in-out infinite;
  filter: drop-shadow(0 8px 20px rgba(0,0,0,.5));
}
@keyframes mascotBounce {
  0%,100%{ transform: translateY(0)  rotate(-6deg); }
  50%     { transform: translateY(-14px) rotate(6deg); }
}

.left-title {
  font-family: var(--font-title);
  font-size: clamp(28px, 4vw, 46px);
  color: var(--sg-yellow);
  text-shadow: var(--stroke);
  line-height: 1.1; margin-bottom: 12px;
}
.left-desc {
  font-family: var(--font-body);
  font-size: 14px; font-weight: 700;
  color: var(--sg-purple3); line-height: 1.75;
  margin-bottom: 28px;
}

/* Feature list */
.auth-features { display: flex; flex-direction: column; gap: 12px; text-align: left; }
.auth-feature {
  display: flex; align-items: center; gap: 12px;
  background: rgba(255,255,255,.07);
  border: 2px solid rgba(255,255,255,.12);
  border-radius: 16px; padding: 10px 16px;
  transition: all .25s; cursor: default;
}
.auth-feature:hover {
  background: rgba(124,58,237,.25);
  border-color: var(--sg-purple3);
  transform: translateX(6px);
}
.feature-icon {
  width: 36px; height: 36px; border-radius: 12px;
  background: var(--sg-orange);
  display: flex; align-items: center; justify-content: center;
  font-size: 18px; flex-shrink: 0;
  border: 2px solid var(--sg-white);
  box-shadow: 0 3px 0 #c2410c;
  animation: iconPop 2.5s ease-in-out infinite;
}
.auth-feature:nth-child(1) .feature-icon { animation-delay: 0s; }
.auth-feature:nth-child(2) .feature-icon { animation-delay: .2s; background: var(--sg-cyan);  box-shadow: 0 3px 0 #0e7490; }
.auth-feature:nth-child(3) .feature-icon { animation-delay: .4s; background: var(--sg-yellow); box-shadow: 0 3px 0 #92400e; }
.auth-feature:nth-child(4) .feature-icon { animation-delay: .6s; background: var(--sg-pink);  box-shadow: 0 3px 0 #9d174d; }
@keyframes iconPop {
  0%,100%{ transform: scale(1) rotate(0deg); }
  50%     { transform: scale(1.1) rotate(-8deg); }
}
.feature-text {
  font-family: var(--font-body); font-size: 13px; font-weight: 700;
  color: rgba(255,255,255,.85);
}

/* ============================================================
   RIGHT PANEL — form
   ============================================================ */
.auth-right {
  width: 480px; flex-shrink: 0;
  background: var(--sg-bg2);
  display: flex; align-items: center; justify-content: center;
  padding: 32px 40px;
  position: relative; overflow-y: auto;
  border-left: 4px solid var(--sg-purple);
}

/* Top stripe */
.auth-right::before {
  content: '';
  position: absolute; top: 0; left: 0; right: 0; height: 6px;
  background: linear-gradient(90deg,var(--sg-orange),var(--sg-pink),var(--sg-purple3),var(--sg-cyan));
  background-size: 200% 100%;
  animation: stripeSlide 3s linear infinite;
}
@keyframes stripeSlide { to { background-position: -200% 0; } }

.auth-form-container {
  width: 100%; max-width: 380px;
  position: relative; z-index: 2;
}

/* Logo */
.auth-logo { margin-bottom: 24px; }
.auth-logo a {
  display: inline-flex; align-items: center; gap: 10px;
  text-decoration: none;
}
.logo-icon {
  width: 46px; height: 46px;
  background: var(--sg-yellow);
  border-radius: 50%;
  border: 3px solid var(--sg-white);
  display: flex; align-items: center; justify-content: center;
  font-size: 22px;
  box-shadow: 0 4px 0 var(--sg-orange);
  animation: logoBounce 2s ease-in-out infinite;
}
@keyframes logoBounce {
  0%,100%{ transform: translateY(0) rotate(-5deg); }
  50%     { transform: translateY(-5px) rotate(5deg); }
}
.logo-name {
  font-family: var(--font-title); font-size: 20px;
  color: var(--sg-yellow); text-shadow: var(--stroke);
}

/* Title */
.auth-title {
  font-family: var(--font-title);
  font-size: 32px; color: var(--sg-white);
  text-shadow: var(--stroke);
  margin-bottom: 4px;
  animation: titleWiggle 3s ease-in-out infinite;
}
@keyframes titleWiggle {
  0%,100%{ transform: rotate(-1.5deg); }
  50%     { transform: rotate(1.5deg); }
}
.auth-subtitle {
  font-family: var(--font-body); font-size: 13px; font-weight: 700;
  color: var(--sg-purple3); margin-bottom: 20px;
}
.auth-subtitle a { color: var(--sg-yellow); text-decoration: none; }
.auth-subtitle a:hover { text-decoration: underline; }

/* Alerts */
.alert {
  font-family: var(--font-label); font-size: 13px;
  padding: 12px 16px; border-radius: 14px;
  margin-bottom: 16px; border: 2px solid; font-weight: 700;
  animation: alertSlide .4s ease;
}
@keyframes alertSlide { from{ opacity:0;transform:translateY(-10px);} to{opacity:1;transform:none;} }
.alert-danger  { background:rgba(239,68,68,.15);  border-color:var(--sg-red);   color:#fca5a5; }
.alert-success { background:rgba(34,197,94,.15);   border-color:var(--sg-green); color:#86efac; }

/* Form groups */
.form-group { margin-bottom: 18px; }
.form-label {
  display: block;
  font-family: var(--font-label); font-size: 13px;
  color: var(--sg-purple3); margin-bottom: 6px;
  letter-spacing: .04em;
}

/* Input wrapper */
.input-icon-wrap {
  position: relative;
  background: rgba(255,255,255,.06);
  border: 3px solid rgba(255,255,255,.15);
  border-radius: 16px;
  display: flex; align-items: center;
  transition: border-color .2s, box-shadow .2s;
  overflow: hidden;
}
.input-icon-wrap:focus-within {
  border-color: var(--sg-yellow);
  box-shadow: 0 0 0 3px rgba(251,191,36,.2), 0 4px 0 var(--sg-orange);
}
.input-icon {
  padding: 0 12px 0 16px; font-size: 18px; flex-shrink: 0;
}
.form-control {
  flex: 1; background: transparent; border: none; outline: none;
  font-family: var(--font-body); font-size: 14px; font-weight: 700;
  color: var(--sg-white); padding: 13px 16px 13px 0;
}
.form-control::placeholder { color: rgba(167,139,250,.5); }
.form-control:-webkit-autofill {
  -webkit-box-shadow: 0 0 0 50px var(--sg-bg3) inset;
  -webkit-text-fill-color: var(--sg-white);
}

/* Show password toggle */
.toggle-pass-wrap {
  display: flex; align-items: center; gap: 8px; margin-bottom: 4px;
}
.toggle-pass-wrap input[type="checkbox"] { width: 16px; height: 16px; cursor: pointer; accent-color: var(--sg-purple); }
.toggle-pass-wrap label {
  font-family: var(--font-label); font-size: 12px;
  color: var(--sg-purple3); cursor: pointer;
}

/* Forgot link */
.forgot-link {
  display: block; text-align: right; margin-top: 6px;
  font-family: var(--font-label); font-size: 12px;
  color: var(--sg-cyan2); text-decoration: none;
  transition: color .2s;
}
.forgot-link:hover { color: var(--sg-yellow); }

/* Submit button */
.btn-submit {
  display: block; width: 100%;
  background: var(--sg-orange); color: var(--sg-white);
  font-family: var(--font-label); font-size: 17px;
  padding: 15px; border-radius: 20px;
  border: 3px solid var(--sg-white);
  box-shadow: 0 6px 0 #c2410c;
  cursor: pointer; text-align: center;
  transition: all .15s;
  margin-top: 6px;
  letter-spacing: .04em;
  position: relative; overflow: hidden;
}
.btn-submit::before {
  content: '';
  position: absolute; inset: 0;
  background: linear-gradient(45deg, transparent 40%, rgba(255,255,255,.15) 50%, transparent 60%);
  transform: translateX(-200%);
  transition: transform .5s;
}
.btn-submit:hover  { transform: translateY(-4px); box-shadow: 0 10px 0 #c2410c; }
.btn-submit:hover::before { transform: translateX(200%); }
.btn-submit:active { transform: translateY(3px);  box-shadow: 0 3px 0 #c2410c; }

/* Divider */
.auth-divider {
  text-align: center; margin: 18px 0;
  position: relative;
  font-family: var(--font-label); font-size: 12px;
  color: var(--sg-purple3);
}
.auth-divider::before, .auth-divider::after {
  content: '';
  position: absolute; top: 50%;
  width: calc(50% - 24px); height: 1px;
  background: rgba(255,255,255,.1);
}
.auth-divider::before { left: 0; }
.auth-divider::after  { right: 0; }

/* Admin button */
.btn-admin {
  display: block; width: 100%; text-align: center;
  padding: 13px; border-radius: 20px;
  border: 3px solid rgba(255,255,255,.2);
  background: rgba(255,255,255,.05);
  font-family: var(--font-label); font-size: 14px;
  color: var(--sg-purple3); text-decoration: none;
  transition: all .2s;
  box-shadow: 0 4px 0 rgba(0,0,0,.2);
}
.btn-admin:hover {
  background: rgba(124,58,237,.25);
  border-color: var(--sg-purple3);
  color: var(--sg-white);
  transform: translateY(-3px);
  box-shadow: 0 7px 0 rgba(0,0,0,.2);
}
.btn-admin:active { transform: translateY(2px); box-shadow: 0 2px 0 rgba(0,0,0,.2); }

/* Register link */
.auth-link {
  text-align: center; margin-top: 18px;
  font-family: var(--font-body); font-size: 13px; font-weight: 700;
  color: var(--sg-purple3);
}
.auth-link a { color: var(--sg-yellow); text-decoration: none; font-weight: 900; }
.auth-link a:hover { text-decoration: underline; }

/* ============================================================
   CONFETTI CLICK
   ============================================================ */
.confetti-dot {
  position: fixed; pointer-events: none; z-index: 9999;
  border-radius: 50%;
}

/* ============================================================
   RESPONSIVE
   ============================================================ */
@media (max-width: 820px) {
  body { overflow: auto; }
  .auth-page { flex-direction: column; }
  .auth-left  { border-right: none; border-bottom: 4px solid var(--sg-yellow); }
  .auth-right { width: 100%; border-left: none; }
}
</style>
</head>
<body>

<!-- BG CANVAS -->
<canvas id="bgCanvas"></canvas>

<div class="auth-page">

  <!-- ==================== LEFT PANEL ==================== -->
  <div class="auth-left">
    <!-- blobs -->
    <div class="blob blob-1"></div>
    <div class="blob blob-2"></div>
    <div class="blob blob-3"></div>
    <!-- floating shapes -->
    <span class="fshape" style="top:8%;left:8%;font-size:26px;animation-duration:6s;animation-delay:0s;">⭐</span>
    <span class="fshape" style="top:15%;right:12%;font-size:20px;animation-duration:7s;animation-delay:1s;">🌟</span>
    <span class="fshape" style="bottom:20%;left:10%;font-size:22px;animation-duration:8s;animation-delay:2s;">✨</span>
    <span class="fshape" style="bottom:30%;right:8%;font-size:18px;animation-duration:5.5s;animation-delay:.5s;">💫</span>
    <span class="fshape" style="top:50%;left:5%;font-size:16px;animation-duration:9s;animation-delay:3s;">🎮</span>
    <span class="fshape" style="top:70%;right:14%;font-size:20px;animation-duration:7.5s;animation-delay:1.5s;">🏆</span>

    <div class="auth-left-content">
      <span class="left-mascot">🏝️</span>
      <div class="left-title">Selamat Datang<br>Kembali!</div>
      <p class="left-desc">Masuk ke akun Anda dan mulai rencanakan petualangan seru di Pulau Pari.</p>

      <div class="auth-features">
        <div class="auth-feature">
          <div class="feature-icon">🤿</div>
          <span class="feature-text">Booking trip dengan mudah &amp; cepat</span>
        </div>
        <div class="auth-feature">
          <div class="feature-icon">📋</div>
          <span class="feature-text">Pantau status pemesanan Anda</span>
        </div>
        <div class="auth-feature">
          <div class="feature-icon">⭐</div>
          <span class="feature-text">Berikan ulasan perjalanan Anda</span>
        </div>
        <div class="auth-feature">
          <div class="feature-icon">🎁</div>
          <span class="feature-text">Dapatkan promo &amp; penawaran spesial</span>
        </div>
      </div>
    </div>
  </div>

  <!-- ==================== RIGHT PANEL ==================== -->
  <div class="auth-right">
    <div class="auth-form-container">

      <!-- Logo -->
      <div class="auth-logo">
        <a href="index.php">
          <div class="logo-icon">🐋</div>
          <div class="logo-name">Pari Adventure</div>
        </a>
      </div>

      <h1 class="auth-title">Masuk Akun!</h1>
      <p class="auth-subtitle">
        Belum punya akun? <a href="register.php">Daftar sekarang</a>
      </p>

      <!-- Flash messages — logika PHP tidak berubah -->
      <?php if ($error): ?>
      <div class="alert alert-danger">
        ⚠️ <?= htmlspecialchars($error) ?>
      </div>
      <?php endif; ?>
      <?php if ($success): ?>
      <div class="alert alert-success">
        ✅ <?= htmlspecialchars($success) ?>
      </div>
      <?php endif; ?>

      <!-- Form — action, method, name fields tidak berubah -->
      <form method="POST" action="login.php" autocomplete="off">

        <div class="form-group">
          <label class="form-label">📧 Alamat Email</label>
          <div class="input-icon-wrap">
            <span class="input-icon">📧</span>
            <input type="email" name="email" class="form-control"
                   placeholder="nama@email.com"
                   required autocomplete="off" readonly
                   onfocus="this.removeAttribute('readonly');">
          </div>
        </div>

        <div class="form-group">
          <label class="form-label">🔒 Password</label>
          <div class="input-icon-wrap">
            <span class="input-icon">🔒</span>
            <input type="password" name="password" class="form-control"
                   placeholder="Masukkan password Anda"
                   id="passwordInput" required autocomplete="new-password" readonly
                   onfocus="this.removeAttribute('readonly');">
          </div>
          <a href="#" class="forgot-link">Lupa password?</a>
        </div>

        <div class="toggle-pass-wrap">
          <input type="checkbox" id="showPass" onchange="togglePass()">
          <label for="showPass">Tampilkan password</label>
        </div>

        <button type="submit" class="btn-submit" id="loginBtn">
          🚀 Masuk Sekarang!
        </button>
      </form>

      <div class="auth-divider">atau</div>

      <!-- Admin link — href tidak berubah -->
      <a href="admin/admin.php" class="btn-admin">
        🔐 Login sebagai Admin
      </a>

      <div class="auth-link">
        Belum punya akun?
        <a href="register.php">Daftar Gratis!</a>
      </div>

    </div><!-- /.auth-form-container -->
  </div><!-- /.auth-right -->

</div><!-- /.auth-page -->

<script>
/* ---- Toggle Password ---- */
function togglePass() {
  const p = document.getElementById('passwordInput');
  p.type = p.type === 'password' ? 'text' : 'password';
}

/* ---- BG Canvas — Floating Confetti ---- */
(function() {
  const canvas = document.getElementById('bgCanvas');
  const ctx    = canvas.getContext('2d');
  let W, H;
  const SHAPES = ['★','●','▲','◆','♥','✦','🎮','⭐'];
  const COLORS = ['#fbbf24','#ec4899','#06b6d4','#a78bfa','#f97316','#22c55e'];
  const particles = [];

  function resize() {
    W = canvas.width  = window.innerWidth;
    H = canvas.height = window.innerHeight;
  }
  window.addEventListener('resize', resize);
  resize();

  class P {
    constructor(init) {
      this.reset(init);
    }
    reset(init) {
      this.x    = Math.random() * W;
      this.y    = init ? Math.random() * H : H + 20;
      this.vy   = -(Math.random() * .7 + .2);
      this.vx   = (Math.random() - .5) * .35;
      this.size = Math.random() * 14 + 6;
      this.alpha= Math.random() * .2 + .04;
      this.rot  = Math.random() * Math.PI * 2;
      this.rotV = (Math.random() - .5) * .018;
      this.color= COLORS[~~(Math.random() * COLORS.length)];
      this.shape= SHAPES[~~(Math.random() * SHAPES.length)];
    }
    update() {
      this.x   += this.vx;
      this.y   += this.vy;
      this.rot += this.rotV;
      if (this.y < -30) this.reset(false);
    }
    draw() {
      ctx.save();
      ctx.globalAlpha = this.alpha;
      ctx.fillStyle   = this.color;
      ctx.font = `${this.size}px serif`;
      ctx.translate(this.x, this.y);
      ctx.rotate(this.rot);
      ctx.textAlign = 'center';
      ctx.textBaseline = 'middle';
      ctx.fillText(this.shape, 0, 0);
      ctx.restore();
    }
  }

  for (let i = 0; i < 50; i++) particles.push(new P(true));

  (function loop() {
    ctx.clearRect(0, 0, W, H);
    particles.forEach(p => { p.update(); p.draw(); });
    requestAnimationFrame(loop);
  })();
})();

/* ---- Click confetti burst ---- */
const COLORS = ['#fbbf24','#ec4899','#06b6d4','#f97316','#22c55e','#a78bfa'];
document.addEventListener('click', e => {
  for (let i = 0; i < 7; i++) {
    const dot = document.createElement('div');
    dot.className = 'confetti-dot';
    const sz = Math.random() * 10 + 5;
    Object.assign(dot.style, {
      width: sz + 'px', height: sz + 'px',
      left: e.clientX + 'px', top: e.clientY + 'px',
      background: COLORS[~~(Math.random() * COLORS.length)],
    });
    document.body.appendChild(dot);
    const angle = (Math.PI * 2 / 7) * i;
    const dist  = Math.random() * 50 + 20;
    dot.animate([
      { transform: 'translate(-50%,-50%) scale(1)', opacity: 1 },
      { transform: `translate(calc(-50% + ${Math.cos(angle)*dist}px),calc(-50% + ${Math.sin(angle)*dist}px)) scale(0)`, opacity: 0 }
    ], { duration: 520, easing: 'ease-out' }).onfinish = () => dot.remove();
  }
});

/* ---- Submit button shake on error ---- */
<?php if ($error): ?>
document.addEventListener('DOMContentLoaded', () => {
  const btn = document.getElementById('loginBtn');
  btn.style.animation = 'shakeBtnKF .45s ease';
  btn.addEventListener('animationend', () => btn.style.animation = '');
});
<?php endif; ?>

/* ---- Input focus glow animation ---- */
document.querySelectorAll('.input-icon-wrap').forEach(wrap => {
  wrap.addEventListener('focusin',  () => wrap.style.transform = 'scale(1.01)');
  wrap.addEventListener('focusout', () => wrap.style.transform = '');
});
</script>

<style>
@keyframes shakeBtnKF {
  0%  { transform: translateX(0); }
  15% { transform: translateX(-8px) rotate(-2deg); }
  35% { transform: translateX(8px)  rotate(2deg); }
  55% { transform: translateX(-5px) rotate(-1deg); }
  75% { transform: translateX(5px)  rotate(1deg); }
  100%{ transform: translateX(0); }
}
</style>

</body>
</html>