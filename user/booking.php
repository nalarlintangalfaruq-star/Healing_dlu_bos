<?php
require_once '../includes/auth.php';
requireUserLogin();

$db = getDB();
$userId = (int)$_SESSION['user_id'];
$user = getCurrentUser();
$error = '';

/* ── AMBIL SEMUA PAKET AKTIF DARI DATABASE ──────────────── */
$stmtPaket = $db->query("SELECT * FROM paket_trip WHERE status = 'aktif' ORDER BY id");
$paket = $stmtPaket->fetchAll();

/* ── AMBIL HARGA SEMUA PAKET ──────────────────────────────── */
$hargaAll = [];
foreach ($paket as $p) {
    $stmt = $db->prepare("SELECT * FROM harga_paket WHERE paket_id = ? ORDER BY min_orang ASC");
    $stmt->execute([$p['id']]);
    $hargaAll[$p['id']] = $stmt->fetchAll();
}

/* ── AMBIL FASILITAS UNTUK SETIAP PAKET ──────────────────── */
$fasilitasAll = [];
foreach ($paket as $p) {
    $stmt = $db->prepare("SELECT * FROM fasilitas WHERE paket_id = ? ORDER BY id ASC");
    $stmt->execute([$p['id']]);
    $fasilitasAll[$p['id']] = $stmt->fetchAll();
}

