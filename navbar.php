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
              50: '#fffbeb',
              100: '#fef3c7',
              400: '#fbbf24',
              500: '#f59e0b',
              600: '#d97706',
              700: '#b45309'
            }
          }
        }
      }
    }
  </script>

  <style>
    /* Animasi Aliran Daya (Power Flow Particles) */
    @keyframes flow-forward {
      0% { stroke-dashoffset: 24; }
      100% { stroke-dashoffset: 0; }
    }
    @keyframes flow-reverse {
      0% { stroke-dashoffset: 0; }
      100% { stroke-dashoffset: 24; }
    }

    .flow-pv-active {
      stroke-dasharray: 6, 6;
      animation: flow-forward 1.0s linear infinite;
    }
    .flow-grid-active {
      stroke-dasharray: 6, 6;
      animation: flow-forward 1.2s linear infinite;
    }
    .flow-load-active {
      stroke-dasharray: 6, 6;
      animation: flow-forward 1.0s linear infinite;
    }
    .flow-batt-charge {
      stroke-dasharray: 6, 6;
      animation: flow-forward 1.1s linear infinite;
    }
    .flow-batt-discharge {
      stroke-dasharray: 6, 6;
      animation: flow-reverse 1.1s linear infinite;
    }

    .pulse-glow-amber {
      box-shadow: 0 0 20px rgba(245, 158, 11, 0.25);
    }
    .pulse-glow-emerald {
      box-shadow: 0 0 20px rgba(16, 185, 129, 0.25);
    }
    .pulse-glow-cyan {
      box-shadow: 0 0 20px rgba(6, 182, 212, 0.25);
    }
  </style>

  <!-- Script Light/Dark Mode Sesuai LocalStorage -->
  <script>
    if (localStorage.theme === 'light') {
      document.documentElement.classList.remove('dark');
    } else {
      document.documentElement.classList.add('dark');
    }

    function toggleDarkMode() {
      if (document.documentElement.classList.contains('dark')) {
        document.documentElement.classList.remove('dark');
        localStorage.theme = 'light';
      } else {
        document.documentElement.classList.add('dark');
        localStorage.theme = 'dark';
      }
      updateThemeIcons();
    }

    function updateThemeIcons() {
      const isDark = document.documentElement.classList.contains('dark');
      const sunIcons = document.querySelectorAll('.theme-icon-sun');
      const moonIcons = document.querySelectorAll('.theme-icon-moon');
      
      sunIcons.forEach(el => {
        el.style.display = isDark ? 'block' : 'none';
      });
      moonIcons.forEach(el => {
        el.style.display = isDark ? 'none' : 'block';
      });
    }

    document.addEventListener("DOMContentLoaded", () => {
      updateThemeIcons();
    });
  </script>
