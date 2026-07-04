<?php
require_once __DIR__ . '/../includes/auth.php';
requireAdminLogin();

$admin = getCurrentAdmin();
$db = getDB();
$pesan = '';

// Proses Hapus Pengguna
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['hapus_pengguna'])) {
    $id_user = $_POST['id_user'];
    try {
        $stmt = $db->prepare("DELETE FROM users WHERE id = ?");
        $stmt->execute([$id_user]);
        $pesan = 'success|🗑️ Pemain berhasil di-kick dari game selamanya!';
    } catch (PDOException $e) {
        $pesan = 'error|❌ Gagal menghapus pemain: ' . $e->getMessage();
    }
}

// Proses Ubah Status Pengguna (Aktif / Nonaktif)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ubah_status'])) {
    $id_user = $_POST['id_user'];
    $status_baru = $_POST['status_baru'];
    try {
        $stmt = $db->prepare("UPDATE users SET status = ? WHERE id = ?");
        $stmt->execute([$status_baru, $id_user]);
        $pesan = 'success|🔄 Status pemain berhasil di-update!';
    } catch (PDOException $e) {
        $pesan = 'error|❌ Gagal mengubah status: ' . $e->getMessage();
    }
}

// AMBIL DATA: Daftar User yang mendaftar
$users = $db->query("SELECT * FROM users ORDER BY created_at DESC")->fetchAll();

$total = count($users);
$aktif = count(array_filter($users, fn($u) => $u['status'] == 'aktif'));
$nonaktif = $total - $aktif;

