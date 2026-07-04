<?php
require_once '../includes/auth.php';
requireUserLogin();

$db = getDB();
$userId = (int)$_SESSION['user_id'];
$user = getCurrentUser();

/* ── AMBIL SEMUA PEMESANAN USER DARI DATABASE ──────────────── */
$stmtRiwayat = $db->prepare("
    SELECT p.*, pt.nama_paket
    FROM pemesanan p
    JOIN paket_trip pt ON p.paket_id = pt.id
    WHERE p.user_id = ?
    ORDER BY p.created_at DESC
");
$stmtRiwayat->execute([$userId]);
$riwayat = $stmtRiwayat->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Riwayat Booking – Pari Adventure</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Fredoka+One&family=Nunito:wght@600;800;900&display=swap">
<link rel="stylesheet" href="../css/style.css">
<style>
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

*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
html { scroll-behavior: smooth; }
body {
  font-family: 'Nunito', sans-serif;
  background-color: var(--sg-bg);
  color: var(--sg-black);
  min-height: 100vh;
  overflow-x: hidden;
}

.dashboard-layout { display: flex; min-height: 100vh; width: 100%; }
.dashboard-main {
  flex: 1;
  min-width: 0;
  padding: 30px 40px;
  background-image: radial-gradient(circle at 10% 20%, rgba(0, 205, 255, 0.1) 0%, transparent 20%),
                    radial-gradient(circle at 90% 80%, rgba(255, 140, 0, 0.1) 0%, transparent 20%);
  overflow-y: auto;
}

@media (min-width: 769px) { .dashboard-main { margin-left: 260px; } }

.page-header { margin-bottom: 28px; animation: popIn 0.5s ease both; }
.page-title {
  font-family: 'Fredoka One', cursive;
  font-size: 38px; color: var(--sg-white); line-height: 1.2;
  text-shadow: 2px 2px 0 var(--sg-black), -1px -1px 0 var(--sg-black), 1px -1px 0 var(--sg-black), -1px 1px 0 var(--sg-black), 2px 4px 0 var(--sg-black);
  margin-bottom: 8px;
}
.page-subtitle {
  font-size: 15px; font-weight: 800; color: var(--sg-black);
  background: rgba(255,255,255,0.8); display: inline-block;
  padding: 8px 16px; border-radius: 12px; border: 3px solid var(--sg-black);
  box-shadow: 0 4px 0 var(--sg-black);
}

.riwayat-card {
  background: var(--sg-white); border-radius: 30px;
  border: 5px solid var(--sg-black); overflow: hidden;
  box-shadow: 0 10px 0 rgba(0,0,0,0.15);
  animation: popIn 0.7s cubic-bezier(0.175, 0.885, 0.32, 1.275) both;
}

.riwayat-header {
  background: var(--sg-blue); padding: 25px 30px;
  border-bottom: 5px solid var(--sg-black); position: relative; overflow: hidden;
}
.riwayat-header::after {
  content: '📋'; position: absolute; right: 20px; top: 50%;
  transform: translateY(-50%); font-size: 60px; opacity: .3; animation: wobble 3s infinite;
}
.riwayat-header h2 { font-family: 'Fredoka One', cursive; font-size: 24px; color: var(--sg-white); text-shadow: 2px 2px 0 var(--sg-black); margin: 0; }
.riwayat-header p { font-size: 14px; font-weight: 800; color: var(--sg-black); margin-top: 5px; }

.riwayat-body { padding: 30px; }

.table-wrap { overflow-x: auto; border-radius: 18px; }
.riwayat-table {
  width: 100%; border-collapse: collapse;
  border: 3px solid var(--sg-black);
}

.riwayat-table thead {
  background: var(--sg-purple); color: var(--sg-white);
}

.riwayat-table th {
  padding: 16px;
  font-family: 'Fredoka One', cursive;
  font-size: 13px; text-transform: uppercase;
  text-align: left; font-weight: 900;
  border-bottom: 3px solid var(--sg-black);
  text-shadow: 1px 1px 0 rgba(0,0,0,0.3);
}

.riwayat-table td {
  padding: 14px 16px;
  font-size: 14px; font-weight: 700;
  border-bottom: 2px solid #e0e0e0;
  color: var(--sg-black);
}

.riwayat-table tbody tr {
  transition: all .2s;
  animation: rowFadeIn .5s ease both;
}

.riwayat-table tbody tr:hover {
  background: rgba(0, 205, 255, 0.1);
  transform: translateX(4px);
}

@keyframes rowFadeIn { from{opacity:0;transform:translateX(-12px)} to{opacity:1;transform:none} }

.kode-booking {
  font-family: 'Fredoka One', cursive;
  color: var(--sg-blue);
  font-weight: 900;
  font-size: 12px;
  letter-spacing: 0.5px;
}

.paket-name {
  font-weight: 900;
  color: var(--sg-black);
}

.badge-status {
  display: inline-flex; align-items: center; gap: 5px;
  font-family: 'Fredoka One', cursive; font-size: 11px;
  padding: 6px 12px; border-radius: 20px; color: var(--sg-white);
  font-weight: 900; text-shadow: 1px 1px 0 rgba(0,0,0,0.3);
  border: 2px solid var(--sg-black); box-shadow: 0 2px 0 var(--sg-black);
}

.badge-pending {
  background: linear-gradient(135deg, #ffd600, #ffb300);
  color: #333;
}

.badge-confirmed {
  background: linear-gradient(135deg, #00cdff, #00a8cc);
}

.badge-paid {
  background: linear-gradient(135deg, #00e676, #00c853);
}

.badge-completed {
  background: linear-gradient(135deg, #7c3aed, #5e2fbf);
}

.badge-cancelled {
  background: linear-gradient(135deg, #ff2a7a, #e91e8c);
}

.harga-value {
  font-family: 'Fredoka One', cursive;
  color: var(--sg-orange);
  font-weight: 900;
  font-size: 13px;
}

.empty-state {
  text-align: center;
  padding: 60px 30px;
  color: #999;
}

.empty-state-icon {
  font-size: 80px;
  margin-bottom: 20px;
  display: block;
  animation: wobble 3s infinite;
}

.empty-state-text {
  font-size: 16px;
  font-weight: 800;
  margin-bottom: 15px;
}

.empty-state-sub {
  font-size: 14px;
  color: #aaa;
  line-height: 1.6;
}

.btn-pesan {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  margin-top: 20px;
  background: var(--sg-green);
  color: var(--sg-white);
  padding: 12px 24px;
  border: 3px solid var(--sg-black);
  border-radius: 20px;
  font-family: 'Fredoka One', cursive;
  font-size: 14px;
  text-decoration: none;
  font-weight: 900;
  text-shadow: 1px 1px 0 #000;
  cursor: pointer;
  transition: all .2s;
  box-shadow: 0 4px 0 var(--sg-black);
}

.btn-pesan:hover {
  transform: translateY(-3px);
  box-shadow: 0 6px 0 var(--sg-black);
}

.btn-detail {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 32px;
  height: 32px;
  background: var(--sg-blue);
  color: var(--sg-white);
  border: 2px solid var(--sg-black);
  border-radius: 8px;
  font-size: 16px;
  text-decoration: none;
  font-weight: 700;
  cursor: pointer;
  transition: all .2s;
  box-shadow: 0 2px 0 var(--sg-black);
}

.btn-detail:hover {
  transform: scale(1.15);
  box-shadow: 0 3px 0 var(--sg-black);
}

@keyframes popIn { 0% { transform: scale(0.9) translateY(20px); opacity: 0; } 100% { transform: scale(1) translateY(0); opacity: 1; } }
@keyframes wobble { 0%,100%{transform:rotate(-5deg);} 50%{transform:rotate(5deg);} }

@media (max-width: 768px) {
  .dashboard-main { padding: 20px 15px; margin-left: 0 !important; }
  .riwayat-body { padding: 20px; }
  .page-title { font-size: 28px; }
  .riwayat-table { font-size: 12px; }
  .riwayat-table th, .riwayat-table td { padding: 10px; }
}
</style>
</head>
<body>
<div class="dashboard-layout">

  <?php @include 'partials/sidebar.php'; ?>

  <main class="dashboard-main">

    <div class="page-header">
      <h1 class="page-title">📋 Riwayat Booking</h1>
      <p class="page-subtitle">Semua pesanan trip yang pernah dibuat</p>
    </div>

    <div class="riwayat-card">
      <div class="riwayat-header">
        <h2>📊 Daftar Pemesanan Anda</h2>
        <p>Total pemesanan: <strong><?= count($riwayat) ?></strong> trip</p>
      </div>

      <div class="riwayat-body">
        <?php if (empty($riwayat)): ?>
        
        <div class="empty-state">
          <span class="empty-state-icon">🏝️</span>
          <div class="empty-state-text">Belum ada pemesanan trip</div>
          <div class="empty-state-sub">
            Yuk, pesan paket trip impianmu ke Pulau Pari sekarang!
          </div>
          <a href="booking.php" class="btn-pesan">🏖️ Pesan Trip Sekarang</a>
        </div>

        <?php else: ?>

        <div class="table-wrap">
          <table class="riwayat-table">
            <thead>
              <tr>
                <th>🎫 Kode Booking</th>
                <th>🏝️ Paket Trip</th>
                <th>📅 Tanggal Trip</th>
                <th>👥 Peserta</th>
                <th>💰 Total Harga</th>
                <th>✅ Status</th>
                <th>⚙️ Aksi</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($riwayat as $i => $booking): 
                $status = strtolower($booking['status']);
                if ($status === 'pending') {
                  $badgeClass = 'badge-pending';
                  $badgeText = '⏳ PENDING';
                } elseif ($status === 'confirmed') {
                  $badgeClass = 'badge-confirmed';
                  $badgeText = '✓ CONFIRMED';
                } elseif ($status === 'paid' || $status === 'completed') {
                  if ($status === 'paid') {
                    $badgeClass = 'badge-paid';
                    $badgeText = '💳 PAID';
                  } else {
                    $badgeClass = 'badge-completed';
                    $badgeText = '🎉 COMPLETED';
                  }
                } else {
                  $badgeClass = 'badge-cancelled';
                  $badgeText = '❌ CANCELLED';
                }
              ?>
              <tr style="animation-delay:<?= $i * 0.05 ?>s">
                <td><span class="kode-booking"><?= htmlspecialchars($booking['kode_booking']) ?></span></td>
                <td><span class="paket-name"><?= htmlspecialchars($booking['nama_paket']) ?></span></td>
                <td><?= date('d/m/Y', strtotime($booking['tanggal_trip'])) ?></td>
                <td><?= $booking['jumlah_peserta'] ?> orang</td>
                <td><span class="harga-value"><?= formatRupiah($booking['total_harga']) ?></span></td>
                <td><span class="badge-status <?= $badgeClass ?>"><?= $badgeText ?></span></td>
                <td><a href="detail-booking.php?id=<?= $booking['id'] ?>" class="btn-detail" title="Lihat Detail">👁️</a></td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>

        <?php endif; ?>
      </div>
    </div>

  </main>
</div>

</body>
</html>