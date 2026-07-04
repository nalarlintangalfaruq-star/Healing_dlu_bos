<?php
// ============================================================
// TIDAK ADA PERUBAHAN PADA BAGIAN PHP / DATABASE
// ============================================================
require_once __DIR__ . '/includes/auth.php';
$db = getDB();

$paket = $db->query("SELECT * FROM paket_trip WHERE status = 'aktif'")->fetchAll();
$harga = [];
foreach ($paket as $p) {
    $stmt = $db->prepare("SELECT * FROM harga_paket WHERE paket_id = ? ORDER BY min_orang");
    $stmt->execute([$p['id']]);
    $harga[$p['id']] = $stmt->fetchAll();
}
$fasilitas = [];
foreach ($paket as $p) {
    $stmt = $db->prepare("SELECT * FROM fasilitas WHERE paket_id = ?");
    $stmt->execute([$p['id']]);
    $fasilitas[$p['id']] = $stmt->fetchAll();
}
$itinerary = [];
if (!empty($paket)) {
    $stmt = $db->prepare("SELECT * FROM itinerary WHERE paket_id = ? ORDER BY hari, urutan");
    $stmt->execute([$paket[0]['id']]);
    $itinerary = $stmt->fetchAll();
}
$testimoni = $db->query("
    SELECT t.*, u.nama as user_nama
    FROM testimoni t
    JOIN users u ON t.user_id = u.id
    WHERE t.status = 'approved'
    ORDER BY t.created_at DESC LIMIT 6
")->fetchAll();
$flash    = getFlash();
$isLoggedIn = isUserLoggedIn();
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Pari Adventure - Private Trip Pulau Pari</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Lilita+One&family=Nunito:wght@700;800;900&family=Fredoka+One&display=swap" rel="stylesheet">

<style>
:root {
  --sg-purple:  #7c3aed;
  --sg-purple2: #5b21b6;
  --sg-purple3: #a78bfa;
  --sg-pink:    #ec4899;
  --sg-orange:  #f97316;
  --sg-orange2: #fb923c;
  --sg-yellow:  #fbbf24;
  --sg-yellow2: #fef08a;
  --sg-cyan:    #06b6d4;
  --sg-cyan2:   #67e8f9;
  --sg-green:   #22c55e;
  --sg-red:     #ef4444;
  --sg-white:   #ffffff;
  --sg-light:   #f0e6ff;
  --sg-bg:      #1e0a3c;
  --sg-bg2:     #2d1458;
  --sg-bg3:     #3b1a72;

  --font-title: 'Lilita One', cursive;
  --font-label: 'Fredoka One', cursive;
  --font-body:  'Nunito', sans-serif;

  --stroke: -3px 3px 0 #2d1458, 3px 3px 0 #2d1458,
             3px -3px 0 #2d1458, -3px -3px 0 #2d1458;
  --stroke-sm: -2px 2px 0 #2d1458, 2px 2px 0 #2d1458,
               2px -2px 0 #2d1458, -2px -2px 0 #2d1458;
}

*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
html { scroll-behavior: smooth; }
body {
  font-family: var(--font-body);
  background: var(--sg-bg);
  color: var(--sg-white);
  overflow-x: hidden;
}
a { text-decoration: none; color: inherit; }
ul { list-style: none; }

::-webkit-scrollbar { width: 8px; }
::-webkit-scrollbar-track { background: var(--sg-bg); }
::-webkit-scrollbar-thumb { background: var(--sg-purple); border-radius: 8px; }

#bgCanvas {
  position: fixed; inset: 0;
  pointer-events: none; z-index: 0;
}

#loading-screen {
  position: fixed; inset: 0;
  background: var(--sg-purple2);
  z-index: 99999;
  display: flex; flex-direction: column;
  align-items: center; justify-content: center; gap: 20px;
  transition: opacity .5s, visibility .5s;
}
#loading-screen.hidden { opacity: 0; visibility: hidden; }
.load-title {
  font-family: var(--font-title);
  font-size: 40px; color: var(--sg-yellow);
  text-shadow: var(--stroke);
  animation: wobble 0.6s ease-in-out infinite alternate;
}
@keyframes wobble {
  from { transform: rotate(-4deg) scale(1); }
  to   { transform: rotate(4deg) scale(1.06); }
}
.load-dots { display: flex; gap: 10px; }
.load-dot {
  width: 18px; height: 18px; border-radius: 50%;
  animation: dotBounce 0.6s ease-in-out infinite alternate;
}
.load-dot:nth-child(1){ background: var(--sg-orange); animation-delay: 0s; }
.load-dot:nth-child(2){ background: var(--sg-cyan);   animation-delay: .15s; }
.load-dot:nth-child(3){ background: var(--sg-pink);   animation-delay: .3s; }
@keyframes dotBounce {
  from { transform: translateY(0); }
  to   { transform: translateY(-16px); }
}
.load-bar-wrap {
  width: 260px; height: 18px;
  background: rgba(255,255,255,0.2);
  border-radius: 20px;
  border: 3px solid var(--sg-white);
  overflow: hidden;
}
.load-bar {
  height: 100%;
  background: linear-gradient(90deg, var(--sg-yellow), var(--sg-orange));
  border-radius: 20px;
  width: 0;
  animation: loadFill 1.6s ease-out forwards;
}
@keyframes loadFill { to { width: 100%; } }

.navbar {
  position: fixed; top: 0; left: 0; right: 0; z-index: 1000;
  background: var(--sg-purple2);
  border-bottom: 4px solid var(--sg-yellow);
  box-shadow: 0 6px 24px rgba(0,0,0,0.4);
  transition: all .3s;
  padding: 0;
}
.navbar .container {
  max-width: 1200px; margin: 0 auto; padding: 0 20px;
  height: 68px; display: flex; align-items: center; justify-content: space-between;
}
.navbar-brand { display: flex; align-items: center; gap: 10px; }
.brand-icon {
  width: 46px; height: 46px;
  background: var(--sg-yellow);
  border-radius: 50%;
  border: 3px solid var(--sg-white);
  display: flex; align-items: center; justify-content: center;
  font-size: 22px;
  box-shadow: 0 4px 0 var(--sg-orange);
  animation: brandBounce 2s ease-in-out infinite;
}
@keyframes brandBounce {
  0%,100%{ transform: translateY(0) rotate(-5deg); }
  50%     { transform: translateY(-6px) rotate(5deg); }
}
.brand-text {
  font-family: var(--font-title);
  font-size: 20px; color: var(--sg-yellow);
  text-shadow: var(--stroke-sm);
  display: block; line-height: 1;
}
.brand-sub {
  font-family: var(--font-label);
  font-size: 11px; color: var(--sg-cyan2);
  display: block; margin-top: 2px;
}
.nav-links { display: flex; align-items: center; gap: 4px; }
.nav-links a {
  font-family: var(--font-label);
  font-size: 13px; color: var(--sg-white);
  padding: 6px 12px; border-radius: 20px;
  transition: all .2s;
}
.nav-links a:hover {
  background: rgba(255,255,255,0.15);
  color: var(--sg-yellow);
  transform: scale(1.05);
}
.nav-cta {
  background: var(--sg-orange) !important;
  color: var(--sg-white) !important;
  border-radius: 20px !important;
  font-family: var(--font-label) !important;
  padding: 8px 18px !important;
  border: 3px solid var(--sg-white) !important;
  box-shadow: 0 4px 0 #c2410c !important;
  transition: all .15s !important;
}
.nav-cta:hover {
  transform: translateY(-3px) scale(1.05) !important;
  box-shadow: 0 7px 0 #c2410c !important;
}
.nav-cta:active {
  transform: translateY(2px) !important;
  box-shadow: 0 2px 0 #c2410c !important;
}