$pesan_parts = $pesan ? explode('|', $pesan, 2) : [];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Data Pengguna - Pari Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Fredoka+One&family=Nunito:wght@400;600;700;800;900&display=swap" rel="stylesheet">
    <style>
        /* ================================================
           STUMBLE GUYS THEME — MATCHED WITH PAKET TRIP
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

        .player-badge {
            background: linear-gradient(135deg, #ff4757, #c0392b);
            color: #fff; font-size: 12px; font-weight: 800;
            padding: 5px 14px; border-radius: 20px;
            border: 2px solid #ff6b7a;
            animation: badgePop 1.5s ease-in-out infinite;
            box-shadow: 0 2px 10px rgba(255,71,87,.4);
        }
        @keyframes badgePop {
            0%, 100% { transform: scale(1); }
            50%       { transform: scale(1.06); }
        }

        /* ============ DASHBOARD BODY ============ */
        .dashboard-body { padding: 28px 32px; flex: 1; }

        /* ============ FLASH MESSAGE ============ */
        .flash-msg {
            border-radius: 14px; padding: 14px 20px;
            margin-bottom: 24px;
            display: flex; align-items: center; gap: 10px;
            font-weight: 700; font-size: 14px;
            animation: flashSlide .5s cubic-bezier(.34,1.56,.64,1) both;
        }
        @keyframes flashSlide { from{opacity:0;transform:translateY(-14px)} to{opacity:1;transform:none} }
        .flash-success {
            background: rgba(16,185,129,.15);
            border: 1.5px solid rgba(52,211,153,.5); color: #34d399;
        }
        .flash-error {
            background: rgba(239,68,68,.15);
            border: 1.5px solid rgba(248,113,113,.5); color: #f87171;
        }

        /* ============ STAT CARDS ============ */
        .stats-row {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 14px;
            margin-bottom: 24px;
        }
        .stat-card {
            background: var(--card-bg);
            border: 1.5px solid var(--card-bd);
            border-radius: 18px;
            padding: 16px;
            display: flex;
            align-items: center;
            gap: 14px;
            animation: cardFloat 4s ease-in-out infinite;
            transition: transform .2s, box-shadow .2s;
            cursor: default;
        }
        .stat-card:nth-child(2) { animation-delay: .8s; }
        .stat-card:nth-child(3) { animation-delay: 1.6s; }
        @keyframes cardFloat {
            0%, 100% { transform: translateY(0); }
            50%       { transform: translateY(-6px); }
        }
        .stat-card:hover {
            transform: translateY(-10px) scale(1.02);
            box-shadow: 0 14px 30px rgba(155,89,182,.15);
            border-color: var(--cyan);
        }

        .stat-icon {
            width: 50px; height: 50px;
            border-radius: 14px;
            display: flex; align-items: center; justify-content: center;
            font-size: 24px;
            flex-shrink: 0;
        }
        .stat-icon.gold   { background: linear-gradient(135deg, #f5c842, #ff8c00); }
        .stat-icon.purple { background: linear-gradient(135deg, #a855f7, #7c3aed); }
        .stat-icon.pink   { background: linear-gradient(135deg, #ec4899, #be185d); }

        .stat-num {
            font-family: 'Fredoka One', cursive;
            font-size: 28px;
            color: #fff;
            line-height: 1;
        }
        .stat-label {
            font-size: 11px;
            font-weight: 700;
            color: var(--dim);
            text-transform: uppercase;
            letter-spacing: .8px;
            margin-top: 3px;
        }

        /* ============ TABLE CARD ============ */
        .table-card {
            background: var(--card-bg);
            border: 1.5px solid var(--card-bd);
            border-radius: 22px;
            overflow: hidden;
            box-shadow: 0 8px 40px rgba(0,0,0,.15);
            backdrop-filter: blur(10px);
            margin-bottom: 20px;
        }

        .sg-table { width: 100%; border-collapse: collapse; min-width: 600px; }

        .sg-table thead th {
            background: rgba(155,89,182,.18);
            color: var(--cyan);
            font-family: 'Fredoka One', cursive;
            font-size: 13px;
            font-weight: 400;
            padding: 14px 16px;
            text-align: left;
            letter-spacing: .5px;
            border-bottom: 2px solid rgba(155,89,182,.3);
        }

        .sg-table tbody tr {
            border-bottom: 1px solid rgba(255,255,255,.05);
            transition: all .25s;
            animation: rowFadeIn .5s ease backwards;
            cursor: default; 
        }
        .sg-table tbody tr:hover td { background: rgba(155,89,182,.1); color: #fff;}

        .sg-table tbody td {
            padding: 13px 16px;
            font-size: 13px;
            color: var(--dim);
            font-weight: 600;
            vertical-align: middle;
            transition: background .2s;
        }

        .rank-cell {
            font-family: 'Fredoka One', cursive;
            font-size: 16px;
            color: var(--yellow);
            text-align: center;
            width: 50px;
        }

        .crown {
            display: inline-block;
            font-size: 20px;
            animation: crownBounce 1.2s ease-in-out infinite;
        }
        @keyframes crownBounce {
            0%, 100% { transform: translateY(0) rotate(-5deg); }
            50%       { transform: translateY(-5px) rotate(5deg); }
        }

        .player-cell { display: flex; align-items: center; gap: 10px; }

        .player-avatar {
            width: 38px; height: 38px;
            border-radius: 50%;
            border: 2px solid var(--yellow);
            display: flex; align-items: center; justify-content: center;
            font-family: 'Fredoka One', cursive;
            font-size: 16px;
            font-weight: 800;
            color: #fff;
            flex-shrink: 0;
            animation: avatarWobble 3s ease-in-out infinite;
        }
        @keyframes avatarWobble {
            0%, 100% { transform: rotate(0deg); }
            25%       { transform: rotate(-6deg); }
            75%       { transform: rotate(6deg); }
        }

        .player-name { font-weight: 800; color: #fff; font-size: 14px; }

        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 800;
            letter-spacing: .5px;
        }
        .status-badge.aktif {
            background: rgba(16,185,129,.2);
            color: #34d399;
            border: 1.5px solid rgba(52,211,153,.4);
        }
        .status-badge.nonaktif {
            background: rgba(239,68,68,.2);
            color: #f87171;
            border: 1.5px solid rgba(248,113,113,.4);
        }
        .sdot { width: 7px; height: 7px; border-radius: 50%; }
        .status-badge.aktif .sdot {
            background: #34d399;
            animation: dotBlink 1.5s ease-in-out infinite;
        }
        .status-badge.nonaktif .sdot { background: #f87171; }
        @keyframes dotBlink {
            0%, 100% { opacity: 1; transform: scale(1); }
            50%       { opacity: .4; transform: scale(.7); }
        }

        /* ── BUTTONS ACTION ── */
        .action-flex { display: flex; gap: 6px; }
        .btn-action {
            display: inline-flex; align-items: center; gap: 4px;
            color: #fff; font-family: 'Fredoka One', cursive;
            font-size: 11px; padding: 6px 12px;
            border-radius: 20px; border: none; cursor: pointer;
            transition: all .25s; letter-spacing: .5px;
        }
        .btn-action:hover { transform: scale(1.05); }

        .btn-delete {
            background: linear-gradient(135deg, #ef4444, #b91c1c);
            box-shadow: 0 3px 10px rgba(239,68,68,.4);
        }
        .btn-delete:hover { box-shadow: 0 4px 15px rgba(239,68,68,.6); }

        .btn-status-off {
            background: linear-gradient(135deg, #f59e0b, #d97706);
            box-shadow: 0 3px 10px rgba(245,158,11,.4);
        }
        .btn-status-off:hover { box-shadow: 0 4px 15px rgba(245,158,11,.6); }

        .btn-status-on {
            background: linear-gradient(135deg, #10b981, #059669);
            box-shadow: 0 3px 10px rgba(16,185,129,.4);
        }
        .btn-status-on:hover { box-shadow: 0 4px 15px rgba(16,185,129,.6); }

        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: var(--dim);
            font-size: 16px;
            font-weight: 700;
        }
        .empty-icon { font-size: 48px; display: block; margin-bottom: 12px; animation: iconBounce 3s ease-in-out infinite; }

        .bottom-bar {
            text-align: center;
            padding: 20px;
            font-family: 'Fredoka One', cursive;
            font-size: 14px;
            color: var(--dim);
            letter-spacing: 1px;
        }

        @media (max-width: 900px) {
            .sidebar { transform: translateX(-100%); }
            .main-content { margin-left: 0; }
            .stats-row { grid-template-columns: 1fr; }
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
    <li><a href="paket_trip.php"><span>🏝️</span> Paket Trip</a></li>
    <li><a href="data_pemesanan.php"><span>📋</span> Data Pemesanan</a></li>
    <li><a href="data_pengguna.php" class="active"><span>👥</span> Data Pengguna</a></li>
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
      <span class="topbar-icon">👥</span>
      Data Pengguna
    </div>
    <div class="topbar-actions">
      <div class="player-badge">🎮 <?= $total ?> PLAYERS</div>
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

        <div class="stats-row">
            <div class="stat-card">
                <div class="stat-icon gold">👑</div>
                <div>
                    <div class="stat-num"><?= $total ?></div>
                    <div class="stat-label">Total Players</div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon purple">⚡</div>
                <div>
                    <div class="stat-num"><?= $aktif ?></div>
                    <div class="stat-label">Aktif</div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon pink">💀</div>
                <div>
                    <div class="stat-num"><?= $nonaktif ?></div>
                    <div class="stat-label">Nonaktif</div>
                </div>
            </div>
        </div>

        <div class="table-card">
            <div class="table-wrap">
                <table class="sg-table">
                    <thead>
                        <tr>
                            <th style="text-align:center;">#</th>
                            <th>Customer</th>
                            <th>Email</th>
                            <th>Telepon</th>
                            <th>Tanggal Daftar</th>
                            <th>Status</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($users)): ?>
                            <?php foreach ($users as $i => $row):
                                $colors = ['#7c3aed','#0ea5e9','#f59e0b','#10b981','#ec4899','#ef4444','#14b8a6'];
                                $bg = $colors[$i % count($colors)];
                                $initial = strtoupper(mb_substr($row['nama'], 0, 1));
                                $delay = ($i * 0.07) . 's';
                            ?>
                            <tr style="animation-delay:<?= $delay ?>;">
                                <td class="rank-cell">
                                    #<?= $i + 1 ?>
                                </td>
                                <td>
                                    <div class="player-cell">
                                        <div class="player-avatar" style="background:<?= $bg ?>;animation-delay:<?= ($i * 0.3) ?>s;">
                                            <?= htmlspecialchars($initial) ?>
                                        </div>
                                        <span class="player-name"><?= htmlspecialchars($row['nama']) ?></span>
                                    </div>
                                </td>
                                <td><?= htmlspecialchars($row['email']) ?></td>
                                <td><?= htmlspecialchars($row['no_telepon'] ?: '-') ?></td>
                                <td><?= date('d M Y', strtotime($row['created_at'])) ?></td>
                                <td>
                                    <span class="status-badge <?= $row['status'] == 'aktif' ? 'aktif' : 'nonaktif' ?>">
                                        <span class="sdot"></span>
                                        <?= ucfirst(htmlspecialchars($row['status'])) ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="action-flex">
                                        <form method="POST" action="" style="margin:0;">
                                            <input type="hidden" name="id_user" value="<?= $row['id'] ?>">
                                            <?php if ($row['status'] === 'aktif'): ?>
                                                <input type="hidden" name="status_baru" value="nonaktif">
                                                <button type="submit" name="ubah_status" class="btn-action btn-status-off" onclick="return confirm('⚠️ Yakin ingin membekukan pemain ini?');">
                                                    ⏸️ Nonaktifkan
                                                </button>
                                            <?php else: ?>
                                                <input type="hidden" name="status_baru" value="aktif">
                                                <button type="submit" name="ubah_status" class="btn-action btn-status-on" onclick="return confirm('✨ Yakin ingin mengaktifkan kembali pemain ini?');">
                                                    ▶️ Aktifkan
                                                </button>
                                            <?php endif; ?>
                                        </form>

                                        <form method="POST" action="" style="margin:0;">
                                            <input type="hidden" name="id_user" value="<?= $row['id'] ?>">
                                            <button type="submit" name="hapus_pengguna" class="btn-action btn-delete" onclick="return confirm('⚠️ Yakin ingin meng-kick pemain ini selamanya? Data akan dihapus!');">
                                                🗑️ Hapus
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7">
                                    <div class="empty-state">
                                        <span class="empty-icon">👻</span>
                                        Belum ada pemain yang bergabung!
                                    </div>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="bottom-bar">⭐ PARI ADMIN ⭐</div>
    </div>
</main>

</div><script>
/* === CONFETTI === */
(function(){
  const wrap = document.getElementById('confettiWrap');
  if(!wrap) return;
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
</script>
</body>
</html>