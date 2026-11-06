<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/auth.php';

$pageTitle = "Realtime Power Flow • " . (defined('APP_NAME') ? APP_NAME : 'PowMr 6.5kW Monitor');
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
        <div class="flex items-center gap-2 flex-wrap">
          <span class="text-xs font-semibold text-slate-500 dark:text-slate-400">Status Komunikasi:</span>
          <span id="conn-status" class="text-xs font-bold px-2.5 py-0.5 rounded-full bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border border-emerald-500/20">LIVE DATA</span>
          <span id="mode-badge" class="text-xs font-bold px-3 py-0.5 rounded-full bg-amber-500/10 text-amber-600 dark:text-amber-400 border border-amber-500/20">Standby</span>
        </div>
        <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">
          Waktu update: <span id="last-update" class="font-mono text-slate-800 dark:text-slate-200 font-semibold">-</span> 
          (<span id="relative-time" class="text-slate-400">menunggu data ESP32</span>)
        </p>
      </div>
    </div>

    <div class="flex items-center gap-2">
      <span class="px-2.5 py-1 rounded-lg bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 text-xs font-mono border border-slate-200 dark:border-slate-700">
        <?= htmlspecialchars(APP_DEVICE_MODEL) ?> @ 2400 Bd
      </span>
    </div>
  </div>

  <!-- 1. DIAGRAM ALIRAN DAYA SCHEMATIC (LIVE POWER FLOW) -->
  <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl p-5 md:p-7 shadow-sm relative overflow-hidden">
    <div class="flex items-center justify-between mb-4 border-b border-slate-100 dark:border-slate-800 pb-3">
      <div class="flex items-center gap-2">
        <div class="p-2 bg-amber-500/10 text-amber-500 rounded-xl">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
        </div>
        <div>
          <h2 class="text-base font-bold text-slate-900 dark:text-white leading-tight">Diagram Aliran Daya (Live Power Flow)</h2>
          <p class="text-[11px] text-slate-500">Visualisasi aliran energi PV, PLN, Inverter, Baterai 48V, dan Beban Rumah</p>
        </div>
      </div>
      <div class="flex items-center gap-1.5 text-xs text-slate-500">
        <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
        <span class="hidden sm:inline">Auto-refresh 2s</span>
      </div>
    </div>

    <!-- Power Flow Schematic Layout (Desktop & Tablet Lebar: 11 Kolom) -->
    <div class="hidden lg:grid grid-cols-11 gap-2 items-center max-w-6xl mx-auto py-6">
      
      <!-- KOLOM KIRI (Cols 1-3): Solar PV (Atas) & Jaringan PLN (Bawah) -->
      <div class="col-span-3 space-y-6">
        
        <!-- Node 1: Solar Panel PV -->
        <div id="card-node-pv" class="bg-slate-50 dark:bg-slate-950/80 border-2 border-slate-200 dark:border-slate-800 rounded-2xl p-4 shadow-sm transition-all duration-300">
          <div class="flex items-center justify-between mb-2">
            <span class="text-xs font-bold text-amber-600 dark:text-amber-400 uppercase tracking-wider flex items-center gap-1.5">
              <span class="w-2 h-2 rounded-full bg-amber-500"></span> Solar Panel PV
            </span>
            <div class="p-2 bg-amber-500/10 text-amber-500 rounded-xl">
              <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z" />
              </svg>
            </div>
          </div>
          <div class="text-3xl font-black text-amber-500 tracking-tight">
            <span id="val-pv-power">0</span> <span class="text-sm font-normal text-slate-500">Watt</span>
          </div>
          <div class="mt-2.5 pt-2 border-t border-slate-200 dark:border-slate-800 grid grid-cols-2 text-xs text-slate-500">
            <div>Volt: <b id="val-pv-volt" class="text-slate-800 dark:text-slate-200 font-mono">0.0 V</b></div>
            <div>Arus: <b id="val-pv-curr" class="text-slate-800 dark:text-slate-200 font-mono">0.0 A</b></div>
          </div>
        </div>

        <!-- Node 2: PLN Grid Input -->
        <div id="card-node-grid" class="bg-slate-50 dark:bg-slate-950/80 border-2 border-slate-200 dark:border-slate-800 rounded-2xl p-4 shadow-sm transition-all duration-300">
          <div class="flex items-center justify-between mb-2">
            <span class="text-xs font-bold text-blue-600 dark:text-blue-400 uppercase tracking-wider flex items-center gap-1.5">
              <span class="w-2 h-2 rounded-full bg-blue-500"></span> Jaringan PLN (Grid)
            </span>
            <div class="p-2 bg-blue-500/10 text-blue-500 rounded-xl">
              <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7zM7 21h10"/>
              </svg>
            </div>
          </div>
          <div class="text-3xl font-black text-blue-500 tracking-tight">
            <span id="val-grid-power">0</span> <span class="text-sm font-normal text-slate-500">Watt</span>
          </div>
          <div class="mt-2.5 pt-2 border-t border-slate-200 dark:border-slate-800 grid grid-cols-2 text-xs text-slate-500">
            <div>Volt: <b id="val-grid-volt" class="text-slate-800 dark:text-slate-200 font-mono">0.0 V</b></div>
            <div>Freq: <b id="val-grid-freq" class="text-slate-800 dark:text-slate-200 font-mono">0.0 Hz</b></div>
          </div>
        </div>

      </div>

      <!-- KOLOM JEMBATAN KIRI ➔ TENGAH (Cols 4-5): Pipa Aliran PV & PLN ke Inverter -->
      <div class="col-span-2 flex flex-col justify-around h-full py-6 space-y-16 px-1">
        
        <!-- Pipa PV ➔ Inverter -->
        <div class="flex flex-col items-center justify-center">
          <div class="text-[11px] font-mono font-bold text-amber-500 mb-1 flex items-center gap-1">
            <span>☀️ PV:</span> <span id="flow-pv-kw">0.00 kW</span>
          </div>
          <svg class="w-full h-5" viewBox="0 0 100 10">
            <line x1="0" y1="5" x2="100" y2="5" stroke="#94a3b8" stroke-width="4" stroke-linecap="round" opacity="0.25" />
            <line id="flow-pv-line" x1="0" y1="5" x2="100" y2="5" class="flow-line-static" />
          </svg>
          <span id="flow-pv-status" class="mt-1 text-[10px] px-2 py-0.5 rounded bg-slate-200 dark:bg-slate-800 text-slate-500 font-medium">Malam / 0 W</span>
        </div>

        <!-- Pipa PLN ➔ Inverter -->
        <div class="flex flex-col items-center justify-center">
          <div class="text-[11px] font-mono font-bold text-blue-500 mb-1 flex items-center gap-1">
            <span>🔌 PLN:</span> <span id="flow-grid-kw">0.00 kW</span>
          </div>
          <svg class="w-full h-5" viewBox="0 0 100 10">
            <line x1="0" y1="5" x2="100" y2="5" stroke="#94a3b8" stroke-width="4" stroke-linecap="round" opacity="0.25" />
            <line id="flow-grid-line" x1="0" y1="5" x2="100" y2="5" class="flow-line-static" />
          </svg>
          <span id="flow-grid-status" class="mt-1 text-[10px] px-2 py-0.5 rounded bg-slate-200 dark:bg-slate-800 text-slate-500 font-medium">Standby / 0 V</span>
        </div>

      </div>

      <!-- KOLOM TENGAH (Cols 6-8): Hub Inverter & Baterai 48V (Vertikal) -->
      <div class="col-span-3 flex flex-col items-center space-y-3">
        
        <!-- Node Pusat: Hybrid Inverter PowMr -->
        <div id="card-node-inv" class="w-full bg-gradient-to-b from-slate-100 to-slate-200 dark:from-slate-800 dark:to-slate-900 border-2 border-slate-300 dark:border-slate-700 rounded-3xl p-5 shadow-xl text-center relative group transition-all duration-300">
          <div class="absolute -top-3 left-1/2 -translate-x-1/2 px-3 py-0.5 rounded-full bg-amber-500 text-slate-950 font-bold text-[10px] tracking-wider uppercase shadow-sm">
            Hybrid Inverter
          </div>
          <div class="w-14 h-14 mx-auto bg-white dark:bg-slate-950 rounded-2xl flex items-center justify-center text-amber-500 mb-2 border border-slate-200 dark:border-slate-700 shadow-sm mt-1">
            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z"/>
            </svg>
          </div>
          <h3 class="font-black text-slate-900 dark:text-white text-base">PowMr 6.5kW 48V</h3>
          <p class="text-[11px] text-slate-500 dark:text-slate-400 font-mono">POW-HVM6.5K-48V-E</p>

          <div class="mt-3.5 grid grid-cols-2 gap-2 text-xs bg-white/70 dark:bg-slate-950/70 p-2.5 rounded-xl border border-slate-200 dark:border-slate-800">
            <div class="text-left">
              <span class="text-[10px] text-slate-400 block">Suhu Heatsink</span>
              <b id="val-inv-temp" class="text-emerald-600 dark:text-emerald-400 font-mono">0.0 °C</b>
            </div>
            <div class="text-right">
              <span class="text-[10px] text-slate-400 block">Kipas Pendingin</span>
              <b id="val-fan-speed" class="text-cyan-600 dark:text-cyan-400 font-mono">0 %</b>
            </div>
          </div>
        </div>

        <!-- Pipa Aliran Vertikal: Inverter <-> Baterai (Dua Arah / Bi-directional) -->
        <div class="flex flex-col items-center justify-center w-full py-0.5">
          <div id="badge-batt-flow" class="text-[11px] font-bold px-3 py-1 rounded-full bg-slate-200 dark:bg-slate-800 text-slate-500 border border-slate-300 dark:border-slate-700 transition-all">
            ⚡ Standby (0.0 A)
          </div>
          <svg class="w-8 h-9 my-0.5" viewBox="0 0 20 36">
            <line x1="10" y1="0" x2="10" y2="36" stroke="#94a3b8" stroke-width="4" stroke-linecap="round" opacity="0.25" />
            <line id="flow-batt-line" x1="10" y1="0" x2="10" y2="36" class="flow-line-static" />
          </svg>
        </div>

        <!-- Node 3: Baterai 48V (Bottom Hub) -->
        <div id="card-node-batt" class="w-full bg-slate-50 dark:bg-slate-950/80 border-2 border-slate-200 dark:border-slate-800 rounded-2xl p-4 shadow-sm transition-all duration-300">
          <div class="flex items-center justify-between mb-2">
            <span class="text-xs font-bold text-emerald-600 dark:text-emerald-400 uppercase tracking-wider flex items-center gap-1.5">
              <span class="w-2 h-2 rounded-full bg-emerald-500"></span> Baterai Bank 48V
            </span>
            <div class="p-2 bg-emerald-500/10 text-emerald-500 rounded-xl">
              <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
              </svg>
            </div>
          </div>

          <!-- SOC dan Bar Kapasitas -->
          <div class="flex items-baseline justify-between">
            <div class="text-3xl font-black text-emerald-500 tracking-tight">
              <span id="val-batt-soc">0</span><span class="text-base font-bold">%</span>
            </div>
            <span id="val-batt-status-pill" class="text-xs font-bold px-2 py-0.5 rounded-md bg-slate-200 dark:bg-slate-800 text-slate-500">Standby</span>
          </div>

          <div class="w-full bg-slate-200 dark:bg-slate-800 rounded-full h-3 my-2.5 overflow-hidden border border-slate-300 dark:border-slate-700">
            <div id="bar-batt-soc" class="bg-gradient-to-r from-emerald-500 to-teal-400 h-full rounded-full transition-all duration-500" style="width: 0%"></div>
          </div>

          <div class="grid grid-cols-2 text-xs text-slate-500 pt-1">
            <div>Tegangan: <b id="val-batt-volt" class="text-slate-800 dark:text-slate-200 font-mono">0.0 V</b></div>
            <div>Bus DC: <b id="val-bus-volt" class="text-slate-800 dark:text-slate-200 font-mono">0 V</b></div>
          </div>
        </div>

      </div>

      <!-- KOLOM JEMBATAN TENGAH ➔ KANAN (Col 9): Pipa Aliran Inverter ke Beban -->
      <div class="col-span-1 flex flex-col items-center justify-center px-1">
        <div class="text-[11px] font-mono font-bold text-cyan-500 mb-1 flex items-center gap-1">
          <span>⚡ Beban:</span> <span id="flow-load-kw">0.00 kW</span>
        </div>
        <svg class="w-full h-5" viewBox="0 0 60 10">
          <line x1="0" y1="5" x2="60" y2="5" stroke="#94a3b8" stroke-width="4" stroke-linecap="round" opacity="0.25" />
          <line id="flow-load-line" x1="0" y1="5" x2="60" y2="5" class="flow-line-static" />
        </svg>
        <span id="flow-load-status" class="mt-1 text-[10px] px-2 py-0.5 rounded bg-slate-200 dark:bg-slate-800 text-slate-500 font-medium">Nol Beban</span>
      </div>

      <!-- KOLOM KANAN (Cols 10-11): Beban Rumah (AC Load) -->
      <div class="col-span-2">
        
        <!-- Node 4: Beban Rumah / Home Load -->
        <div id="card-node-load" class="bg-slate-50 dark:bg-slate-950/80 border-2 border-slate-200 dark:border-slate-800 rounded-2xl p-5 shadow-sm transition-all duration-300">
          <div class="flex items-center justify-between mb-2">
            <span class="text-xs font-bold text-cyan-600 dark:text-cyan-400 uppercase tracking-wider flex items-center gap-1.5">
              <span class="w-2 h-2 rounded-full bg-cyan-500"></span> Beban Rumah (AC Load)
            </span>
            <div class="p-2.5 bg-cyan-500/10 text-cyan-500 rounded-xl">
              <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
              </svg>
            </div>
          </div>
          
          <div class="text-3xl font-black text-cyan-500 tracking-tight">
            <span id="val-load-power">0</span> <span class="text-sm font-normal text-slate-500">Watt</span>
          </div>

          <div class="mt-4 space-y-2 text-xs">
            <div>
              <div class="flex justify-between text-slate-500 mb-1">
                <span>Beban Inverter:</span>
                <b id="val-load-pct" class="text-slate-800 dark:text-slate-200 font-mono">0 %</b>
              </div>
              <div class="w-full bg-slate-200 dark:bg-slate-800 rounded-full h-2 overflow-hidden">
                <div id="bar-load-pct" class="bg-cyan-500 h-full rounded-full transition-all duration-500" style="width: 0%"></div>
              </div>
            </div>
            <div class="grid grid-cols-2 pt-2 border-t border-slate-200 dark:border-slate-800 text-slate-500">
              <div>Output: <b id="val-ac-volt" class="text-slate-800 dark:text-slate-200 font-mono">0.0 V</b></div>
              <div>Freq: <b id="val-ac-freq" class="text-slate-800 dark:text-slate-200 font-mono">0.0 Hz</b></div>
            </div>
          </div>
        </div>

      </div>

    </div>

    <!-- Power Flow Adaptif untuk Mobile Screen -->
    <div class="lg:hidden space-y-4 py-2">
      
      <!-- PV Card Mobile -->
      <div id="card-node-pv-m" class="p-4 bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-2xl flex items-center justify-between">
        <div class="flex items-center gap-3">
          <div class="p-2.5 bg-amber-500/10 text-amber-500 rounded-xl">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z" /></svg>
          </div>
          <div>
            <span class="text-xs text-amber-600 dark:text-amber-400 font-bold block uppercase">Solar PV</span>
            <div class="text-xl font-black text-amber-500"><span id="val-pv-power-m">0</span> W</div>
          </div>
        </div>
        <div class="text-right text-xs text-slate-500">
          <div id="val-pv-volt-m" class="font-mono">0.0 V</div>
          <div id="val-pv-curr-m" class="font-mono">0.0 A</div>
        </div>
      </div>

      <!-- Arrow Flow Indicator PV to Inverter -->
      <div class="flex items-center justify-center text-xs gap-2 py-1">
        <svg class="w-4 h-4 text-slate-400" id="flow-pv-arrow-m" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3"/></svg>
        <span id="flow-pv-status-m" class="text-slate-500 font-medium">Aliran Solar PV: Malam / 0 W</span>
      </div>

      <!-- PLN Grid Mobile Card -->
      <div id="card-node-grid-m" class="p-4 bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-2xl flex items-center justify-between">
        <div class="flex items-center gap-3">
          <div class="p-2.5 bg-blue-500/10 text-blue-500 rounded-xl">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7zM7 21h10"/></svg>
          </div>
          <div>
            <span class="text-xs text-blue-600 dark:text-blue-400 font-bold block uppercase">Jaringan PLN</span>
            <div class="text-xl font-black text-blue-500"><span id="val-grid-power-m">0</span> W</div>
          </div>
        </div>
        <div class="text-right text-xs text-slate-500">
          <div id="val-grid-volt-m" class="font-mono">0.0 V</div>
          <div id="val-grid-freq-m" class="font-mono">0.0 Hz</div>
        </div>
      </div>

      <!-- Arrow Flow Indicator PLN to Inverter -->
      <div class="flex items-center justify-center text-xs gap-2 py-1">
        <svg class="w-4 h-4 text-slate-400" id="flow-grid-arrow-m" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3"/></svg>
        <span id="flow-grid-status-m" class="text-slate-500 font-medium">Aliran PLN: Standby / 0 V</span>
      </div>

      <!-- Inverter Mobile Card -->
      <div class="p-4 bg-slate-100 dark:bg-slate-800 border-2 border-slate-300 dark:border-slate-700 rounded-2xl text-center">
        <span class="text-xs font-bold text-slate-900 dark:text-white uppercase tracking-wider block">PowMr 6.5kW Inverter</span>
        <div class="flex items-center justify-around mt-2 text-xs">
          <div>Suhu: <b id="val-inv-temp-m" class="text-emerald-500 font-mono">0.0 °C</b></div>
          <div>Kipas: <b id="val-fan-speed-m" class="text-cyan-500 font-mono">0%</b></div>
        </div>
      </div>

      <!-- Battery Flow Mobile -->
      <div id="card-node-batt-m" class="p-4 bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-2xl flex items-center justify-between">
        <div class="flex items-center gap-3">
          <div class="p-2.5 bg-emerald-500/10 text-emerald-500 rounded-xl">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
          </div>
          <div>
            <span class="text-xs text-emerald-600 dark:text-emerald-400 font-bold block uppercase">Baterai 48V</span>
            <div class="text-xl font-black text-emerald-500"><span id="val-batt-soc-m">0</span>%</div>
          </div>
        </div>
        <div class="text-right">
          <span id="badge-batt-flow-m" class="text-xs font-bold px-2 py-0.5 rounded bg-slate-200 dark:bg-slate-800 text-slate-500">Standby</span>
          <div id="val-batt-volt-m" class="text-xs text-slate-500 font-mono mt-1">0.0 V</div>
        </div>
      </div>

      <!-- Arrow Flow Indicator Inverter to Load -->
      <div class="flex items-center justify-center text-xs gap-2 py-1">
        <svg class="w-4 h-4 text-slate-400" id="flow-load-arrow-m" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3"/></svg>
        <span id="flow-load-status-m" class="text-slate-500 font-medium">Aliran Beban: Nol Beban (0 W)</span>
      </div>

      <!-- Beban Rumah Mobile -->
      <div id="card-node-load-m" class="p-4 bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-2xl flex items-center justify-between">
        <div class="flex items-center gap-3">
          <div class="p-2.5 bg-cyan-500/10 text-cyan-500 rounded-xl">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
          </div>
          <div>
            <span class="text-xs text-cyan-600 dark:text-cyan-400 font-bold block uppercase">Beban Rumah</span>
            <div class="text-xl font-black text-cyan-500"><span id="val-load-power-m">0</span> W</div>
          </div>
        </div>
        <div class="text-right text-xs text-slate-500">
          <div id="val-load-pct-m" class="font-mono">0 % Load</div>
          <div id="val-ac-volt-m" class="font-mono">0.0 V</div>
        </div>
      </div>

    </div>

  </div>

  <!-- 2. SUMMARY KWH COUNTERS (HGEN INVERTER ACCUMULATORS) -->
  <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
    
    <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-4 shadow-sm hover:border-amber-500 transition-colors">
      <div class="flex items-center justify-between text-slate-500 mb-1">
        <span class="text-xs font-semibold">Produksi Hari Ini</span>
        <span class="p-1.5 bg-amber-500/10 rounded-lg text-amber-500">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
        </span>
      </div>
      <div class="text-2xl font-black text-amber-500 tracking-tight"><span id="val-daily-kwh">0.000</span> <span class="text-xs font-normal text-slate-500">kWh</span></div>
      <p class="text-[11px] text-slate-400 mt-1">Reset tiap jam 00:00</p>
    </div>

    <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-4 shadow-sm hover:border-emerald-500 transition-colors">
      <div class="flex items-center justify-between text-slate-500 mb-1">
        <span class="text-xs font-semibold">Produksi Bulan Ini</span>
        <span class="p-1.5 bg-emerald-500/10 rounded-lg text-emerald-500">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
        </span>
      </div>
      <div class="text-2xl font-black text-emerald-500 tracking-tight"><span id="val-monthly-kwh">0.0</span> <span class="text-xs font-normal text-slate-500">kWh</span></div>
      <p class="text-[11px] text-slate-400 mt-1">Akumulasi bulan berjalan</p>
    </div>

    <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-4 shadow-sm hover:border-cyan-500 transition-colors">
      <div class="flex items-center justify-between text-slate-500 mb-1">
        <span class="text-xs font-semibold">Produksi Tahun Ini</span>
        <span class="p-1.5 bg-cyan-500/10 rounded-lg text-cyan-500">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
        </span>
      </div>
      <div class="text-2xl font-black text-cyan-500 tracking-tight"><span id="val-yearly-kwh">0.0</span> <span class="text-xs font-normal text-slate-500">kWh</span></div>
      <p class="text-[11px] text-slate-400 mt-1">Akumulasi tahun berjalan</p>
    </div>

    <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-4 shadow-sm hover:border-purple-500 transition-colors">
      <div class="flex items-center justify-between text-slate-500 mb-1">
        <span class="text-xs font-semibold">Total Lifetime</span>
        <span class="p-1.5 bg-purple-500/10 rounded-lg text-purple-500">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        </span>
      </div>
      <div class="text-2xl font-black text-purple-500 tracking-tight"><span id="val-total-kwh">0.0</span> <span class="text-xs font-normal text-slate-500">kWh</span></div>
      <p class="text-[11px] text-slate-400 mt-1">Sejak inverter dipasang</p>
    </div>

  </div>

  <!-- 3. RINCIAN TERMAL & STATUS KIPAS -->
  <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
    
    <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-5 shadow-sm">
      <h3 class="text-sm font-bold text-slate-900 dark:text-white mb-4 flex items-center gap-2">
        <svg class="w-4 h-4 text-rose-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
        Suhu Termal Inverter (HTEMP)
      </h3>
      <div class="grid grid-cols-2 gap-3">
        <div class="bg-slate-50 dark:bg-slate-950 p-3 rounded-xl border border-slate-200 dark:border-slate-800">
          <span class="text-[11px] text-slate-400 block">Suhu Inti Heatsink</span>
          <b id="val-temp-core" class="text-lg text-slate-900 dark:text-white font-mono">0.0 °C</b>
        </div>
        <div class="bg-slate-50 dark:bg-slate-950 p-3 rounded-xl border border-slate-200 dark:border-slate-800">
          <span class="text-[11px] text-slate-400 block">Suhu Boost DC-DC</span>
          <b id="val-temp-boost" class="text-lg text-slate-900 dark:text-white font-mono">0.0 °C</b>
        </div>
        <div class="bg-slate-50 dark:bg-slate-950 p-3 rounded-xl border border-slate-200 dark:border-slate-800">
          <span class="text-[11px] text-slate-400 block">Suhu Trafo</span>
          <b id="val-temp-trafo" class="text-lg text-slate-900 dark:text-white font-mono">0.0 °C</b>
        </div>
        <div class="bg-slate-50 dark:bg-slate-950 p-3 rounded-xl border border-slate-200 dark:border-slate-800">
          <span class="text-[11px] text-slate-400 block">Suhu Heatsink PV</span>
          <b id="val-temp-pv" class="text-lg text-slate-900 dark:text-white font-mono">0.0 °C</b>
        </div>
      </div>
    </div>

    <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-5 shadow-sm">
      <h3 class="text-sm font-bold text-slate-900 dark:text-white mb-4 flex items-center gap-2">
        <svg class="w-4 h-4 text-cyan-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        Kecepatan Kipas Pendingin
      </h3>
      <div class="space-y-4 pt-1">
        <div>
          <div class="flex justify-between text-xs mb-1.5">
            <span class="text-slate-500">Kipas 1 (Main Heatsink Fan)</span>
            <b id="val-fan1" class="text-cyan-600 dark:text-cyan-400 font-mono">0 %</b>
          </div>
          <div class="w-full bg-slate-200 dark:bg-slate-800 rounded-full h-2.5 overflow-hidden">
            <div id="bar-fan1" class="bg-cyan-500 h-2.5 rounded-full transition-all duration-300" style="width: 0%"></div>
          </div>
        </div>
        <div>
          <div class="flex justify-between text-xs mb-1.5">
            <span class="text-slate-500">Kipas 2 (Secondary Fan)</span>
            <b id="val-fan2" class="text-cyan-600 dark:text-cyan-400 font-mono">0 %</b>
          </div>
          <div class="w-full bg-slate-200 dark:bg-slate-800 rounded-full h-2.5 overflow-hidden">
            <div id="bar-fan2" class="bg-cyan-500 h-2.5 rounded-full transition-all duration-300" style="width: 0%"></div>
          </div>
        </div>
      </div>
    </div>

  </div>

