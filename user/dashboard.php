<?php
require_once '../includes/auth.php';
requireUserLogin();

$db     = getDB();
$userId = (int)$_SESSION['user_id'];
$user   = getCurrentUser();

/* ── STATISTIK USER ─────────────────────────────── */
$stmtStats = $db->prepare("
    SELECT
        COUNT(*)                                                        AS total_booking,
        SUM(status = 'completed')                                       AS total_selesai,
        SUM(status = 'pending')                                         AS total_pending,
        SUM(status = 'confirmed')                                       AS total_confirmed,
        COALESCE(SUM(CASE WHEN status IN ('paid','completed') THEN total_harga END), 0) AS total_spend
    FROM pemesanan
    WHERE user_id = ?
");
$stmtStats->execute([$userId]);
$stats = $stmtStats->fetch();

/* ── 5 PEMESANAN TERBARU ──────────────────────────── */
$stmtBookings = $db->prepare("
    SELECT p.id, p.kode_booking, p.tanggal_trip, p.jumlah_peserta,
           p.total_harga, p.harga_per_orang, p.status,
           p.via_keberangkatan, p.created_at,
           pt.nama_paket, pt.durasi
    FROM pemesanan p
    JOIN paket_trip pt ON pt.id = p.paket_id
    WHERE p.user_id = ?
    ORDER BY p.created_at DESC
    LIMIT 5
");
$stmtBookings->execute([$userId]);
$bookings = $stmtBookings->fetchAll();

/* ── TRIP MENDATANG ───────────────────────────────── */
$stmtUpcoming = $db->prepare("
    SELECT p.id, p.kode_booking, p.tanggal_trip, p.jumlah_peserta,
           p.total_harga, p.status, p.via_keberangkatan,
           pt.nama_paket,
           DATEDIFF(p.tanggal_trip, CURDATE()) AS hari_lagi
    FROM pemesanan p
    JOIN paket_trip pt ON pt.id = p.paket_id
    WHERE p.user_id = ?
      AND p.tanggal_trip >= CURDATE()
      AND p.status NOT IN ('cancelled')
    ORDER BY p.tanggal_trip ASC
    LIMIT 3
");
$stmtUpcoming->execute([$userId]);
$upcoming = $stmtUpcoming->fetchAll();

/* ── STATISTIK ULASAN ────────────────────────────── */
$stmtUlasan = $db->prepare("
    SELECT COUNT(*) AS total, ROUND(AVG(rating), 1) AS avg_rating
    FROM testimoni WHERE user_id = ?
");
$stmtUlasan->execute([$userId]);
$ulasanStat = $stmtUlasan->fetch();

/* ── PEMBAYARAN PENDING ──────────────────────────── */
$stmtPayPending = $db->prepare("
    SELECT COUNT(*) FROM pembayaran pb
    JOIN pemesanan p ON p.id = pb.pemesanan_id
    WHERE p.user_id = ? AND pb.status = 'pending'
");
$stmtPayPending->execute([$userId]);
$payPending = (int)$stmtPayPending->fetchColumn();

/* ── HELPERS ─────────────────────────────────────── */
$statusLabel = [
    'pending'   => ['label' => 'Menunggu',      'class' => 'badge-pending',   'icon' => '⏳'],
    'confirmed' => ['label' => 'Dikonfirmasi',  'class' => 'badge-confirmed', 'icon' => '✅'],
    'paid'      => ['label' => 'Lunas',         'class' => 'badge-paid',      'icon' => '💰'],
    'cancelled' => ['label' => 'Dibatalkan',    'class' => 'badge-cancelled', 'icon' => '❌'],
    'completed' => ['label' => 'Selesai',       'class' => 'badge-completed', 'icon' => '🏅'],
];
$viaLabel = [
    'muara_angke'  => 'Muara Angke',
    'marina_ancol' => 'Marina Ancol',
];

$flash = getFlash();
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Dashboard – Pari Adventure</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Fredoka+One&family=Nunito:wght@600;800;900&display=swap">
<link rel="stylesheet" href="../css/style.css">
<style>
/* ============================================================
   TEMA STUMBLE GUYS - UI/UX DASHBOARD USER
   ============================================================ */

:root {
  --sg-black: #1a1a1a;
  --sg-white: #ffffff;
  --sg-blue:  #00cdff;
  --sg-orange:#ff8c00;
  --sg-pink:  #ff2a7a;
  --sg-green: #00e676;
  --sg-yellow:#ffcc00;
  --sg-purple:#7c3aed;
  --sg-bg:    #f0f8ff;
}

* { box-sizing: border-box; }

body {
  font-family: 'Nunito', sans-serif;
  background-color: var(--sg-bg);
  margin: 0; padding: 0;
  color: var(--sg-black);
}

.dashboard-layout { 
  display: flex; 
  min-height: 100vh; 
  width: 100%;
}

.dashboard-main {
  flex: 1;
  min-width: 0;
  padding: 30px 40px;
  background-image: radial-gradient(circle at 10% 20%, rgba(0, 205, 255, 0.1) 0%, transparent 20%),
                    radial-gradient(circle at 90% 80%, rgba(255, 140, 0, 0.1) 0%, transparent 20%);
  overflow-x: hidden;
}

@media (min-width: 769px) {
  .dashboard-main { margin-left: 260px; }
}

.welcome-card {
  background: var(--sg-blue);
  border: 5px solid var(--sg-black);
  border-radius: 30px; 
  padding: 35px 40px;
  display: flex; justify-content: space-between; align-items: center;
  margin-bottom: 30px; position: relative; overflow: hidden;
  box-shadow: 0 10px 0 var(--sg-black);
  animation: popIn 0.6s cubic-bezier(0.175, 0.885, 0.32, 1.275) both;
}
.welcome-card::after {
  content: '🏝️'; position: absolute; right: 20px; top: 0px;
  font-size: 120px; opacity: .3; pointer-events: none;
  animation: wobble 4s ease-in-out infinite;
}
@keyframes wobble { 0%,100%{transform:rotate(-5deg);} 50%{transform:rotate(5deg);} }

.welcome-title {
  font-family: 'Fredoka One', cursive;
  font-size: 32px; color: var(--sg-white); margin-bottom: 8px;
  text-shadow: 2px 2px 0 var(--sg-black), -1px -1px 0 var(--sg-black), 1px -1px 0 var(--sg-black), -1px 1px 0 var(--sg-black), 1px 1px 0 var(--sg-black);
}
.welcome-sub { font-size: 16px; font-weight: 800; color: var(--sg-black); max-width: 550px; line-height: 1.5; position: relative; z-index: 2;}

.welcome-cta {
  background: var(--sg-yellow); color: var(--sg-black);
  padding: 15px 30px; border-radius: 50px;
  font-family: 'Fredoka One', cursive; font-size: 16px; 
  text-decoration: none; white-space: nowrap; flex-shrink: 0;
  border: 4px solid var(--sg-black);
  box-shadow: 0 6px 0 var(--sg-black);
  transition: all .2s; position: relative; z-index: 2;
}
.welcome-cta:hover { transform: translateY(-4px); box-shadow: 0 10px 0 var(--sg-black); background: #ffdf33; }
.welcome-cta:active { transform: translateY(4px); box-shadow: 0 2px 0 var(--sg-black); }

.kpi-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px; margin-bottom: 30px; }
.kpi-card {
  background: var(--sg-white); border-radius: 24px; padding: 20px;
  border: 4px solid var(--sg-black);
  box-shadow: 0 8px 0 rgba(0,0,0,0.2);
  display: flex; align-items: center; gap: 15px;
  transition: all .2s;
  animation: popIn 0.5s cubic-bezier(0.175, 0.885, 0.32, 1.275) both;
}
.kpi-card:nth-child(1) { animation-delay: 0.1s; }
.kpi-card:nth-child(2) { animation-delay: 0.2s; }
.kpi-card:nth-child(3) { animation-delay: 0.3s; }
.kpi-card:nth-child(4) { animation-delay: 0.4s; }

.kpi-card:hover { transform: translateY(-6px); box-shadow: 0 14px 0 rgba(0,0,0,0.2); border-color: var(--sg-purple); }
.kpi-icon {
  width: 56px; height: 56px; border-radius: 18px; border: 3px solid var(--sg-black);
  display: flex; align-items: center; justify-content: center;
  font-size: 28px; flex-shrink: 0; box-shadow: 0 4px 0 var(--sg-black);
}
.kpi-icon.blue   { background: var(--sg-blue); }
.kpi-icon.green  { background: var(--sg-green); }
.kpi-icon.orange { background: var(--sg-orange); }
.kpi-icon.teal   { background: var(--sg-yellow); }

.kpi-value {
  font-family: 'Fredoka One', cursive;
  font-size: 24px; color: var(--sg-black); line-height: 1.1;
}
.kpi-label { font-size: 12px; color: #666; font-weight: 800; text-transform: uppercase; letter-spacing: 0.5px; }

@keyframes popIn {
  0%   { opacity: 0; transform: scale(0.8) translateY(20px); }
  100% { opacity: 1; transform: scale(1) translateY(0); }
}

.qa-grid {
  display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px;
  margin-bottom: 30px;
}
.qa-card {
  background: var(--sg-white); border: 4px solid var(--sg-black);
  border-radius: 24px; padding: 24px; display: flex; gap: 16px;
  text-decoration: none; transition: all .2s; align-items: center;
  box-shadow: 0 6px 0 rgba(0,0,0,0.15);
  animation: popIn 0.5s cubic-bezier(0.175, 0.885, 0.32, 1.275) both;
}
.qa-card:nth-child(1) { animation-delay: 0.1s; }
.qa-card:nth-child(2) { animation-delay: 0.2s; }
.qa-card:nth-child(3) { animation-delay: 0.3s; }

.qa-card:hover {
  transform: translateY(-8px);
  box-shadow: 0 12px 0 rgba(0,0,0,0.15);
}
.qa-icon { font-size: 48px; flex-shrink: 0; }
.qa-title { font-family: 'Fredoka One', cursive; font-size: 16px; color: var(--sg-black); margin-bottom: 6px; }
.qa-sub { font-size: 13px; color: #666; font-weight: 600; }

.dash-card {
  background: var(--sg-white); border-radius: 20px; padding: 24px;
  border: 4px solid var(--sg-black);
  margin-bottom: 24px;
  box-shadow: 0 6px 0 rgba(0,0,0,0.1);
}

.dash-card-header {
  display: flex; justify-content: space-between; align-items: center;
  margin-bottom: 20px; gap: 16px;
}
.dash-card-title { font-family: 'Fredoka One', cursive; font-size: 18px; color: var(--sg-black); }
.dash-card-sub { font-size: 13px; color: #888; margin-top: 4px; font-weight: 600; }
.dash-card-link {
  background: var(--sg-yellow); color: var(--sg-black);
  padding: 10px 18px; border-radius: 50px; font-size: 13px;
  text-decoration: none; font-weight: 800;
  border: 3px solid var(--sg-black);
  white-space: nowrap; transition: all .2s;
  box-shadow: 0 4px 0 rgba(0,0,0,0.1);
}
.dash-card-link:hover {
  transform: translateY(-2px);
  box-shadow: 0 6px 0 rgba(0,0,0,0.1);
}

.upcoming-list { display: flex; flex-direction: column; gap: 12px; }
.trip-item {
  background: #f9fafb; border-radius: 12px; padding: 16px;
  border-left: 4px solid var(--sg-blue);
  display: flex; align-items: center; gap: 16px;
  transition: all .2s;
}
.trip-item:hover { background: #f0f8ff; }
.trip-count {
  width: 60px; text-align: center; flex-shrink: 0;
  background: var(--sg-yellow); border-radius: 12px; padding: 8px;
  border: 2px solid var(--sg-black);
}
.days { font-family: 'Fredoka One', cursive; font-size: 20px; color: var(--sg-black); line-height: 1; }
.hlbl { font-size: 11px; color: var(--sg-black); font-weight: 800; margin-top: 4px; }
.trip-name { font-weight: 900; color: var(--sg-black); font-size: 14px; }
.trip-meta { font-size: 12px; color: #888; margin-top: 4px; }
.trip-meta span { margin-right: 12px; }

.detail-btn {
  background: var(--sg-blue); color: var(--sg-white);
  padding: 10px 16px; border-radius: 12px; font-size: 13px;
  text-decoration: none; font-weight: 800;
  border: 2px solid var(--sg-black);
  transition: all .2s;
  box-shadow: 0 3px 0 rgba(0,0,0,0.1);
}
.detail-btn:hover {
  transform: translateY(-2px);
  box-shadow: 0 5px 0 rgba(0,0,0,0.1);
  background: #00b8dd;
}

.dash-table {
  width: 100%; border-collapse: collapse;
}
.dash-table th {
  background: #f9fafb; border-bottom: 2px solid #e5e7eb;
  padding: 12px; font-weight: 800; font-size: 12px;
  color: #6b7280; text-transform: uppercase;
  text-align: left;
}
.dash-table td {
  border-bottom: 1px solid #e5e7eb;
  padding: 16px 12px; font-size: 14px;
}
.dash-table tbody tr:hover { background: #fafbfc; }
.dash-table tbody tr:last-child td { border: none; }

.badge-status {
  display: inline-block; padding: 6px 12px; border-radius: 50px;
  font-size: 12px; font-weight: 800;
  border: 2px solid var(--sg-black);
}
.badge-pending { background: #fef3c7; color: var(--sg-black); }
.badge-confirmed { background: #d1fae5; color: var(--sg-black); }
.badge-paid { background: #c7d2fe; color: var(--sg-black); }
.badge-cancelled { background: #fed7d7; color: var(--sg-black); }
.badge-completed { background: #86efac; color: var(--sg-black); }

.empty-box {
  text-align: center; padding: 40px 20px; color: #9ca3af;
}
.empty-icon { font-size: 64px; margin-bottom: 16px; }
.empty-title { font-family: 'Fredoka One', cursive; font-size: 18px; color: var(--sg-black); margin-bottom: 8px; }
.empty-text { font-size: 14px; line-height: 1.6; }

.alert {
  padding: 16px; border-radius: 12px; margin-bottom: 20px;
  border: 3px solid var(--sg-black);
  font-weight: 700;
}
.alert-success { background: #d1fae5; color: #065f46; }
.alert-danger { background: #fed7d7; color: #991b1b; }

@media (max-width: 1200px) {
  .kpi-grid { grid-template-columns: repeat(2, 1fr); }
  .qa-grid { grid-template-columns: 1fr; }
}

@media (max-width: 768px) {
  .dashboard-main { padding: 20px; }
  .welcome-card { flex-direction: column; text-align: center; }
  .kpi-grid { grid-template-columns: 1fr; }
  .qa-grid { grid-template-columns: 1fr; }
  .page-wrapper { grid-template-columns: 1fr; }
}
</style>
</head>
<body>
<div class="dashboard-layout">
  <?php include 'partials/sidebar.php'; ?>

  <main class="dashboard-main">

    <?php if ($flash): ?>
    <div class="alert alert-<?= $flash['type'] ?>"><?= $flash['message'] ?></div>
    <?php endif; ?>

    <div class="welcome-card">
      <div>
        <div class="welcome-title">Halo, <?= htmlspecialchars(explode(' ', $user['nama'])[0]) ?>! 👋</div>
        <div class="welcome-sub">Selamat datang! Belum punya trip? Yuk rencanakan liburanmu ke Pulau Pari sekarang 🏝️</div>
      </div>
      <a href="booking.php" class="welcome-cta">🏝️ Pesan Trip Sekarang</a>
    </div>

    <div class="kpi-grid">
      <div class="kpi-card">
        <div class="kpi-icon blue">📦</div>
        <div>
          <div class="kpi-value"><?= (int)$stats['total_booking'] ?></div>
          <div class="kpi-label">Total Booking</div>
        </div>
      </div>

      <div class="kpi-card">
        <div class="kpi-icon green">✅</div>
        <div>
          <div class="kpi-value"><?= (int)$stats['total_selesai'] ?></div>
          <div class="kpi-label">Trip Selesai</div>
        </div>
      </div>

      <div class="kpi-card">
        <div class="kpi-icon orange">💰</div>
        <div>
          <div class="kpi-value" style="font-size:18px;"><?= formatRupiah((float)$stats['total_spend']) ?></div>
          <div class="kpi-label">Total Pengeluaran</div>
        </div>
      </div>

      <div class="kpi-card">
        <div class="kpi-icon teal">⭐</div>
        <div>
          <div class="kpi-value">
            <?= (int)$ulasanStat['total'] ?>
            <?php if ($ulasanStat['avg_rating']): ?>
              <span style="font-size:14px;color:#ff8c00; text-shadow: 1px 1px 0 #000;">
                 <?= $ulasanStat['avg_rating'] ?>★
              </span>
            <?php endif; ?>
          </div>
          <div class="kpi-label">Ulasan Diberikan</div>
        </div>
      </div>

    </div>

    <div class="qa-grid">
      <a href="booking.php" class="qa-card" style="background:var(--sg-orange);">
        <div class="qa-icon">🏝️</div>
        <div>
          <div class="qa-title">Pesan Trip Baru</div>
          <div class="qa-sub">Pilih paket &amp; tanggal</div>
        </div>
      </a>

      <a href="pembayaran.php" class="qa-card" style="background:var(--sg-blue);">
        <div class="qa-icon">💳</div>
        <div>
          <div class="qa-title">Upload Pembayaran</div>
          <div class="qa-sub">
            <?= $payPending > 0 ? "$payPending sedang diverifikasi" : 'Kirim bukti transfer' ?>
          </div>
        </div>
      </a>

      <a href="testimoni.php" class="qa-card" style="background:var(--sg-green);">
        <div class="qa-icon">⭐</div>
        <div>
          <div class="qa-title">Tulis Ulasan</div>
          <div class="qa-sub">Bagikan pengalamanmu</div>
        </div>
      </a>
    </div>

    <?php if (!empty($upcoming)): ?>
    <div class="dash-card">
      <div class="dash-card-header">
        <div>
          <div class="dash-card-title">🗓️ Trip Mendatang</div>
          <div class="dash-card-sub">Bersiaplah untuk petualanganmu!</div>
        </div>
        <a href="riwayat.php" class="dash-card-link">Lihat semua →</a>
      </div>
      <div class="upcoming-list">
        <?php foreach ($upcoming as $up):
          $s       = $statusLabel[$up['status']] ?? ['label' => $up['status'], 'class' => 'badge-pending', 'icon' => '•'];
          $hariLagi = (int)$up['hari_lagi'];
        ?>
        <div class="trip-item">
          <div class="trip-count">
            <?php if ($hariLagi === 0): ?>
              <div class="days" style="font-size:24px;">🎉</div>
              <div class="hlbl">Hari ini!</div>
            <?php else: ?>
              <div class="days"><?= $hariLagi ?></div>
              <div class="hlbl">Hari lagi</div>
            <?php endif; ?>
          </div>

          <div style="flex:1;min-width:0;">
            <div class="trip-name"><?= htmlspecialchars($up['nama_paket']) ?></div>
            <div class="trip-meta">
              <span>📅 <?= date('d M Y', strtotime($up['tanggal_trip'])) ?></span>
              <span>👥 <?= $up['jumlah_peserta'] ?> orang</span>
              <span>🚢 <?= $viaLabel[$up['via_keberangkatan']] ?? '-' ?></span>
            </div>
          </div>

          <div style="display:flex;flex-direction:column;align-items:flex-end;gap:10px;flex-shrink:0;">
            <span class="badge-status <?= $s['class'] ?>"><?= $s['icon'] ?> <?= $s['label'] ?></span>
            <a href="detail-booking.php?id=<?= $up['id'] ?>" class="detail-btn">Detail →</a>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
    <?php endif; ?>

    <div class="dash-card">
      <div class="dash-card-header">
        <div>
          <div class="dash-card-title">📋 Pemesanan Terbaru</div>
          <div class="dash-card-sub">5 transaksi terakhirmu di arena ini</div>
        </div>
        <a href="riwayat.php" class="dash-card-link">Lihat semua →</a>
      </div>

      <?php if (empty($bookings)): ?>
        <div class="empty-box">
          <div class="empty-icon">🏝️</div>
          <div class="empty-title">Belum Ada Pemesanan</div>
          <div class="empty-text">Yuk, booking trip pertamamu ke Pulau Pari sekarang dan ciptakan kenangan indah!</div>
          <a href="booking.php"
             style="display:inline-block;margin-top:20px;padding:15px 30px;background:var(--sg-orange);color:#fff;border-radius:50px;font-family:'Fredoka One',cursive;font-size:16px;text-decoration:none;border:4px solid var(--sg-black);box-shadow:0 6px 0 var(--sg-black);transition:all .2s;">
            🎮 Pesan Sekarang
          </a>
        </div>

      <?php else: ?>
        <div style="overflow-x:auto;">
          <table class="dash-table">
            <thead>
              <tr>
                <th>Kode Booking</th>
                <th>Paket Trip</th>
                <th>Berangkat</th>
                <th>Peserta</th>
                <th>Total Harga</th>
                <th>Status</th>
                <th>Aksi</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($bookings as $b):
                $s = $statusLabel[$b['status']] ?? ['label' => $b['status'], 'class' => 'badge-pending', 'icon' => '•'];
                $selisihHari = (int)((strtotime($b['tanggal_trip']) - time()) / 86400);
              ?>
              <tr>
                <td>
                  <span style="font-family:'Fredoka One',cursive;color:var(--sg-purple);font-size:14px;">
                    #<?= htmlspecialchars($b['kode_booking']) ?>
                  </span>
                  <div style="font-size:12px;color:#666;margin-top:4px;">
                    Dipesan <?= date('d M Y', strtotime($b['created_at'])) ?>
                  </div>
                </td>

                <td>
                  <div style="font-weight:900;color:var(--sg-black);font-size:14px;max-width:200px;">
                    <?= htmlspecialchars($b['nama_paket']) ?>
                  </div>
                  <div style="font-size:12px;color:#666;margin-top:4px;">
                    Via <?= $viaLabel[$b['via_keberangkatan']] ?? '-' ?>
                  </div>
                </td>

                <td>
                  <div style="font-weight:900;"><?= date('d M Y', strtotime($b['tanggal_trip'])) ?></div>
                  <?php if ($selisihHari > 0 && !in_array($b['status'], ['cancelled','completed'])): ?>
                    <div style="font-size:12px;color:var(--sg-orange);font-weight:900;margin-top:4px;"><?= $selisihHari ?> hari lagi</div>
                  <?php elseif ($b['status'] === 'completed'): ?>
                    <div style="font-size:12px;color:var(--sg-green);font-weight:900;margin-top:4px;">✓ Selesai</div>
                  <?php endif; ?>
                </td>

                <td style="text-align:center;">
                  <div style="font-family:'Fredoka One',cursive;font-size:18px;color:var(--sg-black);line-height:1;"><?= $b['jumlah_peserta'] ?></div>
                  <div style="font-size:11px;color:#666;font-weight:800;">Orang</div>
                </td>

                <td>
                  <div style="font-family:'Fredoka One',cursive;color:var(--sg-blue);text-shadow:1px 1px 0 #000;"><?= formatRupiah((float)$b['total_harga']) ?></div>
                </td>

                <td>
                  <span class="badge-status <?= $s['class'] ?>"><?= $s['icon'] ?> <?= $s['label'] ?></span>
                </td>

                <td>
                  <a href="detail-booking.php?id=<?= $b['id'] ?>" class="detail-btn">
                    Detail →
                  </a>
                </td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>
    </div>

  </main>
</div>
</body>
</html>