.container { max-width: 1200px; margin: 0 auto; padding: 0 24px; }
.section { padding: 100px 0; position: relative; z-index: 1; }
.section-alt {
  background: linear-gradient(180deg, var(--sg-bg2) 0%, var(--sg-bg) 100%);
}

.section-header { text-align: center; margin-bottom: 60px; }
.section-badge {
  display: inline-block;
  background: var(--sg-yellow);
  color: var(--sg-bg2);
  font-family: var(--font-label);
  font-size: 13px; font-weight: 700;
  padding: 5px 18px; border-radius: 30px;
  margin-bottom: 12px;
  border: 3px solid var(--sg-white);
  box-shadow: 0 3px 0 rgba(0,0,0,0.3);
  animation: badgePop 2s ease-in-out infinite;
}
@keyframes badgePop {
  0%,100%{ transform: scale(1); }
  50%     { transform: scale(1.04); }
}
.section-title {
  font-family: var(--font-title);
  font-size: clamp(28px, 5vw, 52px);
  color: var(--sg-white);
  text-shadow: var(--stroke);
  line-height: 1.15; margin-bottom: 12px;
}
.section-title span { color: var(--sg-yellow); }
.section-subtitle {
  font-family: var(--font-body);
  font-size: 15px; font-weight: 700;
  color: var(--sg-purple3);
  max-width: 560px; margin: 0 auto; line-height: 1.7;
}

.hero {
  min-height: 100vh;
  background: linear-gradient(160deg, var(--sg-purple2) 0%, var(--sg-bg) 60%);
  display: flex; align-items: center;
  position: relative; overflow: hidden;
  padding-top: 80px;
}
.blob {
  position: absolute;
  border-radius: 50%;
  filter: blur(60px);
  pointer-events: none;
  animation: blobFloat 6s ease-in-out infinite;
}
.blob-1 { width: 400px; height: 400px; background: rgba(124,58,237,0.35); top: -100px; right: -100px; animation-delay: 0s; }
.blob-2 { width: 300px; height: 300px; background: rgba(249,115,22,0.25); bottom: 0; left: -80px; animation-delay: 2s; }
.blob-3 { width: 250px; height: 250px; background: rgba(6,182,212,0.2); top: 40%; right: 20%; animation-delay: 4s; }
@keyframes blobFloat {
  0%,100%{ transform: translateY(0) scale(1); }
  50%     { transform: translateY(-30px) scale(1.06); }
}
.hero-shape {
  position: absolute;
  font-size: 28px;
  animation: shapeSpin linear infinite;
  pointer-events: none;
  filter: drop-shadow(0 4px 8px rgba(0,0,0,0.3));
}
@keyframes shapeSpin {
  from { transform: rotate(0deg) translateY(0); }
  50%  { transform: rotate(180deg) translateY(-20px); }
  to   { transform: rotate(360deg) translateY(0); }
}
.hero-content {
  display: grid; grid-template-columns: 1fr 1fr;
  gap: 48px; align-items: center; width: 100%;
  position: relative; z-index: 2;
}
.badge {
  display: inline-flex; align-items: center; gap: 6px;
  background: var(--sg-green);
  color: var(--sg-white);
  font-family: var(--font-label);
  font-size: 13px;
  padding: 7px 18px; border-radius: 30px;
  border: 3px solid var(--sg-white);
  box-shadow: 0 4px 0 #15803d;
  margin-bottom: 20px;
  animation: badgePop 2.5s ease-in-out infinite;
}
.hero h1 {
  font-family: var(--font-title);
  font-size: clamp(32px, 6vw, 64px);
  line-height: 1.1;
  color: var(--sg-white);
  text-shadow: var(--stroke);
  margin-bottom: 16px;
}
.hero h1 span {
  color: var(--sg-yellow);
  display: inline-block;
  animation: titleWiggle 3s ease-in-out infinite;
}
@keyframes titleWiggle {
  0%,100%{ transform: rotate(-2deg) scale(1); }
  50%     { transform: rotate(2deg) scale(1.02); }
}
.hero p {
  font-size: 15px; font-weight: 700;
  color: var(--sg-purple3);
  line-height: 1.75; max-width: 480px;
  margin-bottom: 28px;
  background: rgba(255,255,255,0.07);
  border-radius: 16px; padding: 14px 18px;
  border-left: 4px solid var(--sg-cyan);
}
.hero-buttons { display: flex; gap: 14px; flex-wrap: wrap; margin-bottom: 36px; }

