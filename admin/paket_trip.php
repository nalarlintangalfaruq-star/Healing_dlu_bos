<?php
require_once __DIR__ . '/../includes/auth.php';
requireAdminLogin();

$admin = getCurrentAdmin();
$db = getDB();
$pesan = '';

// Proses Tambah Paket
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['tambah_paket'])) {
    $nama_paket = $_POST['nama_paket'];
    $deskripsi  = $_POST['deskripsi'];
    $via        = $_POST['via'];
    $durasi     = $_POST['durasi'];
    $status     = $_POST['status'];

    try {
        $stmt = $db->prepare("INSERT INTO paket_trip (nama_paket, deskripsi, via, durasi, status) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$nama_paket, $deskripsi, $via, $durasi, $status]);
        $pesan = 'success|✅ Paket berhasil ditambahkan! Level baru terbuka!';
    } catch (PDOException $e) {
        $pesan = 'error|❌ Gagal menambahkan paket: ' . $e->getMessage();
    }
}

// Proses Hapus Paket
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['hapus_paket'])) {
    $id_paket = $_POST['id_paket'];

    try {
        $stmt = $db->prepare("DELETE FROM paket_trip WHERE id = ?");
        $stmt->execute([$id_paket]);
        $pesan = 'success|🗑️ Paket berhasil dihapus! Level ditutup.';
    } catch (PDOException $e) {
        $pesan = 'error|❌ Gagal menghapus paket: ' . $e->getMessage();
    }
}

// Proses Ubah Status Paket (Aktif / Nonaktif)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ubah_status'])) {
    $id_paket = $_POST['id_paket'];
    $status_baru = $_POST['status_baru'];

    try {
        $stmt = $db->prepare("UPDATE paket_trip SET status = ? WHERE id = ?");
        $stmt->execute([$status_baru, $id_paket]);
        $pesan = 'success|🔄 Status paket berhasil diubah!';
    } catch (PDOException $e) {
        $pesan = 'error|❌ Gagal mengubah status: ' . $e->getMessage();
    }
}

$paket_list = $db->query("SELECT * FROM paket_trip ORDER BY created_at DESC")->fetchAll();
$pesan_parts = $pesan ? explode('|', $pesan, 2) : [];
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Paket Trip – Pari Admin</title>
<link href="https://fonts.googleapis.com/css2?family=Fredoka+One&family=Nunito:wght@400;600;700;800;900&display=swap" rel="stylesheet">
<style>
/* ================================================
   STUMBLE GUYS THEME — PAKET TRIP PAGE
   Consistent with admin dashboard theme
   ================================================ */
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
  --card-bg: rgba(255,255,255,.06);
  --card-bd: rgba(255,255,255,.11);
  --r:       16px;
  --r-lg:    24px;
  --r-xl:    36px;
}

*,*::before,*::after { box-sizing: border-box; margin: 0; padding: 0; }
html { scroll-behavior: smooth; }
body {
  font-family: 'Nunito', sans-serif;
  background: var(--bg);
  color: #fff;
  display: flex;
  min-height: 100vh;
  overflow-x: hidden;
}

/* animated radial bg */
body::before {
  content: '';
  position: fixed; inset: 0; z-index: 0; pointer-events: none;
  background:
    radial-gradient(ellipse at 15% 20%, rgba(155,89,182,.22) 0%, transparent 50%),
    radial-gradient(ellipse at 82% 72%, rgba(0,229,255,.13) 0%, transparent 50%),
    radial-gradient(ellipse at 50% 80%, rgba(255,79,163,.09) 0%, transparent 55%);
  animation: bgDrift 12s ease-in-out infinite alternate;
}
@keyframes bgDrift {
  0%   { filter: hue-rotate(0deg); }
  100% { filter: hue-rotate(25deg); }
}

/* scrollbar */
::-webkit-scrollbar { width: 6px; }
::-webkit-scrollbar-track { background: var(--bg2); }
::-webkit-scrollbar-thumb { background: var(--purple); border-radius: 6px; }

