<?php
require_once __DIR__ . '/config.php';
$pageTitle = "Realtime Monitor • " . (defined('APP_NAME') ? APP_NAME : 'PowMr 6.5kW Monitor');
require_once __DIR__ . '/navbar.php';
?>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 space-y-6">

  <!-- Status Bar & Inverter Mode Header -->
  <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-4 shadow-sm flex flex-col md:flex-row md:items-center justify-between gap-4">
    <div class="flex items-center gap-3">
      <span class="relative flex h-3.5 w-3.5">
        <span id="status-ping" class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
        <span id="status-dot" class="relative inline-flex rounded-full h-3.5 w-3.5 bg-emerald-500"></span>
      </span>
      <div>
        <div class="flex items-center gap-2">
          <span class="text-sm font-semibold text-slate-900 dark:text-white">Status Komunikasi:</span>
          <span id="conn-status" class="text-xs font-bold px-2 py-0.5 rounded-full bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border border-emerald-500/20">LIVE DATA</span>
          <span id="mode-badge" class="text-xs font-bold px-2.5 py-0.5 rounded-full bg-amber-500/10 text-amber-600 dark:text-amber-400 border border-amber-500/20">Memuat...</span>
        </div>
        <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Waktu update terakhir: <span id="last-update" class="font-mono text-slate-700 dark:text-slate-300">-</span></p>
      </div>
    </div>

    <div class="flex items-center gap-2">
      <a href="demo_data.php" class="px-3 py-1.5 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 rounded-lg text-xs font-medium border border-slate-300 dark:border-slate-700 transition-colors">
        ⚡ Generate Data Contoh
      </a>
      <span class="text-xs text-slate-400 dark:text-slate-500 font-mono">Baud 2400 (8N1)</span>
    </div>
  </div>

  <!-- 1. Real-time Power Flow Diagram -->
  <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-5 shadow-sm">
    <div class="flex items-center justify-between mb-4">
      <h2 class="text-base font-bold text-slate-900 dark:text-white flex items-center gap-2">
        <svg class="w-5 h-5 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
        Diagram Aliran Daya (Power Flow)
      </h2>
      <span class="text-xs text-slate-500">Auto-refresh 3s</span>
    </div>

    <!-- Layout Node Aliran Daya -->
    <div class="grid grid-cols-1 lg:grid-cols-5 gap-4 items-center py-4">
      
      <!-- PV Solar Panel Card -->
      <div class="bg-slate-50 dark:bg-slate-950 border border-amber-500/30 rounded-2xl p-4 flex flex-col items-center text-center shadow-sm">
        <div class="p-3 bg-amber-500/10 rounded-full text-amber-500 mb-2">
          <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z" />
          </svg>
        </div>
        <span class="text-xs font-semibold text-amber-600 dark:text-amber-400 uppercase tracking-wider">Solar Panel PV</span>
        <div class="text-2xl font-black text-amber-500 mt-1"><span id="val-pv-power">0</span> <span class="text-sm font-normal text-slate-500">W</span></div>
        <div class="mt-2 text-xs text-slate-500 dark:text-slate-400 grid grid-cols-2 gap-2 w-full pt-2 border-t border-slate-200 dark:border-slate-800">
          <div><span class="block text-[11px] text-slate-400">Tegangan</span><b id="val-pv-volt" class="text-slate-700 dark:text-slate-200">0.0 V</b></div>
          <div><span class="block text-[11px] text-slate-400">Arus</span><b id="val-pv-curr" class="text-slate-700 dark:text-slate-200">0.0 A</b></div>
        </div>
      </div>

      <!-- Flow Line PV to Inverter -->
      <div class="hidden lg:flex flex-col items-center justify-center">
        <div class="text-[11px] text-amber-500 font-bold mb-1"><span id="flow-pv-kw">0.00 kW</span></div>
        <svg class="w-full h-8" viewBox="0 0 120 20">
          <line x1="0" y1="10" x2="120" y2="10" stroke="#94a3b8" stroke-width="3" />
          <line id="flow-pv-line" x1="0" y1="10" x2="120" y2="10" stroke="#f59e0b" stroke-width="4" stroke-linecap="round" class="flow-active" />
        </svg>
      </div>

      <!-- Inverter Hub -->
      <div class="bg-gradient-to-b from-slate-100 to-slate-200 dark:from-slate-800 dark:to-slate-900 border-2 border-amber-500/60 rounded-2xl p-5 flex flex-col items-center text-center shadow-md">
        <div class="p-3 bg-white dark:bg-slate-950 rounded-xl text-amber-500 mb-2 border border-slate-200 dark:border-slate-700 shadow-sm">
          <svg class="w-9 h-9" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z"/>
          </svg>
        </div>
        <span class="text-xs font-bold text-slate-900 dark:text-white uppercase tracking-wider">PowMr 6.5kW</span>
        <span class="text-[11px] text-slate-500 dark:text-slate-400">POW-HVM6.5K-48V-E</span>
        <div class="mt-3 text-xs w-full bg-white dark:bg-slate-950/70 p-2 rounded-lg border border-slate-200 dark:border-slate-800 space-y-1">
          <div class="flex justify-between">
            <span class="text-slate-500">Suhu:</span>
            <b id="val-inv-temp" class="text-emerald-600 dark:text-emerald-400">0.0 °C</b>
          </div>
          <div class="flex justify-between">
            <span class="text-slate-500">Kipas:</span>
            <b id="val-fan-speed" class="text-cyan-600 dark:text-cyan-400">0 %</b>
          </div>
        </div>
      </div>

      <!-- Flow Line Inverter to Load -->
      <div class="hidden lg:flex flex-col items-center justify-center">
        <div class="text-[11px] text-cyan-500 font-bold mb-1"><span id="flow-load-kw">0.00 kW</span></div>
        <svg class="w-full h-8" viewBox="0 0 120 20">
          <line x1="0" y1="10" x2="120" y2="10" stroke="#94a3b8" stroke-width="3" />
          <line id="flow-load-line" x1="0" y1="10" x2="120" y2="10" stroke="#06b6d4" stroke-width="4" stroke-linecap="round" class="flow-active" />
        </svg>
      </div>

      <!-- Home Load Card -->
      <div class="bg-slate-50 dark:bg-slate-950 border border-cyan-500/30 rounded-2xl p-4 flex flex-col items-center text-center shadow-sm">
        <div class="p-3 bg-cyan-500/10 rounded-full text-cyan-500 mb-2">
          <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
          </svg>
        </div>
        <span class="text-xs font-semibold text-cyan-600 dark:text-cyan-400 uppercase tracking-wider">Beban Rumah</span>
        <div class="text-2xl font-black text-cyan-500 mt-1"><span id="val-load-power">0</span> <span class="text-sm font-normal text-slate-500">W</span></div>
        <div class="mt-2 text-xs text-slate-500 dark:text-slate-400 grid grid-cols-2 gap-2 w-full pt-2 border-t border-slate-200 dark:border-slate-800">
          <div><span class="block text-[11px] text-slate-400">Persentase</span><b id="val-load-pct" class="text-slate-700 dark:text-slate-200">0 %</b></div>
          <div><span class="block text-[11px] text-slate-400">Tegangan AC</span><b id="val-ac-volt" class="text-slate-700 dark:text-slate-200">0.0 V</b></div>
        </div>
      </div>

    </div>

    <!-- Row Bawah: Baterai 48V & PLN Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4 pt-4 border-t border-slate-200 dark:border-slate-800">
      
      <!-- Baterai 48V -->
      <div class="bg-slate-50 dark:bg-slate-950 border border-emerald-500/30 rounded-xl p-4 flex items-center justify-between shadow-sm">
        <div class="flex items-center gap-3">
          <div class="p-3 bg-emerald-500/10 rounded-xl text-emerald-500">
            <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
            </svg>
          </div>
          <div>
            <span class="text-xs text-slate-500">Baterai 48V</span>
            <div class="text-xl font-bold text-emerald-600 dark:text-emerald-400 flex items-center gap-2">
              <span id="val-batt-soc">0</span>%
              <span id="val-batt-status-pill" class="text-xs px-2 py-0.5 rounded bg-emerald-500/10 text-emerald-600 dark:text-emerald-300 font-medium">Idle</span>
            </div>
            <div class="text-xs text-slate-500 mt-1">
              Tegangan: <b id="val-batt-volt" class="text-slate-700 dark:text-slate-200">0.0 V</b> • DC Bus: <b id="val-bus-volt" class="text-slate-700 dark:text-slate-200">0 V</b>
            </div>
          </div>
        </div>
        <div class="w-24 bg-slate-200 dark:bg-slate-800 rounded-full h-3.5 border border-slate-300 dark:border-slate-700 overflow-hidden">
          <div id="bar-batt-soc" class="bg-emerald-500 h-full rounded-full transition-all duration-500" style="width: 0%"></div>
        </div>
      </div>

      <!-- PLN Grid -->
      <div class="bg-slate-50 dark:bg-slate-950 border border-blue-500/30 rounded-xl p-4 flex items-center justify-between shadow-sm">
        <div class="flex items-center gap-3">
          <div class="p-3 bg-blue-500/10 rounded-xl text-blue-500">
            <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7zM7 21h10"/>
            </svg>
          </div>
          <div>
            <span class="text-xs text-slate-500">Jaringan Listrik PLN (Input Grid)</span>
            <div class="text-xl font-bold text-blue-600 dark:text-blue-400"><span id="val-grid-power">0</span> <span class="text-xs font-normal text-slate-500">W</span></div>
            <div class="text-xs text-slate-500 mt-1">
              Tegangan: <b id="val-grid-volt" class="text-slate-700 dark:text-slate-200">0.0 V</b> • Frekuensi: <b id="val-grid-freq" class="text-slate-700 dark:text-slate-200">0.0 Hz</b>
            </div>
          </div>
        </div>
        <span class="px-2.5 py-1 text-xs rounded-lg font-semibold bg-blue-500/10 text-blue-600 dark:text-blue-400 border border-blue-500/20">Siap / Bypass</span>
      </div>

    </div>
  </div>

  <!-- 2. Ringkasan Akumulasi Energi (HGEN Inverter Counters) -->
  <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
    
    <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-4 shadow-sm">
      <div class="flex items-center justify-between text-slate-500 mb-2">
        <span class="text-xs font-semibold">Produksi Hari Ini</span>
        <svg class="w-4 h-4 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
      </div>
      <div class="text-2xl font-black text-amber-500"><span id="val-daily-kwh">0.000</span> <span class="text-xs font-normal text-slate-500">kWh</span></div>
      <p class="text-[11px] text-slate-400 mt-1">Reset jam 00:00 inverter</p>
    </div>

    <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-4 shadow-sm">
      <div class="flex items-center justify-between text-slate-500 mb-2">
        <span class="text-xs font-semibold">Produksi Bulan Ini</span>
        <svg class="w-4 h-4 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
      </div>
      <div class="text-2xl font-black text-emerald-500"><span id="val-monthly-kwh">0.0</span> <span class="text-xs font-normal text-slate-500">kWh</span></div>
      <p class="text-[11px] text-slate-400 mt-1">Akumulasi bulan berjalan</p>
    </div>

    <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-4 shadow-sm">
      <div class="flex items-center justify-between text-slate-500 mb-2">
        <span class="text-xs font-semibold">Produksi Tahun Ini</span>
        <svg class="w-4 h-4 text-cyan-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
      </div>
      <div class="text-2xl font-black text-cyan-500"><span id="val-yearly-kwh">0.0</span> <span class="text-xs font-normal text-slate-500">kWh</span></div>
      <p class="text-[11px] text-slate-400 mt-1">Total tahun berjalan</p>
    </div>

    <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-4 shadow-sm">
      <div class="flex items-center justify-between text-slate-500 mb-2">
        <span class="text-xs font-semibold">Total Lifetime</span>
        <svg class="w-4 h-4 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
      </div>
      <div class="text-2xl font-black text-purple-500"><span id="val-total-kwh">0.0</span> <span class="text-xs font-normal text-slate-500">kWh</span></div>
      <p class="text-[11px] text-slate-400 mt-1">Lifetime counter inverter</p>
    </div>

  </div>

  <!-- 3. Rincian Termal & Suhu Inverter -->
  <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
    
    <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-5 shadow-sm">
      <h3 class="text-sm font-bold text-slate-900 dark:text-white mb-4 flex items-center gap-2">
        <svg class="w-4 h-4 text-rose-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
        Suhu Termal Inverter (HTEMP)
      </h3>
      <div class="grid grid-cols-2 gap-4">
        <div class="bg-slate-50 dark:bg-slate-950 p-3 rounded-xl border border-slate-200 dark:border-slate-800">
          <span class="text-xs text-slate-500 block">Suhu Inti Inverter</span>
          <b id="val-temp-core" class="text-lg text-slate-900 dark:text-white">0.0 °C</b>
        </div>
        <div class="bg-slate-50 dark:bg-slate-950 p-3 rounded-xl border border-slate-200 dark:border-slate-800">
          <span class="text-xs text-slate-500 block">Suhu Boost DC-DC</span>
          <b id="val-temp-boost" class="text-lg text-slate-900 dark:text-white">0.0 °C</b>
        </div>
        <div class="bg-slate-50 dark:bg-slate-950 p-3 rounded-xl border border-slate-200 dark:border-slate-800">
          <span class="text-xs text-slate-500 block">Suhu Trafo</span>
          <b id="val-temp-trafo" class="text-lg text-slate-900 dark:text-white">0.0 °C</b>
        </div>
        <div class="bg-slate-50 dark:bg-slate-950 p-3 rounded-xl border border-slate-200 dark:border-slate-800">
          <span class="text-xs text-slate-500 block">Suhu Heatsink PV</span>
          <b id="val-temp-pv" class="text-lg text-slate-900 dark:text-white">0.0 °C</b>
        </div>
      </div>
    </div>

    <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-5 shadow-sm">
      <h3 class="text-sm font-bold text-slate-900 dark:text-white mb-4 flex items-center gap-2">
        <svg class="w-4 h-4 text-cyan-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        Kecepatan Kipas Pendingin
      </h3>
      <div class="space-y-4">
        <div>
          <div class="flex justify-between text-xs mb-1">
            <span class="text-slate-500">Kipas 1 (Fan 1)</span>
            <b id="val-fan1" class="text-cyan-600 dark:text-cyan-400">0%</b>
          </div>
          <div class="w-full bg-slate-200 dark:bg-slate-800 rounded-full h-2.5">
            <div id="bar-fan1" class="bg-cyan-500 h-2.5 rounded-full" style="width: 0%"></div>
          </div>
        </div>
        <div>
          <div class="flex justify-between text-xs mb-1">
            <span class="text-slate-500">Kipas 2 (Fan 2)</span>
            <b id="val-fan2" class="text-cyan-600 dark:text-cyan-400">0%</b>
          </div>
          <div class="w-full bg-slate-200 dark:bg-slate-800 rounded-full h-2.5">
            <div id="bar-fan2" class="bg-cyan-500 h-2.5 rounded-full" style="width: 0%"></div>
          </div>
        </div>
      </div>
    </div>

  </div>

