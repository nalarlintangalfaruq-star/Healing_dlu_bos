<?php
require_once __DIR__ . '/../includes/auth.php';
requireAdminLogin();

$admin = getCurrentAdmin();
$db = getDB();

/* ── PROSES AKSI ACC / BATAL / VERIF / TOLAK ─────────────────────── */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['aksi'])) {
    $aksi = $_POST['aksi'];

    /* Aksi lama: acc / batal booking */
    if (in_array($aksi, ['acc', 'batal']) && isset($_POST['id'])) {
        $id = (int)$_POST['id'];
        if ($aksi === 'acc') {
            $db->prepare("UPDATE pemesanan SET status = 'confirmed' WHERE id = ?")->execute([$id]);
        } elseif ($aksi === 'batal') {
            $db->prepare("UPDATE pemesanan SET status = 'cancelled' WHERE id = ?")->execute([$id]);
        }
    }

    /* Aksi baru: verifikasi pembayaran */
    elseif ($aksi === 'verif_bayar' && isset($_POST['pay_id'])) {
        $payId = (int)$_POST['pay_id'];
        $db->prepare("UPDATE pembayaran SET status = 'verified' WHERE id = ?")->execute([$payId]);
        /* Update status pemesanan → paid */
        $stmtP = $db->prepare("SELECT pemesanan_id FROM pembayaran WHERE id = ?");
        $stmtP->execute([$payId]);
        $payRow = $stmtP->fetch();
        if ($payRow) {
            $db->prepare("UPDATE pemesanan SET status = 'paid' WHERE id = ?")->execute([$payRow['pemesanan_id']]);
        }
    }

    /* Aksi baru: tolak pembayaran */
    elseif ($aksi === 'tolak_bayar' && isset($_POST['pay_id'])) {
        $payId = (int)$_POST['pay_id'];
        $db->prepare("UPDATE pembayaran SET status = 'rejected' WHERE id = ?")->execute([$payId]);
    }

    header("Location: data_pemesanan.php");
    exit;
}

/* ── QUERY UTAMA — ditambah LEFT JOIN pembayaran (terbaru per booking) ── */
$query = "SELECT p.*, pt.nama_paket,
          pb.id          AS pay_id,
          pb.jumlah      AS pay_jumlah,
          pb.metode      AS pay_metode,
          pb.bank        AS pay_bank,
          pb.no_rekening AS pay_norek,
          pb.bukti_bayar AS pay_bukti,
          pb.status      AS pay_status,
          pb.created_at  AS pay_date
          FROM pemesanan p
          JOIN paket_trip pt ON p.paket_id = pt.id
          LEFT JOIN pembayaran pb ON pb.pemesanan_id = p.id
            AND pb.id = (SELECT MAX(id) FROM pembayaran WHERE pemesanan_id = p.id)
          ORDER BY p.created_at DESC";
$pemesanan = $db->query($query)->fetchAll();

// Hitung statistik
$total_all       = count($pemesanan);
$total_paid      = count(array_filter($pemesanan, fn($r) => in_array($r['status'], ['paid','completed'])));
$total_pending   = count(array_filter($pemesanan, fn($r) => $r['status'] === 'pending'));
$total_cancelled = count(array_filter($pemesanan, fn($r) => $r['status'] === 'cancelled'));

// Warna avatar berputar
$avatar_colors = ['#7B3FE4','#F041A0','#FF7A00','#00D4B1','#3B82F6','#FFD700','#E4403F','#22D3EE'];

