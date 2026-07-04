<?php
require_once __DIR__ . '/../includes/auth.php';

requireAdminLogin();

$admin = getCurrentAdmin();
$db = getDB();

$total_pesanan = $db->query("SELECT COUNT(*) FROM pemesanan")->fetchColumn() ?: 0;
$total_user    = $db->query("SELECT COUNT(*) FROM users")->fetchColumn() ?: 0;
$total_paket = $db->query("SELECT COUNT(*) FROM paket_trip WHERE status = 'aktif'")->fetchColumn() ?: 0;
$pendapatan    = $db->query("SELECT SUM(total_harga) FROM pemesanan WHERE status = 'paid'")->fetchColumn() ?: 0;

$pesanan_baru  = $db->query("SELECT COUNT(*) FROM pemesanan WHERE status = 'pending'")->fetchColumn() ?: 0;
$user_baru     = $db->query("SELECT COUNT(*) FROM users WHERE DATE(created_at) = CURDATE()")->fetchColumn() ?: 0;
$total_notif   = $pesanan_baru + $user_baru;
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Dashboard Admin – Pari Adventure</title>
<link href="https://fonts.googleapis.com/css2?family=Fredoka+One&family=Nunito:wght@400;600;700;800;900&display=swap" rel="stylesheet">
<style>
/* ====================================================
   STUMBLE GUYS THEME — MATCHED DENGAN PAKET TRIP
   ==================================================== */
:root {
  --bg:      #1a0b2e;
  --bg2:     #2d1a4a;
  --bg3:     #3d2660;
  --purple:  #9b59b6;
  --purple2: #7d3c98;
  --cyan:    #00e5ff;
  --cyan2:   #00b8d4;
  --yellow:  #ffd600;
  --yellow2: #f9a825;
  --pink:    #ff4fa3;
  --pink2:   #e91e8c;
  --green:   #00e676;
  --green2:  #00c853;
  --orange:  #ff6d00;
  --orange2: #e65100;
  --white:   #ffffff;
  --dim:     rgba(255,255,255,.62);
  --text-dim: rgba(255,255,255,0.65);
  --card-bg: rgba(255,255,255,.06);
  --card-bd: rgba(255,255,255,.11);
  --r:       16px;
  --r-lg:    24px;
  --r-xl:    36px;
}

*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
html{scroll-behavior:smooth}
body{
  font-family:'Nunito',sans-serif;
  background:var(--bg);
  color:#fff;
  display:flex;
  min-height:100vh;
  overflow-x:hidden;
}

/* SCROLLBAR */
::-webkit-scrollbar{width:6px}
::-webkit-scrollbar-track{background:var(--bg2)}
::-webkit-scrollbar-thumb{background:var(--purple);border-radius:6px}

/* ==================== ANIMATED BG ==================== */
body::before{
  content:'';
  position:fixed;inset:0;z-index:0;
  background:
    radial-gradient(ellipse at 15% 20%, rgba(155,89,182,.22) 0%, transparent 50%),
    radial-gradient(ellipse at 82% 72%, rgba(0,229,255,.13) 0%, transparent 50%),
    radial-gradient(ellipse at 50% 80%, rgba(255,79,163,.09) 0%, transparent 55%);
  animation:bgShift 12s ease-in-out infinite alternate;
  pointer-events:none;
}
@keyframes bgShift{
  0%{filter: hue-rotate(0deg);}
  100%{filter: hue-rotate(25deg);}
}

/* Floating confetti dots */
.confetti-wrap{position:fixed;inset:0;z-index:0;pointer-events:none;overflow:hidden}
.confetti-dot{
  position:absolute;border-radius:50%;
  animation:floatUp linear infinite;
}
@keyframes floatUp{
  0%  {transform:translateY(110vh) rotate(0deg);opacity:0}
  10% {opacity:.7}
  90% {opacity:.3}
  100%{transform:translateY(-60px) rotate(720deg);opacity:0}
}

/* ==================== LAYOUT ==================== */
.page{position:relative;z-index:1;display:flex;width:100%;min-height:100vh}

