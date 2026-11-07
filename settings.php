<?php
/**
 * Halaman Pengaturan Parameter Inverter PowMr 6.5kW
 * Lokasi: Root Directory (http://plts.shawir.id/settings.php)
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/database.php';
$pageTitle = 'Pengaturan Inverter • ' . (defined('APP_NAME') ? APP_NAME : 'PowMr 6.5kW Monitor');
require_once __DIR__ . '/navbar.php';

// Pastikan koneksi database tersedia
$pdo = getDbConnection();
$currentSettings = [];
$commandLogs = [];

if ($pdo) {
    try {
        $st = $pdo->query("SELECT * FROM inverter_settings WHERE id = 1 LIMIT 1");
        $currentSettings = $st->fetch() ?: [];

        $stLogs = $pdo->query("SELECT * FROM inverter_commands ORDER BY id DESC LIMIT 15");
        $commandLogs = $stLogs->fetchAll() ?: [];
    } catch (Exception $e) {}
}

$curPop       = $currentSettings['output_source_priority'] ?? 'SBU';
$curPcp       = $currentSettings['charger_source_priority'] ?? 'SNU';
$curBt        = $currentSettings['battery_type'] ?? 'USE';
$curAcAmps    = intval($currentSettings['max_ac_charge_current'] ?? 10);
$curTotAmps   = intval($currentSettings['max_total_charge_current'] ?? 60);
$curPbc       = floatval($currentSettings['back_to_grid_voltage'] ?? 48.0);
$curPbd       = floatval($currentSettings['back_to_discharge_voltage'] ?? 54.0);
$curPcu       = floatval($currentSettings['cutoff_voltage'] ?? 42.0);
$curPbu       = floatval($currentSettings['bulk_voltage'] ?? 56.4);
$curPfl       = floatval($currentSettings['float_voltage'] ?? 54.0);
$curBuzzer    = isset($currentSettings['buzzer']) ? (intval($currentSettings['buzzer']) === 1) : true;
$curBacklight = isset($currentSettings['backlight']) ? (intval($currentSettings['backlight']) === 1) : true;
?>

<div class="max-w-7xl mx-auto px-3 sm:px-6 lg:px-8 py-3 sm:py-6 space-y-4 sm:space-y-6">

  <!-- Header Halaman -->
  <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl sm:rounded-3xl p-4 sm:p-6 shadow-sm flex flex-col md:flex-row md:items-center justify-between gap-4">
    <div class="flex items-center gap-3 sm:gap-4">
      <div class="p-3 bg-amber-500/10 text-amber-500 rounded-2xl flex-shrink-0">
        <svg class="w-7 h-7 sm:w-8 sm:h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
        </svg>
      </div>
      <div>
        <h1 class="text-lg sm:text-2xl font-black text-slate-900 dark:text-white leading-tight">Pengaturan Parameter Inverter</h1>
        <p class="text-xs sm:text-sm text-slate-500 dark:text-slate-400 mt-0.5">
          Baca parameter real-time langsung dari firmware PowMr 6.5kW dan ubah konfigurasi prioritas daya & baterai via remote web.
        </p>
      </div>
    </div>

    <!-- Tombol Refresh & Status -->
    <div class="flex items-center gap-2 self-start md:self-auto">
      <button onclick="loadSettings()" id="btn-refresh" class="px-3.5 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 text-xs sm:text-sm font-semibold border border-slate-300 dark:border-slate-700 transition-colors flex items-center gap-1.5 shadow-sm">
        <svg class="w-4 h-4 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
        <span>Refresh Setting</span>
      </button>
      <span id="badge-last-read" class="px-3 py-2 rounded-xl bg-amber-500/10 text-amber-600 dark:text-amber-400 font-mono text-xs font-semibold border border-amber-500/20">
        Update: <?= htmlspecialchars($currentSettings['updated_at'] ?? 'Belum ada data') ?>
      </span>
    </div>
  </div>

  <!-- Banner Pemberitahuan Status -->
  <div id="status-toast" class="hidden rounded-2xl p-4 text-xs sm:text-sm font-medium transition-all shadow-sm"></div>

  <!-- Form Pengaturan Inverter -->
  <form id="form-settings" onsubmit="saveSettings(event)" class="space-y-4 sm:space-y-6">

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 sm:gap-6">

      <!-- CARD 1: Prioritas Sumber Daya & Pengisian -->
      <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl sm:rounded-3xl p-4 sm:p-6 shadow-sm space-y-4">
        <div class="flex items-center gap-2.5 border-b border-slate-100 dark:border-slate-800 pb-3">
          <span class="p-2 bg-blue-500/10 text-blue-500 rounded-xl">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
          </span>
          <div>
            <h2 class="text-sm sm:text-base font-bold text-slate-900 dark:text-white">1. Prioritas Daya & Operasi</h2>
            <p class="text-[11px] text-slate-500">Konfigurasi alur kerja beban rumah dan pengisian aki</p>
          </div>
        </div>

        <!-- 1.1 Output Source Priority (Program 01) -->
        <div>
          <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">
            Prioritas Sumber Beban (Program 01)
          </label>
          <select id="set-output-priority" name="output_source_priority" class="w-full bg-slate-50 dark:bg-slate-950 border border-slate-300 dark:border-slate-700 rounded-xl px-3.5 py-2.5 text-xs sm:text-sm font-medium focus:ring-2 focus:ring-amber-500 outline-none transition-all">
            <option value="SBU" <?= ($curPop === 'SBU') ? 'selected' : '' ?>>SBU - Solar > Baterai > PLN (Rekomendasi Hemat Listrik)</option>
            <option value="SUB" <?= ($curPop === 'SUB') ? 'selected' : '' ?>>SUB - Solar > PLN > Baterai (Menjaga Baterai Tetap Penuh)</option>
            <option value="UTI" <?= ($curPop === 'UTI' || $curPop === 'USB') ? 'selected' : '' ?>>Utility First (USB) - PLN Utama, Solar/Aki sebagai Cadangan UPS</option>
          </select>
          <p class="text-[10px] sm:text-[11px] text-slate-500 mt-1">
            <b>SBU:</b> Rumah memakai tenaga surya dan baterai terlebih dahulu. PLN hanya masuk jika baterai mencapai batas bawah.
          </p>
        </div>

        <!-- 1.2 Charger Source Priority (Program 16) -->
        <div>
          <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">
            Prioritas Pengisian Baterai (Program 16)
          </label>
          <select id="set-charger-priority" name="charger_source_priority" class="w-full bg-slate-50 dark:bg-slate-950 border border-slate-300 dark:border-slate-700 rounded-xl px-3.5 py-2.5 text-xs sm:text-sm font-medium focus:ring-2 focus:ring-amber-500 outline-none transition-all">
            <option value="SNU" <?= ($curPcp === 'SNU') ? 'selected' : '' ?>>SNU - Solar & PLN Bersamaan (Paling Cepat Penuh)</option>
            <option value="CSO" <?= ($curPcp === 'CSO') ? 'selected' : '' ?>>CSO - Solar Utama (PLN hanya mengecas jika matahari tidak ada)</option>
            <option value="OSO" <?= ($curPcp === 'OSO') ? 'selected' : '' ?>>OSO - Only Solar (Murni Matahari, PLN DILARANG mengecas baterai)</option>
            <option value="UTI" <?= ($curPcp === 'UTI' || $curPcp === 'CUE') ? 'selected' : '' ?>>Utility First (PLN mengecas utama)</option>
          </select>
          <p class="text-[10px] sm:text-[11px] text-slate-500 mt-1">
            Pilih <b>OSO (Only Solar)</b> jika ingin 100% baterai hanya dicas dari panel surya dan hemat token PLN.
          </p>
        </div>

        <!-- 1.3 Battery Type (Program 05) -->
        <div>
          <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">
            Tipe Baterai (Program 05)
          </label>
          <select id="set-battery-type" name="battery_type" class="w-full bg-slate-50 dark:bg-slate-950 border border-slate-300 dark:border-slate-700 rounded-xl px-3.5 py-2.5 text-xs sm:text-sm font-medium focus:ring-2 focus:ring-amber-500 outline-none transition-all">
            <option value="USE" <?= ($curBt === 'USE') ? 'selected' : '' ?>>USE - User-Defined (Parameter Custom Kustomisasi)</option>
            <option value="AGM" <?= ($curBt === 'AGM') ? 'selected' : '' ?>>AGM - Aki Kering / SLA / VRLA</option>
            <option value="FLD" <?= ($curBt === 'FLD') ? 'selected' : '' ?>>FLD - Flooded (Aki Basah)</option>
            <option value="LI" <?= (strpos($curBt, 'LI') !== false) ? 'selected' : '' ?>>LI - Lithium (BMS Protocol)</option>
          </select>
        </div>

      </div>

      <!-- CARD 2: Batas Arus Pengisian (Ampere) -->
      <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl sm:rounded-3xl p-4 sm:p-6 shadow-sm space-y-4">
        <div class="flex items-center gap-2.5 border-b border-slate-100 dark:border-slate-800 pb-3">
          <span class="p-2 bg-emerald-500/10 text-emerald-500 rounded-xl">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
          </span>
          <div>
            <h2 class="text-sm sm:text-base font-bold text-slate-900 dark:text-white">2. Batas Arus Pengisian (Ampere)</h2>
            <p class="text-[11px] text-slate-500">Membatasi konsumsi daya saat mengecas dari PLN & PV</p>
          </div>
        </div>

        <!-- 2.1 Max AC Charging Current (Program 11) -->
        <div>
          <div class="flex justify-between items-center mb-1">
            <label class="text-xs font-bold text-slate-700 dark:text-slate-300">
              Arus Cas Maksimal dari PLN (Program 11)
            </label>
            <span id="badge-ac-amps" class="text-xs font-mono font-bold text-blue-500 bg-blue-500/10 px-2 py-0.5 rounded-lg"><?= $curAcAmps ?> A (~<?= round($curAcAmps * 48) ?>W)</span>
          </div>
          <select id="set-max-ac-charge" name="max_ac_charge_current" onchange="updateAmpsBadge(this.value)" class="w-full bg-slate-50 dark:bg-slate-950 border border-slate-300 dark:border-slate-700 rounded-xl px-3.5 py-2.5 text-xs sm:text-sm font-medium focus:ring-2 focus:ring-amber-500 outline-none transition-all">
            <?php for ($a = 10; $a <= 120; $a += 10): ?>
              <option value="<?= $a ?>" <?= ($curAcAmps == $a) ? 'selected' : '' ?>><?= $a ?> Ampere (~<?= round($a * 48) ?> Watt<?= $a <= 20 ? ' - Aman MCB 1300VA' : ($a == 120 ? ' - Maksimal Inverter' : '') ?>)</option>
            <?php endfor; ?>
          </select>
          <p class="text-[10px] sm:text-[11px] text-slate-500 mt-1">
            Setel ke <b>10A</b> atau <b>20A</b> agar MCB meteran PLN rumah tidak anjlok saat inverter mengecas baterai.
          </p>
        </div>

        <!-- 2.2 Max Total Charging Current (Program 02) -->
        <div>
          <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">
            Arus Cas Maksimal Gabungan (Program 02 - PV + PLN)
          </label>
          <select id="set-max-total-charge" name="max_total_charge_current" class="w-full bg-slate-50 dark:bg-slate-950 border border-slate-300 dark:border-slate-700 rounded-xl px-3.5 py-2.5 text-xs sm:text-sm font-medium focus:ring-2 focus:ring-amber-500 outline-none transition-all">
            <?php for ($a = 10; $a <= 120; $a += 10): ?>
              <option value="<?= $a ?>" <?= ($curTotAmps == $a) ? 'selected' : '' ?>><?= $a ?> Ampere (~<?= round($a * 48) ?> Watt<?= $a == 60 ? ' - Standar Aki 100Ah' : ($a == 120 ? ' - Maksimal Inverter' : '') ?>)</option>
            <?php endfor; ?>
          </select>
        </div>

      </div>

    </div>

    <!-- CARD 3: Ambang Batas Tegangan Baterai 48V (Voltase) -->
    <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl sm:rounded-3xl p-4 sm:p-6 shadow-sm space-y-4">
      <div class="flex items-center gap-2.5 border-b border-slate-100 dark:border-slate-800 pb-3">
        <span class="p-2 bg-amber-500/10 text-amber-500 rounded-xl">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        </span>
        <div>
          <h2 class="text-sm sm:text-base font-bold text-slate-900 dark:text-white">3. Ambang Batas Tegangan Baterai 48V</h2>
          <p class="text-[11px] text-slate-500">Konfigurasi tegangan cut-off proteksi dan tegangan perpindahan PLN</p>
        </div>
      </div>

      <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-3 sm:gap-4">
        
        <!-- Back to Grid Voltage (Program 12) -->
        <div>
          <label class="block text-xs font-semibold text-slate-600 dark:text-slate-300 mb-1">
            Pindah ke PLN (Prog 12)
          </label>
          <div class="relative">
            <input type="number" step="0.1" min="44.0" max="52.0" id="set-back-grid" name="back_to_grid_voltage" value="<?= number_format($curPbc, 1, '.', '') ?>" class="w-full bg-slate-50 dark:bg-slate-950 border border-slate-300 dark:border-slate-700 rounded-xl px-3 py-2 text-xs sm:text-sm font-mono font-bold focus:ring-2 focus:ring-amber-500 outline-none">
            <span class="absolute right-3 top-2.5 text-xs text-slate-400 font-bold">V</span>
          </div>
          <span class="text-[10px] text-slate-400">Saat baterai mencapai volt ini, PLN masuk.</span>
        </div>

        <!-- Back to Discharge Voltage (Program 13) -->
        <div>
          <label class="block text-xs font-semibold text-slate-600 dark:text-slate-300 mb-1">
            Balik ke Baterai (Prog 13)
          </label>
          <div class="relative">
            <input type="number" step="0.1" min="48.0" max="58.0" id="set-back-discharge" name="back_to_discharge_voltage" value="<?= number_format($curPbd, 1, '.', '') ?>" class="w-full bg-slate-50 dark:bg-slate-950 border border-slate-300 dark:border-slate-700 rounded-xl px-3 py-2 text-xs sm:text-sm font-mono font-bold focus:ring-2 focus:ring-amber-500 outline-none">
            <span class="absolute right-3 top-2.5 text-xs text-slate-400 font-bold">V</span>
          </div>
          <span class="text-[10px] text-slate-400">Volt baterai siap menyuplai rumah lagi.</span>
        </div>

        <!-- Cut-off Voltage (Program 29) -->
        <div>
          <label class="block text-xs font-semibold text-slate-600 dark:text-slate-300 mb-1">
            Cut-Off Mati (Prog 29)
          </label>
          <div class="relative">
            <input type="number" step="0.1" min="40.0" max="46.0" id="set-cutoff" name="cutoff_voltage" value="<?= number_format($curPcu, 1, '.', '') ?>" class="w-full bg-slate-50 dark:bg-slate-950 border border-slate-300 dark:border-slate-700 rounded-xl px-3 py-2 text-xs sm:text-sm font-mono font-bold focus:ring-2 focus:ring-amber-500 outline-none">
            <span class="absolute right-3 top-2.5 text-xs text-slate-400 font-bold">V</span>
          </div>
          <span class="text-[10px] text-slate-400">Batas darurat inverter mati melindungi sel.</span>
        </div>

        <!-- Bulk Charging (Program 26) -->
        <div>
          <label class="block text-xs font-semibold text-slate-600 dark:text-slate-300 mb-1">
            Bulk / Boost Charge (Prog 26)
          </label>
          <div class="relative">
            <input type="number" step="0.1" min="50.0" max="59.0" id="set-bulk" name="bulk_voltage" value="<?= number_format($curPbu, 1, '.', '') ?>" class="w-full bg-slate-50 dark:bg-slate-950 border border-slate-300 dark:border-slate-700 rounded-xl px-3 py-2 text-xs sm:text-sm font-mono font-bold focus:ring-2 focus:ring-amber-500 outline-none">
            <span class="absolute right-3 top-2.5 text-xs text-slate-400 font-bold">V</span>
          </div>
        </div>

        <!-- Float Charging (Program 27) -->
        <div>
          <label class="block text-xs font-semibold text-slate-600 dark:text-slate-300 mb-1">
            Float Charge (Prog 27)
          </label>
          <div class="relative">
            <input type="number" step="0.1" min="50.0" max="58.0" id="set-float" name="float_voltage" value="<?= number_format($curPfl, 1, '.', '') ?>" class="w-full bg-slate-50 dark:bg-slate-950 border border-slate-300 dark:border-slate-700 rounded-xl px-3 py-2 text-xs sm:text-sm font-mono font-bold focus:ring-2 focus:ring-amber-500 outline-none">
            <span class="absolute right-3 top-2.5 text-xs text-slate-400 font-bold">V</span>
          </div>
        </div>

      </div>
    </div>

    <!-- CARD 4: Saklar Fitur Cepat (Buzzer & Layar) -->
    <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl sm:rounded-3xl p-4 sm:p-6 shadow-sm space-y-3">
      <h2 class="text-sm sm:text-base font-bold text-slate-900 dark:text-white">4. Saklar Perilaku Inverter</h2>
      
      <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-3">
        <!-- Buzzer Switch -->
        <label class="flex items-center justify-between p-3 rounded-xl bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 cursor-pointer hover:border-amber-500 transition-colors">
          <div>
            <span class="text-xs font-bold text-slate-800 dark:text-slate-200 block">Buzzer Suara Beep (Prog 18)</span>
            <span class="text-[10px] text-slate-400">Bunyi saat tombol ditekan / alarm</span>
          </div>
          <input type="checkbox" id="set-buzzer" name="buzzer" value="1" <?= $curBuzzer ? 'checked' : '' ?> class="w-5 h-5 accent-amber-500 rounded">
        </label>

        <!-- Backlight Switch -->
        <label class="flex items-center justify-between p-3 rounded-xl bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 cursor-pointer hover:border-amber-500 transition-colors">
          <div>
            <span class="text-xs font-bold text-slate-800 dark:text-slate-200 block">Lampu Layar LCD (Prog 19)</span>
            <span class="text-[10px] text-slate-400">Backlight layar inverter tetap menyala</span>
          </div>
          <input type="checkbox" id="set-backlight" name="backlight" value="1" <?= $curBacklight ? 'checked' : '' ?> class="w-5 h-5 accent-amber-500 rounded">
        </label>
      </div>
    </div>

    <!-- Tombol Simpan & Terapkan -->
    <div class="flex items-center justify-end gap-3 pt-2">
      <button type="submit" id="btn-save" class="px-6 py-3 rounded-2xl bg-amber-500 hover:bg-amber-400 text-slate-950 font-bold text-sm shadow-md transition-all flex items-center gap-2">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"/></svg>
        <span>Terapkan Pengaturan ke Inverter</span>
      </button>
    </div>

  </form>

  <!-- CARD 5: Antrean Perintah & Riwayat Eksekusi -->
  <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl sm:rounded-3xl p-4 sm:p-6 shadow-sm space-y-3">
    <div class="flex items-center justify-between">
      <h2 class="text-sm sm:text-base font-bold text-slate-900 dark:text-white flex items-center gap-2">
        <span>📋 Riwayat Eksekusi Perintah Inverter</span>
      </h2>
      <div class="flex items-center gap-2">
        <button type="button" onclick="clearCommands()" class="text-[11px] px-2.5 py-1 rounded-lg bg-rose-500/10 hover:bg-rose-500/20 text-rose-500 font-medium transition-colors flex items-center gap-1 border border-rose-500/20">
          <span>🗑️ Bersihkan Antrean</span>
        </button>
        <span class="text-[11px] text-slate-400 font-mono hidden sm:inline">15 Perintah Terakhir</span>
      </div>
    </div>

    <div class="overflow-x-auto">
      <table class="w-full text-left text-xs text-slate-600 dark:text-slate-300">
        <thead class="bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-200 text-[11px] uppercase tracking-wider">
          <tr>
            <th class="px-3.5 py-2.5 rounded-l-xl">Waktu</th>
            <th class="px-3.5 py-2.5">Aksi / Label</th>
            <th class="px-3.5 py-2.5 font-mono">Kode Serial</th>
            <th class="px-3.5 py-2.5">Status</th>
            <th class="px-3.5 py-2.5 rounded-r-xl">Respon Inverter</th>
          </tr>
        </thead>
        <tbody id="table-commands-body" class="divide-y divide-slate-100 dark:divide-slate-800">
          <?php if (empty($commandLogs)): ?>
            <tr>
              <td colspan="5" class="px-3.5 py-4 text-center text-slate-400">Belum ada riwayat perintah pengaturan yang dikirim.</td>
            </tr>
          <?php else: ?>
            <?php foreach ($commandLogs as $cmd): ?>
              <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/50 transition-colors">
                <td class="px-3.5 py-2.5 font-mono"><?= htmlspecialchars($cmd['created_at']) ?></td>
                <td class="px-3.5 py-2.5 font-medium text-slate-800 dark:text-slate-200"><?= htmlspecialchars($cmd['command_label']) ?></td>
                <td class="px-3.5 py-2.5 font-mono font-bold text-amber-500"><?= htmlspecialchars($cmd['command_code']) ?></td>
                <td class="px-3.5 py-2.5">
                  <?php if ($cmd['status'] === 'success'): ?>
                    <span class="px-2 py-0.5 rounded-full bg-emerald-500/10 text-emerald-500 font-bold text-[10px]">Sukses</span>
                  <?php elseif ($cmd['status'] === 'pending'): ?>
                    <span class="px-2 py-0.5 rounded-full bg-amber-500/10 text-amber-500 font-bold text-[10px] animate-pulse">Menunggu ESP32</span>
                  <?php elseif ($cmd['status'] === 'sent'): ?>
                    <span class="px-2 py-0.5 rounded-full bg-blue-500/10 text-blue-500 font-bold text-[10px]">Terkirim</span>
                  <?php else: ?>
                    <span class="px-2 py-0.5 rounded-full bg-rose-500/10 text-rose-500 font-bold text-[10px]">Gagal</span>
                  <?php endif; ?>
                </td>
                <td class="px-3.5 py-2.5 font-mono text-[11px]"><?= htmlspecialchars($cmd['response'] ?: '-') ?></td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>

</div>

<!-- Script Logika Pengaturan -->
<script>
  function updateAmpsBadge(val) {
    // 48V inverter: watt ≈ 48V × Amps
    const watt = Math.round(parseInt(val) * 48);
    const wattStr = watt >= 1000 ? (watt/1000).toFixed(1) + ' kW' : watt + 'W';
    document.getElementById('badge-ac-amps').innerText = val + " A (~" + wattStr + ")";
  }

  function showToast(msg, isSuccess = true) {
    const t = document.getElementById('status-toast');
    t.className = isSuccess 
      ? "rounded-2xl p-4 text-xs sm:text-sm font-semibold bg-emerald-500/10 text-emerald-700 dark:text-emerald-300 border border-emerald-500/30 shadow-sm"
      : "rounded-2xl p-4 text-xs sm:text-sm font-semibold bg-rose-500/10 text-rose-700 dark:text-rose-300 border border-rose-500/30 shadow-sm";
    t.innerHTML = (isSuccess ? "✅ " : "❌ ") + msg;
    t.classList.remove('hidden');
    window.scrollTo({ top: 0, behavior: 'smooth' });
    setTimeout(() => { t.classList.add('hidden'); }, 6000);
  }

  let loadedSettings = <?= json_encode($currentSettings ?: (object)[]) ?>;

  function isFieldChanged(key, formVal, loadedVal) {
    if (loadedVal === undefined || loadedVal === null) return false;

    // 1. Tipe boolean (checkbox buzzer & backlight)
    if (key === 'buzzer' || key === 'backlight') {
      return parseInt(formVal) !== parseInt(loadedVal);
    }

    // 2. Tipe tegangan (voltage float, toleransi 0.05V)
    if (key.includes('voltage')) {
      const vForm = parseFloat(formVal);
      const vLoad = parseFloat(loadedVal);
      if (isNaN(vForm) || isNaN(vLoad)) return false;
      return Math.abs(vForm - vLoad) > 0.05;
    }

    // 3. Tipe arus (ampere integer)
    if (key.includes('current')) {
      const aForm = parseInt(formVal);
      const aLoad = parseInt(loadedVal);
      if (isNaN(aForm) || isNaN(aLoad)) return false;
      return aForm !== aLoad;
    }

    // 4. Tipe string/enum (prioritas SBU/SUB, tipe baterai USE/AGM)
    return String(formVal).trim().toUpperCase() !== String(loadedVal).trim().toUpperCase();
  }

  async function loadSettings(isSilent = false) {
    const btn = document.getElementById('btn-refresh');
    if (!isSilent) {
      btn.disabled = true;
      btn.innerHTML = `<span class="animate-spin">⏳</span> Memuat...`;
    }

    try {
      const resp = await fetch('api.php?action=get_settings&t=' + Date.now());
      const res = await resp.json();

      if (res.status === 'success' && res.settings) {
        const s = res.settings;
        loadedSettings = Object.assign({}, s);

        if (s.output_source_priority) document.getElementById('set-output-priority').value = s.output_source_priority;
        if (s.charger_source_priority) document.getElementById('set-charger-priority').value = s.charger_source_priority;
        if (s.max_ac_charge_current) {
          document.getElementById('set-max-ac-charge').value = s.max_ac_charge_current;
          updateAmpsBadge(s.max_ac_charge_current);
        }
        if (s.max_total_charge_current) document.getElementById('set-max-total-charge').value = s.max_total_charge_current;
        if (s.battery_type) document.getElementById('set-battery-type').value = s.battery_type;
        if (s.back_to_grid_voltage) document.getElementById('set-back-grid').value = parseFloat(s.back_to_grid_voltage).toFixed(1);
        if (s.back_to_discharge_voltage) document.getElementById('set-back-discharge').value = parseFloat(s.back_to_discharge_voltage).toFixed(1);
        if (s.cutoff_voltage) document.getElementById('set-cutoff').value = parseFloat(s.cutoff_voltage).toFixed(1);
        if (s.bulk_voltage) document.getElementById('set-bulk').value = parseFloat(s.bulk_voltage).toFixed(1);
        if (s.float_voltage) document.getElementById('set-float').value = parseFloat(s.float_voltage).toFixed(1);
        if (s.buzzer !== undefined) document.getElementById('set-buzzer').checked = (parseInt(s.buzzer) === 1);
        if (s.backlight !== undefined) document.getElementById('set-backlight').checked = (parseInt(s.backlight) === 1);

        document.getElementById('badge-last-read').innerText = "Update: " + (s.updated_at || 'Baru saja');
        renderCommandTable(res.commands || []);
        if (!isSilent) {
          showToast("Pengaturan terbaru berhasil disinkronkan dari database!", true);
        }
      }
    } catch (e) {
      if (!isSilent) showToast("Gagal memuat pengaturan: " + e.message, false);
    } finally {
      if (!isSilent) {
        btn.disabled = false;
        btn.innerHTML = `<svg class="w-4 h-4 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg> <span>Refresh Setting</span>`;
      }
    }
  }

  function renderCommandTable(commands) {
    const tbody = document.getElementById('table-commands-body');
    if (!commands || commands.length === 0) {
      tbody.innerHTML = `<tr><td colspan="5" class="px-3.5 py-4 text-center text-slate-400">Belum ada riwayat perintah pengaturan.</td></tr>`;
      return;
    }

    tbody.innerHTML = commands.map(c => `
      <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/50 transition-colors">
        <td class="px-3.5 py-2.5 font-mono">${c.created_at}</td>
        <td class="px-3.5 py-2.5 font-medium text-slate-800 dark:text-slate-200">${c.command_label}</td>
        <td class="px-3.5 py-2.5 font-mono font-bold text-amber-500">${c.command_code}</td>
        <td class="px-3.5 py-2.5">
          ${c.status === 'success' ? '<span class="px-2 py-0.5 rounded-full bg-emerald-500/10 text-emerald-500 font-bold text-[10px]">Sukses (ACK)</span>' :
            c.status === 'pending' ? '<span class="px-2 py-0.5 rounded-full bg-amber-500/10 text-amber-500 font-bold text-[10px] animate-pulse">Menunggu ESP32</span>' :
            c.status === 'sent' ? '<span class="px-2 py-0.5 rounded-full bg-blue-500/10 text-blue-500 font-bold text-[10px]">Terkirim</span>' :
            '<span class="px-2 py-0.5 rounded-full bg-rose-500/10 text-rose-500 font-bold text-[10px]">Gagal (NAK)</span>'}
        </td>
        <td class="px-3.5 py-2.5 font-mono text-[11px]">${c.response || '-'}</td>
      </tr>
    `).join('');
  }

  async function clearCommands() {
    if (!confirm("Yakin ingin membatalkan dan membersihkan semua antrean perintah yang masih pending / berjalan?")) return;
    try {
      const resp = await fetch('api.php?action=clear_commands');
      const res = await resp.json();
      if (res.status === 'success') {
        showToast("Antrean perintah berhasil dibersihkan!", true);
        setTimeout(() => loadSettings(true), 500);
      } else {
        showToast("Gagal: " + res.message, false);
      }
    } catch (err) {
      showToast("Error: " + err.message, false);
    }
  }

  async function saveSettings(e) {
    e.preventDefault();
    const btn = document.getElementById('btn-save');

    const form = document.getElementById('form-settings');
    const formData = new FormData(form);
    const formVals = {};
    formData.forEach((val, key) => { formVals[key] = val; });
    formVals['buzzer'] = document.getElementById('set-buzzer').checked ? 1 : 0;
    formVals['backlight'] = document.getElementById('set-backlight').checked ? 1 : 0;

    // Filter HANYA parameter yang benar-benar berbeda dari data yang dimuat sebelumnya
    const payload = {};
    for (const key in formVals) {
      if (isFieldChanged(key, formVals[key], loadedSettings[key])) {
        payload[key] = formVals[key];
      }
    }

    if (Object.keys(payload).length === 0) {
      showToast("Tidak ada parameter yang diubah dari pengaturan inverter saat ini.", false);
      return;
    }

    btn.disabled = true;
    btn.innerHTML = `<span class="animate-spin">⏳</span> Mengirim ${Object.keys(payload).length} Perintah...`;

    try {
      const resp = await fetch('api.php?action=save_settings', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload)
      });
      const res = await resp.json();

      if (res.status === 'success') {
        showToast(`Berhasil! ${res.queued_commands || Object.keys(payload).length} perintah pengaturan diantrekan ke inverter.`, true);
        setTimeout(() => loadSettings(true), 1200);
      } else {
        showToast("Gagal menyimpan: " + (res.message || "Unknown error"), false);
      }
    } catch (err) {
      showToast("Error koneksi: " + err.message, false);
    } finally {
      btn.disabled = false;
      btn.innerHTML = `<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"/></svg> <span>Terapkan Pengaturan ke Inverter</span>`;
    }
  }

  // Auto-sync form dan riwayat saat halaman pertama kali dibuka
  document.addEventListener('DOMContentLoaded', () => {
    loadSettings(true);
  });

  // Auto refresh riwayat antrean tiap 5 detik
  setInterval(() => {
    fetch('api.php?action=get_settings&t=' + Date.now())
      .then(r => r.json())
      .then(res => { if (res.commands) renderCommandTable(res.commands); })
      .catch(() => {});
  }, 5000);
</script>

</body>
</html>
