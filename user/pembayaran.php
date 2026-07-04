<?php
require_once '../includes/auth.php';
requireUserLogin();

$db     = getDB();
$userId = (int)$_SESSION['user_id'];

/* ── Ambil booking ID ────────── */
$bookingId  = (int)($_GET['booking'] ?? $_POST['booking_id'] ?? 0);
$error      = '';
$successMsg = '';

/* ── Ambil booking milik user yang belum lunas ────── */
if ($bookingId) {
    $stmtB = $db->prepare("
        SELECT p.*, pt.nama_paket FROM pemesanan p
        JOIN paket_trip pt ON pt.id = p.paket_id
        WHERE p.id = ? AND p.user_id = ? AND p.status IN ('pending','confirmed')
    ");
    $stmtB->execute([$bookingId, $userId]);
    $targetBooking = $stmtB->fetch();
} else {
    $targetBooking = null;
}

/* ── Semua booking yang bisa dibayar ─────────────── */
$stmtList = $db->prepare("
    SELECT p.id, p.kode_booking, p.total_harga, p.status, p.tanggal_trip,
           pt.nama_paket,
           (SELECT pb.status FROM pembayaran pb WHERE pb.pemesanan_id = p.id ORDER BY pb.created_at DESC LIMIT 1) AS last_pay_status
    FROM pemesanan p
    JOIN paket_trip pt ON pt.id = p.paket_id
    WHERE p.user_id = ? AND p.status IN ('pending','confirmed')
    ORDER BY p.created_at DESC
");
$stmtList->execute([$userId]);
$bookingList = $stmtList->fetchAll();

/* ── Riwayat semua pembayaran user ───────────────── */
$stmtPays = $db->prepare("
    SELECT pb.*, p.kode_booking, pt.nama_paket
    FROM pembayaran pb
    JOIN pemesanan p ON p.id = pb.pemesanan_id
    JOIN paket_trip pt ON pt.id = p.paket_id
    WHERE p.user_id = ?
    ORDER BY pb.created_at DESC
    LIMIT 20
");
$stmtPays->execute([$userId]);
$allPayments = $stmtPays->fetchAll();

/* ── PROSES UPLOAD ───────────────────────────────── */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $bookingIdPost = (int)($_POST['booking_id'] ?? 0);
    $metode        = sanitize($_POST['metode'] ?? '');
    $bank          = sanitize($_POST['bank'] ?? '');
    $noRek         = sanitize($_POST['no_rekening'] ?? '');
    $jumlah        = (float)str_replace(['.', ',', 'Rp', ' '], '', $_POST['jumlah'] ?? '');

    $stmtCheck = $db->prepare("SELECT * FROM pemesanan WHERE id = ? AND user_id = ? AND status IN ('pending','confirmed')");
    $stmtCheck->execute([$bookingIdPost, $userId]);
    $chkBooking = $stmtCheck->fetch();

    if (!$chkBooking) {
        $error = 'Pemesanan tidak valid atau sudah dibayar.';
    } elseif (!$metode) {
        $error = 'Pilih metode pembayaran.';
    } elseif ($jumlah < 1000) {
        $error = 'Masukkan jumlah pembayaran yang benar.';
    } else {
        $buktiBayar = null;
        if (!empty($_FILES['bukti_bayar']['name'])) {
            $file     = $_FILES['bukti_bayar'];
            $ext      = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            $allowed  = ['jpg', 'jpeg', 'png', 'pdf'];
            $maxSize  = 3 * 1024 * 1024;

            if (!in_array($ext, $allowed)) {
                $error = 'Format file tidak didukung.';
            } elseif ($file['size'] > $maxSize) {
                $error = 'Ukuran file maks 3 MB.';
            } else {
                $uploadDir = UPLOAD_PATH . 'bukti/';
                if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
                $newName = 'bukti_' . $bookingIdPost . '_' . time() . '.' . $ext;
                if (move_uploaded_file($file['tmp_name'], $uploadDir . $newName)) {
                    $buktiBayar = 'bukti/' . $newName;
                } else {
                    $error = 'Gagal mengupload file.';
                }
            }
        }

        if (!$error) {
            $stmtIns = $db->prepare("INSERT INTO pembayaran (pemesanan_id, jumlah, metode, bank, no_rekening, bukti_bayar) VALUES (?, ?, ?, ?, ?, ?)");
            $stmtIns->execute([$bookingIdPost, $jumlah, $metode, $bank ?: null, $noRek ?: null, $buktiBayar]);
            setFlash('success', 'Bukti berhasil diupload!');
            header("Location: pembayaran.php");
            exit;
        }
    }
}

$flash = getFlash();
$payStatusLabel = [
    'pending'  => ['label' => 'Menunggu Verifikasi', 'class' => 'badge-pending'],
    'verified' => ['label' => 'Terverifikasi',       'class' => 'badge-paid'],
    'rejected' => ['label' => 'Ditolak',             'class' => 'badge-cancelled'],
];
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Upload Pembayaran – Pari Adventure</title>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Fredoka+One&family=Nunito:wght@600;800;900&display=swap">
<style>
:root { --sg-black: #1a1a1a; --sg-white: #ffffff; --sg-blue: #00cdff; --sg-orange:#ff8c00; --sg-pink: #ff2a7a; --sg-green: #00e676; --sg-yellow:#ffcc00; --sg-purple:#7c3aed; --sg-bg: #f0f8ff; }
body { font-family: 'Nunito', sans-serif; background-color: var(--sg-bg); margin: 0; color: var(--sg-black); }
.dashboard-layout { display: flex; min-height: 100vh; }
.dashboard-main { flex: 1; padding: 30px 40px; min-width: 0; background-image: radial-gradient(circle at 10% 20%, rgba(0, 205, 255, 0.1) 0%, transparent 20%); }
@media (min-width: 769px) { .dashboard-main { margin-left: 260px; } }

.page-title { font-family: 'Fredoka One', cursive; font-size: 32px; margin-bottom: 25px; }
.dash-card { background: var(--sg-white); border: 5px solid var(--sg-black); border-radius: 30px; box-shadow: 0 10px 0 rgba(0,0,0,0.1); margin-bottom: 30px; overflow: hidden; animation: popIn 0.5s ease both; }
.section-title { background: var(--sg-yellow); padding: 15px 25px; font-family: 'Fredoka One', cursive; border-bottom: 5px solid var(--sg-black); }

.booking-select-card { border: 3px solid #eee; border-radius: 15px; padding: 15px; margin-bottom: 10px; cursor: pointer; display: flex; align-items: center; }
.booking-select-card.selected { border: 3px solid var(--sg-purple); background: #f5edff; }

.btn-upload { background: var(--sg-green); border: 4px solid var(--sg-black); border-radius: 20px; padding: 15px; font-family: 'Fredoka One', cursive; color: white; width: 100%; font-size: 16px; cursor: pointer; box-shadow: 0 6px 0 var(--sg-black); }
.btn-upload:hover { transform: translateY(-3px); box-shadow: 0 9px 0 var(--sg-black); }

.badge-status { padding: 5px 12px; border-radius: 50px; font-family: 'Fredoka One', cursive; font-size: 11px; color: white; border: 2px solid var(--sg-black); }
.badge-pending { background: var(--sg-orange); }
.badge-paid { background: var(--sg-green); }
.badge-cancelled { background: var(--sg-pink); }

@keyframes popIn { 0% { transform: scale(0.9) translateY(20px); opacity: 0; } 100% { transform: scale(1) translateY(0); opacity: 1; } }
</style>
</head>
<body>
<div class="dashboard-layout">
  <?php @include 'partials/sidebar.php'; ?>
  <main class="dashboard-main">
    <h1 class="page-title">Upload Pembayaran 💳</h1>
    
    <div style="display:grid; grid-template-columns: 1fr 350px; gap: 30px;">
      <div class="dash-card">
        <div class="section-title">📤 Form Bukti Transfer</div>
        <div style="padding: 25px;">
          <?php if (empty($bookingList)): ?>
            <p>Tidak ada tagihan aktif.</p>
          <?php else: ?>
            <form method="POST" action="pembayaran.php" enctype="multipart/form-data">
              <div style="margin-bottom:20px;">
                <label style="font-family:'Fredoka One',cursive;">Pilih Booking *</label>
                <?php foreach ($bookingList as $bl): ?>
                  <div class="booking-select-card" onclick="this.querySelector('input').click()">
                    <input type="radio" name="booking_id" value="<?= $bl['id'] ?>" required onchange="setTotal(<?= $bl['total_harga'] ?>)">
                    <div style="margin-left:10px;">
                        <strong><?= htmlspecialchars($bl['kode_booking']) ?></strong> - <?= htmlspecialchars($bl['nama_paket']) ?>
                    </div>
                  </div>
                <?php endforeach; ?>
              </div>
              <div style="display:grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                <input type="text" name="metode" placeholder="Metode (Transfer/QRIS)" required style="padding:12px; border-radius:10px;">
                <input type="text" name="jumlah" id="jumlahField" placeholder="Jumlah" required style="padding:12px; border-radius:10px;">
              </div>
              <input type="file" name="bukti_bayar" required style="margin-top:20px;">
              <button type="submit" class="btn-upload" style="margin-top:20px;">Upload Sekarang</button>
            </form>
          <?php endif; ?>
        </div>
      </div>

      <div class="dash-card">
        <div class="section-title">🏦 Rekening Tujuan</div>
        <div style="padding:20px;">
            <p style="font-size:13px; font-weight:800;">BCA: 1234567890 (Pari Adventure)</p>
            <p style="font-size:13px; font-weight:800;">Mandiri: 1122334455 (Pari Adventure)</p>
        </div>
      </div>
    </div>
  </main>
</div>
</body>
</html>