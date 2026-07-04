<?php
/**
 * LOGIN ADMIN - PARI ADVENTURE
 * Lokasi File: htdocs/admin/admin.php
 * ============================================================
 * TIDAK ADA PERUBAHAN PADA LOGIKA PHP / DATABASE
 * ============================================================
 */
require_once __DIR__ . '/../includes/auth.php';

if (isAdminLoggedIn()) {
    header("Location: dashboard.php");
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        $error = "Email dan password wajib diisi!";
    } else {
        $result = loginAdmin($email, $password);
        if ($result['success']) {
            header("Location: dashboard.php");
            exit;
        } else {
            $error = $result['message'];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Login Admin - Pari Adventure</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Lilita+One&family=Fredoka+One&family=Nunito:wght@700;800;900&display=swap" rel="stylesheet">

<style>
/* ============================================================
   STUMBLE GUYS ADMIN LOGIN THEME
   Warna Admin : Merah-Ungu gelap + Gold — lebih "boss level"
   ============================================================ */
:root {
  --sg-bg:      #120824;
  --sg-bg2:     #1e0e3a;
  --sg-bg3:     #2a1250;
  --sg-purple:  #7c3aed;
  --sg-purple2: #5b21b6;
  --sg-purple3: #a78bfa;
  --sg-red:     #dc2626;
  --sg-red2:    #ef4444;
  --sg-orange:  #f97316;
  --sg-yellow:  #fbbf24;
  --sg-yellow2: #fef08a;
  --sg-cyan:    #06b6d4;
  --sg-cyan2:   #67e8f9;
  --sg-green:   #22c55e;
  --sg-pink:    #ec4899;
  --sg-white:   #ffffff;

  --stroke:    -2px 2px 0 #1e0e3a, 2px 2px 0 #1e0e3a,
               2px -2px 0 #1e0e3a, -2px -2px 0 #1e0e3a;
  --stroke-red:-2px 2px 0 #7f1d1d, 2px 2px 0 #7f1d1d,
               2px -2px 0 #7f1d1d, -2px -2px 0 #7f1d1d;

  --font-title: 'Lilita One', cursive;
  --font-label: 'Fredoka One', cursive;
  --font-body:  'Nunito', sans-serif;
}

*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
html, body { height: 100%; }

body {
  font-family: var(--font-body);
  background: var(--sg-bg);
  display: flex;
  align-items: center;
  justify-content: center;
  min-height: 100vh;
  overflow: hidden;
  position: relative;
}

/* ============================================================
   BG CANVAS
   ============================================================ */
#bgCanvas {
  position: fixed; inset: 0;
  pointer-events: none; z-index: 0;
}

/* ============================================================
   ANIMATED BG BLOBS
   ============================================================ */
.blob {
  position: fixed; border-radius: 50%;
  filter: blur(70px); pointer-events: none;
  animation: blobFloat ease-in-out infinite;
}
.blob-1 { width:380px;height:380px;background:rgba(220,38,38,.18); top:-120px;left:-100px;animation-duration:7s;animation-delay:0s; }
.blob-2 { width:280px;height:280px;background:rgba(124,58,237,.2);  bottom:-80px;right:-80px;animation-duration:8s;animation-delay:2s; }
.blob-3 { width:200px;height:200px;background:rgba(251,191,36,.12); top:50%;left:50%;animation-duration:6s;animation-delay:1s; }
@keyframes blobFloat {
  0%,100%{ transform:translateY(0) scale(1); }
  50%     { transform:translateY(-28px) scale(1.07); }
}

/* Floating shapes */
.fshape {
  position: fixed; pointer-events: none; user-select: none;
  animation: fshapeSpin linear infinite;
  filter: drop-shadow(0 4px 8px rgba(0,0,0,.5));
}
@keyframes fshapeSpin {
  0%  { transform: rotate(0deg)   translateY(0); }
  50% { transform: rotate(180deg) translateY(-16px); }
  100%{ transform: rotate(360deg) translateY(0); }
}

/* ============================================================
   MAIN CARD
   ============================================================ */
.login-box {
  width: 90%; max-width: 420px;
  position: relative; z-index: 2;
  background: linear-gradient(160deg, var(--sg-bg3) 0%, var(--sg-bg2) 100%);
  border-radius: 28px;
  border: 4px solid rgba(255,255,255,.15);
  box-shadow: 0 20px 0 rgba(0,0,0,.4), 0 30px 60px rgba(0,0,0,.7);
  overflow: hidden;
  animation: cardEntrance .7s cubic-bezier(.34,1.56,.64,1) both;
}
@keyframes cardEntrance {
  from { opacity:0; transform: scale(.7) translateY(40px); }
  to   { opacity:1; transform: scale(1) translateY(0); }
}

/* Animated top stripe */
.login-box::before {
  content: '';
  position: absolute; top:0; left:0; right:0; height: 6px;
  background: linear-gradient(90deg, var(--sg-red), var(--sg-orange), var(--sg-yellow), var(--sg-red));
  background-size: 200% 100%;
  animation: stripeSlide 2.5s linear infinite;
}
@keyframes stripeSlide { to { background-position: -200% 0; } }

/* Diagonal shine overlay */
.login-box::after {
  content: '';
  position: absolute; inset: 0;
  background: radial-gradient(ellipse at 30% 20%, rgba(255,255,255,.05), transparent 60%);
  pointer-events: none;
}

/* ============================================================
   HEADER
   ============================================================ */
.login-header {
  text-align: center;
  padding: 32px 32px 20px;
  position: relative;
}

/* Admin crown / mascot */
.admin-mascot {
  font-size: 72px;
  display: block;
  filter: drop-shadow(0 8px 20px rgba(0,0,0,.6));
  animation: mascotWobble 2.5s ease-in-out infinite;
  line-height: 1; margin-bottom: 8px;
}
@keyframes mascotWobble {
  0%,100%{ transform: rotate(-5deg) translateY(0); }
  50%     { transform: rotate(5deg) translateY(-10px); }
}

/* ADMIN badge */
.admin-badge {
  display: inline-flex; align-items: center; gap: 6px;
  background: var(--sg-red);
  color: var(--sg-white);
  font-family: var(--font-label); font-size: 11px;
  padding: 4px 16px; border-radius: 20px;
  border: 2px solid var(--sg-white);
  box-shadow: 0 3px 0 #7f1d1d;
  margin-bottom: 10px;
  animation: badgePop 2s ease-in-out infinite;
}
@keyframes badgePop {
  0%,100%{ transform: scale(1); }
  50%     { transform: scale(1.06); }
}

.login-header h1 {
  font-family: var(--font-title);
  font-size: 32px;
  color: var(--sg-yellow);
  text-shadow: var(--stroke);
  line-height: 1.1; margin-bottom: 4px;
  animation: titleWiggle 3s ease-in-out infinite;
}
@keyframes titleWiggle {
  0%,100%{ transform: rotate(-1.5deg); }
  50%     { transform: rotate(1.5deg); }
}

.login-header p {
  font-family: var(--font-label); font-size: 13px;
  color: var(--sg-purple3);
}

/* ============================================================
   FORM BODY
   ============================================================ */
.login-body {
  padding: 8px 32px 32px;
}

/* Alert */
.alert-danger {
  background: rgba(239,68,68,.15);
  border: 2px solid var(--sg-red2);
  color: #fca5a5;
  font-family: var(--font-label); font-size: 13px;
  padding: 12px 16px; border-radius: 14px;
  margin-bottom: 18px; text-align: center;
  animation: alertShake .45s ease, alertSlide .35s ease;
}
@keyframes alertSlide {
  from{ opacity:0; transform: translateY(-10px); }
  to  { opacity:1; transform: none; }
}
@keyframes alertShake {
  0%  { transform: translateX(0); }
  15% { transform: translateX(-7px) rotate(-1.5deg); }
  35% { transform: translateX(7px)  rotate(1.5deg); }
  55% { transform: translateX(-4px); }
  75% { transform: translateX(4px); }
  100%{ transform: translateX(0); }
}

/* Form group */
.form-group { margin-bottom: 16px; }
label {
  display: block;
  font-family: var(--font-label); font-size: 13px;
  color: var(--sg-purple3); margin-bottom: 6px;
  letter-spacing: .04em;
}

/* Input */
.input-wrap {
  position: relative;
  background: rgba(255,255,255,.06);
  border: 3px solid rgba(255,255,255,.12);
  border-radius: 16px;
  display: flex; align-items: center;
  transition: border-color .2s, box-shadow .2s, transform .15s;
  overflow: hidden;
}
.input-wrap:focus-within {
  border-color: var(--sg-red2);
  box-shadow: 0 0 0 3px rgba(239,68,68,.2), 0 4px 0 #7f1d1d;
  transform: scale(1.01);
}
.input-icon { padding: 0 10px 0 14px; font-size: 17px; flex-shrink: 0; }
.form-control {
  flex: 1; background: transparent; border: none; outline: none;
  font-family: var(--font-body); font-size: 14px; font-weight: 700;
  color: var(--sg-white); padding: 13px 14px 13px 0;
}
.form-control::placeholder { color: rgba(167,139,250,.45); }
.form-control:-webkit-autofill {
  -webkit-box-shadow: 0 0 0 50px var(--sg-bg3) inset;
  -webkit-text-fill-color: var(--sg-white);
}

/* Submit button */
.btn-login {
  width: 100%; padding: 15px;
  background: var(--sg-red);
  color: var(--sg-white);
  font-family: var(--font-label); font-size: 17px;
  border: 3px solid var(--sg-white);
  border-radius: 20px;
  box-shadow: 0 6px 0 #7f1d1d;
  cursor: pointer;
  margin-top: 8px;
  letter-spacing: .04em;
  transition: all .15s;
  position: relative; overflow: hidden;
}
/* Shimmer */
.btn-login::before {
  content: '';
  position: absolute; inset: 0;
  background: linear-gradient(45deg, transparent 40%, rgba(255,255,255,.18) 50%, transparent 60%);
  transform: translateX(-200%);
  transition: transform .5s;
}
.btn-login:hover  { transform: translateY(-4px); box-shadow: 0 10px 0 #7f1d1d; }
.btn-login:hover::before { transform: translateX(200%); }
.btn-login:active { transform: translateY(3px);  box-shadow: 0 3px 0 #7f1d1d; }

/* Divider */
.login-divider {
  border: none; border-top: 1px solid rgba(255,255,255,.08);
  margin: 22px 0 18px;
}

/* Back link */
.back-link {
  display: block; text-align: center;
  font-family: var(--font-label); font-size: 13px;
  color: var(--sg-purple3);
}
.back-link a {
  color: var(--sg-yellow); text-decoration: none;
  transition: color .2s;
}
.back-link a:hover { color: var(--sg-orange); text-decoration: underline; }

/* Copyright */
.login-footer {
  text-align: center; margin-top: 14px;
  font-family: var(--font-label); font-size: 11px;
  color: rgba(167,139,250,.4);
}

/* ============================================================
   LOCK ICON SPINNING BEHIND CARD (decorative)
   ============================================================ */
.bg-lock {
  position: fixed; bottom: -60px; right: -60px;
  font-size: 220px; opacity: .04;
  animation: lockSpin 20s linear infinite;
  pointer-events: none; z-index: 0;
  user-select: none;
}
@keyframes lockSpin { to { transform: rotate(360deg); } }

/* ============================================================
   CONFETTI DOT
   ============================================================ */
.confetti-dot {
  position: fixed; pointer-events: none; z-index: 9999;
  border-radius: 50%;
}

/* ============================================================
   RESPONSIVE
   ============================================================ */
@media (max-width: 500px) {
  .login-box { border-radius: 20px; }
  .login-header { padding: 24px 20px 14px; }
  .login-body { padding: 4px 20px 24px; }
  .admin-mascot { font-size: 56px; }
}
</style>
</head>
<body>

<!-- BG CANVAS -->
<canvas id="bgCanvas"></canvas>

<!-- BLOBS -->
<div class="blob blob-1"></div>
<div class="blob blob-2"></div>
<div class="blob blob-3"></div>

<!-- Floating shapes -->
<span class="fshape" style="top:6%; left:6%; font-size:24px; animation-duration:6s;  animation-delay:0s;">⭐</span>
<span class="fshape" style="top:12%;right:8%; font-size:18px; animation-duration:8s;  animation-delay:1s;">🛡️</span>
<span class="fshape" style="bottom:18%;left:7%; font-size:20px; animation-duration:7s;  animation-delay:2s;">🔐</span>
<span class="fshape" style="bottom:25%;right:6%;font-size:16px; animation-duration:5.5s;animation-delay:.5s;">💫</span>
<span class="fshape" style="top:45%; left:4%; font-size:14px; animation-duration:9s;  animation-delay:3s;">⚡</span>
<span class="fshape" style="top:70%;right:10%;font-size:18px; animation-duration:7.5s;animation-delay:1.5s;">🏆</span>

<!-- Decorative big lock -->
<div class="bg-lock">🔒</div>

<!-- ======================== CARD ======================== -->
<div class="login-box">

  <!-- Header -->
  <div class="login-header">
    <span class="admin-mascot">🐋</span>
    <div class="admin-badge">🛡️ ADMIN PANEL</div>
    <h1>Pari Admin!</h1>
    <p>Silakan masuk ke panel kendali</p>
  </div>

  <!-- Form body -->
  <div class="login-body">

    <!-- Error — logika PHP tidak berubah -->
    <?php if ($error): ?>
    <div class="alert-danger" id="errAlert">
      ⚠️ <?= htmlspecialchars($error) ?>
    </div>
    <?php endif; ?>

    <!-- Form — action, method, name fields tidak berubah -->
    <form action="" method="POST" class="admin-form" autocomplete="off">

      <div class="form-group">
        <label>📧 Email Administrator</label>
        <div class="input-wrap">
          <span class="input-icon">📧</span>
          <input type="email" name="email" class="form-control"
                 placeholder="admin@pariadventure.com"
                 required autocomplete="off" readonly
                 onfocus="this.removeAttribute('readonly');">
        </div>
      </div>

      <div class="form-group">
        <label>🔒 Password</label>
        <div class="input-wrap">
          <span class="input-icon">🔒</span>
          <input type="password" name="password" class="form-control"
                 id="passInput"
                 placeholder="••••••••"
                 required autocomplete="new-password" readonly
                 onfocus="this.removeAttribute('readonly');">
          <button type="button" id="eyeBtn"
                  onclick="togglePass()"
                  style="background:none;border:none;cursor:pointer;padding:0 14px 0 6px;font-size:18px;color:rgba(167,139,250,.6);flex-shrink:0;transition:color .2s;"
                  title="Tampilkan / sembunyikan password">👁️</button>
        </div>
      </div>

      <button type="submit" class="btn-login" id="loginBtn">
        🚀 Masuk ke Dashboard!
      </button>
    </form>

    <hr class="login-divider">

    <p class="back-link">
      &copy; 2026 Pari Adventure &nbsp;|&nbsp;
      <a href="../index.php">← Kembali ke Beranda</a>
    </p>

  </div><!-- /.login-body -->
</div><!-- /.login-box -->

<script>
/* ---- Toggle Password ---- */
function togglePass() {
  const p = document.getElementById('passInput');
  const b = document.getElementById('eyeBtn');
  if (p.type === 'password') { p.type = 'text';     b.textContent = '🙈'; }
  else                        { p.type = 'password'; b.textContent = '👁️'; }
}

/* ---- BG Canvas — floating confetti stars ---- */
(function() {
  const canvas = document.getElementById('bgCanvas');
  const ctx    = canvas.getContext('2d');
  let W, H;
  const SHAPES = ['★','●','▲','◆','♥','✦','⭐','⚡'];
  const COLORS = ['#fbbf24','#ef4444','#a78bfa','#f97316','#06b6d4','#ec4899'];
  const pts    = [];

  function resize() {
    W = canvas.width  = window.innerWidth;
    H = canvas.height = window.innerHeight;
  }
  window.addEventListener('resize', resize);
  resize();

  class Pt {
    constructor(init) { this.reset(init); }
    reset(init) {
      this.x    = Math.random() * W;
      this.y    = init ? Math.random() * H : H + 20;
      this.vy   = -(Math.random() * .65 + .2);
      this.vx   = (Math.random() - .5) * .3;
      this.size = Math.random() * 13 + 6;
      this.alpha= Math.random() * .18 + .04;
      this.rot  = Math.random() * Math.PI * 2;
      this.rotV = (Math.random() - .5) * .016;
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
      ctx.globalAlpha   = this.alpha;
      ctx.fillStyle     = this.color;
      ctx.font          = `${this.size}px serif`;
      ctx.translate(this.x, this.y);
      ctx.rotate(this.rot);
      ctx.textAlign     = 'center';
      ctx.textBaseline  = 'middle';
      ctx.fillText(this.shape, 0, 0);
      ctx.restore();
    }
  }

  for (let i = 0; i < 45; i++) pts.push(new Pt(true));
  (function loop() {
    ctx.clearRect(0, 0, W, H);
    pts.forEach(p => { p.update(); p.draw(); });
    requestAnimationFrame(loop);
  })();
})();

/* ---- Click confetti burst ---- */
const C = ['#fbbf24','#ef4444','#a78bfa','#f97316','#22c55e','#06b6d4'];
document.addEventListener('click', e => {
  for (let i = 0; i < 8; i++) {
    const d = document.createElement('div');
    d.className = 'confetti-dot';
    const sz = Math.random() * 10 + 5;
    const angle = (Math.PI * 2 / 8) * i;
    const dist  = Math.random() * 48 + 18;
    Object.assign(d.style, {
      width: sz + 'px', height: sz + 'px',
      left: e.clientX + 'px', top: e.clientY + 'px',
      background: C[~~(Math.random() * C.length)],
    });
    document.body.appendChild(d);
    d.animate([
      { transform: 'translate(-50%,-50%) scale(1)', opacity: 1 },
      { transform: `translate(calc(-50% + ${Math.cos(angle)*dist}px),calc(-50% + ${Math.sin(angle)*dist}px)) scale(0)`, opacity: 0 }
    ], { duration: 500, easing: 'ease-out' }).onfinish = () => d.remove();
  }
});

/* ---- Input glow on focus ---- */
document.querySelectorAll('.input-wrap').forEach(w => {
  w.addEventListener('focusin',  () => w.style.transform = 'scale(1.015)');
  w.addEventListener('focusout', () => w.style.transform = '');
});

/* ---- Button shake on error ---- */
<?php if ($error): ?>
document.getElementById('loginBtn')?.animate([
  { transform: 'translateX(0)' },
  { transform: 'translateX(-8px) rotate(-2deg)' },
  { transform: 'translateX(8px)  rotate(2deg)' },
  { transform: 'translateX(-5px)' },
  { transform: 'translateX(5px)' },
  { transform: 'translateX(0)' }
], { duration: 450, easing: 'ease' });
<?php endif; ?>
</script>

</body>
</html>