</div>

<!-- SCRIPT POLLING DATA REALTIME DENGAN LOGIKA ANIMASI KETAT -->
<script>
  let lastTimestamp = null;

  // Setel semua garis dan elemen ke kondisi STATIK (diam, abu-abu)
  function setAllLinesToStatic() {
    // 1. PV Line
    const flowPvLine = document.getElementById('flow-pv-line');
    if (flowPvLine) flowPvLine.setAttribute('class', 'flow-line-static');
    const flowPvStatus = document.getElementById('flow-pv-status');
    if (flowPvStatus) {
      flowPvStatus.innerText = "Malam / 0 W";
      flowPvStatus.className = "mt-1 text-[10px] px-2 py-0.5 rounded bg-slate-200 dark:bg-slate-800 text-slate-500 font-medium";
    }
    const cardPv = document.getElementById('card-node-pv');
    if (cardPv) cardPv.classList.remove('pulse-glow-amber');
    const flowPvArrowM = document.getElementById('flow-pv-arrow-m');
    if (flowPvArrowM) flowPvArrowM.className.baseVal = "w-4 h-4 text-slate-400";
    const flowPvStatusM = document.getElementById('flow-pv-status-m');
    if (flowPvStatusM) flowPvStatusM.className = "text-slate-500 font-medium";

    // 2. PLN Grid Line
    const flowGridLine = document.getElementById('flow-grid-line');
    if (flowGridLine) flowGridLine.setAttribute('class', 'flow-line-static');
    const flowGridStatus = document.getElementById('flow-grid-status');
    if (flowGridStatus) {
      flowGridStatus.innerText = "Standby / 0 V";
      flowGridStatus.className = "mt-1 text-[10px] px-2 py-0.5 rounded bg-slate-200 dark:bg-slate-800 text-slate-500 font-medium";
    }
    const cardGrid = document.getElementById('card-node-grid');
    if (cardGrid) cardGrid.classList.remove('pulse-glow-blue');
    const flowGridArrowM = document.getElementById('flow-grid-arrow-m');
    if (flowGridArrowM) flowGridArrowM.className.baseVal = "w-4 h-4 text-slate-400";
    const flowGridStatusM = document.getElementById('flow-grid-status-m');
    if (flowGridStatusM) flowGridStatusM.className = "text-slate-500 font-medium";

    // 3. Battery Line
    const flowBattLine = document.getElementById('flow-batt-line');
    if (flowBattLine) flowBattLine.setAttribute('class', 'flow-line-static');
    const battFlowBadge = document.getElementById('badge-batt-flow');
    if (battFlowBadge) {
      battFlowBadge.innerText = "⚡ Standby (0.0 A)";
      battFlowBadge.className = "text-[11px] font-bold px-3 py-1 rounded-full bg-slate-200 dark:bg-slate-800 text-slate-500 border border-slate-300 dark:border-slate-700 transition-all";
    }
    const battPill = document.getElementById('val-batt-status-pill');
    if (battPill) {
      battPill.innerText = "Standby";
      battPill.className = "text-xs font-bold px-2 py-0.5 rounded-md bg-slate-200 dark:bg-slate-800 text-slate-500";
    }
    const battFlowBadgeM = document.getElementById('badge-batt-flow-m');
    if (battFlowBadgeM) {
      battFlowBadgeM.innerText = "Standby";
      battFlowBadgeM.className = "text-xs font-bold px-2 py-0.5 rounded bg-slate-200 dark:bg-slate-800 text-slate-500";
    }
    const cardBatt = document.getElementById('card-node-batt');
    if (cardBatt) cardBatt.classList.remove('pulse-glow-emerald');

    // 4. Load Line
    const flowLoadLine = document.getElementById('flow-load-line');
    if (flowLoadLine) flowLoadLine.setAttribute('class', 'flow-line-static');
    const flowLoadStatus = document.getElementById('flow-load-status');
    if (flowLoadStatus) {
      flowLoadStatus.innerText = "Nol Beban";
      flowLoadStatus.className = "mt-1 text-[10px] px-2 py-0.5 rounded bg-slate-200 dark:bg-slate-800 text-slate-500 font-medium";
    }
    const cardLoad = document.getElementById('card-node-load');
    if (cardLoad) cardLoad.classList.remove('pulse-glow-cyan');
    const flowLoadArrowM = document.getElementById('flow-load-arrow-m');
    if (flowLoadArrowM) flowLoadArrowM.className.baseVal = "w-4 h-4 text-slate-400";
    const flowLoadStatusM = document.getElementById('flow-load-status-m');
    if (flowLoadStatusM) flowLoadStatusM.className = "text-slate-500 font-medium";
  }

  async function fetchLatestData() {
    try {
      const response = await fetch('api.php?action=latest&t=' + Date.now());
      const res = await response.json();
      
      if (res.status === 'success' && res.data) {
        const d = res.data;
        lastTimestamp = new Date(d.created_at).getTime();

        // 1. Status Komunikasi
        const connStatus = document.getElementById('conn-status');
        connStatus.innerText = "LIVE DATA";
        connStatus.className = "text-xs font-bold px-2.5 py-0.5 rounded-full bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border border-emerald-500/20";
        document.getElementById('status-ping').className = "animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75";
        document.getElementById('status-dot').className = "relative inline-flex rounded-full h-3.5 w-3.5 bg-emerald-500";

        document.getElementById('mode-badge').innerText = d.operating_mode || "Normal Mode";
        document.getElementById('last-update').innerText = d.created_at;

        // 2. Solar PV Logic
        const pvW = Math.round(d.pv_power || 0);
        const pvV = parseFloat(d.pv_voltage || 0).toFixed(1);
        const pvA = parseFloat(d.pv_current || 0).toFixed(1);

        document.getElementById('val-pv-power').innerText = pvW;
        document.getElementById('val-pv-volt').innerText = pvV + " V";
        document.getElementById('val-pv-curr').innerText = pvA + " A";
        document.getElementById('flow-pv-kw').innerText = (pvW / 1000).toFixed(2) + " kW";

        const flowPvLine = document.getElementById('flow-pv-line');
        const flowPvStatus = document.getElementById('flow-pv-status');
        const cardPv = document.getElementById('card-node-pv');
        const flowPvArrowM = document.getElementById('flow-pv-arrow-m');
        const flowPvStatusM = document.getElementById('flow-pv-status-m');

        if (pvW > 10) {
          // AKTIF: Garis mengalir warna kuning/emas
          flowPvLine.setAttribute('class', 'flow-pv-active');
          flowPvStatus.innerText = "Aktif (" + pvW + " W)";
          flowPvStatus.className = "mt-1 text-[10px] px-2 py-0.5 rounded bg-amber-500/20 text-amber-500 font-bold border border-amber-500/30";
          cardPv.classList.add('pulse-glow-amber');
          if (flowPvArrowM) flowPvArrowM.className.baseVal = "w-4 h-4 text-amber-500 animate-bounce";
          if (flowPvStatusM) {
            flowPvStatusM.innerText = "Aliran Solar PV: Aktif (" + pvW + " W)";
            flowPvStatusM.className = "text-amber-500 font-bold";
          }
        } else {
          // STATIK: Garis abu-abu diam
          flowPvLine.setAttribute('class', 'flow-line-static');
          flowPvStatus.innerText = "Malam / 0 W";
          flowPvStatus.className = "mt-1 text-[10px] px-2 py-0.5 rounded bg-slate-200 dark:bg-slate-800 text-slate-500 font-medium";
          cardPv.classList.remove('pulse-glow-amber');
          if (flowPvArrowM) flowPvArrowM.className.baseVal = "w-4 h-4 text-slate-400";
          if (flowPvStatusM) {
            flowPvStatusM.innerText = "Aliran Solar PV: Malam / 0 W";
            flowPvStatusM.className = "text-slate-500 font-medium";
          }
        }

        // Mobile PV
        const pvWm = document.getElementById('val-pv-power-m');
        if (pvWm) pvWm.innerText = pvW;
        const pvVm = document.getElementById('val-pv-volt-m');
        if (pvVm) pvVm.innerText = pvV + " V";
        const pvAm = document.getElementById('val-pv-curr-m');
        if (pvAm) pvAm.innerText = pvA + " A";

        // 3. Grid PLN Logic
        const gridW = Math.round(d.mains_power || 0);
        const gridV = parseFloat(d.ac_input_voltage || 0).toFixed(1);
        const gridHz = parseFloat(d.mains_freq || 0).toFixed(1);

        document.getElementById('val-grid-power').innerText = gridW;
        document.getElementById('val-grid-volt').innerText = gridV + " V";
        document.getElementById('val-grid-freq').innerText = gridHz + " Hz";
        document.getElementById('flow-grid-kw').innerText = (gridW / 1000).toFixed(2) + " kW";

        const flowGridLine = document.getElementById('flow-grid-line');
        const flowGridStatus = document.getElementById('flow-grid-status');
        const cardGrid = document.getElementById('card-node-grid');
        const flowGridArrowM = document.getElementById('flow-grid-arrow-m');
        const flowGridStatusM = document.getElementById('flow-grid-status-m');

        if (gridW > 10 || (parseFloat(gridV) > 150 && d.operating_mode && d.operating_mode.toLowerCase().includes('line'))) {
          // AKTIF: Garis mengalir warna biru
          flowGridLine.setAttribute('class', 'flow-grid-active');
          flowGridStatus.innerText = "Menyuplai (" + (gridW > 0 ? gridW + " W" : "Online") + ")";
          flowGridStatus.className = "mt-1 text-[10px] px-2 py-0.5 rounded bg-blue-500/20 text-blue-500 font-bold border border-blue-500/30";
          cardGrid.classList.add('pulse-glow-blue');
          if (flowGridArrowM) flowGridArrowM.className.baseVal = "w-4 h-4 text-blue-500 animate-bounce";
          if (flowGridStatusM) {
            flowGridStatusM.innerText = "Aliran PLN: Menyuplai (" + gridW + " W)";
            flowGridStatusM.className = "text-blue-500 font-bold";
          }
        } else {
          // STATIK: Garis abu-abu diam
          flowGridLine.setAttribute('class', 'flow-line-static');
          flowGridStatus.innerText = parseFloat(gridV) > 100 ? "Standby (" + gridV + "V)" : "Terputus / 0 V";
          flowGridStatus.className = "mt-1 text-[10px] px-2 py-0.5 rounded bg-slate-200 dark:bg-slate-800 text-slate-500 font-medium";
          cardGrid.classList.remove('pulse-glow-blue');
          if (flowGridArrowM) flowGridArrowM.className.baseVal = "w-4 h-4 text-slate-400";
          if (flowGridStatusM) {
            flowGridStatusM.innerText = "Aliran PLN: Standby / 0 V";
            flowGridStatusM.className = "text-slate-500 font-medium";
          }
        }

        // Mobile Grid
        const gridWm = document.getElementById('val-grid-power-m');
        if (gridWm) gridWm.innerText = gridW;
        const gridVm = document.getElementById('val-grid-volt-m');
        if (gridVm) gridVm.innerText = gridV + " V";
        const gridHzm = document.getElementById('val-grid-freq-m');
        if (gridHzm) gridHzm.innerText = gridHz + " Hz";

        // 4. Beban Rumah (AC Load) Logic
        const loadW = Math.round(d.output_active_power || 0);
        const loadPct = Math.round(d.output_load_percent || 0);
        const acV = parseFloat(d.ac_output_voltage || 0).toFixed(1);
        const acHz = parseFloat(d.ac_output_freq || 0).toFixed(1);

        document.getElementById('val-load-power').innerText = loadW;
        document.getElementById('val-load-pct').innerText = loadPct + " %";
        document.getElementById('bar-load-pct').style.width = Math.min(100, Math.max(0, loadPct)) + "%";
        document.getElementById('val-ac-volt').innerText = acV + " V";
        document.getElementById('val-ac-freq').innerText = acHz + " Hz";
        document.getElementById('flow-load-kw').innerText = (loadW / 1000).toFixed(2) + " kW";

        const flowLoadLine = document.getElementById('flow-load-line');
        const flowLoadStatus = document.getElementById('flow-load-status');
        const cardLoad = document.getElementById('card-node-load');
        const flowLoadArrowM = document.getElementById('flow-load-arrow-m');
        const flowLoadStatusM = document.getElementById('flow-load-status-m');

        if (loadW > 10) {
          // AKTIF: Garis mengalir warna cyan
          flowLoadLine.setAttribute('class', 'flow-load-active');
          flowLoadStatus.innerText = "Menyuplai (" + loadW + " W • " + loadPct + "%)";
          flowLoadStatus.className = "mt-1 text-[10px] px-2 py-0.5 rounded bg-cyan-500/20 text-cyan-500 font-bold border border-cyan-500/30";
          cardLoad.classList.add('pulse-glow-cyan');
          if (flowLoadArrowM) flowLoadArrowM.className.baseVal = "w-4 h-4 text-cyan-500 animate-bounce";
          if (flowLoadStatusM) {
            flowLoadStatusM.innerText = "Aliran Beban: Menyuplai (" + loadW + " W)";
            flowLoadStatusM.className = "text-cyan-500 font-bold";
          }
        } else {
          // STATIK: Garis abu-abu diam
          flowLoadLine.setAttribute('class', 'flow-line-static');
          flowLoadStatus.innerText = "Nol Beban";
          flowLoadStatus.className = "mt-1 text-[10px] px-2 py-0.5 rounded bg-slate-200 dark:bg-slate-800 text-slate-500 font-medium";
          cardLoad.classList.remove('pulse-glow-cyan');
          if (flowLoadArrowM) flowLoadArrowM.className.baseVal = "w-4 h-4 text-slate-400";
          if (flowLoadStatusM) {
            flowLoadStatusM.innerText = "Aliran Beban: Nol Beban (0 W)";
            flowLoadStatusM.className = "text-slate-500 font-medium";
          }
        }

        // Mobile Load
        const loadWm = document.getElementById('val-load-power-m');
        if (loadWm) loadWm.innerText = loadW;
        const loadPctM = document.getElementById('val-load-pct-m');
        if (loadPctM) loadPctM.innerText = loadPct + "% Load";
        const acVm = document.getElementById('val-ac-volt-m');
        if (acVm) acVm.innerText = acV + " V";

        // 5. Baterai 48V (BIDIRECTIONAL FLOW: CHARGING VS DISCHARGING)
        const soc = Math.round(d.battery_capacity || 0);
        const battV = parseFloat(d.battery_voltage || 0).toFixed(1);
        const busV = Math.round(d.bus_voltage || 0);
        const chgCurrent = parseFloat(d.charging_current || 0);
        const disCurrent = parseFloat(d.discharge_current || 0);

        document.getElementById('val-batt-soc').innerText = soc;
        document.getElementById('bar-batt-soc').style.width = Math.min(100, Math.max(0, soc)) + "%";
        document.getElementById('val-batt-volt').innerText = battV + " V";
        document.getElementById('val-bus-volt').innerText = busV + " V";

        // Mobile Battery
        const battSocM = document.getElementById('val-batt-soc-m');
        if (battSocM) battSocM.innerText = soc;
        const battVm = document.getElementById('val-batt-volt-m');
        if (battVm) battVm.innerText = battV + " V";

        const battLine = document.getElementById('flow-batt-line');
        const battPill = document.getElementById('val-batt-status-pill');
        const battFlowBadge = document.getElementById('badge-batt-flow');
        const battFlowBadgeM = document.getElementById('badge-batt-flow-m');

        if (chgCurrent > 0.5) {
          // CHARGING: Aliran mengalir MASUK KE BATERAI (Ke Bawah)
          const chgWatt = Math.round(chgCurrent * parseFloat(battV));
          battPill.innerText = "Charging";
          battPill.className = "text-xs font-bold px-2 py-0.5 rounded-md bg-emerald-500/20 text-emerald-600 dark:text-emerald-300";

          battFlowBadge.innerText = "⬇️ Charging +" + chgCurrent.toFixed(1) + "A (+" + chgWatt + "W)";
          battFlowBadge.className = "text-[11px] font-bold px-3 py-1 rounded-full bg-emerald-500/20 text-emerald-600 dark:text-emerald-300 border border-emerald-500/30";
          if (battFlowBadgeM) {
            battFlowBadgeM.innerText = "⬇️ +" + chgCurrent.toFixed(1) + "A";
            battFlowBadgeM.className = "text-xs font-bold px-2 py-0.5 rounded bg-emerald-500/20 text-emerald-600 dark:text-emerald-300";
          }
          if (battLine) {
            battLine.setAttribute('class', 'flow-batt-charge');
          }

        } else if (disCurrent > 0.5) {
          // DISCHARGING: Aliran mengalir KELUAR DARI BATERAI (Ke Atas)
          const disWatt = Math.round(disCurrent * parseFloat(battV));
          battPill.innerText = "Discharge";
          battPill.className = "text-xs font-bold px-2 py-0.5 rounded-md bg-amber-500/20 text-amber-600 dark:text-amber-300";

          battFlowBadge.innerText = "⬆️ Pakai Baterai -" + disCurrent.toFixed(1) + "A (-" + disWatt + "W)";
          battFlowBadge.className = "text-[11px] font-bold px-3 py-1 rounded-full bg-amber-500/20 text-amber-600 dark:text-amber-300 border border-amber-500/30";
          if (battFlowBadgeM) {
            battFlowBadgeM.innerText = "⬆️ -" + disCurrent.toFixed(1) + "A";
            battFlowBadgeM.className = "text-xs font-bold px-2 py-0.5 rounded bg-amber-500/20 text-amber-600 dark:text-amber-300";
          }
          if (battLine) {
            battLine.setAttribute('class', 'flow-batt-discharge');
          }

        } else {
          // STATIK: Standby / Idle, garis abu-abu diam
          battPill.innerText = "Standby";
          battPill.className = "text-xs font-bold px-2 py-0.5 rounded-md bg-slate-200 dark:bg-slate-800 text-slate-500 dark:text-slate-400";

          battFlowBadge.innerText = "⚡ Standby (0.0 A)";
          battFlowBadge.className = "text-[11px] font-bold px-3 py-1 rounded-full bg-slate-200 dark:bg-slate-800 text-slate-500 border border-slate-300 dark:border-slate-700";
          if (battFlowBadgeM) {
            battFlowBadgeM.innerText = "Standby";
            battFlowBadgeM.className = "text-xs font-bold px-2 py-0.5 rounded bg-slate-200 dark:bg-slate-800 text-slate-500";
          }
          if (battLine) {
            battLine.setAttribute('class', 'flow-line-static');
          }
        }

        // 6. Inverter Health (Suhu & Kipas)
        const invTemp = parseFloat(d.inverter_temp || 0).toFixed(1);
        const fanSpeed = Math.round(d.fan1_speed || 0);

        document.getElementById('val-inv-temp').innerText = invTemp + " °C";
        document.getElementById('val-fan-speed').innerText = fanSpeed + " %";

        const invTempm = document.getElementById('val-inv-temp-m');
        if (invTempm) invTempm.innerText = invTemp + " °C";
        const fanSpeedm = document.getElementById('val-fan-speed-m');
        if (fanSpeedm) fanSpeedm.innerText = fanSpeed + " %";

        // Rincian Termal
        document.getElementById('val-temp-core').innerText = invTemp + " °C";
        document.getElementById('val-temp-boost').innerText = parseFloat(d.boost_temp || 0).toFixed(1) + " °C";
        document.getElementById('val-temp-trafo').innerText = parseFloat(d.trafo_temp || 0).toFixed(1) + " °C";
        document.getElementById('val-temp-pv').innerText = parseFloat(d.pv_temp || 0).toFixed(1) + " °C";

        // Dual Fan
        const fan1 = Math.round(d.fan1_speed || 0);
        const fan2 = Math.round(d.fan2_speed || 0);
        document.getElementById('val-fan1').innerText = fan1 + " %";
        document.getElementById('val-fan2').innerText = fan2 + " %";
        document.getElementById('bar-fan1').style.width = fan1 + "%";
        document.getElementById('bar-fan2').style.width = fan2 + "%";

        // 7. Akumulator Energi Inverter
        document.getElementById('val-daily-kwh').innerText = parseFloat(d.daily_kwh || 0).toFixed(3);
        document.getElementById('val-monthly-kwh').innerText = parseFloat(d.monthly_kwh || 0).toFixed(1);
        document.getElementById('val-yearly-kwh').innerText = parseFloat(d.yearly_kwh || 0).toFixed(1);
        document.getElementById('val-total-kwh').innerText = parseFloat(d.total_kwh || 0).toFixed(1);

      } else {
        // Data kosong atau belum ada log -> Pastikan SEMUA garis statik
        setAllLinesToStatic();
        const connStatus = document.getElementById('conn-status');
        connStatus.innerText = "MENUNGGU LOG";
        connStatus.className = "text-xs font-bold px-2.5 py-0.5 rounded-full bg-amber-500/10 text-amber-600 dark:text-amber-400 border border-amber-500/20";
        document.getElementById('mode-badge').innerText = "Belum Ada Data";
      }
    } catch (err) {
      console.warn("Polling error:", err);
      setAllLinesToStatic();
      const connStatus = document.getElementById('conn-status');
      connStatus.innerText = "RECONNECTING";
      connStatus.className = "text-xs font-bold px-2.5 py-0.5 rounded-full bg-rose-500/10 text-rose-600 dark:text-rose-400 border border-rose-500/20";
    }
  }

  // Hitung waktu relatif (misal: '3 detik lalu')
  function updateRelativeTime() {
    if (!lastTimestamp) return;
    const diffSec = Math.round((Date.now() - lastTimestamp) / 1000);
    const relEl = document.getElementById('relative-time');
    if (diffSec < 4) {
      relEl.innerText = "baru saja";
    } else if (diffSec < 60) {
      relEl.innerText = diffSec + " detik lalu";
    } else {
      const min = Math.floor(diffSec / 60);
      relEl.innerText = min + " menit lalu";
    }
  }

  // Setel statik terlebih dahulu sebelum data tiba
  setAllLinesToStatic();

  // Mulai polling continuous setiap 2 detik
  fetchLatestData();
  setInterval(fetchLatestData, 2000);
  setInterval(updateRelativeTime, 1000);
</script>

</body>
</html>
