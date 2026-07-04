<?php
// partials/sidebar.php
// Template sidebar untuk dashboard user
// Path sudah diperbaiki - menggunakan relative path untuk link internal

$currentPage = basename($_SERVER['PHP_SELF']);
?>
<aside class="dashboard-sidebar" style="
  position: fixed;
  left: 0;
  top: 0;
  width: 260px;
  height: 100vh;
  background: linear-gradient(135deg, #0a3d62 0%, #1a5f7a 100%);
  padding: 20px;
  overflow-y: auto;
  box-shadow: 2px 0 10px rgba(0,0,0,0.1);
  z-index: 1000;
  font-family: 'Poppins', sans-serif;
">

  <!-- LOGO / HEADER -->
  <div style="
    text-align: center;
    padding-bottom: 24px;
    border-bottom: 2px solid rgba(255,255,255,0.1);
    margin-bottom: 24px;
  ">
    <div style="
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 10px;
      margin-bottom: 8px;
    ">
      <div style="
        font-size: 28px;
      ">🏝️</div>
      <div style="
        font-size: 16px;
        font-weight: 700;
        color: #fff;
      ">Pari</div>
    </div>
    <div style="
      font-size: 11px;
      color: rgba(255,255,255,0.7);
      text-transform: uppercase;
      letter-spacing: 1px;
    ">USER PANEL</div>
  </div>

  <!-- USER INFO -->
  <div style="
    background: rgba(255,255,255,0.08);
    border-radius: 12px;
    padding: 16px;
    margin-bottom: 24px;
    border: 1px solid rgba(255,255,255,0.1);
  ">
    <div style="
      width: 48px;
      height: 48px;
      background: linear-gradient(135deg, #00cdff, #4ecdc4);
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 24px;
      margin-bottom: 12px;
      margin-left: auto;
      margin-right: auto;
    ">👤</div>
    <div style="
      color: #fff;
      font-weight: 700;
      font-size: 14px;
      text-align: center;
      margin-bottom: 4px;
    ">
      <?php if(isset($_SESSION['user_name'])): ?>
        <?= htmlspecialchars(explode(' ', $_SESSION['user_name'])[0]) ?>
      <?php else: ?>
        User
      <?php endif; ?>
    </div>
    <div style="
      color: rgba(255,255,255,0.6);
      font-size: 12px;
      text-align: center;
    ">Member</div>
  </div>

  <!-- MENU UTAMA -->
  <div style="margin-bottom: 24px;">
    <div style="
      font-size: 11px;
      font-weight: 700;
      color: rgba(255,255,255,0.5);
      text-transform: uppercase;
      letter-spacing: 0.8px;
      margin-bottom: 12px;
      padding-left: 12px;
    ">MENU UTAMA</div>

    <!-- Dashboard Link -->
    <a href="dashboard.php" style="
      display: flex;
      align-items: center;
      gap: 12px;
      padding: 12px 16px;
      margin-bottom: 8px;
      background: <?= $currentPage === 'dashboard.php' ? 'rgba(255,255,255,0.2)' : 'transparent' ?>;
      border-left: 3px solid <?= $currentPage === 'dashboard.php' ? '#00cdff' : 'transparent' ?>;
      color: #fff;
      text-decoration: none;
      border-radius: 8px;
      transition: all 0.2s;
      font-size: 14px;
      font-weight: 500;
    " onmouseover="this.style.background='rgba(255,255,255,0.1)'" onmouseout="this.style.background='<?= $currentPage === 'dashboard.php' ? 'rgba(255,255,255,0.2)' : 'transparent' ?>'">
      <span style="font-size: 18px;">📊</span>
      <span>Dashboard</span>
    </a>

    <!-- Pesan Trip Link -->
    <a href="booking.php" style="
      display: flex;
      align-items: center;
      gap: 12px;
      padding: 12px 16px;
      margin-bottom: 8px;
      background: <?= $currentPage === 'booking.php' ? 'rgba(255,255,255,0.2)' : 'transparent' ?>;
      border-left: 3px solid <?= $currentPage === 'booking.php' ? '#00cdff' : 'transparent' ?>;
      color: #fff;
      text-decoration: none;
      border-radius: 8px;
      transition: all 0.2s;
      font-size: 14px;
      font-weight: 500;
    " onmouseover="this.style.background='rgba(255,255,255,0.1)'" onmouseout="this.style.background='<?= $currentPage === 'booking.php' ? 'rgba(255,255,255,0.2)' : 'transparent' ?>'">
      <span style="font-size: 18px;">🎫</span>
      <span>Pesan Trip</span>
    </a>

    <!-- Riwayat Booking Link -->
    <a href="riwayat.php" style="
      display: flex;
      align-items: center;
      gap: 12px;
      padding: 12px 16px;
      margin-bottom: 8px;
      background: <?= $currentPage === 'riwayat.php' ? 'rgba(255,255,255,0.2)' : 'transparent' ?>;
      border-left: 3px solid <?= $currentPage === 'riwayat.php' ? '#00cdff' : 'transparent' ?>;
      color: #fff;
      text-decoration: none;
      border-radius: 8px;
      transition: all 0.2s;
      font-size: 14px;
      font-weight: 500;
    " onmouseover="this.style.background='rgba(255,255,255,0.1)'" onmouseout="this.style.background='<?= $currentPage === 'riwayat.php' ? 'rgba(255,255,255,0.2)' : 'transparent' ?>'">
      <span style="font-size: 18px;">📋</span>
      <span>Riwayat Booking</span>
    </a>

    <!-- Upload Pembayaran Link -->
    <a href="pembayaran.php" style="
      display: flex;
      align-items: center;
      gap: 12px;
      padding: 12px 16px;
      margin-bottom: 8px;
      background: <?= $currentPage === 'pembayaran.php' ? 'rgba(255,255,255,0.2)' : 'transparent' ?>;
      border-left: 3px solid <?= $currentPage === 'pembayaran.php' ? '#00cdff' : 'transparent' ?>;
      color: #fff;
      text-decoration: none;
      border-radius: 8px;
      transition: all 0.2s;
      font-size: 14px;
      font-weight: 500;
    " onmouseover="this.style.background='rgba(255,255,255,0.1)'" onmouseout="this.style.background='<?= $currentPage === 'pembayaran.php' ? 'rgba(255,255,255,0.2)' : 'transparent' ?>'">
      <span style="font-size: 18px;">💳</span>
      <span>Upload Pembayaran</span>
    </a>

    <!-- Beri Ulasan Link -->
    <a href="testimoni.php" style="
      display: flex;
      align-items: center;
      gap: 12px;
      padding: 12px 16px;
      margin-bottom: 8px;
      background: <?= $currentPage === 'testimoni.php' ? 'rgba(255,255,255,0.2)' : 'transparent' ?>;
      border-left: 3px solid <?= $currentPage === 'testimoni.php' ? '#00cdff' : 'transparent' ?>;
      color: #fff;
      text-decoration: none;
      border-radius: 8px;
      transition: all 0.2s;
      font-size: 14px;
      font-weight: 500;
    " onmouseover="this.style.background='rgba(255,255,255,0.1)'" onmouseout="this.style.background='<?= $currentPage === 'testimoni.php' ? 'rgba(255,255,255,0.2)' : 'transparent' ?>'">
      <span style="font-size: 18px;">⭐</span>
      <span>Beri Ulasan</span>
    </a>
  </div>

  <!-- AKUN -->
  <div style="margin-bottom: 24px;">
    <!-- Kembali ke Website Link -->
    <a href="../index.php" style="
      display: flex;
      align-items: center;
      gap: 12px;
      padding: 12px 16px;
      margin-bottom: 8px;
      color: #fff;
      text-decoration: none;
      border-radius: 8px;
      transition: all 0.2s;
      font-size: 14px;
      font-weight: 500;
    " onmouseover="this.style.background='rgba(255,255,255,0.1)'" onmouseout="this.style.background='transparent'">
      <span style="font-size: 18px;">🌐</span>
      <span>Kembali ke Website</span>
    </a>
  </div>

  <!-- LOGOUT -->
  <div style="
    padding-top: 24px;
    border-top: 2px solid rgba(255,255,255,0.1);
    margin-top: 24px;
  ">
    <a href="../logout.php" style="
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 8px;
      padding: 12px 16px;
      background: rgba(255, 76, 76, 0.2);
      border: 2px solid rgba(255, 76, 76, 0.4);
      color: #ff9999;
      text-decoration: none;
      border-radius: 8px;
      transition: all 0.2s;
      font-size: 14px;
      font-weight: 700;
      text-transform: uppercase;
      letter-spacing: 0.5px;
    " onmouseover="this.style.background='rgba(255, 76, 76, 0.3)'" onmouseout="this.style.background='rgba(255, 76, 76, 0.2)'">
      <span>🚪</span>
      <span>Logout</span>
    </a>
  </div>

</aside>

<style>
  /* Scrollbar styling untuk sidebar */
  .dashboard-sidebar::-webkit-scrollbar {
    width: 6px;
  }
  
  .dashboard-sidebar::-webkit-scrollbar-track {
    background: rgba(255,255,255,0.05);
  }
  
  .dashboard-sidebar::-webkit-scrollbar-thumb {
    background: rgba(255,255,255,0.2);
    border-radius: 3px;
  }
  
  .dashboard-sidebar::-webkit-scrollbar-thumb:hover {
    background: rgba(255,255,255,0.3);
  }

  /* Responsive */
  @media (max-width: 768px) {
    .dashboard-sidebar {
      width: 100%;
      height: auto;
      position: static;
      padding: 16px;
      display: flex;
      flex-direction: column;
      border-bottom: 2px solid rgba(255,255,255,0.1);
    }
  }
</style>