</div>

<!-- Script Polling Data Realtime -->
<script>
  async function fetchLatestData() {
    try {
      const response = await fetch('api.php?action=latest');
      const res = await response.json();
      
      if (res.status === 'success' && res.data) {
        const d = res.data;
        document.getElementById('conn-status').innerText = "TERHUBUNG";
        document.getElementById('conn-status').className = "text-xs font-bold px-2 py-0.5 rounded-full bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border border-emerald-500/20";
        document.getElementById('mode-badge').innerText = d.operating_mode || "Normal";
        document.getElementById('last-update').innerText = d.created_at;

        // PV
        document.getElementById('val-pv-power').innerText = Math.round(d.pv_power);
        document.getElementById('val-pv-volt').innerText = parseFloat(d.pv_voltage).toFixed(1) + " V";
        document.getElementById('val-pv-curr').innerText = parseFloat(d.pv_current).toFixed(1) + " A";
        document.getElementById('flow-pv-kw').innerText = (d.pv_power / 1000).toFixed(2) + " kW";

        // Load
        document.getElementById('val-load-power').innerText = Math.round(d.output_active_power);
        document.getElementById('val-load-pct').innerText = Math.round(d.output_load_percent) + " %";
        document.getElementById('val-ac-volt').innerText = parseFloat(d.ac_output_voltage).toFixed(1) + " V";
        document.getElementById('flow-load-kw').innerText = (d.output_active_power / 1000).toFixed(2) + " kW";

        // Battery
        const soc = Math.round(d.battery_capacity);
        document.getElementById('val-batt-soc').innerText = soc;
        document.getElementById('bar-batt-soc').style.width = Math.min(100, Math.max(0, soc)) + "%";
        document.getElementById('val-batt-volt').innerText = parseFloat(d.battery_voltage).toFixed(1) + " V";
        document.getElementById('val-bus-volt').innerText = Math.round(d.bus_voltage) + " V";

        const chg = parseFloat(d.charging_current);
        const dis = parseFloat(d.discharge_current);
        const battPill = document.getElementById('val-batt-status-pill');
        if (chg > 0) {
          battPill.innerText = "Charging +" + chg.toFixed(1) + "A";
          battPill.className = "text-xs px-2 py-0.5 rounded bg-emerald-500/20 text-emerald-600 dark:text-emerald-300 font-medium";
        } else if (dis > 0) {
          battPill.innerText = "Discharge -" + dis.toFixed(1) + "A";
          battPill.className = "text-xs px-2 py-0.5 rounded bg-amber-500/20 text-amber-600 dark:text-amber-300 font-medium";
        } else {
          battPill.innerText = "Idle 0.0A";
          battPill.className = "text-xs px-2 py-0.5 rounded bg-slate-200 dark:bg-slate-700 text-slate-700 dark:text-slate-300 font-medium";
        }

        // Grid
        document.getElementById('val-grid-power').innerText = Math.round(d.mains_power);
        document.getElementById('val-grid-volt').innerText = parseFloat(d.ac_input_voltage).toFixed(1) + " V";
        document.getElementById('val-grid-freq').innerText = parseFloat(d.mains_freq).toFixed(1) + " Hz";

        // Inverter
        document.getElementById('val-inv-temp').innerText = parseFloat(d.inverter_temp).toFixed(1) + " °C";
        document.getElementById('val-fan-speed').innerText = Math.round(d.fan1_speed) + " %";

        // Suhu rincian
        document.getElementById('val-temp-core').innerText = parseFloat(d.inverter_temp).toFixed(1) + " °C";
        document.getElementById('val-temp-boost').innerText = parseFloat(d.boost_temp).toFixed(1) + " °C";
        document.getElementById('val-temp-trafo').innerText = parseFloat(d.trafo_temp).toFixed(1) + " °C";
        document.getElementById('val-temp-pv').innerText = parseFloat(d.pv_temp).toFixed(1) + " °C";
        document.getElementById('val-fan1').innerText = Math.round(d.fan1_speed) + "%";
        document.getElementById('val-fan2').innerText = Math.round(d.fan2_speed) + "%";
        document.getElementById('bar-fan1').style.width = Math.round(d.fan1_speed) + "%";
        document.getElementById('bar-fan2').style.width = Math.round(d.fan2_speed) + "%";

        // kWh counters
        document.getElementById('val-daily-kwh').innerText = parseFloat(d.daily_kwh).toFixed(3);
        document.getElementById('val-monthly-kwh').innerText = parseFloat(d.monthly_kwh).toFixed(1);
        document.getElementById('val-yearly-kwh').innerText = parseFloat(d.yearly_kwh).toFixed(1);
        document.getElementById('val-total-kwh').innerText = parseFloat(d.total_kwh).toFixed(1);

      } else if (res.status === 'error') {
        document.getElementById('conn-status').innerText = "SETUP DATABASE";
        document.getElementById('conn-status').className = "text-xs font-bold px-2 py-0.5 rounded-full bg-rose-500/10 text-rose-600 dark:text-rose-400 border border-rose-500/20";
        document.getElementById('mode-badge').innerHTML = "<a href='install.php' class='underline text-amber-500 font-bold'>Klik di sini untuk Setup Database (install.php)</a>";
      } else {
        document.getElementById('conn-status').innerText = "MENUNGGU DATA";
        document.getElementById('conn-status').className = "text-xs font-bold px-2 py-0.5 rounded-full bg-amber-500/10 text-amber-600 dark:text-amber-400 border border-amber-500/20";
        document.getElementById('mode-badge').innerText = "Belum Ada Log";
      }
    } catch (err) {
      console.warn("Gagal mengambil data:", err);
    }
  }

  fetchLatestData();
  setInterval(fetchLatestData, 3000);
</script>

</body>
</html>