function getInitials($name) {
    $parts = explode(' ', trim($name));
    $initials = '';
    foreach (array_slice($parts, 0, 2) as $p) $initials .= strtoupper($p[0]);
    return $initials;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Data Pemesanan - Pari Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Fredoka+One&family=Nunito:wght@400;600;700;800;900&display=swap" rel="stylesheet">
    <style>
        /* ================================================
           STUMBLE GUYS THEME — MATCHED DENGAN PAKET TRIP
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
        html { scroll-behavior: smooth; height: 100%; }
        
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

        /* ============ BODY CONTENT ============ */
        .dashboard-body { padding: 24px 28px; }

        /* ── STAT CARDS ── */
        .stat-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 16px;
            margin-bottom: 24px;
        }
        .stat-card {
            background: var(--card-bg);
            border: 1.5px solid var(--card-bd);
            border-radius: var(--r-lg);
            padding: 16px;
            text-align: center;
            position: relative;
            overflow: hidden;
            animation: popIn 0.5s cubic-bezier(.34,1.56,.64,1) both;
            transition: transform 0.2s, box-shadow 0.2s;
            cursor: default;
        }
        .stat-card:hover { transform: translateY(-4px); box-shadow: 0 0 20px rgba(155,89,182,.15); }
        .stat-card::before {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0;
            height: 3px;
        }
        .stat-card.c-yellow::before { background: var(--yellow); box-shadow: 0 0 10px var(--yellow); }
        .stat-card.c-teal::before   { background: var(--cyan);   box-shadow: 0 0 10px var(--cyan); }
        .stat-card.c-orange::before { background: var(--orange); box-shadow: 0 0 10px var(--orange); }
        .stat-card.c-pink::before   { background: var(--pink);   box-shadow: 0 0 10px var(--pink); }
        
        .stat-card:nth-child(1) { animation-delay: 0.1s; }
        .stat-card:nth-child(2) { animation-delay: 0.2s; }
        .stat-card:nth-child(3) { animation-delay: 0.3s; }
        .stat-card:nth-child(4) { animation-delay: 0.4s; }
        
        @keyframes popIn {
            0%   { transform: scale(0.6) translateY(20px); opacity: 0; }
            100% { transform: scale(1) translateY(0); opacity: 1; }
        }
        .stat-icon { font-size: 26px; margin-bottom: 6px; animation: iconBounce 2.5s ease-in-out infinite; }
        .stat-val {
            font-family: 'Fredoka One', cursive;
            font-size: 28px;
            font-weight: 900;
            line-height: 1;
        }
        .stat-card.c-yellow .stat-val { color: var(--yellow); text-shadow: 0 0 10px rgba(255,214,0,0.5); }
        .stat-card.c-teal   .stat-val { color: var(--cyan);   text-shadow: 0 0 10px rgba(0,229,255,0.5); }
        .stat-card.c-orange .stat-val { color: var(--orange); text-shadow: 0 0 10px rgba(255,109,0,0.5); }
        .stat-card.c-pink   .stat-val { color: var(--pink);   text-shadow: 0 0 10px rgba(255,79,163,0.5); }
        .stat-label { font-size: 10px; color: var(--dim); font-weight: 800; text-transform: uppercase; letter-spacing: 0.6px; margin-top: 6px; }

        /* ============ TABLE ============ */
        .sg-card {
          background: var(--card-bg);
          border: 1.5px solid var(--card-bd);
          border-radius: var(--r-lg);
          overflow: hidden;
          margin-bottom: 28px;
          position: relative;
          transition: box-shadow .3s;
        }
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
        .card-icon.cyan   { background: linear-gradient(135deg,#00e5ff,#00b8d4); box-shadow: 0 4px 16px rgba(0,229,255,.35); }
        .card-title {
          font-family: 'Fredoka One', cursive;
          font-size: 1.1rem; color: #fff; line-height: 1;
        }
        .card-sub { font-size: .72rem; color: var(--dim); margin-top: 3px; }
        .card-body { padding: 18px 24px 24px; }
        
        .table-wrap { overflow-x: auto; border-radius: var(--r); }
        .table-data {
          width: 100%; border-collapse: collapse;
          min-width: 700px;
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
        .table-data tbody tr:hover td {
          background: rgba(155,89,182,.1);
          color: #fff;
        }
        @keyframes rowFadeIn { from{opacity:0;transform:translateX(-12px)} to{opacity:1;transform:none} }

        /* ── CELLS FORMATTING ── */
        .booking-code {
            font-family: 'Fredoka One', cursive;
            font-size: .8rem;
            color: var(--yellow);
            text-shadow: 0 0 8px rgba(255,214,0,0.4);
            letter-spacing: 0.5px;
        }
        .pemesan-cell { display: flex; align-items: center; gap: 10px; }
        .player-avatar-tb {
            width: 34px; height: 34px;
            border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            font-family: 'Fredoka One', cursive;
            font-size: 12px;
            color: #fff;
            flex-shrink: 0;
            border: 2px solid rgba(255,255,255,0.2);
            box-shadow: 0 2px 8px rgba(0,0,0,0.2);
        }
        .pemesan-name { font-weight: 800; color: #fff; font-size: .85rem; }
        .pemesan-phone { font-size: .65rem; color: var(--dim); margin-top: 1px; }
        
        .peserta-pill {
            background: rgba(0,229,255,0.15);
            color: var(--cyan);
            border-radius: 50px;
            padding: 3px 10px;
            font-size: .75rem;
            font-weight: 800;
            border: 1px solid rgba(0,229,255,0.3);
        }
        .harga {
            font-family: 'Fredoka One', cursive;
            color: var(--green);
            font-size: .85rem;
        }

        .badge-status {
            display: inline-flex; align-items: center; gap: 5px;
            font-family: 'Fredoka One', cursive; font-size: .7rem;
            padding: .24rem .75rem; border-radius: 50px; color: #fff;
        }
        .badge-paid {
            background: linear-gradient(135deg,#00c853,#64dd17);
            box-shadow: 0 3px 12px rgba(0,200,83,.4);
        }
        .badge-pending {
            background: linear-gradient(135deg,#f9a825,#ffd600);
            box-shadow: 0 3px 12px rgba(249,168,37,.4);
            color: #1a0b2e;
        }
        .badge-cancelled {
            background: linear-gradient(135deg,#ff4fa3,#e91e8c);
            box-shadow: 0 3px 12px rgba(255,79,163,.35);
        }

        /* ── TOMBOL AKSI ── */
        .btn-aksi {
            display: inline-flex; align-items: center; gap: 5px;
            font-family: 'Fredoka One', cursive; font-size: .7rem;
            padding: .3rem .85rem; border-radius: 50px;
            border: none; cursor: pointer; color: #fff;
            transition: all .2s; text-decoration: none;
        }
        .btn-acc {
            background: linear-gradient(135deg,#00c853,#00e676);
            box-shadow: 0 3px 10px rgba(0,200,83,.4);
        }
        .btn-acc:hover { transform: translateY(-2px); box-shadow: 0 6px 16px rgba(0,200,83,.5); }
        .btn-batal {
            background: linear-gradient(135deg,#ff4fa3,#e91e8c);
            box-shadow: 0 3px 10px rgba(255,79,163,.4);
        }
        .btn-batal:hover { transform: translateY(-2px); box-shadow: 0 6px 16px rgba(255,79,163,.5); }
        .aksi-wrap { display: flex; gap: 6px; flex-wrap: wrap; }

        .empty-row td {
            text-align: center;
            padding: 40px;
            color: var(--dim);
            font-size: .9rem;
            font-weight: 700;
        }
        .empty-icon { font-size: 3.5rem; display: block; margin-bottom: 12px; animation: iconBounce 2s ease-in-out infinite; }

        @media (max-width: 900px) {
          .sidebar { transform: translateX(-100%); }
          .main-content { margin-left: 0; }
          .stat-grid { grid-template-columns: repeat(2, 1fr); }
          .dashboard-body { padding: 18px; }
          .col-hide { display: none; }
        }

        /* ============================================================
           TAMBAHAN BARU — Bukti Bayar Badge & Tombol Lihat
           ============================================================ */

        /* Badge status bukti bayar di kolom tabel */
        .bukti-badge {
            display: inline-flex; align-items: center; gap: 4px;
            font-family: 'Fredoka One', cursive; font-size: .68rem;
            padding: .22rem .7rem; border-radius: 50px;
            white-space: nowrap;
        }
        .bukti-none     { background: rgba(255,255,255,.1);  color: var(--dim); }
        .bukti-pending  { background: linear-gradient(135deg,#f9a825,#ffd600); color: #1a0b2e; box-shadow: 0 2px 8px rgba(249,168,37,.3); animation: buktiPing 2s ease-in-out infinite; }
        .bukti-verified { background: linear-gradient(135deg,#00c853,#00e676); color: #fff; box-shadow: 0 2px 8px rgba(0,200,83,.3); }
        .bukti-rejected { background: linear-gradient(135deg,#ff4fa3,#e91e8c); color: #fff; box-shadow: 0 2px 8px rgba(255,79,163,.3); }
        @keyframes buktiPing {
          0%,100%{box-shadow:0 2px 8px rgba(249,168,37,.3)}
          50%{box-shadow:0 2px 16px rgba(249,168,37,.7)}
        }

        /* Tombol Lihat Bukti */
        .btn-lihat {
            background: linear-gradient(135deg, var(--cyan2), var(--purple));
            box-shadow: 0 3px 10px rgba(0,229,255,.3);
        }
        .btn-lihat:hover { transform: translateY(-2px); box-shadow: 0 6px 16px rgba(0,229,255,.45); }

        /* ============================================================
           MODAL BUKTI PEMBAYARAN
           ============================================================ */
        .modal-overlay {
          position: fixed; inset: 0; z-index: 9000;
          background: rgba(0,0,0,.75);
          backdrop-filter: blur(6px);
          -webkit-backdrop-filter: blur(6px);
          display: none; align-items: center; justify-content: center;
          padding: 20px;
        }
        .modal-overlay.open { display: flex; }

        .modal-box {
          background: var(--bg2);
          border: 2px solid rgba(155,89,182,.45);
          border-radius: var(--r-lg);
          width: 100%; max-width: 500px;
          max-height: 88vh; overflow-y: auto;
          position: relative;
          animation: modalPop .3s cubic-bezier(.34,1.56,.64,1) both;
        }
        @keyframes modalPop {
          from { transform: scale(.85) translateY(20px); opacity: 0; }
          to   { transform: scale(1)   translateY(0);    opacity: 1; }
        }

        /* Rainbow top bar sama dengan card lain */
        .modal-box::before {
          content: '';
          position: absolute; top: 0; left: 0; right: 0; height: 3px;
          background: linear-gradient(90deg,#ff4fa3,#ffd600,#00e5ff,#00e676,#ff6d00,#ff4fa3);
          background-size: 200% 100%;
          animation: rainbowSlide 3s linear infinite;
          border-radius: var(--r-lg) var(--r-lg) 0 0;
        }

        .modal-header {
          background: linear-gradient(135deg, #9b59b6, #00b8d4);
          padding: 16px 20px;
          display: flex; align-items: center; justify-content: space-between;
          position: sticky; top: 0; z-index: 1;
        }
        .modal-title {
          font-family: 'Fredoka One', cursive;
          font-size: 1rem; color: #fff;
          display: flex; align-items: center; gap: 7px;
        }
        .modal-close {
          width: 30px; height: 30px; border-radius: 50%;
          background: rgba(255,255,255,.2);
          border: 1px solid rgba(255,255,255,.3);
          color: #fff; cursor: pointer; font-size: .9rem;
          display: flex; align-items: center; justify-content: center;
          transition: all .2s;
        }
        .modal-close:hover { background: rgba(255,255,255,.35); transform: rotate(90deg); }

        .modal-body { padding: 20px; }

        /* Info grid di dalam modal */
        .modal-info-grid {
          display: grid; grid-template-columns: 1fr 1fr; gap: 10px;
          margin-bottom: 16px;
        }
        .modal-info-item {
          background: rgba(255,255,255,.05);
          border: 1px solid rgba(255,255,255,.08);
          border-radius: 10px; padding: 10px 14px;
        }
        .modal-info-label {
          font-size: .64rem; font-weight: 800;
          color: var(--dim); text-transform: uppercase;
          letter-spacing: .09em; margin-bottom: 4px;
        }
        .modal-info-value {
          font-size: .875rem; font-weight: 800; color: #fff;
          word-break: break-word;
        }

        /* Preview gambar bukti */
        .bukti-preview-wrap {
          background: rgba(0,0,0,.3);
          border: 1.5px solid rgba(155,89,182,.3);
          border-radius: var(--r);
          padding: 12px;
          margin-bottom: 16px;
          text-align: center;
        }
        .bukti-preview-wrap img {
          max-width: 100%; max-height: 280px;
          border-radius: 8px; cursor: zoom-in;
          transition: transform .2s;
        }
        .bukti-preview-wrap img:hover { transform: scale(1.02); }

        /* Aksi verif/tolak di modal */
        .modal-aksi-row {
          display: flex; gap: 10px; margin-top: 16px;
        }
        .modal-aksi-row form { flex: 1; }
        .btn-aksi-full {
          width: 100%; justify-content: center; padding: .55rem 1rem;
        }

        /* Divider dalam modal */
        .modal-divider {
          height: 1px;
          background: rgba(255,255,255,.08);
          margin: 14px 0;
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
    <li><a href="data_pemesanan.php" class="active"><span>📋</span> Data Pemesanan</a></li>
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
      <span class="topbar-icon">📋</span>
      Data Pemesanan
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

        <div class="stat-grid">
            <div class="stat-card c-yellow">
                <div class="stat-icon">🎟️</div>
                <div class="stat-val"><?= $total_all ?></div>
                <div class="stat-label">Total Booking</div>
            </div>
            <div class="stat-card c-teal">
                <div class="stat-icon">✅</div>
                <div class="stat-val"><?= $total_paid ?></div>
                <div class="stat-label">Paid / Done</div>
            </div>
            <div class="stat-card c-orange">
                <div class="stat-icon">⏳</div>
                <div class="stat-val"><?= $total_pending ?></div>
                <div class="stat-label">Pending</div>
            </div>
            <div class="stat-card c-pink">
                <div class="stat-icon">❌</div>
                <div class="stat-val"><?= $total_cancelled ?></div>
                <div class="stat-label">Cancelled</div>
            </div>
        </div>

        <div class="sg-card">
            <div class="card-header">
                <div class="card-icon cyan">🏆</div>
                <div>
                <div class="card-title">Arena Booking — All Players</div>
                <div class="card-sub">Daftar pesanan tiket masuk</div>
                </div>
            </div>
            
            <div class="card-body" style="padding-top:14px">
                <div class="table-wrap">
                    <table class="table-data">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Kode Booking</th>
                                <th>Pemesan</th>
                                <th class="col-hide">Paket Trip</th>
                                <th class="col-hide">Tanggal Trip</th>
                                <th>Peserta</th>
                                <th>Total Bayar</th>
                                <th>Bukti Bayar</th><!-- BARU -->
                                <th>Status</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($pemesanan)): ?>
                            <tr class="empty-row">
                                <td colspan="10">
                                    <span class="empty-icon">🏁</span>
                                    Belum ada data pemesanan.
                                </td>
                            </tr>
                            <?php else: ?>
                            <?php foreach ($pemesanan as $i => $row):
                                $color    = $avatar_colors[$i % count($avatar_colors)];
                                $initials = getInitials($row['nama_pemesan']);
                                $status   = $row['status'];

                                /* Badge status booking */
                                if ($status === 'paid' || $status === 'completed') {
                                    $badgeClass = 'badge-paid';
                                } elseif ($status === 'cancelled') {
                                    $badgeClass = 'badge-cancelled';
                                } else {
                                    $badgeClass = 'badge-pending';
                                }

                                /* ── BARU: Badge bukti bayar ── */
                                $payStatus = $row['pay_status'] ?? null;
                                if (!$row['pay_id']) {
                                    $buktiClass = 'bukti-none';
                                    $buktiBadge = '📂 Belum Upload';
                                } elseif ($payStatus === 'pending') {
                                    $buktiClass = 'bukti-pending';
                                    $buktiBadge = '⏳ Menunggu';
                                } elseif ($payStatus === 'verified') {
                                    $buktiClass = 'bukti-verified';
                                    $buktiBadge = '✅ Terverifikasi';
                                } elseif ($payStatus === 'rejected') {
                                    $buktiClass = 'bukti-rejected';
                                    $buktiBadge = '❌ Ditolak';
                                } else {
                                    $buktiClass = 'bukti-none';
                                    $buktiBadge = '📂 Belum Upload';
                                }

                                /* ── BARU: Payload JSON untuk modal ── */
                                $modalPayload = htmlspecialchars(json_encode([
                                    'kode'    => $row['kode_booking'],
                                    'payId'   => (int)($row['pay_id'] ?? 0),
                                    'metode'  => $row['pay_metode'] ?? '',
                                    'jumlah'  => $row['pay_jumlah'] ? formatRupiah((float)$row['pay_jumlah']) : '-',
                                    'bank'    => $row['pay_bank'] ?? '',
                                    'norek'   => $row['pay_norek'] ?? '',
                                    'bukti'   => $row['pay_bukti'] ?? '',
                                    'status'  => $row['pay_status'] ?? '',
                                    'tanggal' => $row['pay_date']
                                                 ? date('d M Y H:i', strtotime($row['pay_date']))
                                                 : '-',
                                ]), ENT_QUOTES, 'UTF-8');
                            ?>
                            <tr style="animation-delay:<?= $i * 0.07 ?>s">
                                <td><span style="color:var(--dim); font-weight:800;"><?= $i+1 ?></span></td>
                                <td><span class="booking-code">#<?= htmlspecialchars($row['kode_booking']) ?></span></td>
                                <td>
                                    <div class="pemesan-cell">
                                        <div class="player-avatar-tb" style="background:<?= $color ?>"><?= $initials ?></div>
                                        <div>
                                            <div class="pemesan-name"><?= htmlspecialchars($row['nama_pemesan']) ?></div>
                                            <div class="pemesan-phone">📞 <?= htmlspecialchars($row['telepon_pemesan']) ?></div>
                                        </div>
                                    </div>
                                </td>
                                <td class="col-hide"><?= htmlspecialchars($row['nama_paket']) ?></td>
                                <td class="col-hide"><?= date('d M Y', strtotime($row['tanggal_trip'])) ?></td>
                                <td><span class="peserta-pill"><?= $row['jumlah_peserta'] ?> Org</span></td>
                                <td><span class="harga"><?= formatRupiah($row['total_harga']) ?></span></td>

                                <!-- BARU: Kolom Bukti Bayar -->
                                <td>
                                    <span class="bukti-badge <?= $buktiClass ?>"><?= $buktiBadge ?></span>
                                </td>

                                <td><span class="badge-status <?= $badgeClass ?>"><?= strtoupper($status) ?></span></td>
                                <td>
                                    <div class="aksi-wrap">
                                    <?php if ($status !== 'confirmed' && $status !== 'paid' && $status !== 'completed' && $status !== 'cancelled'): ?>
                                        <form method="POST" style="display:inline">
                                            <input type="hidden" name="id" value="<?= $row['id'] ?>">
                                            <input type="hidden" name="aksi" value="acc">
                                            <button type="submit" class="btn-aksi btn-acc" onclick="return confirm('Konfirmasi booking ini?')">✅ Acc</button>
                                        </form>
                                        <form method="POST" style="display:inline">
                                            <input type="hidden" name="id" value="<?= $row['id'] ?>">
                                            <input type="hidden" name="aksi" value="batal">
                                            <button type="submit" class="btn-aksi btn-batal" onclick="return confirm('Batalkan booking ini?')">❌ Batal</button>
                                        </form>
                                    <?php elseif ($status === 'confirmed'): ?>
                                        <form method="POST" style="display:inline">
                                            <input type="hidden" name="id" value="<?= $row['id'] ?>">
                                            <input type="hidden" name="aksi" value="batal">
                                            <button type="submit" class="btn-aksi btn-batal" onclick="return confirm('Batalkan booking ini?')">❌ Batal</button>
                                        </form>
                                    <?php else: ?>
                                        <span style="font-size:.7rem;color:var(--dim);">—</span>
                                    <?php endif; ?>

                                    <!-- BARU: Tombol Lihat Bukti (selalu tampil) -->
                                    <button class="btn-aksi btn-lihat"
                                            data-p="<?= $modalPayload ?>"
                                            onclick="bukaModal(JSON.parse(this.dataset.p))">
                                        👁️ Lihat
                                    </button>

                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </div><!-- /.dashboard-body -->
</main>

</div><!-- /.page -->

<!-- ============================================================
     MODAL BUKTI PEMBAYARAN (BARU)
     ============================================================ -->
<div class="modal-overlay" id="modalBukti" onclick="tutupModal(event)">
  <div class="modal-box" id="modalBox">
    <div class="modal-header">
      <div class="modal-title">💳 Bukti Pembayaran</div>
      <button class="modal-close" onclick="tutupModal()">&times;</button>
    </div>
    <div class="modal-body" id="modalBody">
      <!-- Diisi oleh JavaScript -->
    </div>
  </div>
</div>

<script>
/* === CONFETTI (existing) === */
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

/* ============================================================
   MODAL BUKTI PEMBAYARAN — BARU
   ============================================================ */
const BASE_URL = '<?= BASE_URL ?>';

function bukaModal(data) {
  /* ── Tentukan status badge ── */
  let statusBadge = '';
  if (!data.payId) {
    statusBadge = '<span class="bukti-badge bukti-none">📂 Belum ada pembayaran diupload</span>';
  } else if (data.status === 'pending') {
    statusBadge = '<span class="bukti-badge bukti-pending">⏳ Menunggu Verifikasi</span>';
  } else if (data.status === 'verified') {
    statusBadge = '<span class="bukti-badge bukti-verified">✅ Terverifikasi</span>';
  } else if (data.status === 'rejected') {
    statusBadge = '<span class="bukti-badge bukti-rejected">❌ Ditolak</span>';
  }

  /* ── Preview bukti ── */
  let buktiHTML = '';
  if (!data.bukti) {
    buktiHTML = `
      <div class="bukti-preview-wrap" style="padding:30px">
        <div style="font-size:2.5rem;margin-bottom:8px">📂</div>
        <div style="color:var(--dim);font-size:.85rem;font-weight:700">User belum mengupload bukti pembayaran</div>
      </div>`;
  } else {
    const buktiURL  = BASE_URL + '/uploads/' + data.bukti;
    const isPDF     = data.bukti.toLowerCase().endsWith('.pdf');
    if (isPDF) {
      buktiHTML = `
        <div class="bukti-preview-wrap">
          <div style="font-size:2.5rem;margin-bottom:10px">📄</div>
          <div style="color:var(--dim);font-size:.82rem;margin-bottom:12px">File PDF</div>
          <a href="${buktiURL}" target="_blank" class="btn-aksi btn-acc" style="display:inline-flex">
            📄 Buka PDF
          </a>
        </div>`;
    } else {
      buktiHTML = `
        <div class="bukti-preview-wrap">
          <img src="${buktiURL}"
               alt="Bukti Transfer"
               onclick="window.open('${buktiURL}','_blank')"
               title="Klik untuk buka di tab baru"
               onerror="this.outerHTML='<div style=color:var(--dim);font-size:.82rem>⚠️ Gambar tidak dapat ditampilkan</div>'">
        </div>`;
    }
  }

  /* ── Tombol Verifikasi / Tolak (hanya jika status pending) ── */
  let aksiHTML = '';
  if (data.payId && data.status === 'pending') {
    aksiHTML = `
      <div class="modal-aksi-row">
        <form method="POST" style="flex:1">
          <input type="hidden" name="aksi"   value="verif_bayar">
          <input type="hidden" name="pay_id" value="${data.payId}">
          <button type="submit" class="btn-aksi btn-acc btn-aksi-full"
                  onclick="return confirm('Verifikasi pembayaran ini? Status booking akan berubah menjadi PAID.')">
            ✅ Verifikasi
          </button>
        </form>
        <form method="POST" style="flex:1">
          <input type="hidden" name="aksi"   value="tolak_bayar">
          <input type="hidden" name="pay_id" value="${data.payId}">
          <button type="submit" class="btn-aksi btn-batal btn-aksi-full"
                  onclick="return confirm('Tolak pembayaran ini?')">
            ❌ Tolak
          </button>
        </form>
      </div>`;
  }

  /* ── Rakitan konten modal ── */
  document.getElementById('modalBody').innerHTML = `
    <div style="margin-bottom:14px">
      <div class="modal-info-label" style="margin-bottom:5px">Kode Booking</div>
      <span class="booking-code" style="font-size:.95rem">#${data.kode}</span>
    </div>

    <div class="modal-divider"></div>

    <div class="modal-info-grid">
      <div class="modal-info-item">
        <div class="modal-info-label">Metode</div>
        <div class="modal-info-value">${data.metode || '—'}</div>
      </div>
      <div class="modal-info-item">
        <div class="modal-info-label">Jumlah Transfer</div>
        <div class="modal-info-value" style="color:var(--green)">${data.jumlah}</div>
      </div>
      <div class="modal-info-item">
        <div class="modal-info-label">Bank</div>
        <div class="modal-info-value">${data.bank || '—'}</div>
      </div>
      <div class="modal-info-item">
        <div class="modal-info-label">No. Rekening</div>
        <div class="modal-info-value">${data.norek || '—'}</div>
      </div>
      <div class="modal-info-item" style="grid-column:1/-1">
        <div class="modal-info-label">Waktu Upload</div>
        <div class="modal-info-value">${data.tanggal}</div>
      </div>
    </div>

    <div class="modal-divider"></div>

    <div style="margin-bottom:10px">
      <div class="modal-info-label" style="margin-bottom:7px">Status Pembayaran</div>
      ${statusBadge}
    </div>

    <div class="modal-info-label" style="margin-bottom:8px">Foto / File Bukti Transfer</div>
    ${buktiHTML}
    ${aksiHTML}
  `;

  document.getElementById('modalBukti').classList.add('open');
}

function tutupModal(e) {
  /* Tutup hanya jika klik di overlay (bukan modal box) */
  if (e && e.target !== document.getElementById('modalBukti')) return;
  document.getElementById('modalBukti').classList.remove('open');
}

/* Tombol close di header */
document.querySelector('.modal-close')?.addEventListener('click', function() {
  document.getElementById('modalBukti').classList.remove('open');
});

/* Tutup modal dengan tombol Escape */
document.addEventListener('keydown', function(e) {
  if (e.key === 'Escape') document.getElementById('modalBukti').classList.remove('open');
});
</script>

</body>
</html>