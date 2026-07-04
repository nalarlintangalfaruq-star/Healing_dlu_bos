<?php
// htdocs/pari-adventure/admin/dashboard.php
require_once __DIR__ . '/includes/auth.php';

// Proteksi halaman admin
requireAdminLogin();

$admin = getCurrentAdmin();
$db = getDB();

// Query Statistik
$total_pesanan = $db->query("SELECT COUNT(*) FROM pemesanan")->fetchColumn() ?: 0;
$total_user = $db->query("SELECT COUNT(*) FROM users")->fetchColumn() ?: 0;
$total_paket = $db->query("SELECT COUNT(*) FROM paket_trip")->fetchColumn() ?: 0;
$pendapatan = $db->query("SELECT SUM(total_harga) FROM pemesanan WHERE status = 'paid'")->fetchColumn() ?: 0;
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Admin - Pari Adventure</title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;700&family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
    
    <style>
        /* RESET & BASE */
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f3f4f6; /* Warna abu-abu terang seperti di gambar */
            display: flex;
            min-height: 100vh;
            color: #333;
        }

        /* --- SIDEBAR --- */
        .sidebar {
            width: 250px;
            background-color: #0f172a; /* Biru sangat gelap (Navy) */
            color: white;
            display: flex;
            flex-direction: column;
            position: fixed;
            top: 0; left: 0; bottom: 0;
        }

        .sidebar-header {
            padding: 24px 20px;
        }

        .brand {
            font-family: 'Playfair Display', serif;
            font-size: 22px;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 8px;
        }

        .badge-role {
            background: linear-gradient(135deg, #ff7e5f, #feb47b);
            color: white;
            font-size: 10px;
            font-weight: 700;
            padding: 4px 12px;
            border-radius: 20px;
            display: inline-block;
            letter-spacing: 1px;
        }

        .menu-label {
            font-size: 11px;
            color: #64748b;
            font-weight: 600;
            padding: 0 20px;
            margin-top: 10px;
            margin-bottom: 10px;
            letter-spacing: 0.5px;
        }

        .nav-menu {
            list-style: none;
            padding: 0 10px;
        }

        .nav-menu li a {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 15px;
            color: #cbd5e1;
            text-decoration: none;
            font-size: 14px;
            border-radius: 8px;
            margin-bottom: 5px;
            transition: 0.3s;
        }

        .nav-menu li a:hover {
            background-color: rgba(255,255,255,0.05);
            color: white;
        }

        /* Active Menu Styling (Kotak gelap dengan garis orange di kiri) */
        .nav-menu li a.active {
            background-color: #1e293b;
            color: #fb923c;
            border-left: 4px solid #fb923c;
            border-radius: 0 8px 8px 0;
            font-weight: 500;
        }

        .user-profile {
            margin-top: auto;
            padding: 20px;
            border-top: 1px solid #1e293b;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .avatar {
            width: 40px; height: 40px;
            background-color: #1d4ed8;
            color: white;
            border-radius: 8px;
            display: flex; align-items: center; justify-content: center;
            font-weight: 600; font-size: 16px;
        }

        .user-info .name { font-size: 13px; font-weight: 600; display: block; }
        .user-info .email { font-size: 11px; color: #64748b; }


        /* --- MAIN CONTENT --- */
        .main-content {
            flex: 1;
            margin-left: 250px; /* Space untuk sidebar */
            display: flex;
            flex-direction: column;
        }

        /* TOPBAR */
        .topbar {
            background-color: white;
            height: 70px;
            padding: 0 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid #e2e8f0;
        }

        .topbar h1 {
            font-family: 'Playfair Display', serif;
            font-size: 20px;
            color: #0f172a;
        }

        .topbar-actions {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .notif-btn {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            width: 40px; height: 40px;
            display: flex; align-items: center; justify-content: center;
            cursor: pointer; position: relative;
        }

        .notif-dot {
            position: absolute; top: 8px; right: 8px;
            width: 8px; height: 8px;
            background-color: #ef4444;
            border-radius: 50%; border: 2px solid white;
        }

        .logout-btn {
            background-color: #f1f5f9;
            border: 1px solid #e2e8f0;
            padding: 8px 16px;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 500;
            color: #334155;
            cursor: pointer;
            text-decoration: none;
            display: flex; align-items: center; gap: 8px;
        }

        .logout-btn:hover { background-color: #e2e8f0; }


        /* DASHBOARD CONTENT */
        .dashboard-body {
            padding: 30px;
        }

        .section-title {
            font-size: 16px;
            font-weight: 600;
            margin-bottom: 20px;
            color: #1e293b;
        }

        /* STATS GRID (4 Kolom) */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background-color: white;
            padding: 25px 20px;
            border-radius: 12px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
            position: relative;
            overflow: hidden; /* Agar bulatan tidak keluar kotak */
        }

        /* Bulatan hiasan di pojok kanan atas */
        .stat-card::after {
            content: '';
            position: absolute;
            top: -20px; right: -20px;
            width: 80px; height: 80px;
            border-radius: 50%;
            opacity: 0.1;
        }

        /* Varian Warna Kartu */
        .card-blue .icon-box { background-color: #e0f2fe; }
        .card-blue::after { background-color: #0284c7; }

        .card-green .icon-box { background-color: #dcfce7; }
        .card-green::after { background-color: #16a34a; }

        .card-orange .icon-box { background-color: #ffedd5; }
        .card-orange::after { background-color: #ea580c; }

        .card-red .icon-box { background-color: #ffe4e6; }
        .card-red::after { background-color: #e11d48; }

        .icon-box {
            width: 45px; height: 45px;
            border-radius: 10px;
            display: flex; align-items: center; justify-content: center;
            font-size: 20px; margin-bottom: 15px;
        }

        .stat-value {
            font-family: 'Playfair Display', serif;
            font-size: 28px;
            font-weight: 700;
            color: #0f172a;
            margin-bottom: 5px;
        }

        .stat-label {
            font-size: 13px;
            color: #64748b;
        }

        /* WELCOME CARD */
        .welcome-card {
            background-color: white;
            border-radius: 12px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
        }

        .welcome-header {
            padding: 20px 25px;
            border-bottom: 1px solid #f1f5f9;
            font-size: 16px; font-weight: 600; color: #0f172a;
        }

        .welcome-body {
            padding: 25px;
            color: #64748b;
            font-size: 14px;
            line-height: 1.6;
        }
    </style>
</head>
<body>

    <aside class="sidebar">
        <div class="sidebar-header">
            <div class="brand">🐋 Pari Admin</div>
            <div class="badge-role"><?= strtoupper(htmlspecialchars($admin['role'] ?? 'SUPERADMIN')) ?></div>
        </div>
        
        <div class="menu-label">MENU UTAMA</div>
        <ul class="nav-menu">
    <li><a href="dashboard.php" class="active"><span class="icon">📊</span> Dashboard</a></li>
    <li><a href="paket_trip.php"><span class="icon">🏝️</span> Paket Trip</a></li>
    <li><a href="data_pemesanan.php"><span class="icon">📝</span> Data Pemesanan</a></li>
    <li><a href="data_pengguna.php"><span class="icon">👥</span> Data Pengguna</a></li>
</ul>

        <div class="user-profile">
            <div class="avatar"><?= strtoupper(substr($admin['nama'] ?? 'S', 0, 1)) ?></div>
            <div class="user-info">
                <span class="name"><?= htmlspecialchars($admin['nama'] ?? 'Super Admin') ?></span>
                <span class="email"><?= htmlspecialchars($admin['email'] ?? 'admin@pariadventure.com') ?></span>
            </div>
        </div>
    </aside>

    <main class="main-content">
        <header class="topbar">
            <h1>Dashboard Analitik</h1>
            <div class="topbar-actions">
                <div class="notif-btn">
                    🔔<div class="notif-dot"></div>
                </div>
                <a href="../logout.php" class="logout-btn" onclick="return confirm('Yakin ingin keluar?');">
                    🚪 Logout
                </a>
            </div>
        </header>

        <div class="dashboard-body">
            <div class="section-title">Ringkasan Hari Ini</div>

            <div class="stats-grid">
                <div class="stat-card card-blue">
                    <div class="icon-box">📝</div>
                    <div class="stat-value"><?= $total_pesanan ?></div>
                    <div class="stat-label">Total Pemesanan</div>
                </div>

                <div class="stat-card card-green">
                    <div class="icon-box">💰</div>
                    <div class="stat-value"><?= formatRupiah($pendapatan) ?></div>
                    <div class="stat-label">Pendapatan Bersih</div>
                </div>

                <div class="stat-card card-orange">
                    <div class="icon-box">👥</div>
                    <div class="stat-value"><?= $total_user ?></div>
                    <div class="stat-label">Pengguna Terdaftar</div>
                </div>

                <div class="stat-card card-red">
                    <div class="icon-box">🏝️</div>
                    <div class="stat-value"><?= $total_paket ?></div>
                    <div class="stat-label">Paket Aktif</div>
                </div>
            </div>

            <div class="welcome-card">
                <div class="welcome-header">
                    Selamat Datang, <?= htmlspecialchars($admin['nama'] ?? 'Super Admin') ?>!
                </div>
                <div class="welcome-body">
                    Ini adalah halaman dashboard admin Pari Adventure. Di sini Anda dapat mengelola seluruh data pemesanan, pengguna, dan paket trip yang tersedia. Silakan gunakan menu di sebelah kiri untuk menavigasi.
                </div>
            </div>

        </div>
    </main>

</body>
</html>