/* ============ CONFETTI ============ */
.confetti-wrap { position: fixed; inset: 0; z-index: 0; pointer-events: none; overflow: hidden; }
.cdot {
  position: absolute; border-radius: 50%;
  animation: floatUp linear infinite;
}
@keyframes floatUp {
  0%   { transform: translateY(110vh) rotate(0deg); opacity: 0; }
  10%  { opacity: .7; }
  90%  { opacity: .3; }
  100% { transform: translateY(-60px) rotate(720deg); opacity: 0; }
}

/* ============ LAYOUT ============ */
.page { position: relative; z-index: 1; display: flex; width: 100%; min-height: 100vh; }

/* ============ SIDEBAR ============ */
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

/* ============ MAIN CONTENT ============ */
.main-content {
  flex: 1; margin-left: 260px;
  display: flex; flex-direction: column; min-height: 100vh;
}

/* ============ TOPBAR ============ */
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

/* ============ BODY ============ */
.dashboard-body { padding: 28px 32px; flex: 1; }

/* ============ SECTION TITLE ============ */
.section-title {
  font-family: 'Fredoka One', cursive;
  font-size: 1.05rem; color: #fff;
  display: flex; align-items: center; gap: 8px;
  margin-bottom: 18px;
}
.section-title::after {
  content: ''; flex: 1; height: 2px;
  background: linear-gradient(90deg, rgba(255,255,255,.14), transparent);
  border-radius: 2px;
}

/* ============ FLASH MESSAGE ============ */
.flash-msg {
  border-radius: var(--r); padding: 14px 20px;
  margin-bottom: 22px;
  display: flex; align-items: center; gap: 10px;
  font-weight: 700; font-size: .9rem;
  animation: flashSlide .5s cubic-bezier(.34,1.56,.64,1) both;
}
@keyframes flashSlide { from{opacity:0;transform:translateY(-14px)} to{opacity:1;transform:none} }
.flash-success {
  background: linear-gradient(135deg,rgba(0,200,83,.18),rgba(100,221,23,.1));
  border: 1.5px solid rgba(0,200,83,.45); color: #00e676;
}
.flash-error {
  background: linear-gradient(135deg,rgba(255,79,163,.15),rgba(255,109,0,.1));
  border: 1.5px solid rgba(255,79,163,.45); color: #ff4fa3;
}

