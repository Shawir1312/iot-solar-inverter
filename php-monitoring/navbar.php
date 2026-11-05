<?php
require_once __DIR__ . '/config.php';
$currentPage = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="id" class="dark">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= htmlspecialchars($pageTitle ?? APP_NAME) ?></title>
  
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
    @keyframes flow-right {
      0% { stroke-dashoffset: 24; }
      100% { stroke-dashoffset: 0; }
    }
    .flow-active {
      stroke-dasharray: 6, 6;
      animation: flow-right 1.2s linear infinite;
    }
  </style>

  <!-- Script Light/Dark Mode -->
  <script>
    if (localStorage.theme === 'light' || (!('theme' in localStorage) && window.matchMedia('(prefers-color-scheme: light)').matches)) {
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
      updateThemeIcon();
    }
  </script>
</head>
<body class="bg-slate-50 dark:bg-slate-950 text-slate-800 dark:text-slate-100 min-h-screen font-sans antialiased transition-colors duration-200">

  <!-- Navbar Utama -->
  <nav class="bg-white dark:bg-slate-900 border-b border-slate-200 dark:border-slate-800 sticky top-0 z-50 shadow-sm transition-colors duration-200">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
      <div class="flex items-center justify-between h-16">
        
        <!-- Logo & Judul -->
        <div class="flex items-center gap-3">
          <div class="p-2 bg-amber-500/10 dark:bg-amber-500/20 rounded-xl text-amber-500">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z" />
            </svg>
          </div>
          <div>
            <span class="font-bold text-base md:text-lg text-slate-900 dark:text-white block leading-tight">PowMr 6.5kW</span>
            <span class="text-[11px] text-slate-500 dark:text-slate-400 font-medium">Solar Energy Monitor</span>
          </div>
        </div>

        <!-- Menu Navigasi Desktop -->
        <div class="hidden md:flex items-center space-x-1">
          <a href="index.php" class="px-3.5 py-2 rounded-lg text-sm font-medium transition-colors <?= ($currentPage === 'index.php') ? 'bg-amber-500 text-white' : 'text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800' ?>">
            ⚡ Realtime Monitor
          </a>
          <a href="daily.php" class="px-3.5 py-2 rounded-lg text-sm font-medium transition-colors <?= ($currentPage === 'daily.php') ? 'bg-amber-500 text-white' : 'text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800' ?>">
            📅 Pemakaian Harian
          </a>
          <a href="monthly.php" class="px-3.5 py-2 rounded-lg text-sm font-medium transition-colors <?= ($currentPage === 'monthly.php') ? 'bg-amber-500 text-white' : 'text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800' ?>">
            📊 Pemakaian Bulanan
          </a>
          <a href="history.php" class="px-3.5 py-2 rounded-lg text-sm font-medium transition-colors <?= ($currentPage === 'history.php') ? 'bg-amber-500 text-white' : 'text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800' ?>">
            📋 Riwayat Data
          </a>
          <a href="install.php" class="px-3.5 py-2 rounded-lg text-sm font-medium transition-colors <?= ($currentPage === 'install.php') ? 'bg-amber-500 text-white' : 'text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800' ?>">
            🛠️ Database Setup
          </a>
        </div>

        <!-- Tombol Aksi: Light/Dark Mode + Status -->
        <div class="flex items-center gap-2">
          <!-- Tombol Switch Dark/Light Mode -->
          <button onclick="toggleDarkMode()" class="p-2 rounded-xl text-slate-500 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors" title="Ubah Mode Terang / Gelap">
            <!-- Icon Sun (Muncul di Dark Mode) -->
            <svg id="theme-icon-sun" class="w-5 h-5 hidden dark:block text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z" />
            </svg>
            <!-- Icon Moon (Muncul di Light Mode) -->
            <svg id="theme-icon-moon" class="w-5 h-5 block dark:hidden text-slate-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z" />
            </svg>
          </button>

          <!-- Menu Mobile Toggle -->
          <button onclick="toggleMobileMenu()" class="md:hidden p-2 rounded-xl text-slate-500 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
          </button>
        </div>

      </div>

      <!-- Dropdown Menu Mobile -->
      <div id="mobile-menu" class="hidden md:hidden pb-3 pt-2 space-y-1 border-t border-slate-200 dark:border-slate-800">
        <a href="index.php" class="block px-3 py-2 rounded-md text-base font-medium <?= ($currentPage === 'index.php') ? 'bg-amber-500 text-white' : 'text-slate-600 dark:text-slate-300' ?>">⚡ Realtime Monitor</a>
        <a href="daily.php" class="block px-3 py-2 rounded-md text-base font-medium <?= ($currentPage === 'daily.php') ? 'bg-amber-500 text-white' : 'text-slate-600 dark:text-slate-300' ?>">📅 Pemakaian Harian</a>
        <a href="monthly.php" class="block px-3 py-2 rounded-md text-base font-medium <?= ($currentPage === 'monthly.php') ? 'bg-amber-500 text-white' : 'text-slate-600 dark:text-slate-300' ?>">📊 Pemakaian Bulanan</a>
        <a href="history.php" class="block px-3 py-2 rounded-md text-base font-medium <?= ($currentPage === 'history.php') ? 'bg-amber-500 text-white' : 'text-slate-600 dark:text-slate-300' ?>">📋 Riwayat Data</a>
        <a href="install.php" class="block px-3 py-2 rounded-md text-base font-medium <?= ($currentPage === 'install.php') ? 'bg-amber-500 text-white' : 'text-slate-600 dark:text-slate-300' ?>">🛠️ Database Setup</a>
      </div>

    </div>
  </nav>

  <script>
    function toggleMobileMenu() {
      const menu = document.getElementById('mobile-menu');
      menu.classList.toggle('hidden');
    }
  </script>

