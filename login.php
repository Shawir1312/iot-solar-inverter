<?php
/**
 * Halaman Login Admin Modern (Mendukung Dark & Light Mode, Responsive Mobile & Desktop)
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/auth.php';

$error = '';
$redirect = $_GET['redirect'] ?? 'index.php';

// Jika sudah login, langsung lempar ke dashboard
if (isLoggedIn()) {
    header("Location: {$redirect}");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if (attemptLogin($username, $password)) {
        header("Location: {$redirect}");
        exit;
    } else {
        $error = 'Username atau Password salah! Periksa kembali konfigurasi Anda.';
    }
}
?>
<!DOCTYPE html>
<html lang="id" class="dark">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Login Admin • <?= htmlspecialchars(APP_NAME) ?></title>
  
  <!-- Tailwind CSS -->
  <script src="https://cdn.tailwindcss.com"></script>
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
            }
          }
        }
      }
    }
  </script>

  <!-- Script Theme Selector: Light & Dark Mode -->
  <script>
    function applyTheme() {
      if (localStorage.theme === 'light' || (!('theme' in localStorage) && window.matchMedia('(prefers-color-scheme: light)').matches)) {
        document.documentElement.classList.remove('dark');
      } else {
        document.documentElement.classList.add('dark');
      }
      updateThemeButtons();
    }

    function setTheme(mode) {
      if (mode === 'light') {
        document.documentElement.classList.remove('dark');
        localStorage.theme = 'light';
      } else {
        document.documentElement.classList.add('dark');
        localStorage.theme = 'dark';
      }
      updateThemeButtons();
    }

    function toggleTheme() {
      if (document.documentElement.classList.contains('dark')) {
        setTheme('light');
      } else {
        setTheme('dark');
      }
    }

    function updateThemeButtons() {
      const isDark = document.documentElement.classList.contains('dark');
      const lightBtn = document.getElementById('btn-theme-light');
      const darkBtn = document.getElementById('btn-theme-dark');
      if (lightBtn && darkBtn) {
        if (isDark) {
          darkBtn.className = "px-3 py-1.5 rounded-lg text-xs font-semibold bg-amber-500 text-slate-950 shadow-sm flex items-center gap-1.5 transition-all";
          lightBtn.className = "px-3 py-1.5 rounded-lg text-xs font-medium text-slate-400 hover:text-white flex items-center gap-1.5 transition-all";
        } else {
          lightBtn.className = "px-3 py-1.5 rounded-lg text-xs font-semibold bg-amber-500 text-white shadow-sm flex items-center gap-1.5 transition-all";
          darkBtn.className = "px-3 py-1.5 rounded-lg text-xs font-medium text-slate-600 hover:text-slate-900 flex items-center gap-1.5 transition-all";
        }
      }
    }

    // Inisialisasi awal
    if (localStorage.theme === 'light') {
      document.documentElement.classList.remove('dark');
    } else {
      document.documentElement.classList.add('dark');
    }
  </script>
</head>
<body class="bg-slate-100 dark:bg-slate-950 text-slate-900 dark:text-slate-100 min-h-screen flex flex-col justify-between font-sans antialiased selection:bg-amber-500 selection:text-white transition-colors duration-300 relative overflow-x-hidden">

  <!-- Ambient Glow Effects di Background -->
  <div class="fixed inset-0 pointer-events-none overflow-hidden z-0">
    <div class="absolute -top-32 -left-32 w-96 h-96 bg-amber-500/15 dark:bg-amber-500/10 rounded-full blur-3xl"></div>
    <div class="absolute -bottom-32 -right-32 w-96 h-96 bg-blue-500/15 dark:bg-blue-500/10 rounded-full blur-3xl"></div>
  </div>

  <!-- Header Atas: Pilihan Mode Terang / Gelap -->
  <header class="relative z-10 max-w-5xl mx-auto w-full px-4 pt-6 flex items-center justify-between">
    <a href="index.php" class="flex items-center gap-2.5 group">
      <div class="p-2 bg-amber-500/10 dark:bg-amber-500/20 text-amber-500 rounded-xl group-hover:scale-105 transition-transform">
        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z" />
        </svg>
      </div>
      <div>
        <span class="font-bold text-base text-slate-900 dark:text-white tracking-tight leading-tight block">PowMr 6.5kW</span>
        <span class="text-[11px] text-slate-500 dark:text-slate-400">Solar Energy Monitor</span>
      </div>
    </a>

    <!-- Theme Switcher Selector Pills -->
    <div class="flex items-center p-1 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl shadow-sm">
      <button type="button" id="btn-theme-light" onclick="setTheme('light')" class="px-3 py-1.5 rounded-lg text-xs font-medium flex items-center gap-1.5 transition-all">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z" />
        </svg>
        <span>Terang</span>
      </button>
      <button type="button" id="btn-theme-dark" onclick="setTheme('dark')" class="px-3 py-1.5 rounded-lg text-xs font-semibold bg-amber-500 text-slate-950 flex items-center gap-1.5 transition-all">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z" />
        </svg>
        <span>Gelap</span>
      </button>
    </div>
  </header>

  <!-- Form Login Card -->
  <main class="relative z-10 max-w-md mx-auto w-full px-4 py-8">
    <div class="bg-white/80 dark:bg-slate-900/80 backdrop-blur-xl border border-slate-200 dark:border-slate-800 rounded-3xl p-6 sm:p-8 shadow-xl shadow-slate-200/50 dark:shadow-none transition-all">
      
      <!-- Icon & Headline -->
      <div class="text-center mb-6">
        <div class="inline-flex items-center justify-center w-14 h-14 rounded-2xl bg-gradient-to-tr from-amber-500 to-amber-400 text-slate-950 shadow-lg shadow-amber-500/20 mb-3">
          <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
          </svg>
        </div>
        <h1 class="text-xl sm:text-2xl font-black text-slate-900 dark:text-white tracking-tight">Login Administrator</h1>
        <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">Masuk untuk mengelola konfigurasi & telemetri inverter</p>
      </div>

      <!-- Pesan Kesalahan -->
      <?php if (!empty($error)): ?>
        <div class="mb-5 p-3.5 bg-rose-500/10 border border-rose-500/30 text-rose-600 dark:text-rose-400 rounded-xl text-xs flex items-center gap-2.5">
          <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
          </svg>
          <span><?= htmlspecialchars($error) ?></span>
        </div>
      <?php endif; ?>

      <form method="POST" action="login.php?redirect=<?= htmlspecialchars($redirect) ?>" class="space-y-4">
        <!-- Input Username -->
        <div>
          <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1.5">Username Admin</label>
          <div class="relative">
            <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
              </svg>
            </span>
            <input type="text" name="username" required autofocus placeholder="admin" value="admin"
              class="w-full pl-10 pr-4 py-2.5 bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl text-xs font-medium text-slate-900 dark:text-white placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-amber-500 transition-all">
          </div>
        </div>

        <!-- Input Password -->
        <div>
          <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1.5">Password</label>
          <div class="relative">
            <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/>
              </svg>
            </span>
            <input type="password" id="input-password" name="password" required placeholder="••••••••" value="admin123"
              class="w-full pl-10 pr-10 py-2.5 bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl text-xs font-medium text-slate-900 dark:text-white placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-amber-500 transition-all">
            <button type="button" onclick="togglePasswordVisibility()" class="absolute inset-y-0 right-0 pr-3.5 flex items-center text-slate-400 hover:text-slate-600 dark:hover:text-slate-200">
              <svg id="eye-icon" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
              </svg>
            </button>
          </div>
        </div>

        <!-- Tombol Submit -->
        <button type="submit"
          class="w-full py-3 bg-gradient-to-r from-amber-500 to-amber-600 hover:from-amber-600 hover:to-amber-700 text-slate-950 font-bold rounded-xl text-xs sm:text-sm tracking-wide shadow-lg shadow-amber-500/20 active:scale-[0.99] transition-all flex items-center justify-center gap-2">
          <span>Masuk ke Dashboard</span>
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/>
          </svg>
        </button>
      </form>

      <!-- Info Default Password & Quick Link -->
      <div class="mt-6 pt-5 border-t border-slate-200 dark:border-slate-800 text-center space-y-2">
        <p class="text-[11px] text-slate-500 dark:text-slate-400">
          Akun standar: <code class="px-1.5 py-0.5 rounded bg-slate-100 dark:bg-slate-800 text-amber-500 font-mono">admin</code> / <code class="px-1.5 py-0.5 rounded bg-slate-100 dark:bg-slate-800 text-amber-500 font-mono">admin123</code>
        </p>
        <div>
          <a href="index.php" class="text-xs font-semibold text-slate-600 dark:text-slate-400 hover:text-amber-500 dark:hover:text-amber-400 inline-flex items-center gap-1 transition-colors">
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
            Kembali ke Tampilan Monitor
          </a>
        </div>
      </div>

    </div>
  </main>

  <!-- Footer -->
  <footer class="relative z-10 text-center py-4 text-xs text-slate-400 dark:text-slate-500">
    <p>&copy; <?= date('Y') ?> PowMr Hybrid Inverter Monitor • plts.shawir.id</p>
  </footer>

  <script>
    updateThemeButtons();

    function togglePasswordVisibility() {
      const passInput = document.getElementById('input-password');
      if (passInput.type === 'password') {
        passInput.type = 'text';
      } else {
        passInput.type = 'password';
      }
    }
  </script>
</body>
</html>