/* ============ CARD ============ */
.sg-card {
  background: var(--card-bg);
  border: 1.5px solid var(--card-bd);
  border-radius: var(--r-lg);
  overflow: hidden;
  margin-bottom: 28px;
  position: relative;
  transition: box-shadow .3s;
}
.sg-card:hover { box-shadow: 0 0 40px rgba(155,89,182,.12); }
.sg-card::before {
  content: '';
  position: absolute; top: 0; left: 0; right: 0; height: 3px;
  background: linear-gradient(90deg,#ff4fa3,#ffd600,#00e5ff,#00e676,#ff6d00,#ff4fa3);
  background-size: 200% 100%;
  animation: rainbowSlide 3s linear infinite;
}
.card-header {
  padding: 18px 24px 0;
  display: flex; align-items: center; gap: 12px;
}
.card-icon {
  width: 46px; height: 46px; border-radius: 14px;
  display: flex; align-items: center; justify-content: center;
  font-size: 1.3rem; flex-shrink: 0;
  animation: iconBounce 3s ease-in-out infinite;
}
.card-icon.purple { background: linear-gradient(135deg,#9b59b6,#7d3c98); box-shadow: 0 4px 16px rgba(155,89,182,.4); }
.card-icon.cyan   { background: linear-gradient(135deg,#00e5ff,#00b8d4); box-shadow: 0 4px 16px rgba(0,229,255,.35); }
.card-title {
  font-family: 'Fredoka One', cursive;
  font-size: 1.1rem; color: #fff; line-height: 1;
}
.card-sub { font-size: .72rem; color: var(--dim); margin-top: 3px; }
.card-body { padding: 18px 24px 24px; }

/* ============ FORM ============ */
.form-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 16px;
  margin-bottom: 16px;
}
.form-group { display: flex; flex-direction: column; gap: 6px; }
.form-label {
  font-size: .78rem; font-weight: 800;
  color: var(--dim); text-transform: uppercase; letter-spacing: .08em;
}
.form-control {
  width: 100%; padding: 11px 14px;
  background: rgba(255,255,255,.07);
  border: 1.5px solid rgba(255,255,255,.14);
  border-radius: var(--r); color: #fff;
  font-family: 'Nunito', sans-serif; font-size: .9rem; font-weight: 700;
  outline: none; transition: all .25s;
}
.form-control:focus {
  border-color: var(--cyan);
  background: rgba(0,229,255,.07);
  box-shadow: 0 0 0 3px rgba(0,229,255,.15);
}
.form-control::placeholder { color: rgba(255,255,255,.3); font-weight: 600; }
select.form-control option { background: #2d1a4a; color: #fff; }
textarea.form-control { resize: vertical; min-height: 90px; }

/* ============ SUBMIT BTN ============ */
.btn-submit {
  display: inline-flex; align-items: center; gap: 8px;
  background: linear-gradient(135deg, #00e5ff, #9b59b6);
  color: #fff; font-family: 'Fredoka One', cursive;
  font-size: .92rem; letter-spacing: .04em;
  padding: .72rem 2rem; border-radius: 50px;
  border: none; cursor: pointer;
  box-shadow: 0 6px 24px rgba(0,229,255,.35);
  transition: all .28s;
  animation: btnPulse 3s ease-in-out infinite;
}
@keyframes btnPulse {
  0%,100%{box-shadow:0 6px 24px rgba(0,229,255,.35)}
  50%{box-shadow:0 6px 36px rgba(0,229,255,.65)}
}
.btn-submit:hover { transform: translateY(-3px) scale(1.04); }
.btn-submit:active { transform: scale(.97); }
.btn-icon { font-size: 1.1rem; animation: iconBounce 1.8s ease-in-out infinite; }

/* ============ BUTTONS ACTION (HAPUS & UBAH STATUS) ============ */
.btn-action {
  display: inline-flex; align-items: center; gap: 4px;
  color: #fff; font-family: 'Fredoka One', cursive;
  font-size: .75rem; padding: .35rem .8rem;
  border-radius: 50px; border: none; cursor: pointer;
  transition: all .25s;
}
.btn-action:hover { transform: scale(1.05); }

.btn-delete {
  background: linear-gradient(135deg, #ff4fa3, #e91e8c);
  box-shadow: 0 3px 12px rgba(255,79,163,.4);
}
.btn-delete:hover { box-shadow: 0 4px 16px rgba(255,79,163,.6); }

.btn-status-off {
  background: linear-gradient(135deg, #ff6d00, #e65100);
  box-shadow: 0 3px 12px rgba(255,109,0,.4);
}
.btn-status-off:hover { box-shadow: 0 4px 16px rgba(255,109,0,.6); }

.btn-status-on {
  background: linear-gradient(135deg, #00e676, #00c853);
  box-shadow: 0 3px 12px rgba(0,230,118,.4);
}
.btn-status-on:hover { box-shadow: 0 4px 16px rgba(0,230,118,.6); }

/* ============ TABLE ============ */
.table-wrap { overflow-x: auto; border-radius: var(--r); }
.table-data {
  width: 100%; border-collapse: collapse;
  min-width: 600px;
}
.table-data thead tr {
  background: rgba(155,89,182,.18);
  border-bottom: 2px solid rgba(155,89,182,.3);
}
.table-data th {
  padding: 13px 16px;
  font-family: 'Fredoka One', cursive;
  font-size: .8rem; letter-spacing: .06em; text-transform: uppercase;
  color: var(--cyan); text-align: left; white-space: nowrap;
}
.table-data td {
  padding: 14px 16px;
  font-size: .875rem; font-weight: 700;
  border-bottom: 1px solid rgba(255,255,255,.05);
  color: var(--dim);
  vertical-align: middle;
  transition: background .2s;
}
.table-data tbody tr {
  transition: all .25s;
  animation: rowFadeIn .5s ease both;
}
@keyframes rowFadeIn { from{opacity:0;transform:translateX(-12px)} to{opacity:1;transform:none} }
.table-data tbody tr:hover td {
  background: rgba(155,89,182,.1);
  color: #fff;
}
.table-data td strong { color: #fff; }

/* ID badge */
.id-badge {
  display: inline-flex; align-items: center;
  background: rgba(0,229,255,.12);
  border: 1px solid rgba(0,229,255,.25);
  color: var(--cyan);
  font-family: 'Fredoka One', cursive;
  font-size: .78rem; padding: .2rem .65rem;
  border-radius: 50px; letter-spacing: .05em;
}

/* Via badge */
.via-badge {
  display: inline-flex; align-items: center; gap: 5px;
  font-family: 'Fredoka One', cursive;
  font-size: .76rem; padding: .24rem .75rem;
  border-radius: 50px; color: #fff;
  animation: badgeWobble 3s ease-in-out infinite;
}
@keyframes badgeWobble { 0%,100%{transform:scale(1)} 50%{transform:scale(1.05)} }
.via-angke { background: linear-gradient(135deg,#ff6d00,#ffd600); box-shadow: 0 3px 12px rgba(255,109,0,.35); }
.via-ancol { background: linear-gradient(135deg,#9b59b6,#00b8d4); box-shadow: 0 3px 12px rgba(155,89,182,.35); }

/* Status badge */
.status-badge {
  display: inline-flex; align-items: center; gap: 5px;
  font-family: 'Fredoka One', cursive; font-size: .78rem;
  padding: .24rem .75rem; border-radius: 50px; color: #fff;
}
.status-aktif {
  background: linear-gradient(135deg,#00c853,#64dd17);
  box-shadow: 0 3px 12px rgba(0,200,83,.4);
  animation: statusGlow 2s ease-in-out infinite;
}
@keyframes statusGlow {
  0%,100%{box-shadow:0 3px 12px rgba(0,200,83,.4)}
  50%{box-shadow:0 3px 20px rgba(0,200,83,.75)}
}
.status-nonaktif {
  background: linear-gradient(135deg,#ff4fa3,#ff6d00);
  box-shadow: 0 3px 12px rgba(255,79,163,.35);
}

/* Durasi pill */
.durasi-pill {
  display: inline-flex; align-items: center; gap: 4px;
  background: rgba(255,214,0,.1);
  border: 1px solid rgba(255,214,0,.25);
  color: var(--yellow);
  font-size: .8rem; font-weight: 800;
  padding: .22rem .65rem; border-radius: 50px;
}

/* Empty state */
.empty-state {
  text-align: center; padding: 48px 20px;
  animation: fadeIn .6s ease both;
}
@keyframes fadeIn { from{opacity:0} to{opacity:1} }
.empty-icon { font-size: 3.5rem; margin-bottom: 12px; animation: iconBounce 2s ease-in-out infinite; }
.empty-text { color: var(--dim); font-size: .9rem; font-weight: 700; }

/* ============ RUNNING CHARACTERS ============ */
.runners {
  position: fixed; bottom: 0; left: 260px; right: 0;
  height: 48px; pointer-events: none; z-index: 0; overflow: hidden;
}
.runner { position: absolute; bottom: 0; font-size: 1.5rem; animation: runAcross linear infinite; }
@keyframes runAcross { from{left:-50px} to{left:calc(100% + 50px)} }

/* ============ RESPONSIVE ============ */
@media (max-width: 900px) {
  .sidebar { transform: translateX(-100%); }
  .main-content { margin-left: 0; }
  .form-grid { grid-template-columns: 1fr; }
  .dashboard-body { padding: 18px; }
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
    <li><a href="dashboard.php"><span>🏆</span> Dashboard</a></li>
    <li><a href="paket_trip.php" class="active"><span>🏝️</span> Paket Trip</a></li>
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
      <span class="topbar-icon">🏝️</span>
      Manajemen Paket Trip
    </div>
    <div class="topbar-actions">
      <a href="data_pemesanan.php" class="notif-btn" title="Pesanan Baru">
        🔔
        <div class="notif-dot">!</div>
      </a>
      <a href="../logout.php" class="logout-btn" onclick="return confirm('Yakin keluar dari game?');">
        🚪 Logout
      </a>
    </div>
  </header>

  <div class="dashboard-body">

    <?php if (!empty($pesan_parts)): ?>
    <div class="flash-msg flash-<?= $pesan_parts[0] === 'success' ? 'success' : 'error' ?>">
      <?= htmlspecialchars($pesan_parts[1] ?? '') ?>
    </div>
    <?php endif; ?>

    <div class="sg-card" id="formCard">
      <div class="card-header">
        <div class="card-icon purple">✨</div>
        <div>
          <div class="card-title">Tambah Paket Baru</div>
          <div class="card-sub">Buka level baru untuk petualangan!</div>
        </div>
      </div>
      <div class="card-body">
        <form method="POST" action="" id="paketForm">
          <div class="form-grid">
            <div class="form-group">
              <label class="form-label">🎯 Nama Paket</label>
              <input type="text" name="nama_paket" class="form-control" required
                     placeholder="Cth: Private Trip 2D1N">
            </div>
            <div class="form-group">
              <label class="form-label">⏱️ Durasi</label>
              <input type="text" name="durasi" class="form-control" required
                     placeholder="Cth: 2 Hari 1 Malam">
            </div>
            <div class="form-group">
              <label class="form-label">⚓ Keberangkatan (Via)</label>
              <select name="via" class="form-control" required>
                <option value="muara_angke">🚢 Muara Angke</option>
                <option value="marina_ancol">⛵ Marina Ancol</option>
              </select>
            </div>
            <div class="form-group">
              <label class="form-label">⚡ Status</label>
              <select name="status" class="form-control" required>
                <option value="aktif">✅ Aktif</option>
                <option value="nonaktif">❌ Nonaktif</option>
              </select>
            </div>
          </div>
          <div class="form-group" style="margin-bottom:20px">
            <label class="form-label">📝 Deskripsi</label>
            <textarea name="deskripsi" class="form-control" rows="3"
                      required placeholder="Deskripsi seru paket trip ini..."></textarea>
          </div>
          <button type="submit" name="tambah_paket" class="btn-submit" id="submitBtn">
            <span class="btn-icon">🚀</span> Simpan Paket
          </button>
        </form>
      </div>
    </div>

    <div class="sg-card">
      <div class="card-header">
        <div class="card-icon cyan">🏆</div>
        <div>
          <div class="card-title">Daftar Paket Trip</div>
          <div class="card-sub"><?= count($paket_list) ?> paket ditemukan</div>
        </div>
      </div>
      <div class="card-body" style="padding-top:14px">
        <div class="table-wrap">
          <?php if (!empty($paket_list)): ?>
          <table class="table-data">
            <thead>
              <tr>
                <th>ID</th>
                <th>Nama Paket</th>
                <th>Via</th>
                <th>Durasi</th>
                <th>Status</th>
                <th>Aksi</th> 
              </tr>
            </thead>
            <tbody>
              <?php foreach ($paket_list as $i => $row): ?>
              <tr style="animation-delay:<?= $i * 0.07 ?>s">
                <td><span class="id-badge">#<?= $row['id'] ?></span></td>
                <td><strong><?= htmlspecialchars($row['nama_paket']) ?></strong></td>
                <td>
                  <?php if ($row['via'] === 'muara_angke'): ?>
                    <span class="via-badge via-angke">🚢 Muara Angke</span>
                  <?php else: ?>
                    <span class="via-badge via-ancol">⛵ Marina Ancol</span>
                  <?php endif; ?>
                </td>
                <td><span class="durasi-pill">⏱️ <?= htmlspecialchars($row['durasi']) ?></span></td>
                <td>
                  <span class="status-badge status-<?= $row['status'] ?>">
                    <?= $row['status'] === 'aktif' ? '✅' : '❌' ?>
                    <?= ucfirst($row['status']) ?>
                  </span>
                </td>
                <td>
                  <div style="display: flex; gap: 6px;">
                    <form method="POST" action="" style="margin:0;">
                      <input type="hidden" name="id_paket" value="<?= $row['id'] ?>">
                      <?php if ($row['status'] === 'aktif'): ?>
                          <input type="hidden" name="status_baru" value="nonaktif">
                          <button type="submit" name="ubah_status" class="btn-action btn-status-off" onclick="return confirm('⚠️ Yakin ingin menonaktifkan paket ini?');">
                            ⏸️ Nonaktifkan
                          </button>
                      <?php else: ?>
                          <input type="hidden" name="status_baru" value="aktif">
                          <button type="submit" name="ubah_status" class="btn-action btn-status-on" onclick="return confirm('✨ Yakin ingin mengaktifkan paket ini?');">
                            ▶️ Aktifkan
                          </button>
                      <?php endif; ?>
                    </form>

                    <form method="POST" action="" style="margin:0;">
                      <input type="hidden" name="id_paket" value="<?= $row['id'] ?>">
                      <button type="submit" name="hapus_paket" class="btn-action btn-delete" onclick="return confirm('⚠️ Yakin ingin menghapus paket ini? Level ini akan hilang selamanya!');">
                        🗑️ Hapus
                      </button>
                    </form>
                  </div>
                </td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
          <?php else: ?>
          <div class="empty-state">
            <div class="empty-icon">🏝️</div>
            <div class="empty-text">Belum ada paket trip. Tambahkan paket pertamamu!</div>
          </div>
          <?php endif; ?>
        </div>
      </div>
    </div>

  </div>
</main>

</div><div class="runners" id="runners"></div>

<script>
/* === CONFETTI === */
(function(){
  const wrap = document.getElementById('confettiWrap');
  const cols = ['#ff4fa3','#ffd600','#00e5ff','#00e676','#ff6d00','#9b59b6','#ffffff'];
  for (let i = 0; i < 30; i++) {
    const d = document.createElement('div');
    d.classList.add('cdot');
    const s = Math.random() * 7 + 2;
    d.style.cssText = `
      width:${s}px; height:${s}px;
      background:${cols[i % cols.length]};
      left:${Math.random() * 100}%;
      animation-duration:${Math.random() * 12 + 7}s;
      animation-delay:${Math.random() * 12}s;
      opacity:.65;
    `;
    wrap.appendChild(d);
  }
})();

/* === RUNNING CHARACTERS === */
(function(){
  const strip = document.getElementById('runners');
  const chars = ['🏃','👦','🧒','🕺','🧑','🏃‍♀️'];
  for (let i = 0; i < 5; i++) {
    const c = document.createElement('div');
    c.classList.add('runner');
    c.textContent = chars[i % chars.length];
    const dur = Math.random() * 16 + 12;
    c.style.cssText = `animation-duration:${dur}s;animation-delay:${Math.random() * 14}s;bottom:${Math.random() * 6}px`;
    strip.appendChild(c);
  }
})();

/* === SUBMIT BUTTON BOUNCE === */
document.getElementById('submitBtn')?.addEventListener('click', function(e) {
  if(document.getElementById('paketForm').checkValidity()) {
    this.style.animation = 'none';
    this.textContent = '⏳ Menyimpan...';
    setTimeout(() => {
        this.innerHTML = '<span class="btn-icon">🚀</span> Simpan Paket';
        this.style.animation = '';
    }, 2000);
  }
});

/* === FORM INPUT FOCUS EFFECT === */
document.querySelectorAll('.form-control').forEach(el => {
  el.addEventListener('focus', () => {
    el.parentElement.querySelector('.form-label') &&
      (el.parentElement.querySelector('.form-label').style.color = '#00e5ff');
  });
  el.addEventListener('blur', () => {
    el.parentElement.querySelector('.form-label') &&
      (el.parentElement.querySelector('.form-label').style.color = '');
  });
});

/* === TABLE ROW CLICK RIPPLE === */
document.querySelectorAll('.table-data tbody tr').forEach(row => {
  row.style.cursor = 'default'; 
});
</script>
</body>
</html>