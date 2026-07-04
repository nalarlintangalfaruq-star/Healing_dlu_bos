<?php
require_once '../includes/auth.php';
requireUserLogin();

$db     = getDB();
$userId = (int)$_SESSION['user_id'];
$error  = '';

/* ── Trip selesai yang belum diberi ulasan ────────── */
$stmtTrip = $db->prepare("
    SELECT p.id, p.kode_booking, p.tanggal_trip, pt.id AS paket_id, pt.nama_paket
    FROM pemesanan p
    JOIN paket_trip pt ON pt.id = p.paket_id
    WHERE p.user_id = ?
      AND p.status IN ('pending','confirmed','paid','completed')
      AND NOT EXISTS (
          SELECT 1 FROM testimoni t
          WHERE t.user_id = ? AND t.pemesanan_id = p.id
      )
    ORDER BY p.tanggal_trip DESC
");
$stmtTrip->execute([$userId, $userId]);
$tripsBelumUlasan = $stmtTrip->fetchAll();

/* ── Pre-select dari parameter ─────────────────────── */
$preBookingId = (int)($_GET['booking'] ?? 0);
$preBooking   = null;
if ($preBookingId) {
    foreach ($tripsBelumUlasan as $t) {
        if ($t['id'] == $preBookingId) { $preBooking = $t; break; }
    }
}

/* ── Ulasan yang sudah diberikan ─────────────────── */
$stmtUlasan = $db->prepare("
    SELECT t.*, pt.nama_paket, p.kode_booking, p.tanggal_trip
    FROM testimoni t
    JOIN paket_trip pt ON pt.id = t.paket_id
    LEFT JOIN pemesanan p ON p.id = t.pemesanan_id
    WHERE t.user_id = ?
    ORDER BY t.created_at DESC
");
$stmtUlasan->execute([$userId]);
$ulasanSaya = $stmtUlasan->fetchAll();

/* ── PROSES SUBMIT ───────────────────────────────── */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $bookingIdPost = (int)($_POST['pemesanan_id'] ?? 0);
    $paketId       = (int)($_POST['paket_id']     ?? 0);
    $rating        = (int)($_POST['rating']       ?? 0);
    $komentar      = sanitize($_POST['komentar'] ?? '');

    $stmtCheck = $db->prepare("SELECT * FROM pemesanan WHERE id = ? AND user_id = ? AND status IN ('pending','confirmed','paid','completed')");
    $stmtCheck->execute([$bookingIdPost, $userId]);
    $chkBook = $stmtCheck->fetch();

    if (!$chkBook) {
        $error = 'Booking tidak valid.';
    } elseif ($rating < 1 || $rating > 5) {
        $error = 'Pilih rating bintang (1–5).';
    } elseif (strlen($komentar) < 10) {
        $error = 'Ulasan minimal 10 karakter.';
    } else {
        $stmtDup = $db->prepare("SELECT id FROM testimoni WHERE user_id = ? AND pemesanan_id = ?");
        $stmtDup->execute([$userId, $bookingIdPost]);
        if ($stmtDup->fetch()) {
            $error = 'Anda sudah memberikan ulasan untuk trip ini.';
        } else {
            $stmtIns = $db->prepare("INSERT INTO testimoni (user_id, paket_id, pemesanan_id, rating, komentar) VALUES (?, ?, ?, ?, ?)");
            $stmtIns->execute([$userId, $paketId, $bookingIdPost, $rating, $komentar]);
            setFlash('success', 'Ulasan berhasil dikirim! Terima kasih atas penilaian Anda 🌟');
            header("Location: testimoni.php");
            exit;
        }
    }
}

$statusUlasan = [
    'pending'  => ['label' => 'Menunggu Review', 'color' => '#856404', 'bg' => '#fff3cd'],
    'approved' => ['label' => 'Ditampilkan',     'color' => '#155724', 'bg' => '#d4edda'],
    'rejected' => ['label' => 'Ditolak',         'color' => '#721c24', 'bg' => '#f8d7da'],
];

$flash = getFlash();
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Ulasan Saya – Pari Adventure</title>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Fredoka+One&family=Nunito:wght@600;800;900&display=swap">
<style>
:root { --sg-black: #1a1a1a; --sg-white: #ffffff; --sg-blue: #00cdff; --sg-orange:#ff8c00; --sg-pink: #ff2a7a; --sg-green: #00e676; --sg-yellow:#ffcc00; --sg-purple:#7c3aed; --sg-bg: #f0f8ff; }
body { font-family: 'Nunito', sans-serif; background-color: var(--sg-bg); margin: 0; color: var(--sg-black); }
.dashboard-layout { display: flex; min-height: 100vh; }
.dashboard-main { flex: 1; padding: 30px 40px; min-width: 0; }
@media (min-width: 769px) { .dashboard-main { margin-left: 260px; } }

.page-title { font-family: 'Fredoka One', cursive; font-size: 32px; margin-bottom: 25px; }
.dash-card { background: var(--sg-white); border: 5px solid var(--sg-black); border-radius: 30px; box-shadow: 0 10px 0 rgba(0,0,0,0.15); margin-bottom: 30px; overflow: hidden; animation: popIn 0.6s cubic-bezier(0.175, 0.885, 0.32, 1.275) both; }
.section-title { background: var(--sg-yellow); padding: 18px 25px; font-family: 'Fredoka One', cursive; font-size: 18px; border-bottom: 5px solid var(--sg-black); text-transform: uppercase; }

.trip-radio-card { border: 3px solid #eee; border-radius: 20px; padding: 15px; margin-bottom: 12px; cursor: pointer; display: flex; align-items: center; gap: 15px; transition: all .2s; }
.trip-radio-card.selected { border: 4px solid var(--sg-purple); background: #f5edff; }
.trip-radio-card:hover { border-color: var(--sg-purple); }

.star-picker { display: flex; flex-direction: row-reverse; justify-content: flex-end; gap: 5px; }
.star-picker input { display: none; }
.star-picker label { font-size: 40px; cursor: pointer; color: #d0d8df; transition: all .2s; }
.star-picker input:checked ~ label, .star-picker label:hover, .star-picker label:hover ~ label { color: var(--sg-yellow); transform: scale(1.1); }

.btn-submit { background: var(--sg-green); border: 4px solid var(--sg-black); border-radius: 20px; padding: 15px; font-family: 'Fredoka One', cursive; color: white; width: 100%; font-size: 16px; cursor: pointer; box-shadow: 0 6px 0 var(--sg-black); text-shadow: 1px 1px 0 #000; }
.btn-submit:hover { transform: translateY(-3px); box-shadow: 0 9px 0 var(--sg-black); }

.review-card { border: 4px solid var(--sg-black); border-radius: 20px; padding: 20px; margin-bottom: 15px; transition: all .2s; background: white; box-shadow: 0 6px 0 rgba(0,0,0,0.1); }
.review-card:hover { transform: translateY(-4px); box-shadow: 0 10px 0 rgba(0,0,0,0.1); }

@keyframes popIn { 0% { transform: scale(0.9) translateY(20px); opacity: 0; } 100% { transform: scale(1) translateY(0); opacity: 1; } }
</style>
</head>
<body>
<div class="dashboard-layout">
  <?php @include 'partials/sidebar.php'; ?>
  <main class="dashboard-main">
    <h1 class="page-title">Ulasan Saya ⭐</h1>

    <div style="display:grid; grid-template-columns: 1.2fr 1fr; gap: 30px; align-items: start;">
      <div class="dash-card">
        <div class="section-title">✍️ Tulis Ulasan Baru</div>
        <div style="padding: 25px;">
          <?php if (empty($tripsBelumUlasan)): ?>
            <div style="text-align:center; padding: 40px;">Semua trip sudah diulas! 🌟</div>
          <?php else: ?>
            <form method="POST" action="testimoni.php">
              <div style="margin-bottom:25px;">
                <label style="font-family:'Fredoka One',cursive; display:block; margin-bottom:10px;">Pilih Trip *</label>
                <?php foreach ($tripsBelumUlasan as $trip): ?>
                <label class="trip-radio-card" onclick="this.closest('form').querySelector('#paketIdField').value='<?= $trip['paket_id'] ?>';">
                  <input type="radio" name="pemesanan_id" value="<?= $trip['id'] ?>" required onchange="document.querySelectorAll('.trip-radio-card').forEach(c=>c.classList.remove('selected'));this.parentElement.classList.add('selected')">
                  <div><strong><?= htmlspecialchars($trip['nama_paket']) ?></strong><div style="font-size:12px;color:#666;">🗓️ <?= date('d M Y', strtotime($trip['tanggal_trip'])) ?></div></div>
                </label>
                <?php endforeach; ?>
                <input type="hidden" name="paket_id" id="paketIdField">
              </div>
              <div class="star-picker">
                <?php for ($i = 5; $i >= 1; $i--): ?>
                <input type="radio" id="star<?= $i ?>" name="rating" value="<?= $i ?>" required><label for="star<?= $i ?>">★</label>
                <?php endfor; ?>
              </div>
              <textarea name="komentar" rows="4" style="width:100%; padding:15px; border:3px solid var(--sg-black); border-radius:18px; margin:20px 0; font-size:15px;" placeholder="Tulis pengalamanmu..." required></textarea>
              <button type="submit" class="btn-submit">🌟 Kirim Ulasan</button>
            </form>
          <?php endif; ?>
        </div>
      </div>

      <div class="dash-card">
        <div class="section-title">📝 Riwayat Ulasan</div>
        <div style="padding: 20px;">
          <?php foreach ($ulasanSaya as $ul): 
            $us = $statusUlasan[$ul['status']] ?? ['label' => $ul['status'], 'color' => '#333', 'bg' => '#eee'];
          ?>
          <div class="review-card">
            <div style="font-family:'Fredoka One',cursive; color:var(--sg-purple);"><?= htmlspecialchars($ul['nama_paket']) ?></div>
            <div style="color:var(--sg-yellow); margin:5px 0;"><?= str_repeat('★', $ul['rating']) ?></div>
            <div style="font-size:14px; font-weight:800;">"<?= htmlspecialchars($ul['komentar']) ?>"</div>
            <div style="font-size:11px; margin-top:10px; color:#888;">Status: <span style="padding:2px 8px; border-radius:10px; background:<?= $us['bg'] ?>; color:<?= $us['color'] ?>;"><?= $us['label'] ?></span></div>
          </div>
          <?php endforeach; ?>
        </div>
      </div>
    </div>
  </main>
</div>
</body>
</html>