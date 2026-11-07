<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/auth.php';

// Cek apakah halaman memerlukan login
checkAccess();

$currentPage = basename($_SERVER['PHP_SELF']);
$adminLoggedIn = isLoggedIn();
?>
<!DOCTYPE html>
<html lang="id" class="dark">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= htmlspecialchars($pageTitle ?? (defined('APP_NAME') ? APP_NAME : 'PowMr 6.5kW Solar Monitor')) ?></title>
  
  <!-- Tailwind CSS & Chart.js -->
  <script src="https://cdn.tailwindcss.com"></script>
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  
  <script>
    tailwind.config = {
      darkMode: 'class',
      theme: {
        extend: {
          colors: {
            solar: {
              400: '#fbbf24',
              500: '#f59e0b',
              600: '#d97706',
              700: '#b45309'
            },
            cyber: {
              bg: '#060c1d',
              card: 'rgba(13, 24, 54, 0.75)',
              border: 'rgba(59, 130, 246, 0.22)',
              blue: '#2563eb',
              cyan: '#06b6d4',
              amber: '#f59e0b',
              emerald: '#10b981',
              purple: '#a855f7'
            }
          }
        }
      }
    }
  </script>

  <style>
    /* Global Cyber Midnight Background & Scrollbar */
    body {
      background: radial-gradient(circle at 50% 0%, #0d2258 0%, #08112d 35%, #040817 100%);
      background-attachment: fixed;
      color: #f1f5f9;
      font-family: ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
    }

    ::-webkit-scrollbar {
      width: 6px;
      height: 6px;
    }
    ::-webkit-scrollbar-track {
      background: #060c1d;
    }
    ::-webkit-scrollbar-thumb {
      background: #1e293b;
      border-radius: 9999px;
    }
    ::-webkit-scrollbar-thumb:hover {
      background: #3b82f6;
    }

    /* Glassmorphism Cyber Cards */
    .glass-panel {
      background: rgba(12, 22, 48, 0.72);
      backdrop-filter: blur(16px);
      -webkit-backdrop-filter: blur(16px);
      border: 1px solid rgba(59, 130, 246, 0.2);
    }
    .glass-panel-hover {
      transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }
    .glass-panel-hover:hover {
      border-color: rgba(59, 130, 246, 0.45);
      box-shadow: 0 10px 30px -10px rgba(37, 99, 235, 0.25);
    }

    /* Animasi Aliran Daya (Power Flow Particles) */
    @keyframes flow-forward {
      0% { stroke-dashoffset: 28; }
      100% { stroke-dashoffset: 0; }
    }
    @keyframes flow-reverse {
      0% { stroke-dashoffset: 0; }
      100% { stroke-dashoffset: 28; }
    }

    /* Status Statik: Garis diam tanpa animasi saat daya 0 / idle / offline */
    .pipe-tube {
      stroke: #1e293b !important;
      stroke-width: 9 !important;
      stroke-linecap: round !important;
      stroke-linejoin: round !important;
      opacity: 0.6 !important;
    }
    .flow-line-static {
      stroke: #334155 !important;
      stroke-width: 3.5 !important;
      stroke-linecap: round !important;
      stroke-linejoin: round !important;
      stroke-dasharray: none !important;
      animation: none !important;
      opacity: 0.5;
    }

    /* Status Aktif: Partikel Mengalir Sesuai Sumber Daya */
    .flow-pv-active {
      stroke: #f59e0b !important;
      stroke-width: 4 !important;
      stroke-linecap: round !important;
      stroke-linejoin: round !important;
      stroke-dasharray: 6, 8 !important;
      animation: flow-forward 1.0s linear infinite !important;
      opacity: 1 !important;
      filter: drop-shadow(0 0 8px rgba(245, 158, 11, 0.9));
    }
    .flow-grid-active {
      stroke: #3b82f6 !important;
      stroke-width: 4 !important;
      stroke-linecap: round !important;
      stroke-linejoin: round !important;
      stroke-dasharray: 6, 8 !important;
      animation: flow-forward 1.2s linear infinite !important;
      opacity: 1 !important;
      filter: drop-shadow(0 0 8px rgba(59, 130, 246, 0.9));
    }
    .flow-load-active {
      stroke: #06b6d4 !important;
      stroke-width: 4 !important;
      stroke-linecap: round !important;
      stroke-linejoin: round !important;
      stroke-dasharray: 6, 8 !important;
      animation: flow-forward 1.0s linear infinite !important;
      opacity: 1 !important;
      filter: drop-shadow(0 0 8px rgba(6, 182, 212, 0.9));
    }
    .flow-batt-charge {
      stroke: #10b981 !important;
      stroke-width: 4 !important;
      stroke-linecap: round !important;
      stroke-linejoin: round !important;
      stroke-dasharray: 6, 8 !important;
      animation: flow-forward 1.1s linear infinite !important;
      opacity: 1 !important;
      filter: drop-shadow(0 0 8px rgba(16, 185, 129, 0.9));
    }
    .flow-batt-discharge {
      stroke: #f59e0b !important;
      stroke-width: 4 !important;
      stroke-linecap: round !important;
      stroke-linejoin: round !important;
      stroke-dasharray: 6, 8 !important;
      animation: flow-reverse 1.1s linear infinite !important;
      opacity: 1 !important;
      filter: drop-shadow(0 0 8px rgba(245, 158, 11, 0.9));
    }

    /* Card Glow Presets */
    .pulse-glow-amber {
      box-shadow: 0 0 25px rgba(245, 158, 11, 0.35) !important;
      border-color: rgba(245, 158, 11, 0.8) !important;
    }
    .pulse-glow-blue {
      box-shadow: 0 0 25px rgba(59, 130, 246, 0.35) !important;
      border-color: rgba(59, 130, 246, 0.8) !important;
    }
    .pulse-glow-emerald {
      box-shadow: 0 0 25px rgba(16, 185, 129, 0.35) !important;
      border-color: rgba(16, 185, 129, 0.8) !important;
    }
    .pulse-glow-cyan {
      box-shadow: 0 0 25px rgba(6, 182, 212, 0.35) !important;
      border-color: rgba(6, 182, 212, 0.8) !important;
    }

    /* Fan Spin Animation */
    @keyframes spin-fan {
      from { transform: rotate(0deg); }
      to { transform: rotate(360deg); }
    }
    .fan-spinning {
      animation: spin-fan 1.5s linear infinite;
    }
  </style>
</head>
<body class="min-h-screen text-slate-100 antialiased selection:bg-blue-600 selection:text-white">

  <!-- ======================================================== -->
  <!-- 1. DESKTOP LEFT SIDEBAR (FIXED w-64)                    -->
  <!-- ======================================================== -->
  <aside class="hidden md:flex flex-col fixed inset-y-0 left-0 w-64 z-50 bg-[#08122c]/95 backdrop-blur-2xl border-r border-blue-500/20 shadow-2xl transition-all duration-300">
    
    <!-- Logo Brand PLTS Shawir -->
    <div class="px-6 py-6 border-b border-blue-500/10 flex items-center gap-3.5">
      <!-- Icon Solar Panel Glossy with Golden Rays -->
      <div class="relative w-11 h-11 flex-shrink-0">
        <svg class="w-full h-full drop-shadow-[0_0_12px_rgba(56,189,248,0.5)]" viewBox="0 0 48 48" fill="none">
          <!-- Sun Rays -->
          <circle cx="38" cy="10" r="6" fill="#f59e0b" opacity="0.9" />
          <path d="M38 2v3M38 15v3M46 10h-3M33 10h-3M43.7 4.3l-2.1 2.1M34.4 13.6l-2.1 2.1M43.7 15.7l-2.1-2.1M34.4 6.4l-2.1-2.1" stroke="#f59e0b" stroke-width="1.8" stroke-linecap="round"/>
          
          <!-- Solar Photovoltaic Grid Panel -->
          <path d="M6 38L18 16H42L32 38H6Z" fill="url(#pv-grad)" stroke="#38bdf8" stroke-width="1.5" stroke-linejoin="round"/>
          <!-- Grid Lines -->
          <path d="M12 27H37M20 16L14 38M30 16L24 38" stroke="#38bdf8" stroke-width="1" stroke-opacity="0.8"/>
          
          <defs>
            <linearGradient id="pv-grad" x1="18" y1="16" x2="19" y2="38" gradientUnits="userSpaceOnUse">
              <stop stop-color="#0284c7"/>
              <stop offset="1" stop-color="#0f172a"/>
            </linearGradient>
          </defs>
        </svg>
      </div>

      <!-- Text Logo -->
      <div>
        <div class="flex items-baseline gap-1.5">
          <span class="text-xl font-black text-white tracking-wider">PLTS</span>
          <span class="text-xl font-black text-blue-400">Shawir</span>
        </div>
        <span class="text-[10px] text-slate-400 font-medium tracking-wide uppercase block -mt-0.5">Monitoring System</span>
      </div>
    </div>

    <!-- Navigation Menu Items -->
    <nav class="flex-1 px-4 py-6 space-y-1.5 overflow-y-auto">
      
      <!-- 1. Realtime Monitor -->
      <a href="index.php" class="flex items-center gap-3.5 px-4 py-3 rounded-xl text-sm font-semibold transition-all duration-200 <?= ($currentPage === 'index.php') ? 'bg-blue-600 text-white shadow-lg shadow-blue-500/30' : 'text-slate-300 hover:text-white hover:bg-blue-600/10' ?>">
        <svg class="w-5 h-5 flex-shrink-0 <?= ($currentPage === 'index.php') ? 'text-white' : 'text-blue-400' ?>" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
        </svg>
        <span>Realtime Monitor</span>
      </a>

      <!-- 2. Pemakaian Harian -->
      <a href="daily.php" class="flex items-center gap-3.5 px-4 py-3 rounded-xl text-sm font-semibold transition-all duration-200 <?= ($currentPage === 'daily.php') ? 'bg-blue-600 text-white shadow-lg shadow-blue-500/30' : 'text-slate-300 hover:text-white hover:bg-blue-600/10' ?>">
        <svg class="w-5 h-5 flex-shrink-0 <?= ($currentPage === 'daily.php') ? 'text-white' : 'text-amber-400' ?>" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
        </svg>
        <span>Pemakaian Harian</span>
      </a>

      <!-- 3. Pemakaian Bulanan -->
      <a href="monthly.php" class="flex items-center gap-3.5 px-4 py-3 rounded-xl text-sm font-semibold transition-all duration-200 <?= ($currentPage === 'monthly.php') ? 'bg-blue-600 text-white shadow-lg shadow-blue-500/30' : 'text-slate-300 hover:text-white hover:bg-blue-600/10' ?>">
        <svg class="w-5 h-5 flex-shrink-0 <?= ($currentPage === 'monthly.php') ? 'text-white' : 'text-emerald-400' ?>" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
        </svg>
        <span>Pemakaian Bulanan</span>
      </a>

      <!-- 4. Riwayat Data -->
      <a href="history.php" class="flex items-center gap-3.5 px-4 py-3 rounded-xl text-sm font-semibold transition-all duration-200 <?= ($currentPage === 'history.php') ? 'bg-blue-600 text-white shadow-lg shadow-blue-500/30' : 'text-slate-300 hover:text-white hover:bg-blue-600/10' ?>">
        <svg class="w-5 h-5 flex-shrink-0 <?= ($currentPage === 'history.php') ? 'text-white' : 'text-purple-400' ?>" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
        </svg>
        <span>Riwayat Data</span>
      </a>

      <!-- 5. Pengaturan Inverter -->
      <a href="settings.php" class="flex items-center gap-3.5 px-4 py-3 rounded-xl text-sm font-semibold transition-all duration-200 <?= ($currentPage === 'settings.php') ? 'bg-blue-600 text-white shadow-lg shadow-blue-500/30' : 'text-slate-300 hover:text-white hover:bg-blue-600/10' ?>">
        <svg class="w-5 h-5 flex-shrink-0 <?= ($currentPage === 'settings.php') ? 'text-white' : 'text-cyan-400' ?>" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
        </svg>
        <span>Pengaturan Inverter</span>
      </a>

      <!-- 6. Admin Panel / Login -->
      <?php if ($adminLoggedIn): ?>
        <a href="logout.php" class="flex items-center gap-3.5 px-4 py-3 rounded-xl text-sm font-semibold text-rose-400 hover:text-white hover:bg-rose-600/20 transition-all duration-200">
          <svg class="w-5 h-5 flex-shrink-0 text-rose-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
          </svg>
          <span>Keluar (<?= htmlspecialchars(getCurrentUser()) ?>)</span>
        </a>
      <?php else: ?>
        <a href="login.php" class="flex items-center gap-3.5 px-4 py-3 rounded-xl text-sm font-semibold transition-all duration-200 <?= ($currentPage === 'login.php') ? 'bg-blue-600 text-white shadow-lg shadow-blue-500/30' : 'text-slate-300 hover:text-white hover:bg-blue-600/10' ?>">
          <svg class="w-5 h-5 flex-shrink-0 <?= ($currentPage === 'login.php') ? 'text-white' : 'text-slate-400' ?>" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
          </svg>
          <span>Login Admin</span>
        </a>
      <?php endif; ?>

    </nav>

    <!-- Bottom Sidebar Graphic (Solar House Artwork & Clean Energy Quote) -->
    <div class="p-4 border-t border-blue-500/10">
      <div class="relative overflow-hidden rounded-2xl p-4 bg-gradient-to-b from-[#0e214d] to-[#08122c] border border-blue-500/30 shadow-lg text-center group">
        <!-- Modern House with Solar Rooftop SVG Artwork -->
        <svg class="w-full h-24 mx-auto mb-2 drop-shadow-[0_0_12px_rgba(59,130,246,0.3)] transition-transform group-hover:scale-105 duration-300" viewBox="0 0 200 100" fill="none">
          <defs>
            <linearGradient id="sky-grad" x1="0" y1="0" x2="0" y2="100" gradientUnits="userSpaceOnUse">
              <stop stop-color="#1e3a8a" stop-opacity="0.8"/>
              <stop offset="1" stop-color="#091436"/>
            </linearGradient>
            <linearGradient id="roof-panel" x1="50" y1="30" x2="130" y2="70" gradientUnits="userSpaceOnUse">
              <stop stop-color="#0284c7"/>
              <stop offset="1" stop-color="#0369a1"/>
            </linearGradient>
            <linearGradient id="tree-grad" x1="0" y1="0" x2="0" y2="1">
              <stop stop-color="#10b981"/>
              <stop offset="1" stop-color="#047857"/>
            </linearGradient>
          </defs>
          
          <!-- Sky & Stars -->
          <rect width="200" height="100" rx="12" fill="url(#sky-grad)"/>
          <circle cx="30" cy="20" r="1.5" fill="#ffffff" opacity="0.8"/>
          <circle cx="75" cy="14" r="1" fill="#ffffff" opacity="0.6"/>
          <circle cx="170" cy="24" r="1.5" fill="#ffffff" opacity="0.8"/>
          <circle cx="145" cy="16" r="1" fill="#ffffff" opacity="0.7"/>

          <!-- Green Hill Base -->
          <ellipse cx="100" cy="98" rx="110" ry="24" fill="#065f46" opacity="0.85"/>
          <ellipse cx="100" cy="94" rx="90" ry="16" fill="#10b981" opacity="0.3"/>

          <!-- Trees (Left & Right) -->
          <path d="M22 80 C 18 72, 18 64, 25 58 C 32 64, 32 72, 28 80 Z" fill="url(#tree-grad)"/>
          <path d="M15 82 C 12 76, 12 70, 18 66 C 24 70, 24 76, 21 82 Z" fill="#047857"/>
          <path d="M175 80 C 170 72, 170 64, 178 58 C 186 64, 186 72, 181 80 Z" fill="url(#tree-grad)"/>

          <!-- Modern House Building -->
          <polygon points="50,82 50,54 100,26 150,54 150,82" fill="#1e293b"/>
          <!-- Warm glowing windows -->
          <rect x="64" y="60" width="16" height="14" rx="2" fill="#fef08a" opacity="0.9" filter="drop-shadow(0 0 4px #eab308)"/>
          <rect x="120" y="60" width="16" height="14" rx="2" fill="#fef08a" opacity="0.9" filter="drop-shadow(0 0 4px #eab308)"/>
          <!-- Front Door -->
          <rect x="94" y="64" width="12" height="18" rx="2" fill="#0f172a"/>

          <!-- Solar Rooftop Panel with Golden Frame -->
          <polygon points="52,52 100,26 148,52 100,56" fill="url(#roof-panel)" stroke="#38bdf8" stroke-width="1.2"/>
          <!-- Photovoltaic Grid on Roof -->
          <line x1="76" y1="39" x2="124" y2="39" stroke="#7dd3fc" stroke-width="0.8"/>
          <line x1="64" y1="46" x2="136" y2="46" stroke="#7dd3fc" stroke-width="0.8"/>
          <line x1="88" y1="33" x2="88" y2="54" stroke="#7dd3fc" stroke-width="0.8"/>
          <line x1="100" y1="27" x2="100" y2="56" stroke="#7dd3fc" stroke-width="0.8"/>
          <line x1="112" y1="33" x2="112" y2="54" stroke="#7dd3fc" stroke-width="0.8"/>

          <!-- Sun Reflection Flare -->
          <ellipse cx="118" cy="38" rx="14" ry="4" fill="#ffffff" opacity="0.4" transform="rotate(-15 118 38)"/>
        </svg>

        <p class="text-xs font-semibold text-slate-200 italic tracking-wide">
          "Energi Bersih Untuk Masa Depan"
        </p>
        <div class="w-12 h-0.5 mx-auto mt-2 bg-gradient-to-r from-emerald-500 to-cyan-400 rounded-full shadow-[0_0_8px_rgba(16,185,129,0.8)]"></div>
      </div>
    </div>

  </aside>

  <!-- ======================================================== -->
  <!-- 2. MOBILE TOP HEADER (STICKY)                           -->
  <!-- ======================================================== -->
  <header class="md:hidden sticky top-0 z-40 bg-[#08122c]/90 backdrop-blur-xl border-b border-blue-500/20 px-4 py-3 flex items-center justify-between shadow-md">
    
    <!-- Logo Brand PLTS Shawir -->
    <a href="index.php" class="flex items-center gap-2.5">
      <div class="w-8 h-8 flex-shrink-0">
        <svg class="w-full h-full" viewBox="0 0 48 48" fill="none">
          <circle cx="38" cy="10" r="5" fill="#f59e0b" />
          <path d="M6 38L18 16H42L32 38H6Z" fill="#0284c7" stroke="#38bdf8" stroke-width="2"/>
        </svg>
      </div>
      <div>
        <div class="flex items-baseline gap-1">
          <span class="text-base font-black text-white">PLTS</span>
          <span class="text-base font-black text-blue-400">Shawir</span>
        </div>
      </div>
    </a>

    <!-- Status Badges Mobile Top Right -->
    <div class="flex items-center gap-2">
      <!-- Live Status Dot -->
      <div class="flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-emerald-500/15 border border-emerald-500/30 text-emerald-400 text-[11px] font-bold">
        <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
        <span>Online</span>
      </div>

      <!-- Settings / Profile Link -->
      <a href="settings.php" class="p-2 rounded-xl bg-slate-800/80 border border-slate-700 text-slate-300 hover:text-white transition-colors" title="Pengaturan">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
        </svg>
      </a>
    </div>

  </header>

  <!-- ======================================================== -->
  <!-- 3. MOBILE FLOATING BOTTOM NAVIGATION DOCK (PINNED)       -->
  <!-- ======================================================== -->
  <nav class="md:hidden fixed bottom-3 inset-x-3 z-50 bg-[#0c183d]/95 backdrop-blur-2xl border border-blue-500/30 rounded-2xl py-2 px-5 flex items-center justify-around shadow-[0_10px_30px_rgba(0,0,0,0.8)]">
    
    <!-- 1. Beranda -->
    <a href="index.php" class="flex flex-col items-center gap-0.5 py-1 px-2.5 rounded-xl transition-colors <?= ($currentPage === 'index.php') ? 'text-blue-400 font-bold' : 'text-slate-400 hover:text-slate-200' ?>">
      <svg class="w-5 h-5 <?= ($currentPage === 'index.php') ? 'text-blue-400 drop-shadow-[0_0_8px_rgba(59,130,246,0.6)]' : '' ?>" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
      </svg>
      <span class="text-[10px]">Beranda</span>
    </a>

    <!-- 2. Data & Laporan -->
    <a href="daily.php" class="flex flex-col items-center gap-0.5 py-1 px-2.5 rounded-xl transition-colors <?= in_array($currentPage, ['daily.php', 'monthly.php', 'history.php']) ? 'text-blue-400 font-bold' : 'text-slate-400 hover:text-slate-200' ?>">
      <svg class="w-5 h-5 <?= in_array($currentPage, ['daily.php', 'monthly.php', 'history.php']) ? 'text-blue-400 drop-shadow-[0_0_8px_rgba(59,130,246,0.6)]' : '' ?>" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
      </svg>
      <span class="text-[10px]">Data</span>
    </a>

    <!-- 3. Pengaturan -->
    <a href="settings.php" class="flex flex-col items-center gap-0.5 py-1 px-2.5 rounded-xl transition-colors <?= ($currentPage === 'settings.php') ? 'text-blue-400 font-bold' : 'text-slate-400 hover:text-slate-200' ?>">
      <svg class="w-5 h-5 <?= ($currentPage === 'settings.php') ? 'text-blue-400 drop-shadow-[0_0_8px_rgba(59,130,246,0.6)]' : '' ?>" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
      </svg>
      <span class="text-[10px]">Pengaturan</span>
    </a>

    <!-- 4. Admin -->
    <?php if ($adminLoggedIn): ?>
      <a href="logout.php" class="flex flex-col items-center gap-0.5 py-1 px-2.5 rounded-xl transition-colors text-rose-400">
        <svg class="w-5 h-5 text-rose-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
        </svg>
        <span class="text-[10px]">Keluar</span>
      </a>
    <?php else: ?>
      <a href="login.php" class="flex flex-col items-center gap-0.5 py-1 px-2.5 rounded-xl transition-colors <?= ($currentPage === 'login.php') ? 'text-blue-400 font-bold' : 'text-slate-400 hover:text-slate-200' ?>">
        <svg class="w-5 h-5 <?= ($currentPage === 'login.php') ? 'text-blue-400' : '' ?>" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
        </svg>
        <span class="text-[10px]">Admin</span>
      </a>
    <?php endif; ?>

  </nav>

  <!-- ======================================================== -->
  <!-- 4. MAIN CONTENT WRAPPER (DESKTOP OFFSET + TOP BAR)       -->
  <!-- ======================================================== -->
  <div class="md:pl-64 flex flex-col min-h-screen pb-24 md:pb-8">
    
    <!-- Top Header Bar (Desktop Only) -->
    <header class="hidden md:flex items-center justify-between px-6 lg:px-8 py-3.5 border-b border-blue-500/15 bg-[#08122c]/60 backdrop-blur-md sticky top-0 z-40">
      
      <!-- Status Pills Container (Left) -->
      <div class="flex items-center gap-2.5 flex-wrap text-xs">
        
        <!-- Online Pill -->
        <div id="header-status-pill" class="flex items-center gap-2 px-3 py-1.5 rounded-full bg-emerald-500/10 border border-emerald-500/30 text-emerald-400 font-semibold shadow-sm">
          <span class="relative flex h-2.5 w-2.5">
            <span id="header-ping-dot" class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
            <span id="header-solid-dot" class="relative inline-flex rounded-full h-2.5 w-2.5 bg-emerald-500"></span>
          </span>
          <span id="header-status-text">Online</span>
        </div>

        <!-- ESP32 Wifi Pill -->
        <div id="header-esp32-pill" class="flex items-center gap-2 px-3 py-1.5 rounded-full bg-cyan-500/10 border border-cyan-500/30 text-cyan-400 font-medium">
          <svg class="w-3.5 h-3.5 text-cyan-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.111 16.404a5.5 5.5 0 017.778 0M12 20h.01m-7.08-7.071c3.904-3.905 10.236-3.905 14.141 0M1.394 9.393c5.857-5.857 15.355-5.857 21.213 0"/>
          </svg>
          <span id="header-esp32-text">ESP32: Terhubung</span>
        </div>

        <!-- Inverter Model Pill -->
        <div class="flex items-center gap-1.5 px-3 py-1.5 rounded-full bg-slate-800/80 border border-slate-700/60 text-slate-300 font-mono text-[11px]">
          <svg class="w-3 h-3 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2z"/>
          </svg>
          <span>Inverter: <?= htmlspecialchars(APP_DEVICE_MODEL) ?> @ 2400 Bd</span>
        </div>

      </div>

      <!-- Realtime Live Clock & Actions (Right) -->
      <div class="flex items-center gap-3">
        <!-- Live Clock Pill -->
        <div class="flex items-center gap-2 px-3 py-1.5 rounded-full bg-slate-900/80 border border-blue-500/20 text-slate-300 font-mono text-xs shadow-inner">
          <svg class="w-3.5 h-3.5 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
          </svg>
          <span id="header-live-clock">--:--:--</span>
        </div>

        <!-- Quick Settings Gear Button -->
        <a href="settings.php" class="p-2 rounded-xl bg-slate-800/80 border border-slate-700/80 text-slate-400 hover:text-white hover:border-blue-500/50 transition-colors shadow-sm" title="Pengaturan">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
          </svg>
        </a>
      </div>

    </header>

    <!-- Main Content Area Starts -->
    <main class="flex-1">

  <script>
    // Live Digital Header Clock Script (Indonesian Date & Time)
    function updateHeaderClock() {
      const clockEl = document.getElementById('header-live-clock');
      if (!clockEl) return;
      const now = new Date();
      const months = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
      const day = String(now.getDate()).padStart(2, '0');
      const mon = months[now.getMonth()];
      const year = now.getFullYear();
      const hrs = String(now.getHours()).padStart(2, '0');
      const min = String(now.getMinutes()).padStart(2, '0');
      const sec = String(now.getSeconds()).padStart(2, '0');
      clockEl.innerText = `${day} ${mon} ${year} ${hrs}:${min}:${sec}`;
    }
    updateHeaderClock();
    setInterval(updateHeaderClock, 1000);
  </script>