/* ==================== SIDEBAR ==================== */
.sidebar {
  width: 260px;
  background: linear-gradient(180deg, #2d1a4a 0%, #1a0b2e 100%);
  border-right: 2px solid rgba(155,89,182,.28);
  display: flex; flex-direction: column;
  position: fixed; top: 0; left: 0; bottom: 0; z-index: 500;
  overflow: hidden;
}
.sidebar::before {
  content: '';
  position: absolute; top: 0; left: 0; right: 0; height: 4px;
  background: linear-gradient(90deg,#ff4fa3,#ffd600,#00e5ff,#00e676,#ff6d00,#ff4fa3);
  background-size: 200% 100%;
  animation: rainbowSlide 3s linear infinite;
}
@keyframes rainbowSlide { to { background-position: -200% 0; } }

.sidebar-header { padding: 26px 20px 18px; border-bottom: 1px solid rgba(255,255,255,.07); }
.brand { display: flex; align-items: center; gap: 11px; margin-bottom: 10px; }
.brand-icon {
  width: 44px; height: 44px; border-radius: 50%;
  background: linear-gradient(135deg, #ff4fa3, #9b59b6);
  display: flex; align-items: center; justify-content: center;
  font-size: 1.25rem;
  box-shadow: 0 0 18px rgba(255,79,163,.45);
  animation: iconBounce 2.2s ease-in-out infinite;
  flex-shrink: 0;
}
@keyframes iconBounce { 0%,100%{transform:translateY(0) scale(1)} 50%{transform:translateY(-6px) scale(1.06)} }
.brand-text { font-family: 'Fredoka One', cursive; font-size: 1.3rem; color: #fff; line-height: 1; display: block; }
.brand-sub  { font-size: .65rem; color: rgba(255,255,255,.38); text-transform: uppercase; letter-spacing: .12em; display: block; margin-top: 2px; }
.badge-role {
  display: inline-flex; align-items: center; gap: 5px;
  background: linear-gradient(90deg, #ff6d00, #ffd600);
  color: #1a0b2e;
  font-family: 'Fredoka One', cursive; font-size: .74rem;
  padding: .28rem .85rem; border-radius: 50px;
  box-shadow: 0 3px 12px rgba(255,109,0,.4);
  animation: pulseBadge 2.5s ease-in-out infinite;
}
@keyframes pulseBadge {
  0%,100%{box-shadow:0 3px 12px rgba(255,109,0,.4)}
  50%{box-shadow:0 3px 24px rgba(255,109,0,.8)}
}

.menu-label { font-size: .66rem; font-weight: 900; color: rgba(255,255,255,.28); text-transform: uppercase; letter-spacing: .15em; padding: 18px 22px 8px; }
.nav-menu { list-style: none; padding: 0 10px; }
.nav-menu li a {
  display: flex; align-items: center; gap: 11px;
  padding: 12px 16px;
  color: var(--dim);
  text-decoration: none; font-size: .86rem; font-weight: 700;
  border-radius: var(--r); margin-bottom: 4px;
  transition: all .25s; position: relative; overflow: hidden;
}
.nav-menu li a::before {
  content: '';
  position: absolute; inset: 0;
  background: linear-gradient(135deg, rgba(155,89,182,.28), rgba(0,229,255,.08));
  opacity: 0; transition: opacity .25s; border-radius: var(--r);
}
.nav-menu li a:hover { color: #fff; transform: translateX(4px); }
.nav-menu li a:hover::before { opacity: 1; }
.nav-menu li a.active {
  background: linear-gradient(135deg, #9b59b6, #00b8d4);
  color: #fff; box-shadow: 0 6px 20px rgba(155,89,182,.38);
}
.nav-menu li a.active::before { opacity: 0; }

.sidebar-footer {
  margin-top: auto; padding: 18px 20px;
  border-top: 1px solid rgba(255,255,255,.07);
}
.user-profile { display: flex; align-items: center; gap: 11px; }
.avatar {
  width: 40px; height: 40px; border-radius: 50%;
  background: linear-gradient(135deg, #00e5ff, #9b59b6);
  display: flex; align-items: center; justify-content: center;
  font-family: 'Fredoka One', cursive; font-size: .95rem; color: #fff;
  flex-shrink: 0; box-shadow: 0 0 14px rgba(0,229,255,.38);
}
.user-info .name  { font-weight: 800; font-size: .84rem; color: #fff; display: block; }
.user-info .email { font-size: .68rem; color: var(--dim); }

/* ==================== MAIN CONTENT ==================== */
.main-content{
  flex:1;
  margin-left:260px;
  display:flex;flex-direction:column;
  min-height:100vh;
}

/* ==================== TOPBAR ==================== */
.topbar {
  background: rgba(45,26,74,.88);
  backdrop-filter: blur(20px); -webkit-backdrop-filter: blur(20px);
  border-bottom: 2px solid rgba(255,255,255,.07);
  height: 70px; padding: 0 32px;
  display: flex; align-items: center; justify-content: space-between;
  position: sticky; top: 0; z-index: 400;
}
.topbar-title {
  font-family: 'Fredoka One', cursive;
  font-size: 1.35rem; color: #fff;
  display: flex; align-items: center; gap: 10px;
}
.topbar-icon { animation: spinIcon 5s linear infinite; display: inline-block; }
@keyframes spinIcon { to { transform: rotate(360deg); } }

.topbar-actions { display: flex; align-items: center; gap: 10px; }
.notif-btn {
  position: relative; width: 42px; height: 42px;
  background: rgba(255,255,255,.08);
  border: 1.5px solid rgba(255,255,255,.14); border-radius: 12px;
  display: flex; align-items: center; justify-content: center;
  font-size: 1.1rem; cursor: pointer; text-decoration: none;
  transition: all .25s;
}
.notif-btn:hover { background: rgba(255,255,255,.15); transform: scale(1.06); }
.notif-dot {
  position: absolute; top: -5px; right: -5px;
  min-width: 18px; height: 18px; padding: 0 4px;
  background: linear-gradient(135deg, #ff4fa3, #ff6d00);
  border-radius: 50px; border: 2px solid var(--bg);
  color: #fff; font-size: .6rem; font-weight: 900;
  display: flex; align-items: center; justify-content: center;
  font-family: 'Fredoka One', cursive;
  animation: notifPing 1.5s ease-in-out infinite;
}
@keyframes notifPing {
  0%,100%{box-shadow:0 0 0 0 rgba(255,79,163,.5)}
  50%{box-shadow:0 0 0 8px rgba(255,79,163,0)}
}
.logout-btn {
  display: flex; align-items: center; gap: 7px;
  background: linear-gradient(135deg, #ff4fa3, #ff6d00);
  color: #fff; font-family: 'Fredoka One', cursive;
  font-size: .82rem; letter-spacing: .03em;
  padding: .5rem 1.2rem; border-radius: 50px; border: none;
  cursor: pointer; text-decoration: none;
  box-shadow: 0 4px 16px rgba(255,79,163,.38);
  transition: all .25s;
}
.logout-btn:hover { transform: translateY(-2px) scale(1.03); box-shadow: 0 8px 24px rgba(255,79,163,.6); }

/* ==================== DASHBOARD BODY ==================== */
.dashboard-body{
  padding: 28px 32px;
  flex:1;
}

/* Section heading */
.section-title{
  font-family:'Fredoka One',cursive;
  font-size: 1.05rem; color: #fff;
  display:flex;align-items:center;gap:8px;
  margin-bottom: 18px;
}
.section-title::after{
  content:'';flex:1;height:2px;
  background:linear-gradient(90deg,rgba(255,255,255,.14),transparent);
  border-radius:2px;
}

/* ==================== STAT CARDS ==================== */
.stats-grid{
  display:grid;
  grid-template-columns:repeat(4,1fr);
  gap:20px;
  margin-bottom:32px;
}
.stat-card{
  border-radius:var(--r-lg);
  padding:26px 22px;
  position:relative;overflow:hidden;
  cursor:default;
  transition:transform .3s, box-shadow .3s;
  border:2px solid transparent;
}
.stat-card:hover{
  transform:translateY(-8px) scale(1.02);
}
.stat-card::before{
  content:'';
  position:absolute;inset:0;
  border-radius:inherit;
  background:inherit;
  opacity:.15;
  z-index:0;
}

/* Individual card colors + wobble animations */
.card-blue{
  background:linear-gradient(135deg,#0070f3,#00b8d4);
  box-shadow:0 8px 32px rgba(0,112,243,.35);
  animation:wobble1 6s ease-in-out infinite;
}
.card-blue:hover{box-shadow:0 16px 48px rgba(0,112,243,.55)}

.card-green{
  background:linear-gradient(135deg,#00c853,#64dd17);
  box-shadow:0 8px 32px rgba(0,200,83,.35);
  animation:wobble2 7s ease-in-out infinite;
}
.card-green:hover{box-shadow:0 16px 48px rgba(0,200,83,.55)}

.card-orange{
  background:linear-gradient(135deg,#ff6d00,#ffd600);
  box-shadow:0 8px 32px rgba(255,109,0,.35);
  animation:wobble3 5.5s ease-in-out infinite;
}
.card-orange:hover{box-shadow:0 16px 48px rgba(255,109,0,.55)}

.card-red{
  background:linear-gradient(135deg,#ff4fa3,#9b59b6);
  box-shadow:0 8px 32px rgba(255,79,163,.35);
  animation:wobble4 8s ease-in-out infinite;
}
.card-red:hover{box-shadow:0 16px 48px rgba(255,79,163,.55)}

@keyframes wobble1{0%,100%{transform:rotate(0deg)}33%{transform:rotate(.8deg)}66%{transform:rotate(-.6deg)}}
@keyframes wobble2{0%,100%{transform:rotate(0deg)}33%{transform:rotate(-.7deg)}66%{transform:rotate(.9deg)}}
@keyframes wobble3{0%,100%{transform:rotate(0deg)}40%{transform:rotate(1deg)}70%{transform:rotate(-.5deg)}}
@keyframes wobble4{0%,100%{transform:rotate(0deg)}25%{transform:rotate(-.8deg)}75%{transform:rotate(.7deg)}}
.stat-card:hover{transform:translateY(-8px) scale(1.03) rotate(0deg) !important}

.card-top{
  display:flex;align-items:center;justify-content:space-between;
  margin-bottom:16px;position:relative;z-index:1;
}
.icon-box{
  width:52px;height:52px;border-radius:16px;
  background:rgba(255,255,255,.2);
  display:flex;align-items:center;justify-content:center;
  font-size:1.5rem;
  backdrop-filter:blur(4px);
}
.card-badge{
  font-family:'Fredoka One',cursive;
  font-size:.7rem;letter-spacing:.05em;
  background:rgba(255,255,255,.25);
  padding:.25rem .7rem;border-radius:50px;
  color:#fff;
}
.stat-value{
  font-family:'Fredoka One',cursive;
  font-size:2rem;color:#fff;
  line-height:1;margin-bottom:6px;
  position:relative;z-index:1;
  text-shadow:0 2px 10px rgba(0,0,0,.2);
}
.stat-label{
  font-size:.8rem;font-weight:700;
  color:rgba(255,255,255,.8);
  position:relative;z-index:1;
  text-transform:uppercase;letter-spacing:.08em;
}

.stat-card .deco{
  position:absolute;
  right:-16px;bottom:-16px;
  font-size:5rem;opacity:.12;
  line-height:1;pointer-events:none;
  animation:decoSpin 12s linear infinite;
}
@keyframes decoSpin{to{transform:rotate(360deg)}}

/* ==================== WELCOME CARD ==================== */
.welcome-card{
  background:var(--card-bg);
  border:1.5px solid var(--card-bd);
  border-radius:var(--r-lg);
  overflow:hidden;
  position:relative;
}
.welcome-card::before{
  content:'';
  position:absolute;top:0;left:0;right:0;height:3px;
  background:linear-gradient(90deg,#ff4fa3,#ffd600,#00e5ff,#00e676,#ff6d00,#ff4fa3);
  background-size:200% 100%;
  animation:rainbowSlide 3s linear infinite;
}
.welcome-header{
  padding:22px 28px 0;
  display:flex;align-items:center;gap:14px;
}
.welcome-avatar{
  width:56px;height:56px;border-radius:50%;
  background:linear-gradient(135deg,#00e5ff,#9b59b6);
  display:flex;align-items:center;justify-content:center;
  font-family:'Fredoka One',cursive;font-size:1.4rem;color:#fff;
  box-shadow:0 0 20px rgba(0,229,255,.4);
  flex-shrink:0;
  animation:avatarFloat 3s ease-in-out infinite;
}
@keyframes avatarFloat{0%,100%{transform:translateY(0)}50%{transform:translateY(-6px)}}
.welcome-name{
  font-family:'Fredoka One',cursive;
  font-size:1.2rem;color:#fff;
  display:block;line-height:1;
}
.welcome-sub{font-size:.8rem;color:var(--text-dim);margin-top:3px}
.welcome-body{
  padding:20px 28px 28px;
  color:var(--text-dim);
  font-size:.9rem;line-height:1.7;
}
.welcome-tags{
  display:flex;flex-wrap:wrap;gap:8px;margin-top:18px;
}
.wtag{
  font-family:'Fredoka One',cursive;
  font-size:.78rem;letter-spacing:.03em;
  padding:.3rem .9rem;border-radius:50px;
  color:#fff;
  animation:tagPop 3s ease-in-out infinite;
}
.wtag:nth-child(1){background:#ff4fa3;box-shadow:0 3px 12px rgba(255,79,163,.4);animation-delay:0s}
.wtag:nth-child(2){background:#00b8d4;box-shadow:0 3px 12px rgba(0,184,212,.4);animation-delay:.3s}
.wtag:nth-child(3){background:#00c853;box-shadow:0 3px 12px rgba(0,200,83,.4);animation-delay:.6s}
.wtag:nth-child(4){background:#ff6d00;box-shadow:0 3px 12px rgba(255,109,0,.4);animation-delay:.9s}
.wtag:nth-child(5){background:#9b59b6;box-shadow:0 3px 12px rgba(155,89,182,.4);animation-delay:1.2s}
@keyframes tagPop{0%,100%{transform:scale(1)}50%{transform:scale(1.06)}}

/* ==================== RESPONSIVE ==================== */
@media(max-width:1100px){
  .stats-grid{grid-template-columns:repeat(2,1fr)}
}
@media(max-width:768px){
  .sidebar{transform:translateX(-100%)}
  .main-content{margin-left:0}
  .stats-grid{grid-template-columns:1fr}
  .dashboard-body{padding:20px}
}
</style>
</head>
<body>

<div class="confetti-wrap" id="confettiWrap"></div>

<div class="page">

<aside class="sidebar">
  <div class="sidebar-header">
    <div class="brand">
      <div class="brand-icon">🎮</div>
      <div>
        <span class="brand-text">Pari Admin</span>
        <span class="brand-sub">Kepulauan Seribu</span>
      </div>
    </div>
    <div class="badge-role">⚡ <?= strtoupper(htmlspecialchars($admin['role'] ?? 'SUPERADMIN')) ?></div>
  </div>

  <div class="menu-label">🕹️ Menu Utama</div>
  <ul class="nav-menu">
    <li><a href="dashboard.php" class="active"><span>🏆</span> Dashboard</a></li>
    <li><a href="paket_trip.php"><span>🏝️</span> Paket Trip</a></li>
    <li><a href="data_pemesanan.php"><span>📋</span> Data Pemesanan</a></li>
    <li><a href="data_pengguna.php"><span>👥</span> Data Pengguna</a></li>
  </ul>

  <div class="sidebar-footer">
    <div class="user-profile">
      <div class="avatar"><?= strtoupper(substr($admin['nama'] ?? 'S', 0, 1)) ?></div>
      <div class="user-info">
        <span class="name"><?= htmlspecialchars($admin['nama'] ?? 'Super Admin') ?></span>
        <span class="email"><?= htmlspecialchars($admin['email'] ?? 'admin@pariadventure.com') ?></span>
      </div>
    </div>
  </div>
</aside>

<main class="main-content">

  <header class="topbar">
    <div class="topbar-title">
      <span class="topbar-icon">⭐</span>
      Dashboard Analitik
    </div>
    <div class="topbar-actions">
      <a href="data_pemesanan.php" class="notif-btn" title="Lihat Pesanan Baru">
        🔔
        <?php if($total_notif > 0): ?>
          <div class="notif-dot"><?= $total_notif ?></div>
        <?php endif; ?>
      </a>
      <a href="../logout.php" class="logout-btn" onclick="return confirm('Apakah Anda yakin ingin keluar?');">
        🚪 Logout
      </a>
    </div>
  </header>

  <div class="dashboard-body">

    <div class="section-title">🎯 Ringkasan Hari Ini</div>

    <div class="stats-grid">

      <div class="stat-card card-blue">
        <div class="card-top">
          <div class="icon-box">📝</div>
          <div class="card-badge">TOTAL</div>
        </div>
        <div class="stat-value" id="cnt-pesanan">0</div>
        <div class="stat-label">Total Pemesanan</div>
        <div class="deco">📋</div>
      </div>

      <div class="stat-card card-green">
        <div class="card-top">
          <div class="icon-box">💰</div>
          <div class="card-badge">LUNAS</div>
        </div>
        <div class="stat-value"><?= 'Rp ' . number_format($pendapatan, 0, ',', '.') ?></div>
        <div class="stat-label">Pendapatan Bersih</div>
        <div class="deco">💎</div>
      </div>

      <div class="stat-card card-orange">
        <div class="card-top">
          <div class="icon-box">👥</div>
          <div class="card-badge">PLAYERS</div>
        </div>
        <div class="stat-value" id="cnt-user">0</div>
        <div class="stat-label">Pengguna Terdaftar</div>
        <div class="deco">🎮</div>
      </div>

      <div class="stat-card card-red">
        <div class="card-top">
          <div class="icon-box">🏝️</div>
          <div class="card-badge">AKTIF</div>
         </div>
        <div class="stat-value" id="cnt-paket"><?= $total_paket ?></div>
        <div class="stat-label">Paket Aktif</div>
        <div class="deco">🌴</div>
      </div>

    </div>

    <div class="welcome-card">
      <div class="welcome-header">
        <div class="welcome-avatar"><?= strtoupper(substr($admin['nama'] ?? 'S', 0, 1)) ?></div>
        <div class="welcome-name-wrap">
          <span class="welcome-name">Selamat Datang, <?= htmlspecialchars($admin['nama'] ?? 'Super Admin') ?>! 🎉</span>
          <div class="welcome-sub">Level Admin • Pari Adventure HQ</div>
        </div>
      </div>
      <div class="welcome-body">
        Ini adalah panel kontrol utama Pari Adventure. Gunakan menu di sebelah kiri untuk mengelola pemesanan, pengguna, dan paket trip. Semua data tampil real-time — saatnya menguasai arena! 🏆
        <div class="welcome-tags">
          <span class="wtag">📝 <?= $total_pesanan ?> Pemesanan</span>
          <span class="wtag">👥 <?= $total_user ?> Pengguna</span>
          <span class="wtag">🏝️ <?= $total_paket ?> Paket</span>
          <?php if($pesanan_baru > 0): ?>
            <span class="wtag">⏳ <?= $pesanan_baru ?> Pending</span>
          <?php endif; ?>
          <?php if($user_baru > 0): ?>
            <span class="wtag">🆕 <?= $user_baru ?> User Baru</span>
          <?php endif; ?>
        </div>
      </div>
    </div>

  </div>
</main>

</div>

<script>
/* =========== CONFETTI =========== */
(function(){
  const wrap = document.getElementById('confettiWrap');
  if(!wrap) return;
  const colors = ['#ff4fa3','#ffd600','#00e5ff','#00e676','#ff6d00','#9b59b6','#ffffff'];
  for(let i = 0; i < 35; i++){
    const d = document.createElement('div');
    d.classList.add('confetti-dot');
    const size = Math.random()*8+3;
    d.style.cssText=`
      width:${size}px;height:${size}px;
      background:${colors[Math.floor(Math.random()*colors.length)]};
      left:${Math.random()*100}%;
      animation-duration:${Math.random()*12+8}s;
      animation-delay:${Math.random()*12}s;
      opacity:.7;
    `;
    wrap.appendChild(d);
  }
})();

/* =========== COUNTER ANIMATION =========== */
function countUp(el, target, duration){
  if(!el) return;
  const start = performance.now();
  const update = (now) => {
    const progress = Math.min((now - start) / duration, 1);
    const ease = 1 - Math.pow(1 - progress, 3);
    el.textContent = Math.round(ease * target).toLocaleString('id-ID');
    if(progress < 1) requestAnimationFrame(update);
  };
  requestAnimationFrame(update);
}

window.addEventListener('DOMContentLoaded', () => {
  countUp(document.getElementById('cnt-pesanan'), <?= (int)$total_pesanan ?>, 1200);
  countUp(document.getElementById('cnt-user'),    <?= (int)$total_user ?>,    1400);
  countUp(document.getElementById('cnt-paket'),   <?= (int)$total_paket ?>,   1000);
});

/* =========== CARD HOVER SOUND EFFECT (visual only) =========== */
document.querySelectorAll('.stat-card').forEach(card => {
  card.addEventListener('mouseenter', () => {
    card.style.animationPlayState = 'paused';
  });
  card.addEventListener('mouseleave', () => {
    card.style.animationPlayState = 'running';
  });
});
</script>
</body>
</html>