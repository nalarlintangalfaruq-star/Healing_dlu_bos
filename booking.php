<?php
require_once '../includes/auth.php';
requireUserLogin();

$db = getDB();
$userId = $_SESSION['user_id'];
$user = getCurrentUser();

// Ambil semua paket aktif
$paket = $db->query("SELECT * FROM paket_trip WHERE status = 'aktif'")->fetchAll();

// Ambil harga semua paket
$hargaAll = [];
foreach ($paket as $p) {
    $stmt = $db->prepare("SELECT * FROM harga_paket WHERE paket_id = ? ORDER BY min_orang");
    $stmt->execute([$p['id']]);
    $hargaAll[$p['id']] = $stmt->fetchAll();
}

$error = '';

// Proses booking
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $paketId       = (int)($_POST['paket_id'] ?? 0);
    $tanggal       = sanitize($_POST['tanggal_trip'] ?? '');
    $jumlah        = (int)($_POST['jumlah_peserta'] ?? 0);
    $namaPemesan   = sanitize($_POST['nama_pemesan'] ?? '');
    $emailPemesan  = sanitize($_POST['email_pemesan'] ?? '');
    $teleponPemesan= sanitize($_POST['telepon_pemesan'] ?? '');
    $catatan       = sanitize($_POST['catatan'] ?? '');

    if (!$paketId || !$tanggal || !$jumlah || !$namaPemesan || !$emailPemesan || !$teleponPemesan) {
        $error = 'Semua field wajib diisi.';
    } elseif ($jumlah < 2) {
        $error = 'Minimum pemesanan 2 orang.';
    } elseif (strtotime($tanggal) < strtotime('+3 days')) {
        $error = 'Tanggal trip minimal 3 hari dari sekarang.';
    } else {
        // Hitung harga
        $stmt = $db->prepare("
            SELECT harga_per_orang FROM harga_paket
            WHERE paket_id = ?
            AND min_orang <= ?
            AND (max_orang IS NULL OR max_orang >= ?)
            LIMIT 1
        ");
        $stmt->execute([$paketId, $jumlah, $jumlah]);
        $hargaRow = $stmt->fetch();

        if (!$hargaRow) {
            $error = 'Harga tidak ditemukan untuk jumlah peserta ini.';
        } else {
            $hargaPerOrang = $hargaRow['harga_per_orang'];
            $totalHarga = $hargaPerOrang * $jumlah;

            // Ambil info paket
            $stmt2 = $db->prepare("SELECT * FROM paket_trip WHERE id = ?");
            $stmt2->execute([$paketId]);
            $paketData = $stmt2->fetch();

            $kode = generateKodeBooking();

            $stmt3 = $db->prepare("
                INSERT INTO pemesanan
                (kode_booking, user_id, paket_id, tanggal_trip, jumlah_peserta,
                 total_harga, harga_per_orang, nama_pemesan, email_pemesan,
                 telepon_pemesan, catatan, via_keberangkatan)
                VALUES (?,?,?,?,?,?,?,?,?,?,?,?)
            ");
            $stmt3->execute([
                $kode, $userId, $paketId, $tanggal, $jumlah,
                $totalHarga, $hargaPerOrang, $namaPemesan, $emailPemesan,
                $teleponPemesan, $catatan, $paketData['via']
            ]);

            setFlash('success', "Booking berhasil! Kode booking Anda: <strong>$kode</strong>. Silakan lakukan pembayaran.");
            redirect(BASE_URL . '/user/riwayat.php');
        }
    }
}

$selectedPaket = (int)($_GET['paket'] ?? 0);
$flash = getFlash();
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Pesan Trip - Pari Adventure</title>
<link rel="stylesheet" href="../css/style.css">
</head>
<body>
<div class="dashboard-layout">

  <!-- SIDEBAR -->
  <aside class="sidebar">
    <div class="sidebar-logo">
      <a href="../index.php" style="display:flex;align-items:center;gap:10px;text-decoration:none;">
        <div style="width:38px;height:38px;background:linear-gradient(135deg,#48b4e0,#0a3d62);border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:18px;">🐋</div>
        <div>
          <div style="font-family:'Playfair Display',serif;font-size:16px;font-weight:700;color:white;">Pari Adventure</div>
          <div style="font-size:10px;color:rgba(255,255,255,0.5);">User Panel</div>
        </div>
      </a>
    </div>
    <ul class="sidebar-nav">
      <li><a href="dashboard.php"><span class="nav-icon">🏠</span> Dashboard</a></li>
      <li><a href="booking.php" class="active"><span class="nav-icon">🏖️</span> Pesan Trip</a></li>
      <li><a href="riwayat.php"><span class="nav-icon">📋</span> Riwayat Booking</a></li>
      <li><a href="pembayaran.php"><span class="nav-icon">💳</span> Pembayaran</a></li>
      <li><a href="testimoni.php"><span class="nav-icon">⭐</span> Tulis Ulasan</a></li>
      <li><a href="profil.php"><span class="nav-icon">👤</span> Profil Saya</a></li>
      <li style="margin-top:20px;border-top:1px solid rgba(255,255,255,0.1);padding-top:16px;">
        <a href="../logout.php"><span class="nav-icon">🚪</span> Keluar</a>
      </li>
    </ul>
  </aside>

  <!-- MAIN -->
  <main class="dashboard-main">
    <div class="page-header">
      <h1 class="page-title">Pesan Trip 🏝️</h1>
      <p class="page-subtitle">Pilih paket dan isi form pemesanan di bawah ini.</p>
    </div>

    <?php if ($error): ?>
      <div class="alert alert-danger">⚠️ <?= $error ?></div>
    <?php endif; ?>

    <div style="display:grid;grid-template-columns:2fr 1fr;gap:32px;">

      <!-- FORM -->
      <div class="booking-card">
        <div class="booking-header">
          <h3>Form Pemesanan</h3>
          <p style="font-size:13px;opacity:0.8;">Lengkapi data di bawah dengan benar</p>
        </div>
        <div class="booking-body">
          <form method="POST" action="booking.php" id="bookingForm">

            <div class="form-group">
              <label class="form-label">Pilih Paket Trip *</label>
              <select name="paket_id" class="form-control" id="paketSelect" required onchange="updateHarga()">
                <option value="">-- Pilih Paket --</option>
                <?php foreach ($paket as $p): ?>
                <option value="<?= $p['id'] ?>"
                        data-harga='<?= json_encode($hargaAll[$p['id']]) ?>'
                        <?= $selectedPaket == $p['id'] ? 'selected' : '' ?>>
                  <?= htmlspecialchars($p['nama_paket']) ?>
                </option>
                <?php endforeach; ?>
              </select>
            </div>

            <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;">
              <div class="form-group">
                <label class="form-label">Tanggal Trip *</label>
                <input type="date" name="tanggal_trip" class="form-control"
                       min="<?= date('Y-m-d', strtotime('+3 days')) ?>"
                       value="<?= htmlspecialchars($_POST['tanggal_trip'] ?? '') ?>"
                       required>
              </div>
              <div class="form-group">
                <label class="form-label">Jumlah Peserta *</label>
                <input type="number" name="jumlah_peserta" class="form-control"
                       id="jumlahInput" min="2" max="100"
                       placeholder="Min. 2 orang"
                       value="<?= htmlspecialchars($_POST['jumlah_peserta'] ?? '') ?>"
                       required onchange="updateHarga()">
              </div>
            </div>

            <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;">
              <div class="form-group">
                <label class="form-label">Nama Pemesan *</label>
                <input type="text" name="nama_pemesan" class="form-control"
                       placeholder="Nama lengkap"
                       value="<?= htmlspecialchars($_POST['nama_pemesan'] ?? $user['nama']) ?>"
                       required>
              </div>
              <div class="form-group">
                <label class="form-label">Email *</label>
                <input type="email" name="email_pemesan" class="form-control"
                       placeholder="email@example.com"
                       value="<?= htmlspecialchars($_POST['email_pemesan'] ?? $user['email']) ?>"
                       required>
              </div>
            </div>

            <div class="form-group">
              <label class="form-label">Nomor WhatsApp *</label>
              <input type="tel" name="telepon_pemesan" class="form-control"
                     placeholder="08xxxxxxxxxx"
                     value="<?= htmlspecialchars($_POST['telepon_pemesan'] ?? $user['no_telepon']) ?>"
                     required>
            </div>

            <div class="form-group">
              <label class="form-label">Catatan Khusus</label>
              <textarea name="catatan" class="form-control" rows="3"
                        placeholder="Alergi makanan, kebutuhan khusus, dll..."
                        style="resize:vertical;"><?= htmlspecialchars($_POST['catatan'] ?? '') ?></textarea>
            </div>

            <button type="submit" class="btn-book" style="font-size:16px;">
              🏖️ Konfirmasi Pemesanan
            </button>
          </form>
        </div>
      </div>

      <!-- SUMMARY -->
      <div>
        <div class="booking-card" style="position:sticky;top:20px;">
          <div class="booking-header" style="padding:20px 24px;">
            <h3 style="font-size:18px;">Ringkasan Harga</h3>
          </div>
          <div style="padding:24px;">
            <div id="hargaInfo">
              <p style="text-align:center;color:#8daab8;font-size:13px;padding:20px 0;">
                Pilih paket & jumlah peserta untuk melihat harga
              </p>
            </div>

            <div style="background:#f8f9fa;border-radius:10px;padding:16px;margin-top:16px;">
              <div style="font-size:12px;font-weight:700;color:#8daab8;text-transform:uppercase;letter-spacing:1px;margin-bottom:12px;">Yang Termasuk</div>
              <div style="font-size:13px;color:#3d5a6c;line-height:1.8;">
                ✅ Tiket kapal PP<br>
                ✅ Penginapan full AC<br>
                ✅ Makan 3x sehari<br>
                ✅ Snorkeling + alat<br>
                ✅ Tour guide<br>
                ✅ BBQ malam
              </div>
            </div>

            <div style="margin-top:16px;padding:12px;background:#fff3cd;border-radius:8px;font-size:12px;color:#856404;">
              ⚠️ Pembayaran dilakukan setelah konfirmasi booking. Min. 2 orang.
            </div>
          </div>
        </div>
      </div>

    </div>
  </main>
</div>

<script>
const hargaData = <?= json_encode($hargaAll) ?>;

function updateHarga() {
  const paketSelect = document.getElementById('paketSelect');
  const jumlahInput = document.getElementById('jumlahInput');
  const paketId = paketSelect.value;
  const jumlah = parseInt(jumlahInput.value) || 0;

  if (!paketId || jumlah < 2) {
    document.getElementById('hargaInfo').innerHTML = '<p style="text-align:center;color:#8daab8;font-size:13px;padding:20px 0;">Pilih paket & jumlah peserta untuk melihat harga</p>';
    return;
  }

  const hargaList = hargaData[paketId];
  let hargaPerOrang = 0;

  for (const h of hargaList) {
    const minOk = jumlah >= h.min_orang;
    const maxOk = h.max_orang === null || jumlah <= h.max_orang;
    if (minOk && maxOk) { hargaPerOrang = h.harga_per_orang; break; }
  }

  if (!hargaPerOrang) {
    document.getElementById('hargaInfo').innerHTML = '<p style="color:#dc3545;font-size:13px;">Harga tidak tersedia untuk jumlah ini.</p>';
    return;
  }

  const total = hargaPerOrang * jumlah;
  const fmt = n => 'Rp ' + new Intl.NumberFormat('id-ID').format(n);

  document.getElementById('hargaInfo').innerHTML = `
    <div style="border-bottom:1px solid #f0f4f8;padding-bottom:12px;margin-bottom:12px;">
      <div style="display:flex;justify-content:space-between;font-size:14px;color:#6b8a9a;margin-bottom:8px;">
        <span>Harga/orang</span><span>${fmt(hargaPerOrang)}</span>
      </div>
      <div style="display:flex;justify-content:space-between;font-size:14px;color:#6b8a9a;">
        <span>Jumlah peserta</span><span>${jumlah} orang</span>
      </div>
    </div>
    <div style="display:flex;justify-content:space-between;font-size:18px;font-weight:700;color:#1a2f3d;">
      <span>Total</span><span style="color:#1e6fa5;">${fmt(total)}</span>
    </div>
  `;
}
</script>
</body>
</html>