.btn-primary {
  display: inline-flex; align-items: center; gap: 8px;
  background: var(--sg-orange);
  color: var(--sg-white);
  font-family: var(--font-label);
  font-size: 16px;
  padding: 14px 28px; border-radius: 30px;
  border: 3px solid var(--sg-white);
  box-shadow: 0 6px 0 #c2410c;
  cursor: pointer; text-decoration: none;
  transition: all .15s;
}
.btn-primary:hover  { transform: translateY(-4px); box-shadow: 0 10px 0 #c2410c; }
.btn-primary:active { transform: translateY(3px); box-shadow: 0 3px 0 #c2410c; }

.btn-secondary {
  display: inline-flex; align-items: center; gap: 8px;
  background: var(--sg-cyan);
  color: var(--sg-white);
  font-family: var(--font-label);
  font-size: 16px;
  padding: 14px 28px; border-radius: 30px;
  border: 3px solid var(--sg-white);
  box-shadow: 0 6px 0 #0e7490;
  cursor: pointer; text-decoration: none;
  transition: all .15s;
}
.btn-secondary:hover  { transform: translateY(-4px); box-shadow: 0 10px 0 #0e7490; }
.btn-secondary:active { transform: translateY(3px); box-shadow: 0 3px 0 #0e7490; }

.btn-whatsapp {
  display: inline-flex; align-items: center; gap: 8px;
  background: #25d366; color: var(--sg-white);
  font-family: var(--font-label); font-size: 16px;
  padding: 14px 28px; border-radius: 30px;
  border: 3px solid var(--sg-white);
  box-shadow: 0 6px 0 #15803d;
  text-decoration: none; transition: all .15s;
}
.btn-whatsapp:hover  { transform: translateY(-4px); box-shadow: 0 10px 0 #15803d; }
.btn-whatsapp:active { transform: translateY(3px); box-shadow: 0 3px 0 #15803d; }

.hero-stats { display: flex; gap: 16px; flex-wrap: wrap; }
.stat-item {
  background: rgba(255,255,255,0.1);
  border: 3px solid rgba(255,255,255,0.25);
  border-radius: 18px; padding: 12px 20px;
  text-align: center; flex: 1; min-width: 90px;
  backdrop-filter: blur(4px);
  transition: transform .2s;
}
.stat-item:hover { transform: translateY(-4px) rotate(-2deg); }
.stat-number {
  font-family: var(--font-title);
  font-size: 26px; color: var(--sg-yellow);
  text-shadow: var(--stroke-sm);
  display: block; line-height: 1;
}
.stat-label {
  font-family: var(--font-label);
  font-size: 11px; color: var(--sg-purple3);
  margin-top: 3px; text-transform: uppercase; letter-spacing: .05em;
}

.hero-visual {
  display: flex; justify-content: center; align-items: center;
  position: relative;
}
.char-card {
  background: linear-gradient(145deg, var(--sg-purple), var(--sg-purple2));
  border: 4px solid var(--sg-white);
  border-radius: 28px;
  padding: 24px;
  width: 300px;
  box-shadow: 0 12px 0 var(--sg-purple2), 0 20px 40px rgba(0,0,0,0.5);
  animation: cardFloat 3.5s ease-in-out infinite;
  position: relative;
  overflow: hidden;
}
@keyframes cardFloat {
  0%,100%{ transform: translateY(0) rotate(-2deg); }
  50%     { transform: translateY(-14px) rotate(2deg); }
}
.char-card::before {
  content: '';
  position: absolute; inset: 0;
  background: radial-gradient(circle at 30% 30%, rgba(255,255,255,0.12), transparent 60%);
  pointer-events: none;
}
.char-card-top {
  display: flex; justify-content: space-between; align-items: center;
  margin-bottom: 12px;
}
.card-badge-sg {
  font-family: var(--font-label);
  font-size: 11px; background: var(--sg-green);
  color: white; padding: 4px 12px; border-radius: 20px;
  border: 2px solid white;
  box-shadow: 0 3px 0 #15803d;
}
.card-stars { color: var(--sg-yellow); font-size: 14px; text-shadow: 0 0 8px var(--sg-yellow); }
.char-img {
  background: linear-gradient(135deg, #1d4ed8, #7c3aed);
  border-radius: 20px;
  height: 190px;
  display: flex; align-items: center; justify-content: center;
  font-size: 90px;
  border: 3px solid rgba(255,255,255,0.3);
  position: relative; overflow: hidden;
  margin-bottom: 14px;
}
.char-img::after {
  content: '';
  position: absolute; inset: 0;
  background: linear-gradient(45deg, transparent 40%, rgba(255,255,255,0.12) 50%, transparent 60%);
  animation: shimmer 2.5s ease-in-out infinite;
}
@keyframes shimmer {
  0%   { transform: translateX(-200%); }
  100% { transform: translateX(200%); }
}
.char-name {
  font-family: var(--font-title);
  font-size: 18px; color: var(--sg-yellow);
  text-shadow: var(--stroke-sm);
  text-align: center; margin-bottom: 4px;
}
.char-sub {
  font-family: var(--font-label);
  font-size: 12px; color: var(--sg-purple3);
  text-align: center;
}
.prog-label {
  font-family: var(--font-label);
  font-size: 10px; color: var(--sg-cyan2);
  display: flex; justify-content: space-between;
  margin: 10px 0 4px;
}
.prog-wrap {
  height: 10px; background: rgba(255,255,255,0.15);
  border-radius: 10px; overflow: hidden;
  border: 2px solid rgba(255,255,255,0.2);
}
.prog-fill {
  height: 100%;
  background: linear-gradient(90deg, var(--sg-cyan), var(--sg-green));
  border-radius: 10px;
  width: 0; animation: progFill 2s 1.5s ease-out forwards;
}
@keyframes progFill { to { width: 87%; } }

.card-corner-badge {
  position: absolute; top: -8px; right: 16px;
  background: var(--sg-pink);
  color: white; font-family: var(--font-title);
  font-size: 13px; padding: 6px 14px;
  border-radius: 0 0 14px 14px;
  border: 3px solid white; border-top: none;
  box-shadow: 0 4px 0 #9d174d;
}

.alert {
  font-family: var(--font-label);
  font-size: 14px; padding: 14px 20px;
  border-radius: 16px; margin: 20px 0;
  border: 3px solid; font-weight: 700;
}
.alert-success { background: rgba(34,197,94,0.2); border-color: var(--sg-green); color: #86efac; }
.alert-error   { background: rgba(239,68,68,0.2);  border-color: var(--sg-red);   color: #fca5a5; }
.alert-info    { background: rgba(6,182,212,0.2);   border-color: var(--sg-cyan);  color: var(--sg-cyan2); }

.packages-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(340px, 1fr));
  gap: 28px;
}
.package-card {
  background: linear-gradient(145deg, var(--sg-bg3), var(--sg-bg2));
  border: 4px solid rgba(255,255,255,0.2);
  border-radius: 24px;
  overflow: hidden;
  box-shadow: 0 8px 0 rgba(0,0,0,0.3), 0 16px 40px rgba(0,0,0,0.4);
  transition: transform .25s, box-shadow .25s, border-color .25s;
  opacity: 0; transform: translateY(30px);
  position: relative;
}
.package-card:hover {
  transform: translateY(-8px) rotate(-1deg);
  box-shadow: 0 16px 0 rgba(0,0,0,0.3), 0 24px 60px rgba(124,58,237,0.3);
  border-color: var(--sg-yellow);
}
.package-card::before {
  content: '';
  position: absolute; top: 0; left: 0; right: 0; height: 6px;
  background: linear-gradient(90deg, var(--sg-orange), var(--sg-pink), var(--sg-purple3), var(--sg-cyan));
  background-size: 200% 100%;
  animation: stripeSlide 3s linear infinite;
}
@keyframes stripeSlide { to { background-position: -200% 0; } }

.package-header {
  background: linear-gradient(135deg, var(--sg-purple), var(--sg-purple2));
  padding: 26px 26px 20px;
  position: relative; overflow: hidden;
}
.package-header::after {
  content: '🏝️';
  position: absolute; right: 14px; bottom: -8px;
  font-size: 56px; opacity: .14;
}
.package-via {
  display: inline-block;
  background: var(--sg-yellow); color: var(--sg-bg2);
  font-family: var(--font-label); font-size: 11px; font-weight: 700;
  padding: 4px 14px; border-radius: 20px;
  border: 2px solid white;
  box-shadow: 0 3px 0 #92400e;
  margin-bottom: 10px;
}
.package-title {
  font-family: var(--font-title);
  font-size: 22px; color: var(--sg-white);
  text-shadow: var(--stroke-sm);
  margin-bottom: 4px;
}
.package-duration {
  font-family: var(--font-label);
  font-size: 12px; color: var(--sg-purple3);
}
.package-body { padding: 22px 26px 26px; }

.fasilitas-title {
  font-family: var(--font-label);
  font-size: 13px; color: var(--sg-yellow);
  margin-bottom: 10px; letter-spacing: .05em;
  display: flex; align-items: center; gap: 6px;
}

.price-table { width: 100%; border-collapse: collapse; margin-bottom: 6px; }
.price-table th {
  background: rgba(124,58,237,0.3);
  color: var(--sg-purple3);
  font-family: var(--font-label); font-size: 11px;
  padding: 8px 10px; text-align: left;
  border-radius: 8px 8px 0 0;
}
.price-table td {
  padding: 10px 10px;
  font-family: var(--font-body); font-size: 13px; font-weight: 700;
  color: var(--sg-white);
  border-bottom: 1px solid rgba(255,255,255,0.07);
}
.price-table tr:hover td { background: rgba(124,58,237,0.15); }
.price-table tr:last-child td { border-bottom: none; }
.price-amount {
  font-family: var(--font-label) !important;
  font-size: 14px !important;
  color: var(--sg-yellow) !important;
  text-align: right !important;
}

.fasilitas-grid {
  display: grid; grid-template-columns: 1fr 1fr;
  gap: 6px; margin-bottom: 20px;
}
.fasilitas-item {
  display: flex; align-items: center; gap: 7px;
  font-family: var(--font-body); font-size: 12px; font-weight: 700;
  color: rgba(255,255,255,0.8);
  background: rgba(255,255,255,0.05);
  border: 2px solid rgba(255,255,255,0.1);
  border-radius: 12px; padding: 7px 10px;
  transition: all .2s;
}
.fasilitas-item:hover {
  background: rgba(124,58,237,0.25);
  border-color: var(--sg-purple3);
  color: white;
  transform: scale(1.03);
}

.btn-book {
  display: block; width: 100%; text-align: center;
  background: var(--sg-orange); color: var(--sg-white);
  font-family: var(--font-label); font-size: 16px;
  padding: 15px; border-radius: 20px;
  border: 3px solid var(--sg-white);
  box-shadow: 0 6px 0 #c2410c;
  transition: all .15s; text-decoration: none;
}
.btn-book:hover  { transform: translateY(-4px); box-shadow: 0 10px 0 #c2410c; }
.btn-book:active { transform: translateY(3px);  box-shadow: 0 3px 0 #c2410c; }

.itinerary-tabs { display: flex; gap: 8px; margin-bottom: 28px; }
.itinerary-tab {
  font-family: var(--font-label); font-size: 14px;
  padding: 10px 24px; border-radius: 20px;
  border: 3px solid rgba(255,255,255,0.25);
  background: rgba(255,255,255,0.07); color: rgba(255,255,255,0.5);
  cursor: pointer; transition: all .2s;
}
.itinerary-tab.active {
  background: var(--sg-purple); border-color: var(--sg-white);
  color: var(--sg-white);
  box-shadow: 0 4px 0 var(--sg-purple2);
  transform: translateY(-2px);
}
.itinerary-tab:hover:not(.active) {
  border-color: var(--sg-purple3); color: var(--sg-purple3);
}

.itinerary-timeline { max-width: 700px; position: relative; padding-left: 10px; }
.itinerary-timeline::before {
  content: '';
  position: absolute; left: 38px; top: 0; bottom: 0; width: 4px;
  background: linear-gradient(to bottom, var(--sg-purple), var(--sg-pink), var(--sg-orange));
  border-radius: 4px;
}
.timeline-item {
  display: flex; align-items: flex-start; gap: 16px;
  padding: 12px 0;
  animation: slideInLeft .4s ease both;
  opacity: 0;
}
.timeline-item:nth-child(1){animation-delay:.05s} .timeline-item:nth-child(2){animation-delay:.1s}
.timeline-item:nth-child(3){animation-delay:.15s} .timeline-item:nth-child(4){animation-delay:.2s}
.timeline-item:nth-child(5){animation-delay:.25s} .timeline-item:nth-child(6){animation-delay:.3s}
@keyframes slideInLeft {
  from{ opacity:0; transform: translateX(-24px); }
  to  { opacity:1; transform: translateX(0); }
}
.timeline-time {
  font-family: var(--font-label);
  font-size: 11px; color: var(--sg-yellow);
  min-width: 62px; text-align: right;
  padding-top: 8px; line-height: 1.5;
}
.timeline-dot {
  width: 20px; height: 20px; flex-shrink: 0;
  background: var(--sg-orange);
  border-radius: 50%; margin-top: 6px;
  border: 3px solid var(--sg-white);
  box-shadow: 0 0 14px var(--sg-orange);
  animation: dotPulse 2s ease-in-out infinite;
  z-index: 1;
}
@keyframes dotPulse {
  0%,100%{ box-shadow: 0 0 10px var(--sg-orange); }
  50%     { box-shadow: 0 0 22px var(--sg-orange), 0 0 40px rgba(249,115,22,0.4); }
}
.timeline-content {
  flex: 1;
  background: rgba(255,255,255,0.06);
  border: 2px solid rgba(255,255,255,0.12);
  border-radius: 16px; padding: 12px 16px;
  transition: all .2s;
}
.timeline-content:hover {
  background: rgba(124,58,237,0.2);
  border-color: var(--sg-purple3);
  transform: translateX(6px);
}
.timeline-activity {
  font-family: var(--font-body);
  font-size: 13px; font-weight: 700;
  color: var(--sg-white); line-height: 1.5;
}

/* ============================================================
   GALLERY — DENGAN GAMBAR NYATA
   ============================================================ */
.gallery-grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 12px;
}
.gallery-item {
  aspect-ratio: 1;
  border-radius: 20px;
  overflow: hidden;
  position: relative; cursor: pointer;
  border: 3px solid rgba(255,255,255,0.15);
  transition: all .3s;
  opacity: 0; transform: scale(0.88);
}
.gallery-item:hover {
  transform: scale(1.06) rotate(-2deg);
  border-color: var(--sg-yellow);
  box-shadow: 0 12px 0 rgba(0,0,0,0.3), 0 0 30px rgba(251,191,36,0.4);
  z-index: 2;
}
.gallery-item img {
  width: 100%; height: 100%;
  object-fit: cover;
  display: block;
  transition: transform .4s;
}
.gallery-item:hover img { transform: scale(1.08); }
.gallery-overlay {
  position: absolute; inset: 0;
  background: linear-gradient(to top, rgba(30,10,60,0.9), transparent 50%);
  display: flex; align-items: flex-end; padding: 14px;
  opacity: 0; transition: opacity .3s;
}
.gallery-item:hover .gallery-overlay { opacity: 1; }
.gallery-overlay span {
  font-family: var(--font-label);
  font-size: 12px; color: var(--sg-yellow);
  letter-spacing: .06em; text-transform: uppercase;
}
/* Fallback jika gambar gagal load */
.gallery-item .img-fallback {
  width: 100%; height: 100%;
  display: none;
  align-items: center; justify-content: center;
  font-size: 56px;
}
.gallery-item img.error ~ .img-fallback { display: flex; }

.testimoni-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
  gap: 20px;
}
.testimoni-card {
  background: linear-gradient(145deg, var(--sg-bg3), var(--sg-bg2));
  border: 3px solid rgba(255,255,255,0.15);
  border-radius: 22px; padding: 24px;
  position: relative; overflow: hidden;
  transition: all .3s;
  opacity: 0; transform: translateY(24px);
}
.testimoni-card::before {
  content: '❝';
  position: absolute; top: -10px; left: 14px;
  font-size: 64px; color: var(--sg-purple3); opacity: .15;
  font-family: serif; pointer-events: none; line-height: 1;
}
.testimoni-card:hover {
  transform: translateY(-6px) rotate(1deg);
  border-color: var(--sg-purple3);
  box-shadow: 0 12px 0 rgba(0,0,0,0.3), 0 20px 40px rgba(124,58,237,0.3);
}
.rating { margin-bottom: 10px; }
.star { color: var(--sg-yellow); font-size: 16px; text-shadow: 0 0 8px var(--sg-yellow); }
.testimoni-text {
  font-family: var(--font-body); font-size: 13px; font-weight: 700;
  color: rgba(255,255,255,0.75);
  line-height: 1.7; margin-bottom: 18px; font-style: italic;
}
.testimoni-author { display: flex; align-items: center; gap: 12px; }
.author-avatar {
  width: 44px; height: 44px; border-radius: 50%;
  background: linear-gradient(135deg, var(--sg-orange), var(--sg-pink));
  display: flex; align-items: center; justify-content: center;
  font-family: var(--font-title); font-size: 16px;
  color: var(--sg-white); flex-shrink: 0;
  border: 3px solid var(--sg-white);
  box-shadow: 0 3px 0 rgba(0,0,0,0.3);
}
.author-name { font-family: var(--font-label); font-size: 14px; color: var(--sg-white); }
.author-trip { font-family: var(--font-label); font-size: 10px; color: var(--sg-purple3); margin-top: 2px; }

.cta-section {
  padding: 100px 0; text-align: center;
  position: relative; z-index: 1; overflow: hidden;
}
.cta-section::before {
  content: '';
  position: absolute; inset: 0;
  background: radial-gradient(ellipse at center, rgba(124,58,237,0.15) 0%, transparent 70%);
}
.cta-content { position: relative; z-index: 2; max-width: 640px; margin: 0 auto; }
.cta-content h2 {
  font-family: var(--font-title);
  font-size: clamp(28px, 5vw, 52px);
  color: var(--sg-white); text-shadow: var(--stroke);
  margin-bottom: 14px;
  animation: titleWiggle 3s ease-in-out infinite;
}
.cta-content p {
  font-family: var(--font-body); font-size: 15px; font-weight: 700;
  color: var(--sg-purple3); margin-bottom: 36px; line-height: 1.7;
}
.cta-buttons { display: flex; gap: 16px; justify-content: center; flex-wrap: wrap; }

footer {
  background: var(--sg-bg2);
  border-top: 4px solid var(--sg-purple);
  padding: 60px 0 24px;
  position: relative; z-index: 1;
}
.footer-grid {
  display: grid;
  grid-template-columns: 2fr 1fr 1fr 1.4fr;
  gap: 40px; margin-bottom: 40px;
}
.footer-desc {
  font-family: var(--font-body); font-size: 13px; font-weight: 700;
  color: var(--sg-purple3); line-height: 1.75;
  margin: 12px 0 20px;
}
.footer-social { display: flex; gap: 10px; }
.social-btn {
  width: 40px; height: 40px; border-radius: 12px;
  background: rgba(255,255,255,0.08);
  border: 2px solid rgba(255,255,255,0.2);
  display: flex; align-items: center; justify-content: center;
  font-size: 18px; cursor: pointer;
  transition: all .2s; text-decoration: none;
}
.social-btn:hover {
  background: var(--sg-purple);
  border-color: var(--sg-purple3);
  transform: translateY(-4px) rotate(-5deg);
}
.footer-title {
  font-family: var(--font-label); font-size: 13px;
  color: var(--sg-yellow); margin-bottom: 14px;
  text-transform: uppercase; letter-spacing: .08em;
}
.footer-links li { margin-bottom: 8px; }
.footer-links a {
  font-family: var(--font-body); font-size: 13px; font-weight: 700;
  color: var(--sg-purple3); transition: all .2s; display: inline-block;
}
.footer-links a:hover { color: var(--sg-white); transform: translateX(4px); }
.footer-contact li {
  font-family: var(--font-body); font-size: 13px; font-weight: 700;
  color: var(--sg-purple3); line-height: 2;
}
.footer-bottom {
  border-top: 2px solid rgba(255,255,255,0.1);
  padding-top: 20px;
  display: flex; justify-content: space-between; align-items: center;
  flex-wrap: wrap; gap: 8px;
}
.footer-bottom span { font-family: var(--font-label); font-size: 12px; color: var(--sg-purple3); }

.wave-divider { width: 100%; overflow: hidden; line-height: 0; position: relative; z-index: 1; }
.wave-divider svg { display: block; }

.reveal {
  opacity: 0; transform: translateY(28px);
  transition: opacity .65s ease, transform .65s ease;
}
.reveal.visible { opacity: 1; transform: none; }

.wa-float {
  position: fixed; right: 24px; bottom: 24px; z-index: 900;
  width: 58px; height: 58px;
  background: #25d366; border-radius: 50%;
  display: flex; align-items: center; justify-content: center;
  font-size: 26px;
  border: 3px solid white;
  box-shadow: 0 6px 0 #15803d, 0 10px 30px rgba(37,211,102,0.5);
  text-decoration: none;
  animation: waBounce 2s ease-in-out infinite;
  transition: transform .2s;
}
.wa-float:hover { transform: scale(1.15); }
@keyframes waBounce {
  0%,100%{ transform: rotate(-8deg) scale(1); }
  50%     { transform: rotate(8deg) scale(1.08); }
}

.cursor-ripple {
  position: fixed; pointer-events: none; z-index: 9998;
  border-radius: 50%;
  transform: translate(-50%, -50%) scale(0);
}

@media (max-width: 768px) {
  .hero-content { grid-template-columns: 1fr; }
  .hero-visual  { display: none; }
  .footer-grid  { grid-template-columns: 1fr 1fr; }
  .gallery-grid { grid-template-columns: 1fr 1fr; }
  .nav-links    { display: none; }
}
</style>
</head>
<body>

<div id="loading-screen">
  <div class="load-title">PARI ADVENTURE!</div>
  <div class="load-dots">
    <div class="load-dot"></div>
    <div class="load-dot"></div>
    <div class="load-dot"></div>
  </div>
  <div class="load-bar-wrap"><div class="load-bar"></div></div>
</div>

<canvas id="bgCanvas"></canvas>

<nav class="navbar" id="mainNav">
  <div class="container">
    <a href="index.php" class="navbar-brand">
      <div class="brand-icon">🐋</div>
      <div>
        <span class="brand-text">Pari Adventure</span>
        <span class="brand-sub">Kepulauan Seribu</span>
      </div>
    </a>
    <ul class="nav-links">
      <li><a href="#paket">Paket Trip</a></li>
      <li><a href="#itinerary">Itinerary</a></li>
      <li><a href="#galeri">Galeri</a></li>
      <li><a href="#testimoni">Testimoni</a></li>
      <li><a href="#kontak">Kontak</a></li>
      <?php if ($isLoggedIn): ?>
        <li><a href="user/dashboard.php">Dashboard</a></li>
        <li><a href="logout.php" class="nav-cta">Logout</a></li>
      <?php else: ?>
        <li><a href="login.php">Masuk</a></li>
        <li><a href="register.php" class="nav-cta">Daftar</a></li>
      <?php endif; ?>
    </ul>
  </div>
</nav>

<section class="hero" id="home">
  <div class="blob blob-1"></div>
  <div class="blob blob-2"></div>
  <div class="blob blob-3"></div>
  <div class="hero-shape" style="top:12%;left:5%;animation-duration:6s;animation-delay:0s;">⭐</div>
  <div class="hero-shape" style="top:20%;right:8%;animation-duration:7s;animation-delay:1s;font-size:20px;">🌟</div>
  <div class="hero-shape" style="bottom:25%;left:8%;animation-duration:8s;animation-delay:2s;font-size:22px;">✨</div>
  <div class="hero-shape" style="bottom:35%;right:6%;animation-duration:5s;animation-delay:0.5s;font-size:18px;">💫</div>
  <div class="hero-shape" style="top:55%;left:3%;animation-duration:9s;animation-delay:3s;font-size:16px;">🎮</div>

  <div class="container">
    <div class="hero-content">
      <div class="hero-text">
        <div class="badge">🌊 Wisata Kepulauan Seribu</div>
        <h1>Petualangan Seru di<br><span>Pulau Pari!</span></h1>
        <p>Rasakan keindahan pantai pasir putih, snorkeling di spot terbaik, hunting sunset memukau, dan BBQ tepi pantai yang tak terlupakan bersama orang-orang tersayang.</p>
        <div class="hero-buttons">
          <a href="#paket" class="btn-primary">🏖️ Lihat Paket Trip</a>
          <a href="#itinerary" class="btn-secondary">📋 Cek Itinerary</a>
        </div>
        <div class="hero-stats">
          <div class="stat-item">
            <span class="stat-number" data-target="500">0</span>
            <div class="stat-label">Trip Selesai</div>
          </div>
          <div class="stat-item">
            <span class="stat-number" data-target="2000">0</span>
            <div class="stat-label">Pelanggan Puas</div>
          </div>
          <div class="stat-item">
            <span class="stat-number">4.9⭐</span>
            <div class="stat-label">Rating</div>
          </div>
        </div>
      </div>

      <div class="hero-visual">
        <div class="char-card">
          <div class="card-corner-badge">2D1N</div>
          <div class="char-card-top">
            <div class="card-badge-sg">✅ AKTIF</div>
            <div class="card-stars">★★★★★</div>
          </div>
          <div class="char-img">🏝️</div>
          <div class="char-name">Pulau Pari</div>
          <div class="char-sub">Kepulauan Seribu, Jakarta ✨</div>
          <div class="prog-label">
            <span>⭐ Rating XP</span><span>4.9 / 5.0</span>
          </div>
          <div class="prog-wrap"><div class="prog-fill"></div></div>
        </div>
      </div>
    </div>
  </div>
</section>

<div class="wave-divider">
  <svg viewBox="0 0 1440 70" xmlns="http://www.w3.org/2000/svg" preserveAspectRatio="none">
    <path fill="#2d1458" d="M0,30L60,36C120,42,240,54,360,52C480,50,600,32,720,28C840,24,960,32,1080,36C1200,40,1320,44,1380,46L1440,48L1440,70L0,70Z"/>
  </svg>
</div>

<?php if ($flash): ?>
<div class="container" style="padding-top:16px;position:relative;z-index:2;">
  <div class="alert alert-<?= $flash['type'] ?>">
    <?= htmlspecialchars($flash['message']) ?>
  </div>
</div>
<?php endif; ?>

<section class="section" id="paket" style="background:linear-gradient(180deg,#2d1458 0%,#1e0a3c 100%);">
  <div class="container">
    <div class="section-header reveal">
      <div class="section-badge">🚢 Pilih Keberangkatan</div>
      <h2 class="section-title">Paket <span>Private Trip</span> Kami</h2>
      <p class="section-subtitle">Dua pilihan keberangkatan dengan fasilitas lengkap. Harga makin terjangkau untuk rombongan lebih besar!</p>
    </div>
    <div class="packages-grid">
      <?php foreach ($paket as $p): ?>
      <div class="package-card">
        <div class="package-header">
          <div class="package-via">Via <?= $p['via'] === 'muara_angke' ? 'Muara Angke' : 'Marina Ancol' ?></div>
          <div class="package-title"><?= htmlspecialchars($p['nama_paket']) ?></div>
          <div class="package-duration">⏱️ <?= htmlspecialchars($p['durasi']) ?></div>
        </div>
        <div class="package-body">
          <?php if (!empty($harga[$p['id']])): ?>
          <div style="margin-bottom:20px;">
            <div class="fasilitas-title">💰 Daftar Harga</div>
            <table class="price-table">
              <thead>
                <tr>
                  <th>Jumlah Peserta</th>
                  <th style="text-align:right">Harga/Orang</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($harga[$p['id']] as $h): ?>
                <tr>
                  <td>
                    <?php if ($h['max_orang'] === null): ?>
                      &gt; <?= $h['min_orang'] ?> Orang
                    <?php elseif ($h['min_orang'] === $h['max_orang']): ?>
                      <?= $h['min_orang'] ?> Orang
                    <?php else: ?>
                      <?= $h['min_orang'] ?> - <?= $h['max_orang'] ?> Orang
                    <?php endif; ?>
                  </td>
                  <td class="price-amount"><?= formatRupiah($h['harga_per_orang']) ?>/orang</td>
                </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
          <?php endif; ?>

          <?php if (!empty($fasilitas[$p['id']])): ?>
          <div class="fasilitas-title">✅ Fasilitas Termasuk</div>
          <div class="fasilitas-grid">
            <?php foreach ($fasilitas[$p['id']] as $f): ?>
            <div class="fasilitas-item">
              <span><?= $f['icon'] ?></span>
              <span><?= htmlspecialchars($f['nama_fasilitas']) ?></span>
            </div>
            <?php endforeach; ?>
          </div>
          <?php endif; ?>

          <a href="<?= $isLoggedIn ? 'user/booking.php?paket=' . $p['id'] : 'login.php' ?>"
             class="btn-book">🏖️ Pesan Sekarang!</a>
          <?php if (!$isLoggedIn): ?>
          <p style="text-align:center;font-size:11px;color:rgba(167,139,250,0.6);margin-top:10px;font-family:'Fredoka One',cursive;">
            Perlu <a href="login.php" style="color:var(--sg-yellow);">login</a> untuk memesan
          </p>
          <?php endif; ?>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<section class="section section-alt" id="itinerary">
  <div class="container">
    <div class="section-header reveal">
      <div class="section-badge">📋 Quest Log</div>
      <h2 class="section-title">Itinerary <span>Trip</span></h2>
      <p class="section-subtitle">Rundown kegiatan selama 2 hari 1 malam yang sudah kami siapkan dengan matang.</p>
    </div>

    <div class="itinerary-tabs">
      <button class="itinerary-tab active" onclick="showDay(1, this)">🌅 Hari 1</button>
      <button class="itinerary-tab" onclick="showDay(2, this)">🌊 Hari 2</button>
    </div>

    <?php
    $days = [1 => [], 2 => []];
    foreach ($itinerary as $item) {
      $days[$item['hari']][] = $item;
    }
    ?>

    <?php foreach ([1, 2] as $day): ?>
    <div class="itinerary-timeline" id="day-<?= $day ?>" style="<?= $day > 1 ? 'display:none' : '' ?>">
      <?php if (!empty($days[$day])): ?>
        <?php foreach ($days[$day] as $item): ?>
        <div class="timeline-item">
          <div class="timeline-time">
            <?= substr($item['waktu_mulai'], 0, 5) ?><br>
            <span style="color:rgba(167,139,250,0.5)">-<?= substr($item['waktu_selesai'], 0, 5) ?></span>
          </div>
          <div class="timeline-dot"></div>
          <div class="timeline-content">
            <div class="timeline-activity"><?= htmlspecialchars($item['kegiatan']) ?></div>
          </div>
        </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>
    <?php endforeach; ?>

    <p style="margin-top:24px;font-size:12px;color:rgba(167,139,250,0.6);font-family:'Fredoka One',cursive;padding:12px 16px;background:rgba(124,58,237,0.1);border-radius:14px;border:2px solid rgba(124,58,237,0.3);">
      ⚠️ Catatan: Rundown/jadwal kegiatan dapat berubah sewaktu-waktu karena cuaca &amp; kondisi alam sekitar.
    </p>
  </div>
</section>

<section class="section" id="galeri" style="background:linear-gradient(180deg,#1e0a3c 0%,#2d1458 100%);">
  <div class="container">
    <div class="section-header reveal">
      <div class="section-badge">📸 Photo Gallery</div>
      <h2 class="section-title">Galeri <span>Foto</span></h2>
      <p class="section-subtitle">Lihat keindahan Pulau Pari melalui foto-foto dari trip peserta kami.</p>
    </div>
    <div class="gallery-grid">

      <div class="gallery-item">
        <img
          src="https://images.unsplash.com/photo-1507525428034-b723cf961d3e?w=600&q=80&fit=crop"
          alt="Pantai Pasir Perawan"
          loading="lazy"
          onerror="this.style.display='none';this.nextElementSibling.style.display='flex';"
        >
        <div class="img-fallback" style="background:linear-gradient(135deg,#1d4ed8,#7c3aed);">🏖️</div>
        <div class="gallery-overlay"><span>Pantai Pasir Perawan</span></div>
      </div>

      <div class="gallery-item">
        <img
          src="https://images.unsplash.com/photo-1544551763-46a013bb70d5?w=600&q=80&fit=crop"
          alt="Snorkeling Zone"
          loading="lazy"
          onerror="this.style.display='none';this.nextElementSibling.style.display='flex';"
        >
        <div class="img-fallback" style="background:linear-gradient(135deg,#0f766e,#0891b2);">🤿</div>
        <div class="gallery-overlay"><span>Snorkeling Zone</span></div>
      </div>

      <div class="gallery-item">
        <img
          src="https://images.unsplash.com/photo-1499346030926-9a72daac6c63?w=600&q=80&fit=crop"
          alt="Hunting Sunset"
          loading="lazy"
          onerror="this.style.display='none';this.nextElementSibling.style.display='flex';"
        >
        <div class="img-fallback" style="background:linear-gradient(135deg,#c2410c,#d97706);">🌅</div>
        <div class="gallery-overlay"><span>Hunting Sunset</span></div>
      </div>

      <div class="gallery-item">
        <img
          src="https://images.unsplash.com/photo-1582967788606-a171c1080cb0?w=600&q=80&fit=crop"
          alt="Keindahan Bawah Laut"
          loading="lazy"
          onerror="this.style.display='none';this.nextElementSibling.style.display='flex';"
        >
        <div class="img-fallback" style="background:linear-gradient(135deg,#1d4ed8,#0891b2);">🐠</div>
        <div class="gallery-overlay"><span>Bawah Laut</span></div>
      </div>

      <div class="gallery-item">
        <img
          src="https://images.unsplash.com/photo-1504674900247-0877df9cc836?w=600&q=80&fit=crop"
          alt="Bersepeda Keliling Pulau"
          loading="lazy"
          onerror="this.style.display='none';this.nextElementSibling.style.display='flex';"
        >
        <div class="img-fallback" style="background:linear-gradient(135deg,#15803d,#0f766e);">🚲</div>
        <div class="gallery-overlay"><span>Bersepeda</span></div>
      </div>

      <div class="gallery-item">
        <img
          src="https://images.unsplash.com/photo-1555939594-58d7cb561ad1?w=600&q=80&fit=crop"
          alt="BBQ Night"
          loading="lazy"
          onerror="this.style.display='none';this.nextElementSibling.style.display='flex';"
        >
        <div class="img-fallback" style="background:linear-gradient(135deg,#9f1239,#c2410c);">🍖</div>
        <div class="gallery-overlay"><span>BBQ Night</span></div>
      </div>

    </div>
  </div>
</section>

<section class="section section-alt" id="testimoni">
  <div class="container">
    <div class="section-header reveal">
      <div class="section-badge">⭐ Player Reviews</div>
      <h2 class="section-title">Testimoni <span>Peserta</span></h2>
      <p class="section-subtitle">Ribuan peserta telah merasakan pengalaman tak terlupakan bersama Pari Adventure.</p>
    </div>
    <div class="testimoni-grid">
      <?php if (!empty($testimoni)): ?>
        <?php foreach ($testimoni as $t): ?>
        <div class="testimoni-card">
          <div class="rating">
            <?php for ($i = 1; $i <= 5; $i++): ?>
              <span class="star"><?= $i <= $t['rating'] ? '★' : '☆' ?></span>
            <?php endfor; ?>
          </div>
          <p class="testimoni-text">"<?= htmlspecialchars($t['komentar']) ?>"</p>
          <div class="testimoni-author">
            <div class="author-avatar"><?= strtoupper(substr($t['user_nama'], 0, 1)) ?></div>
            <div>
              <div class="author-name"><?= htmlspecialchars($t['user_nama']) ?></div>
              <div class="author-trip">Peserta Trip Pulau Pari</div>
            </div>
          </div>
        </div>
        <?php endforeach; ?>
      <?php else: ?>
        <?php
        $dummyTestimoni = [
          ['nama' => 'Sari Dewi',    'rating' => 5, 'komentar' => 'Trip yang luar biasa! Snorkeling di Pulau Pari sungguh mengagumkan, ikannya banyak banget. Pemandu wisatanya ramah dan profesional. Pasti balik lagi!'],
          ['nama' => 'Budi Santoso', 'rating' => 5, 'komentar' => 'Paket yang sangat worth it! Fasilitas lengkap, makan enak, penginapan nyaman ber-AC. BBQ malamnya seru banget. Recommended banget buat yang mau liburan ke Pulau Pari.'],
          ['nama' => 'Anisa Rahman', 'rating' => 5, 'komentar' => 'Hunting sunset di Pantai Pasir Perawan itu magical banget. Foto-fotonya cantik semua. Terima kasih Pari Adventure sudah membuat liburan kami begitu berkesan!'],
        ];
        foreach ($dummyTestimoni as $t): ?>
        <div class="testimoni-card">
          <div class="rating"><?= str_repeat('<span class="star">★</span>', $t['rating']) ?></div>
          <p class="testimoni-text">"<?= $t['komentar'] ?>"</p>
          <div class="testimoni-author">
            <div class="author-avatar"><?= substr($t['nama'], 0, 1) ?></div>
            <div>
              <div class="author-name"><?= $t['nama'] ?></div>
              <div class="author-trip">Peserta Trip Pulau Pari</div>
            </div>
          </div>
        </div>
        <?php endforeach; endif; ?>
    </div>
  </div>
</section>

<section class="cta-section" id="kontak">
  <div class="container">
    <div class="cta-content reveal">
      <h2>Siap Berpetualang? 🌊</h2>
      <p>Hubungi kami sekarang dan dapatkan info paket terbaru, promo menarik, serta konsultasi trip gratis!</p>
      <div class="cta-buttons">
        <a href="https://wa.me/6285891140764?text=Halo Pari Adventure, saya ingin info paket trip Pulau Pari"
           class="btn-whatsapp" target="_blank">💬 Chat WhatsApp</a>
        <a href="<?= $isLoggedIn ? 'user/booking.php' : 'register.php' ?>" class="btn-primary">
          🏖️ Booking Sekarang
        </a>
      </div>
    </div>
  </div>
</section>

<footer>
  <div class="container">
    <div class="footer-grid">
      <div>
        <a href="index.php" class="navbar-brand" style="margin-bottom:14px;display:inline-flex;gap:10px;align-items:center;">
          <div class="brand-icon" style="width:38px;height:38px;font-size:18px;">🐋</div>
          <div>
            <span class="brand-text" style="font-size:17px;">Pari Adventure</span>
            <span class="brand-sub">Kepulauan Seribu</span>
          </div>
        </a>
        <p class="footer-desc">Operator wisata terpercaya untuk private trip Pulau Pari. Melayani dengan sepenuh hati untuk pengalaman liburan yang tak terlupakan.</p>
        <div class="footer-social">
          <a href="#" class="social-btn">📸</a>
          <a href="#" class="social-btn">💬</a>
          <a href="#" class="social-btn">🎵</a>
        </div>
      </div>
      <div>
        <div class="footer-title">Navigasi</div>
        <ul class="footer-links">
          <li><a href="#paket">Paket Trip</a></li>
          <li><a href="#itinerary">Itinerary</a></li>
          <li><a href="#galeri">Galeri</a></li>
          <li><a href="#testimoni">Testimoni</a></li>
        </ul>
      </div>
      <div>
        <div class="footer-title">Akun</div>
        <ul class="footer-links">
          <li><a href="login.php">Masuk</a></li>
          <li><a href="register.php">Daftar Akun</a></li>
          <li><a href="user/dashboard.php">Dashboard</a></li>
          <li><a href="user/booking.php">Pesan Trip</a></li>
        </ul>
      </div>
      <div>
        <div class="footer-title">Kontak</div>
        <ul class="footer-links footer-contact">
          <li>📍 Pulau Pari, Kepulauan Seribu, Jakarta</li>
          <li>📞 +62 858-9114-0764</li>
          <li>✉️ info@pariadventure.com</li>
          <li>⏰ 08.00 - 21.00 WIB</li>
        </ul>
      </div>
    </div>
    <div class="footer-bottom">
      <span>© 2026 Pari Adventure. All rights reserved.</span>
      <span>Made with 🌊 for Pulau Pari lovers</span>
    </div>
  </div>
</footer>

<a href="https://wa.me/6285891140764" class="wa-float" target="_blank">💬</a>

<script>
window.addEventListener('load', () => {
  setTimeout(() => {
    document.getElementById('loading-screen').classList.add('hidden');
    setTimeout(() => document.getElementById('loading-screen').remove(), 600);
  }, 1700);
});

(function() {
  const canvas = document.getElementById('bgCanvas');
  const ctx    = canvas.getContext('2d');
  let W, H;
  const particles = [];
  const SHAPES = ['★','●','▲','◆','♥','✦'];
  const COLORS = ['#fbbf24','#ec4899','#06b6d4','#a78bfa','#f97316','#22c55e'];

  function resize() {
    W = canvas.width  = window.innerWidth;
    H = canvas.height = window.innerHeight;
  }
  window.addEventListener('resize', resize);
  resize();

  class Particle {
    constructor() { this.reset(true); }
    reset(init) {
      this.x  = Math.random() * W;
      this.y  = init ? Math.random() * H : H + 20;
      this.vy = -(Math.random() * 0.8 + 0.3);
      this.vx = (Math.random() - 0.5) * 0.4;
      this.size = Math.random() * 14 + 7;
      this.alpha = Math.random() * 0.25 + 0.05;
      this.rot   = Math.random() * Math.PI * 2;
      this.rotV  = (Math.random() - 0.5) * 0.02;
      this.color = COLORS[Math.floor(Math.random() * COLORS.length)];
      this.shape = SHAPES[Math.floor(Math.random() * SHAPES.length)];
    }
    update() {
      this.x   += this.vx;
      this.y   += this.vy;
      this.rot += this.rotV;
      if (this.y < -30) this.reset(false);
    }
    draw() {
      ctx.save();
      ctx.globalAlpha = this.alpha;
      ctx.fillStyle   = this.color;
      ctx.font = `${this.size}px serif`;
      ctx.translate(this.x, this.y);
      ctx.rotate(this.rot);
      ctx.textAlign = 'center';
      ctx.textBaseline = 'middle';
      ctx.fillText(this.shape, 0, 0);
      ctx.restore();
    }
  }

  for (let i = 0; i < 60; i++) particles.push(new Particle());

  function loop() {
    ctx.clearRect(0, 0, W, H);
    particles.forEach(p => { p.update(); p.draw(); });
    requestAnimationFrame(loop);
  }
  loop();
})();

const nav = document.getElementById('mainNav');
window.addEventListener('scroll', () => {
  nav.style.boxShadow = window.scrollY > 60
    ? '0 6px 30px rgba(124,58,237,0.5)' : '0 6px 24px rgba(0,0,0,0.4)';
});

function showDay(day, btn) {
  document.querySelectorAll('[id^="day-"]').forEach(el => el.style.display = 'none');
  document.querySelectorAll('.itinerary-tab').forEach(el => el.classList.remove('active'));
  document.getElementById('day-' + day).style.display = 'block';
  btn.classList.add('active');
  document.querySelectorAll('#day-' + day + ' .timeline-item').forEach((el, i) => {
    el.style.animation = 'none';
    el.style.opacity   = '0';
    el.offsetHeight;
    el.style.animation = `slideInLeft .4s ease ${i * 0.07}s both`;
  });
}

document.querySelectorAll('a[href^="#"]').forEach(a => {
  a.addEventListener('click', function(e) {
    e.preventDefault();
    const t = document.querySelector(this.getAttribute('href'));
    if (t) t.scrollIntoView({ behavior: 'smooth', block: 'start' });
  });
});

function animateCounter(el, target) {
  let cur = 0;
  const step = Math.ceil(target / 55);
  const t = setInterval(() => {
    cur = Math.min(cur + step, target);
    el.textContent = cur.toLocaleString('id-ID') + '+';
    if (cur >= target) clearInterval(t);
  }, 28);
}
const counterEls = document.querySelectorAll('[data-target]');
let countersDone = false;
function checkCounters() {
  if (countersDone) return;
  const hero = document.querySelector('.hero-stats');
  if (hero && hero.getBoundingClientRect().top < window.innerHeight) {
    countersDone = true;
    counterEls.forEach(el => animateCounter(el, parseInt(el.dataset.target)));
  }
}
window.addEventListener('scroll', checkCounters);
setTimeout(checkCounters, 2000);

const revealObs = new IntersectionObserver(entries => {
  entries.forEach(e => {
    if (e.isIntersecting) {
      e.target.classList.add('visible');
      if (e.target.classList.contains('package-card') || e.target.classList.contains('testimoni-card') || e.target.classList.contains('gallery-item')) {
        e.target.style.opacity = '1';
        e.target.style.transform = 'none';
      }
      revealObs.unobserve(e.target);
    }
  });
}, { threshold: 0.1 });

document.querySelectorAll('.reveal').forEach(el => revealObs.observe(el));

document.querySelectorAll('.package-card, .testimoni-card, .gallery-item').forEach((el, i) => {
  el.style.transitionDelay = (i % 3 * 0.1) + 's';
  el.style.transition = 'opacity .6s ease, transform .6s ease, box-shadow .25s, border-color .25s';
  revealObs.observe(el);
});

document.addEventListener('click', function(e) {
  const colors = ['#fbbf24','#ec4899','#06b6d4','#f97316','#22c55e','#a78bfa'];
  for (let i = 0; i < 5; i++) {
    const dot = document.createElement('div');
    dot.className = 'cursor-ripple';
    const size = Math.random() * 12 + 6;
    const angle = (Math.PI * 2 / 5) * i;
    const dist  = Math.random() * 40 + 20;
    Object.assign(dot.style, {
      width:  size + 'px',
      height: size + 'px',
      left:   e.clientX + 'px',
      top:    e.clientY + 'px',
      background: colors[Math.floor(Math.random() * colors.length)],
      border: 'none',
      zIndex: '9998',
    });
    document.body.appendChild(dot);
    const dx = Math.cos(angle) * dist, dy = Math.sin(angle) * dist;
    dot.animate([
      { transform: 'translate(-50%,-50%) scale(1)', opacity: 1 },
      { transform: `translate(calc(-50% + ${dx}px), calc(-50% + ${dy}px)) scale(0)`, opacity: 0 }
    ], { duration: 500, easing: 'ease-out' }).onfinish = () => dot.remove();
  }
});

document.querySelectorAll('.package-card').forEach(card => {
  card.addEventListener('mousemove', e => {
    const r = card.getBoundingClientRect();
    const x = (e.clientX - r.left) / r.width  - 0.5;
    const y = (e.clientY - r.top)  / r.height - 0.5;
    card.style.transform = `translateY(-8px) rotateX(${-y * 7}deg) rotateY(${x * 7}deg) rotate(-1deg)`;
  });
  card.addEventListener('mouseleave', () => { card.style.transform = ''; });
});
</script>
</body>
</html>