</head>
<body class="bg-slate-50 dark:bg-slate-950 text-slate-800 dark:text-slate-100 min-h-screen font-sans antialiased selection:bg-amber-500 selection:text-white transition-colors duration-200">

  <!-- Navbar Utama -->
  <nav class="bg-white/90 dark:bg-slate-900/90 backdrop-blur-md border-b border-slate-200 dark:border-slate-800 sticky top-0 z-50 shadow-sm transition-colors duration-200">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
      <div class="flex items-center justify-between h-16">
        
        <!-- Logo & Judul -->
        <a href="index.php" class="flex items-center gap-3 group">
          <div class="p-2 bg-amber-500/10 dark:bg-amber-500/20 rounded-xl text-amber-500 group-hover:scale-105 transition-transform">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z" />
            </svg>
          </div>
          <div>
            <span class="font-bold text-base md:text-lg text-slate-900 dark:text-white block leading-tight">PowMr 6.5kW</span>
            <span class="text-[11px] text-slate-500 dark:text-slate-400 font-medium">Solar Energy Monitor</span>
          </div>
        </a>

        <!-- Menu Navigasi Desktop -->
        <div class="hidden md:flex items-center space-x-1">
          <a href="index.php" class="px-3.5 py-2 rounded-xl text-xs sm:text-sm font-semibold transition-colors <?= ($currentPage === 'index.php') ? 'bg-amber-500 text-slate-950 shadow-sm' : 'text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800' ?>">
            ⚡ Realtime Monitor
          </a>
          <a href="daily.php" class="px-3.5 py-2 rounded-xl text-xs sm:text-sm font-semibold transition-colors <?= ($currentPage === 'daily.php') ? 'bg-amber-500 text-slate-950 shadow-sm' : 'text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800' ?>">
            📅 Pemakaian Harian
          </a>
          <a href="monthly.php" class="px-3.5 py-2 rounded-xl text-xs sm:text-sm font-semibold transition-colors <?= ($currentPage === 'monthly.php') ? 'bg-amber-500 text-slate-950 shadow-sm' : 'text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800' ?>">
            📊 Pemakaian Bulanan
          </a>
          <a href="history.php" class="px-3.5 py-2 rounded-xl text-xs sm:text-sm font-semibold transition-colors <?= ($currentPage === 'history.php') ? 'bg-amber-500 text-slate-950 shadow-sm' : 'text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800' ?>">
            📋 Riwayat Data
          </a>
          <a href="install.php" class="px-3 py-2 rounded-xl text-xs sm:text-sm font-medium transition-colors <?= ($currentPage === 'install.php') ? 'bg-amber-500 text-slate-950 shadow-sm' : 'text-slate-500 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800' ?>" title="Database Setup">
            🛠️ Database
          </a>
        </div>

        <!-- Tombol Aksi Kanan: Light/Dark Mode + Status Admin -->
        <div class="flex items-center gap-2">
          
          <!-- Tombol Switch Dark/Light Mode -->
          <button onclick="toggleDarkMode()" class="p-2 rounded-xl text-slate-500 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 border border-slate-200 dark:border-slate-800 transition-colors" title="Ubah Mode Terang / Gelap">
            <!-- Icon Sun (Muncul di Dark Mode) -->
            <svg class="theme-icon-sun w-5 h-5 text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z" />
            </svg>
            <!-- Icon Moon (Muncul di Light Mode) -->
            <svg class="theme-icon-moon w-5 h-5 text-slate-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z" />
            </svg>
          </button>

          <!-- Status Login Admin -->
          <?php if ($adminLoggedIn): ?>
            <div class="relative group">
              <div class="flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-amber-500/10 border border-amber-500/30 text-amber-600 dark:text-amber-400 text-xs font-semibold">
                <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                <span>Admin</span>
              </div>
              <!-- Dropdown Logout -->
              <div class="absolute right-0 mt-1 w-36 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl shadow-lg py-1 hidden group-hover:block transition-all z-50">
                <a href="logout.php" class="block px-3 py-2 text-xs font-medium text-rose-600 hover:bg-rose-50 dark:hover:bg-rose-950/30">
                  🚪 Keluar (Logout)
                </a>
              </div>
            </div>
          <?php else: ?>
            <a href="login.php" class="px-3 py-1.5 rounded-xl text-xs font-semibold bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 border border-slate-300 dark:border-slate-700 transition-colors flex items-center gap-1.5">
              <svg class="w-3.5 h-3.5 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"/></svg>
              <span>Login Admin</span>
            </a>
          <?php endif; ?>

          <!-- Tombol Menu Mobile -->
          <button onclick="toggleMobileMenu()" class="md:hidden p-2 rounded-xl text-slate-500 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 border border-slate-200 dark:border-slate-800">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
          </button>
        </div>

      </div>

      <!-- Dropdown Menu Mobile -->
      <div id="mobile-menu" class="hidden md:hidden pb-3 pt-2 space-y-1 border-t border-slate-200 dark:border-slate-800">
        <a href="index.php" class="block px-3 py-2 rounded-xl text-sm font-semibold <?= ($currentPage === 'index.php') ? 'bg-amber-500 text-slate-950' : 'text-slate-700 dark:text-slate-300' ?>">⚡ Realtime Monitor</a>
        <a href="daily.php" class="block px-3 py-2 rounded-xl text-sm font-semibold <?= ($currentPage === 'daily.php') ? 'bg-amber-500 text-slate-950' : 'text-slate-700 dark:text-slate-300' ?>">📅 Pemakaian Harian</a>
        <a href="monthly.php" class="block px-3 py-2 rounded-xl text-sm font-semibold <?= ($currentPage === 'monthly.php') ? 'bg-amber-500 text-slate-950' : 'text-slate-700 dark:text-slate-300' ?>">📊 Pemakaian Bulanan</a>
        <a href="history.php" class="block px-3 py-2 rounded-xl text-sm font-semibold <?= ($currentPage === 'history.php') ? 'bg-amber-500 text-slate-950' : 'text-slate-700 dark:text-slate-300' ?>">📋 Riwayat Data</a>
        <a href="install.php" class="block px-3 py-2 rounded-xl text-sm font-medium <?= ($currentPage === 'install.php') ? 'bg-amber-500 text-slate-950' : 'text-slate-500 dark:text-slate-400' ?>">🛠️ Database Setup</a>
        <?php if ($adminLoggedIn): ?>
          <a href="logout.php" class="block px-3 py-2 rounded-xl text-sm font-semibold text-rose-500 hover:bg-rose-50 dark:hover:bg-rose-950/20">🚪 Logout (<?= htmlspecialchars(getCurrentUser()) ?>)</a>
        <?php else: ?>
          <a href="login.php" class="block px-3 py-2 rounded-xl text-sm font-semibold text-amber-500 hover:bg-amber-50 dark:hover:bg-amber-950/20">🔑 Login Admin</a>
        <?php endif; ?>
      </div>

    </div>
  </nav>

  <script>
    function toggleMobileMenu() {
      const menu = document.getElementById('mobile-menu');
      menu.classList.toggle('hidden');
    }
  </script>