/* ── PROSES BOOKING ──────────────────────────────────────── */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $paketId        = (int)($_POST['paket_id'] ?? 0);
    $tanggal        = sanitize($_POST['tanggal_trip'] ?? '');
    $jumlah         = (int)($_POST['jumlah_peserta'] ?? 0);
    $namaPemesan    = sanitize($_POST['nama_pemesan'] ?? '');
    $emailPemesan   = filter_var($_POST['email_pemesan'] ?? '', FILTER_SANITIZE_EMAIL);
    $teleponPemesan = sanitize($_POST['telepon_pemesan'] ?? '');
    $catatan        = sanitize($_POST['catatan'] ?? '');

    if (!$paketId || !$tanggal || !$jumlah || !$namaPemesan || !$emailPemesan || !$teleponPemesan) {
        $error = '⚠️ Semua field yang bertanda (*) wajib diisi.';
    } elseif ($jumlah < 2 || $jumlah > 100) {
        $error = '⚠️ Jumlah peserta harus antara 2–100 orang.';
    } elseif (strtotime($tanggal) < strtotime('+3 days')) {
        $error = '⚠️ Tanggal trip minimal 3 hari dari hari ini.';
    } else {
        $stmtPaketCheck = $db->prepare("SELECT * FROM paket_trip WHERE id = ? AND status = 'aktif'");
        $stmtPaketCheck->execute([$paketId]);
        $paketData = $stmtPaketCheck->fetch();

        if (!$paketData) {
            $error = '⚠️ Paket trip tidak valid atau sudah tidak aktif.';
        } else {
            $stmtHarga = $db->prepare("
                SELECT harga_per_orang FROM harga_paket
                WHERE paket_id = ?
                  AND min_orang <= ?
                  AND (max_orang IS NULL OR max_orang >= ?)
                LIMIT 1
            ");
            $stmtHarga->execute([$paketId, $jumlah, $jumlah]);
            $hargaRow = $stmtHarga->fetch();

            if (!$hargaRow) {
                $error = '⚠️ Harga tidak ditemukan untuk jumlah peserta ini.';
            } else {
                $hargaPerOrang = (float)$hargaRow['harga_per_orang'];
                $totalHarga = $hargaPerOrang * $jumlah;
                $kode = generateKodeBooking();

                $stmtInsert = $db->prepare("
                    INSERT INTO pemesanan
                    (kode_booking, user_id, paket_id, tanggal_trip, jumlah_peserta,
                     total_harga, harga_per_orang, nama_pemesan, email_pemesan,
                     telepon_pemesan, catatan, via_keberangkatan, status, created_at)
                    VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,NOW())
                ");
                $stmtInsert->execute([
                    $kode, $userId, $paketId, $tanggal, $jumlah,
                    $totalHarga, $hargaPerOrang, $namaPemesan, $emailPemesan,
                    $teleponPemesan, $catatan, $paketData['via'], 'pending'
                ]);

                setFlash('success', "✅ Booking berhasil! Kode: <strong>$kode</strong>");
                redirect(BASE_URL . '/user/riwayat.php');
            }
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
<title>Pesan Trip – Pari Adventure</title>
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
  margin-bottom: 8px; display: flex; align-items: center; gap: 10px;
}
.page-subtitle {
  font-size: 15px; font-weight: 800; color: var(--sg-black);
  background: rgba(255,255,255,0.8); display: inline-block;
  padding: 8px 16px; border-radius: 12px; border: 3px solid var(--sg-black);
  box-shadow: 0 4px 0 var(--sg-black);
}

.alert {
  padding: 15px 20px; border-radius: 18px; margin-bottom: 25px;
  font-size: 15px; font-weight: 800; display: flex; align-items: center; gap: 12px;
  border: 4px solid var(--sg-black); box-shadow: 0 6px 0 var(--sg-black);
  animation: popIn 0.4s ease both;
}
.alert-danger  { background: var(--sg-pink); color: var(--sg-white); text-shadow: 1px 1px 0 #000; }

.booking-section { display: grid; grid-template-columns: 1.55fr 1fr; gap: 30px; align-items: start; }

.booking-card, .summary-card {
  background: var(--sg-white); border-radius: 30px;
  border: 5px solid var(--sg-black); overflow: hidden;
  box-shadow: 0 10px 0 rgba(0,0,0,0.15);
  animation: popIn 0.7s cubic-bezier(0.175, 0.885, 0.32, 1.275) both;
}

.booking-header {
  background: var(--sg-blue); padding: 25px 30px;
  border-bottom: 5px solid var(--sg-black); position: relative; overflow: hidden;
}
.booking-header::after {
  content: '📝'; position: absolute; right: 20px; top: 50%;
  transform: translateY(-50%); font-size: 60px; opacity: .3; animation: wobble 3s infinite;
}
.booking-header h2 { font-family: 'Fredoka One', cursive; font-size: 24px; color: var(--sg-white); text-shadow: 2px 2px 0 var(--sg-black); margin: 0; }
.booking-header p { font-size: 14px; font-weight: 800; color: var(--sg-black); margin-top: 5px; }

.booking-body, .summary-body { padding: 30px; }

.form-group { margin-bottom: 25px; }
.form-group label {
  display: block; font-family: 'Fredoka One', cursive; font-size: 14px;
  color: var(--sg-black); margin-bottom: 8px; text-transform: uppercase; letter-spacing: 1px;
}
.form-group label .req { color: var(--sg-pink); margin-left: 4px; font-size: 18px; }

.form-group input, .form-group select, .form-group textarea {
  width: 100%; padding: 15px 20px;
  border: 3px solid var(--sg-black); border-radius: 18px;
  font-family: 'Nunito', sans-serif; font-size: 15px; font-weight: 800; color: var(--sg-black);
  background: var(--sg-bg); outline: none; transition: all .2s;
  box-shadow: 0 4px 0 rgba(0,0,0,0.1);
}
.form-group input:focus, .form-group select:focus, .form-group textarea:focus {
  background: var(--sg-white); border-color: var(--sg-purple); box-shadow: 0 6px 0 var(--sg-purple); transform: translateY(-2px);
}
.form-group textarea { resize: vertical; min-height: 100px; }
.form-hint { font-size: 12px; font-weight: 800; color: #666; margin-top: 6px; }
.form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }

.btn-submit {
  width: 100%; padding: 18px;
  background: var(--sg-green); color: var(--sg-white);
  border: 4px solid var(--sg-black); border-radius: 20px;
  font-family: 'Fredoka One', cursive; font-size: 18px; text-shadow: 2px 2px 0 var(--sg-black);
  cursor: pointer; transition: all .2s; box-shadow: 0 8px 0 var(--sg-black);
  display: flex; align-items: center; justify-content: center; gap: 10px; letter-spacing: 1px;
}
.btn-submit:hover { transform: translateY(-4px); box-shadow: 0 12px 0 var(--sg-black); background: #00ff84; }
.btn-submit:active { transform: translateY(4px); box-shadow: 0 0px 0 var(--sg-black); }

.summary-card { position: sticky; top: 30px; }
.summary-header {
  background: var(--sg-green); padding: 25px 30px; color: var(--sg-white);
  font-family: 'Fredoka One', cursive; font-size: 22px; text-shadow: 2px 2px 0 var(--sg-black);
  border-bottom: 5px solid var(--sg-black); position: relative; overflow: hidden;
}
.summary-header::after {
  content: '💰'; position: absolute; right: 15px; top: 50%; transform: translateY(-50%); font-size: 60px; opacity: .3;
}

.summary-empty { text-align: center; padding: 40px 20px; color: #555; font-weight: 800; font-size: 15px; line-height: 1.6; }
.summary-empty-icon { font-size: 60px; display: block; margin-bottom: 15px; animation: wobble 3s infinite; }

.summary-row {
  display: flex; justify-content: space-between; align-items: center;
  padding: 12px 0; border-bottom: 2px dashed #ccc; font-size: 14px;
}
.summary-row:last-of-type { border-bottom: none; }
.summary-row-label { font-family: 'Fredoka One', cursive; color: var(--sg-purple); }
.summary-row-value { font-weight: 900; color: var(--sg-black); text-align: right; }

.summary-total-box {
  background: var(--sg-orange); border: 4px solid var(--sg-black); border-radius: 20px;
  padding: 20px; margin-top: 20px; display: flex; justify-content: space-between; align-items: center;
  box-shadow: 0 6px 0 var(--sg-black); color: var(--sg-white);
}
.summary-total-label { font-family: 'Fredoka One', cursive; font-size: 16px; text-shadow: 1px 1px 0 #000; }
.summary-total-value { font-family: 'Fredoka One', cursive; font-size: 24px; text-shadow: 2px 2px 0 #000; }

.fasilitas-box {
  background: var(--sg-white); border: 3px solid var(--sg-black); border-radius: 18px;
  padding: 18px; margin-top: 20px; box-shadow: 0 4px 0 var(--sg-black);
}
.fasilitas-title {
  font-family: 'Fredoka One', cursive; font-size: 14px; color: var(--sg-blue);
  text-transform: uppercase; margin-bottom: 12px; text-shadow: 1px 1px 0 #000;
}
.fasilitas-grid-list { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; }
.fasilitas-item { display: flex; align-items: center; gap: 8px; font-size: 13px; font-weight: 800; color: var(--sg-black); }
.fasilitas-icon { font-size: 18px; }

.note-box {
  background: #fffbe6; border: 3px solid var(--sg-yellow); border-radius: 16px;
  padding: 15px; margin-top: 20px; font-size: 13px; font-weight: 800; color: #92400e;
  display: flex; gap: 10px; line-height: 1.5;
}

@keyframes popIn { 0% { transform: scale(0.9) translateY(20px); opacity: 0; } 100% { transform: scale(1) translateY(0); opacity: 1; } }
@keyframes wobble { 0%,100%{transform:translateY(-50%) rotate(-5deg);} 50%{transform:translateY(-50%) rotate(5deg);} }

@media (max-width: 1100px) { .booking-section { grid-template-columns: 1fr; } .summary-card { position: static; } }
@media (max-width: 768px) {
  .dashboard-main { padding: 20px 15px; margin-left: 0 !important; }
  .booking-body, .summary-body { padding: 20px; }
  .form-row { grid-template-columns: 1fr; }
  .page-title { font-size: 28px; }
}
</style>
</head>
<body>
<div class="dashboard-layout">

  <?php @include 'partials/sidebar.php'; ?>

  <main class="dashboard-main">

    <div class="page-header">
      <h1 class="page-title">🏝️ Pesan Trip Sekarang</h1>
      <p class="page-subtitle">Isi formulir di bawah untuk memesan paket trip impianmu ke Pulau Pari.</p>
    </div>

    <?php if ($error): ?>
    <div class="alert alert-danger"><span>⚠️</span><span><?= $error ?></span></div>
    <?php endif; ?>

    <div class="booking-section">

      <div class="booking-card">
        <div class="booking-header">
          <h2>📋 Form Pemesanan</h2>
          <p>Data diambil langsung dari database — harga otomatis dihitung</p>
        </div>

        <div class="booking-body">
          <form method="POST" action="booking.php" id="bookingForm">

            <div class="form-group">
              <label>Pilih Paket Trip <span class="req">*</span></label>
              <select name="paket_id" id="paketSelect" required onchange="updateSummary()">
                <option value="">-- Pilih Paket Trip --</option>
                <?php foreach ($paket as $p): ?>
                <option value="<?= $p['id'] ?>"
                        data-harga='<?= json_encode($hargaAll[$p['id']]) ?>'
                        data-fasilitas='<?= json_encode($fasilitasAll[$p['id']]) ?>'
                        <?= $selectedPaket == $p['id'] ? 'selected' : '' ?>>
                  <?= htmlspecialchars($p['nama_paket']) ?> (<?= $p['via'] === 'muara_angke' ? 'Muara Angke' : 'Marina Ancol' ?>)
                </option>
                <?php endforeach; ?>
              </select>
            </div>

            <div class="form-row">
              <div class="form-group">
                <label>Tanggal Trip <span class="req">*</span></label>
                <input type="date" name="tanggal_trip"
                       min="<?= date('Y-m-d', strtotime('+3 days')) ?>"
                       value="<?= htmlspecialchars($_POST['tanggal_trip'] ?? '') ?>"
                       required onchange="updateSummary()">
                <div class="form-hint">📅 Minimal 3 hari dari sekarang</div>
              </div>
              <div class="form-group">
                <label>Jumlah Peserta <span class="req">*</span></label>
                <input type="number" name="jumlah_peserta" id="jumlahInput"
                       min="2" max="100" placeholder="Min. 2 orang"
                       value="<?= htmlspecialchars($_POST['jumlah_peserta'] ?? '') ?>"
                       required oninput="updateSummary()">
                <div class="form-hint">👥 2 – 100 orang</div>
              </div>
            </div>

            <div class="form-row">
              <div class="form-group">
                <label>Nama Lengkap <span class="req">*</span></label>
                <input type="text" name="nama_pemesan" placeholder="Nama lengkap"
                       value="<?= htmlspecialchars($_POST['nama_pemesan'] ?? $user['nama']) ?>" required>
              </div>
              <div class="form-group">
                <label>Email <span class="req">*</span></label>
                <input type="email" name="email_pemesan" placeholder="email@example.com"
                       value="<?= htmlspecialchars($_POST['email_pemesan'] ?? $user['email']) ?>" required>
              </div>
            </div>

            <div class="form-group">
              <label>Nomor WhatsApp <span class="req">*</span></label>
              <input type="tel" name="telepon_pemesan" placeholder="08xxxxxxxxxx"
                     value="<?= htmlspecialchars($_POST['telepon_pemesan'] ?? ($user['no_telepon'] ?? '')) ?>" required>
              <div class="form-hint">📱 Akan dihubungi untuk konfirmasi</div>
            </div>

            <div class="form-group">
              <label>Catatan Khusus</label>
              <textarea name="catatan" placeholder="Alergi makanan, kebutuhan khusus, atau permintaan lainnya..."><?= htmlspecialchars($_POST['catatan'] ?? '') ?></textarea>
              <div class="form-hint">Opsional</div>
            </div>

            <button type="submit" class="btn-submit" id="submitBtn">
              🏖️ Konfirmasi Pemesanan
            </button>

          </form>
        </div>
      </div>

      <div class="summary-card">
        <div class="summary-header">
          💰 Ringkasan Harga
        </div>

        <div class="summary-body">
          <div id="summaryEmpty" class="summary-empty">
            <span class="summary-empty-icon">🔍</span>
            Pilih paket &amp; isi jumlah peserta<br>untuk melihat estimasi harga
          </div>

          <div id="summaryContent" style="display:none">
            <div id="summaryRows"></div>

            <div class="summary-total-box">
              <div class="summary-total-label">💳 Total Harga</div>
              <div class="summary-total-value" id="summaryTotal">-</div>
            </div>
          </div>

          <div id="fasilitasBox" class="fasilitas-box" style="display:none">
            <div class="fasilitas-title">✅ Fasilitas Termasuk</div>
            <div class="fasilitas-grid-list" id="fasilitasList"></div>
          </div>

          <div class="note-box">
            <span>⚠️</span>
            <span>Pembayaran dilakukan setelah booking dikonfirmasi admin. Slot akan disimpan selama 24 jam.</span>
          </div>

        </div>
      </div>

    </div>
  </main>
</div>

<script>
const hargaData     = <?= json_encode($hargaAll) ?>;
const fasilitasData = <?= json_encode($fasilitasAll) ?>;

function fmt(n) {
  return 'Rp ' + new Intl.NumberFormat('id-ID').format(parseInt(n));
}

function updateSummary() {
  const sel    = document.getElementById('paketSelect');
  const jumlahEl = document.getElementById('jumlahInput');
  const paketId  = parseInt(sel.value);
  const jumlah   = parseInt(jumlahEl.value) || 0;

  if (!paketId || jumlah < 2) {
    document.getElementById('summaryEmpty').style.display   = 'block';
    document.getElementById('summaryContent').style.display = 'none';
    document.getElementById('fasilitasBox').style.display   = 'none';
    return;
  }

  const tiers = hargaData[paketId] || [];
  let hargaPerOrang = 0;

  for (const h of tiers) {
    const maxOk = !h.max_orang || jumlah <= parseInt(h.max_orang);
    if (jumlah >= parseInt(h.min_orang) && maxOk) {
      hargaPerOrang = parseInt(h.harga_per_orang);
      break;
    }
  }

  if (!hargaPerOrang) {
    document.getElementById('summaryEmpty').style.display   = 'block';
    document.getElementById('summaryContent').style.display = 'none';
    document.getElementById('summaryEmpty').innerHTML = '<span class="summary-empty-icon">❌</span>Harga tidak tersedia untuk jumlah peserta ini.';
    return;
  }

  document.getElementById('summaryEmpty').style.display   = 'none';
  document.getElementById('summaryContent').style.display = 'block';

  const total = hargaPerOrang * jumlah;
  const tgl   = document.querySelector('[name="tanggal_trip"]').value;
  const tglFmt = tgl ? new Date(tgl).toLocaleDateString('id-ID', { weekday:'long', day:'numeric', month:'long', year:'numeric' }) : '–';

  document.getElementById('summaryRows').innerHTML = `
    <div class="summary-row">
      <span class="summary-row-label">📦 Harga/Orang</span>
      <span class="summary-row-value">${fmt(hargaPerOrang)}</span>
    </div>
    <div class="summary-row">
      <span class="summary-row-label">👥 Peserta</span>
      <span class="summary-row-value">${jumlah} orang</span>
    </div>
  `;

  document.getElementById('summaryTotal').textContent = fmt(total);

  const facs = fasilitasData[paketId] || [];
  if (facs.length > 0) {
    let html = '';
    facs.forEach(f => {
      html += `<div class="fasilitas-item"><span class="fasilitas-icon">${f.icon || '✅'}</span><span>${f.nama_fasilitas}</span></div>`;
    });
    document.getElementById('fasilitasList').innerHTML = html;
    document.getElementById('fasilitasBox').style.display = 'block';
  } else {
    document.getElementById('fasilitasBox').style.display = 'none';
  }

  const btn = document.getElementById('submitBtn');
  btn.textContent = `🏖️ Pesan ${jumlah} Orang – ${fmt(total)}`;
}

document.addEventListener('DOMContentLoaded', () => {
  const sel = document.getElementById('paketSelect');
  if (sel && sel.value) updateSummary();
  document.querySelector('[name="tanggal_trip"]')?.addEventListener('change', updateSummary);
});
</script>
</body>
</html>