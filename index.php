<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/auth.php';

$pageTitle = "Realtime Power Flow • " . (defined('APP_NAME') ? APP_NAME : 'PowMr 6.5kW Monitor');
require_once __DIR__ . '/navbar.php';
?>

<div class="max-w-7xl mx-auto px-2 sm:px-6 lg:px-8 py-3 sm:py-6 space-y-3 sm:space-y-6">

  <!-- Status Bar: Komunikasi Inverter & Deteksi Mikrokontroler (ESP32-C3) -->
  <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-3 sm:p-4 shadow-sm flex flex-col md:flex-row md:items-center justify-between gap-3">
    
    <!-- Status Komunikasi & MCU Heartbeat -->
    <div class="flex items-center gap-3">
      <span class="relative flex h-3.5 w-3.5 flex-shrink-0">
        <span id="status-ping" class="animate-ping absolute inline-flex h-full w-full rounded-full bg-slate-400 opacity-75"></span>
        <span id="status-dot" class="relative inline-flex rounded-full h-3.5 w-3.5 bg-slate-400"></span>
      </span>
      <div>
        <div class="flex items-center gap-2 flex-wrap">
          <!-- Badge Deteksi Mikrokontroler ESP32-C3 -->
          <span id="mcu-badge" class="text-xs font-bold px-2.5 py-0.5 rounded-full bg-slate-200 dark:bg-slate-800 text-slate-500 border border-slate-300 dark:border-slate-700 flex items-center gap-1.5 shadow-sm">
            <span id="mcu-dot" class="w-2 h-2 rounded-full bg-slate-400"></span>
            <span id="mcu-text">ESP32: Menghubungkan...</span>
          </span>

          <!-- Mode Operasi Inverter -->
          <span id="mode-badge" class="text-xs font-bold px-2.5 py-0.5 rounded-full bg-amber-500/10 text-amber-600 dark:text-amber-400 border border-amber-500/20 shadow-sm">
            Standby
          </span>
        </div>
        <p class="text-[11px] sm:text-xs text-slate-500 dark:text-slate-400 mt-1">
          Waktu log: <span id="last-update" class="font-mono text-slate-800 dark:text-slate-200 font-semibold">-</span> 
          (<span id="relative-time" class="text-slate-400 font-medium">memeriksa sinyal ESP32...</span>)
        </p>
      </div>
    </div>

    <!-- Identitas Perangkat & Baud Rate -->
    <div class="flex items-center gap-2 flex-wrap text-xs">
      <span id="mcu-heartbeat-pill" class="px-2.5 py-1 rounded-lg bg-slate-100 dark:bg-slate-800 text-slate-500 font-mono text-[11px] border border-slate-200 dark:border-slate-700">
        Heartbeat: -
      </span>
      <span class="px-2.5 py-1 rounded-lg bg-amber-500/10 text-amber-600 dark:text-amber-400 font-mono text-[11px] border border-amber-500/20 font-bold">
        <?= htmlspecialchars(APP_DEVICE_MODEL) ?> @ 2400 Bd
      </span>
    </div>
  </div>

  <!-- Banner Peringatan Saat Mikrokontroler OFFLINE -->
  <div id="mcu-offline-banner" class="hidden bg-rose-500/10 border border-rose-500/30 text-rose-700 dark:text-rose-300 rounded-2xl p-3 sm:p-4 text-xs sm:text-sm flex items-center justify-between gap-3 shadow-sm transition-all duration-300">
    <div class="flex items-center gap-2.5">
      <div class="p-1.5 bg-rose-500 text-white rounded-lg flex-shrink-0">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
      </div>
      <div>
        <b>Mikrokontroler ESP32-C3 OFFLINE:</b> <span id="mcu-offline-msg">Belum ada data telemetri baru diterima dalam 25 detik terakhir. Pastikan ESP32 terhubung ke WiFi dan daya aktif.</span>
      </div>
    </div>
    <span class="px-2 py-0.5 rounded bg-rose-500/20 text-rose-700 dark:text-rose-300 font-bold text-[10px] uppercase flex-shrink-0">Standby</span>
  </div>

  <!-- 1. DIAGRAM ALIRAN DAYA SEJATI (POWER FLOW SCHEMATIC) -->
  <!-- Bekerja responsif di Desktop & Mobile tanpa perubahan tata letak kartu -->
  <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl p-3 sm:p-6 lg:p-7 shadow-sm relative overflow-hidden">
    
    <!-- Header Diagram -->
    <div class="flex items-center justify-between mb-3 sm:mb-5 border-b border-slate-100 dark:border-slate-800 pb-2.5 sm:pb-3">
      <div class="flex items-center gap-2">
        <div class="p-2 bg-amber-500/10 text-amber-500 rounded-xl">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
        </div>
        <div>
          <h2 class="text-sm sm:text-base font-bold text-slate-900 dark:text-white leading-tight">Diagram Aliran Daya (Live Power Flow)</h2>
          <p class="text-[10px] sm:text-[11px] text-slate-500">Visualisasi aliran energi PV, PLN Grid ➔ Inverter ◄══► Baterai 48V ➔ Beban Rumah</p>
        </div>
      </div>
      <div class="flex items-center gap-1.5 text-[11px] text-slate-500">
        <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
        <span class="hidden sm:inline font-mono">Live 2s</span>
      </div>
    </div>

    <!-- Canvas Skematik 5-Kolom (Left Cards - Left Pipes - Center Hub - Right Pipe - Right Card) -->
    <!-- Tetap proporsional 3-kolom di Mobile maupun Desktop tanpa horizontal scrolling -->
    <div class="w-full overflow-x-auto sm:overflow-visible">
      <div class="grid grid-cols-12 gap-1 sm:gap-2.5 lg:gap-4 items-center min-w-[340px] max-w-6xl mx-auto py-1 sm:py-3">
        
        <!-- KOLOM 1 (Cols 1-3, 25%): Sumber Energi (Solar PV Atas & Jaringan PLN Bawah) -->
        <div class="col-span-3 space-y-2 sm:space-y-4">
          
          <!-- Node 1: Solar Panel PV -->
          <div id="card-node-pv" class="bg-slate-50 dark:bg-slate-950/80 border-2 border-slate-200 dark:border-slate-800 rounded-xl sm:rounded-2xl p-1.5 sm:p-3.5 shadow-sm transition-all duration-300">
            <div class="flex items-center justify-between mb-0.5 sm:mb-1.5">
              <span class="text-[8px] sm:text-xs font-bold text-amber-600 dark:text-amber-400 uppercase tracking-wider flex items-center gap-1">
                <span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span>
                <span class="truncate">Solar PV</span>
              </span>
              <div class="p-0.5 sm:p-1.5 bg-amber-500/10 text-amber-500 rounded-md sm:rounded-lg">
                <svg class="w-3.5 h-3.5 sm:w-5 sm:h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z" />
                </svg>
              </div>
            </div>
            <div class="text-xs sm:text-2xl font-black text-amber-500 tracking-tight leading-tight">
              <span id="val-pv-power">0</span> <span class="text-[9px] sm:text-xs font-normal text-slate-500">W</span>
            </div>
            <div class="mt-1 pt-1 border-t border-slate-200 dark:border-slate-800 grid grid-cols-2 text-[8px] sm:text-xs text-slate-500">
              <div class="truncate"><span class="hidden sm:inline">Volt: </span><b id="val-pv-volt" class="text-slate-800 dark:text-slate-200 font-mono">0.0V</b></div>
              <div class="truncate"><span class="hidden sm:inline">Arus: </span><b id="val-pv-curr" class="text-slate-800 dark:text-slate-200 font-mono">0.0A</b></div>
            </div>
          </div>

          <!-- Node 2: PLN Grid Input (Bawah) -->
          <div id="card-node-grid" class="bg-slate-50 dark:bg-slate-950/80 border-2 border-slate-200 dark:border-slate-800 rounded-xl sm:rounded-2xl p-1.5 sm:p-3.5 shadow-sm transition-all duration-300">
            <div class="flex items-center justify-between mb-0.5 sm:mb-1.5">
              <span class="text-[8px] sm:text-xs font-bold text-blue-600 dark:text-blue-400 uppercase tracking-wider flex items-center gap-1">
                <span class="w-1.5 h-1.5 rounded-full bg-blue-500"></span>
                <span class="truncate">PLN Grid</span>
              </span>
              <div class="p-0.5 sm:p-1.5 bg-blue-500/10 text-blue-500 rounded-md sm:rounded-lg">
                <svg class="w-3.5 h-3.5 sm:w-5 sm:h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7zM7 21h10"/>
                </svg>
              </div>
            </div>
            <div class="text-xs sm:text-2xl font-black text-blue-500 tracking-tight leading-tight">
              <span id="val-grid-power">0</span> <span class="text-[9px] sm:text-xs font-normal text-slate-500">W</span>
            </div>
            <div class="mt-1 pt-1 border-t border-slate-200 dark:border-slate-800 grid grid-cols-2 text-[8px] sm:text-xs text-slate-500">
              <div class="truncate"><span class="hidden sm:inline">Volt: </span><b id="val-grid-volt" class="text-slate-800 dark:text-slate-200 font-mono">0.0V</b></div>
              <div class="truncate"><span class="hidden sm:inline">Freq: </span><b id="val-grid-freq" class="text-slate-800 dark:text-slate-200 font-mono">0.0Hz</b></div>
            </div>
          </div>

        </div>

        <!-- KOLOM 2 (Col 4 pada mobile, Cols 4-5 pada desktop): Pipa Kiri (PV Lurus & PLN Melingkar Naik ke Inverter) -->
        <div class="col-span-1 sm:col-span-2 flex flex-col justify-between h-full py-1 sm:py-4 relative min-h-[190px] sm:min-h-[260px]">
          
          <!-- SVG Canvas Pipa Kiri (PV Lurus & PLN Melengkung Naik ke Inverter) -->
          <svg class="absolute inset-0 w-full h-full overflow-visible pointer-events-none" viewBox="0 0 100 240" preserveAspectRatio="none">
            <defs>
              <marker id="arrow-amber" viewBox="0 0 10 10" refX="6" refY="5" markerWidth="5" markerHeight="5" orient="auto">
                <path d="M 0 1 L 8 5 L 0 9 z" fill="#f59e0b"/>
              </marker>
              <marker id="arrow-blue" viewBox="0 0 10 10" refX="6" refY="5" markerWidth="5" markerHeight="5" orient="auto">
                <path d="M 0 1 L 8 5 L 0 9 z" fill="#3b82f6"/>
              </marker>
              <marker id="arrow-grey" viewBox="0 0 10 10" refX="6" refY="5" markerWidth="5" markerHeight="5" orient="auto">
                <path d="M 0 1 L 8 5 L 0 9 z" fill="#94a3b8"/>
              </marker>
            </defs>

            <!-- 1. Pipa PV ➔ Inverter (Lurus Mendatar ke Inverter) -->
            <line x1="0" y1="42" x2="92" y2="42" class="pipe-tube" vector-effect="non-scaling-stroke" />
            <line id="flow-pv-line" x1="0" y1="42" x2="92" y2="42" class="flow-line-static" marker-end="url(#arrow-grey)" vector-effect="non-scaling-stroke" />

            <!-- 2. Pipa PLN ➔ Inverter (MELENGKUNG / MELINGKAR NAIK KE INVERTER, TIDAK KE BATERAI!) -->
            <path d="M 0,198 C 45,198 45,95 92,95" class="pipe-tube" fill="none" vector-effect="non-scaling-stroke" />
            <path id="flow-grid-line" d="M 0,198 C 45,198 45,95 92,95" class="flow-line-static" fill="none" marker-end="url(#arrow-grey)" vector-effect="non-scaling-stroke" />
          </svg>

          <!-- Badge Nilai PV di Atas Pipa PV -->
          <div class="relative z-10 flex flex-col items-center justify-center -mt-1 sm:mt-1">
            <span id="flow-pv-kw" class="text-[7px] sm:text-xs font-mono font-bold text-amber-500 bg-white/95 dark:bg-slate-900/95 px-1 py-0.2 rounded shadow-sm border border-slate-200 dark:border-slate-800">0.00 kW</span>
            <span id="flow-pv-status" class="hidden sm:inline-block text-[9px] px-1.5 py-0.2 rounded bg-slate-200 dark:bg-slate-800 text-slate-500 mt-0.5 font-medium">Malam / 0W</span>
          </div>

          <!-- Badge Nilai PLN di Atas Pipa PLN -->
          <div class="relative z-10 flex flex-col items-center justify-center mb-1 sm:mb-2">
            <span id="flow-grid-kw" class="text-[7px] sm:text-xs font-mono font-bold text-blue-500 bg-white/95 dark:bg-slate-900/95 px-1 py-0.2 rounded shadow-sm border border-slate-200 dark:border-slate-800">0.00 kW</span>
            <span id="flow-grid-status" class="hidden sm:inline-block text-[9px] px-1.5 py-0.2 rounded bg-slate-200 dark:bg-slate-800 text-slate-500 mt-0.5 font-medium">Standby / 0V</span>
          </div>

        </div>

        <!-- KOLOM 3 (Cols 5-8 pada mobile, Cols 6-8 pada desktop: Pusat Hub Inverter & Baterai) -->
        <div class="col-span-4 sm:col-span-3 space-y-1 sm:space-y-2.5 flex flex-col items-center">
          
          <!-- Node Pusat: Hybrid Inverter PowMr 6.5kW -->
          <div id="card-node-inv" class="w-full bg-gradient-to-b from-slate-100 to-slate-200 dark:from-slate-800 dark:to-slate-900 border-2 border-slate-300 dark:border-slate-700 rounded-xl sm:rounded-2xl p-1.5 sm:p-3.5 shadow-md text-center relative transition-all duration-300">
            <div class="absolute -top-2 left-1/2 -translate-x-1/2 px-2 sm:px-3 py-0.2 rounded-full bg-amber-500 text-slate-950 font-bold text-[7px] sm:text-[10px] tracking-wider uppercase shadow-sm">
              Inverter
            </div>
            
            <div class="w-7 h-7 sm:w-11 sm:h-11 mx-auto bg-white dark:bg-slate-950 rounded-lg sm:rounded-xl flex items-center justify-center text-amber-500 mb-0.5 border border-slate-200 dark:border-slate-700 shadow-sm mt-0.5">
              <svg class="w-4 h-4 sm:w-6 sm:h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z"/>
              </svg>
            </div>
            <h3 class="font-bold sm:font-black text-slate-900 dark:text-white text-[10px] sm:text-sm lg:text-base leading-tight">PowMr 6.5kW</h3>
            <p class="text-[7px] sm:text-[10px] text-slate-500 dark:text-slate-400 font-mono">48V Hybrid</p>

            <div class="mt-1 sm:mt-2 grid grid-cols-2 gap-1 text-[7px] sm:text-xs bg-white/70 dark:bg-slate-950/70 p-1 sm:p-1.5 rounded-lg border border-slate-200 dark:border-slate-800">
              <div class="text-left">
                <span class="text-[6px] sm:text-[9px] text-slate-400 block">Suhu</span>
                <b id="val-inv-temp" class="text-emerald-600 dark:text-emerald-400 font-mono">0.0°C</b>
              </div>
              <div class="text-right">
                <span class="text-[6px] sm:text-[9px] text-slate-400 block">Kipas</span>
                <b id="val-fan-speed" class="text-cyan-600 dark:text-cyan-400 font-mono">0%</b>
              </div>
            </div>
          </div>

          <!-- Pipa Aliran Vertikal: Inverter ◄══► Baterai 48V (Dua Arah: Charging & Discharging) -->
          <div class="flex flex-col items-center justify-center w-full py-0.5 relative">
            <div id="badge-batt-flow" class="text-[7px] sm:text-[10px] font-bold px-1.5 sm:px-2 py-0.2 rounded-full bg-slate-200 dark:bg-slate-800 text-slate-500 border border-slate-300 dark:border-slate-700 transition-all z-10 shadow-sm">
              ⚡ Standby (0.0 A)
            </div>
            
            <svg class="w-6 h-6 sm:h-8 my-0.5 overflow-visible" viewBox="0 0 20 34">
              <defs>
                <marker id="arrow-green-down" viewBox="0 0 10 10" refX="5" refY="6" markerWidth="5" markerHeight="5" orient="auto">
                  <path d="M 1 0 L 5 8 L 9 0 z" fill="#10b981"/>
                </marker>
                <marker id="arrow-amber-up" viewBox="0 0 10 10" refX="5" refY="2" markerWidth="5" markerHeight="5" orient="auto-start-reverse">
                  <path d="M 1 8 L 5 0 L 9 8 z" fill="#f59e0b"/>
                </marker>
              </defs>
              <line x1="10" y1="0" x2="10" y2="32" class="pipe-tube" vector-effect="non-scaling-stroke" />
              <line id="flow-batt-line" x1="10" y1="0" x2="10" y2="32" class="flow-line-static" marker-end="url(#arrow-grey)" vector-effect="non-scaling-stroke" />
            </svg>
          </div>

          <!-- Node 3: Baterai 48V (Bawah) -->
          <div id="card-node-batt" class="w-full bg-slate-50 dark:bg-slate-950/80 border-2 border-slate-200 dark:border-slate-800 rounded-xl sm:rounded-2xl p-1.5 sm:p-3.5 shadow-sm transition-all duration-300">
            <div class="flex items-center justify-between mb-0.5 sm:mb-1.5">
              <span class="text-[8px] sm:text-xs font-bold text-emerald-600 dark:text-emerald-400 uppercase tracking-wider flex items-center gap-1">
                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                <span class="truncate">Baterai 48V</span>
              </span>
              <div class="p-0.5 sm:p-1.5 bg-emerald-500/10 text-emerald-500 rounded-md sm:rounded-lg">
                <svg class="w-3.5 h-3.5 sm:w-5 sm:h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                </svg>
              </div>
            </div>

            <!-- SOC % dan Status -->
            <div class="flex items-baseline justify-between">
              <div class="text-xs sm:text-2xl font-black text-emerald-500 tracking-tight leading-tight">
                <span id="val-batt-soc">0</span><span class="text-[9px] sm:text-sm font-bold">%</span>
              </div>
              <span id="val-batt-status-pill" class="text-[7px] sm:text-xs font-bold px-1 sm:px-1.5 py-0.2 rounded bg-slate-200 dark:bg-slate-800 text-slate-500">Standby</span>
            </div>

            <div class="w-full bg-slate-200 dark:bg-slate-800 rounded-full h-1 sm:h-2 my-1 sm:my-1.5 overflow-hidden border border-slate-300 dark:border-slate-700">
              <div id="bar-batt-soc" class="bg-gradient-to-r from-emerald-500 to-teal-400 h-full rounded-full transition-all duration-500" style="width: 0%"></div>
            </div>

            <div class="grid grid-cols-2 text-[8px] sm:text-xs text-slate-500 pt-0.5">
              <div class="truncate"><span class="hidden sm:inline">Volt: </span><b id="val-batt-volt" class="text-slate-800 dark:text-slate-200 font-mono">0.0V</b></div>
              <div class="truncate"><span class="hidden sm:inline">Bus: </span><b id="val-bus-volt" class="text-slate-800 dark:text-slate-200 font-mono">0V</b></div>
            </div>
          </div>

        </div>

        <!-- KOLOM 4 (Col 9 pada mobile, Col 9 pada desktop: Pipa Inverter ➔ Beban Rumah) -->
        <div class="col-span-1 sm:col-span-1 flex flex-col items-center justify-center relative min-h-[190px] sm:min-h-[260px]">
          <svg class="absolute inset-0 w-full h-full overflow-visible pointer-events-none" viewBox="0 0 60 240" preserveAspectRatio="none">
            <defs>
              <marker id="arrow-cyan" viewBox="0 0 10 10" refX="6" refY="5" markerWidth="5" markerHeight="5" orient="auto">
                <path d="M 0 1 L 8 5 L 0 9 z" fill="#06b6d4"/>
              </marker>
            </defs>
            <!-- Pipa dari Inverter (Y=65) ke Beban Rumah (Y=65) -->
            <line x1="0" y1="65" x2="52" y2="65" class="pipe-tube" vector-effect="non-scaling-stroke" />
            <line id="flow-load-line" x1="0" y1="65" x2="52" y2="65" class="flow-line-static" marker-end="url(#arrow-grey)" vector-effect="non-scaling-stroke" />
          </svg>

          <!-- Badge Beban pada Pipa -->
          <div class="relative z-10 flex flex-col items-center justify-center -mt-16 sm:-mt-24">
            <span id="flow-load-kw" class="text-[7px] sm:text-xs font-mono font-bold text-cyan-500 bg-white/95 dark:bg-slate-900/95 px-1 py-0.2 rounded shadow-sm border border-slate-200 dark:border-slate-800">0.00 kW</span>
            <span id="flow-load-status" class="hidden sm:inline-block text-[9px] px-1.5 py-0.2 rounded bg-slate-200 dark:bg-slate-800 text-slate-500 mt-0.5 font-medium">Nol Beban</span>
          </div>
        </div>

        <!-- KOLOM 5 (Cols 10-12, 25%): Beban Rumah (AC Load) -->
        <div class="col-span-3 sm:col-span-3">
          
          <!-- Node 4: Beban Rumah / Home Load -->
          <div id="card-node-load" class="bg-slate-50 dark:bg-slate-950/80 border-2 border-slate-200 dark:border-slate-800 rounded-xl sm:rounded-2xl p-1.5 sm:p-3.5 shadow-sm transition-all duration-300">
            <div class="flex items-center justify-between mb-0.5 sm:mb-1.5">
              <span class="text-[8px] sm:text-xs font-bold text-cyan-600 dark:text-cyan-400 uppercase tracking-wider flex items-center gap-1">
                <span class="w-1.5 h-1.5 rounded-full bg-cyan-500"></span>
                <span class="truncate">Beban</span>
              </span>
              <div class="p-0.5 sm:p-1.5 bg-cyan-500/10 text-cyan-500 rounded-md sm:rounded-lg">
                <svg class="w-3.5 h-3.5 sm:w-5 sm:h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                </svg>
              </div>
            </div>
            
            <div class="text-xs sm:text-2xl font-black text-cyan-500 tracking-tight leading-tight">
              <span id="val-load-power">0</span> <span class="text-[9px] sm:text-xs font-normal text-slate-500">W</span>
            </div>

            <div class="mt-1 space-y-1 text-[8px] sm:text-xs">
              <div>
                <div class="flex justify-between text-slate-500 mb-0.5">
                  <span class="hidden sm:inline">Persentase:</span>
                  <b id="val-load-pct" class="text-slate-800 dark:text-slate-200 font-mono">0%</b>
                </div>
                <div class="w-full bg-slate-200 dark:bg-slate-800 rounded-full h-1 sm:h-2 overflow-hidden">
                  <div id="bar-load-pct" class="bg-cyan-500 h-full rounded-full transition-all duration-500" style="width: 0%"></div>
                </div>
              </div>
              <div class="grid grid-cols-2 pt-1 border-t border-slate-200 dark:border-slate-800 text-slate-500">
                <div class="truncate"><span class="hidden sm:inline">AC: </span><b id="val-ac-volt" class="text-slate-800 dark:text-slate-200 font-mono">0.0V</b></div>
                <div class="truncate"><span class="hidden sm:inline">Hz: </span><b id="val-ac-freq" class="text-slate-800 dark:text-slate-200 font-mono">0.0Hz</b></div>
              </div>
            </div>
          </div>

        </div>

      </div>
    </div>

  </div>

  <!-- 2. SUMMARY KWH COUNTERS (HGEN INVERTER ACCUMULATORS) -->
  <div class="grid grid-cols-2 md:grid-cols-4 gap-2.5 sm:gap-4">
    
    <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-3 sm:p-4 shadow-sm hover:border-amber-500 transition-colors">
      <div class="flex items-center justify-between text-slate-500 mb-1">
        <span class="text-xs font-semibold">Produksi Hari Ini</span>
        <span class="p-1.5 bg-amber-500/10 rounded-lg text-amber-500">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
        </span>
      </div>
      <div class="text-lg sm:text-2xl font-black text-amber-500 tracking-tight"><span id="val-daily-kwh">0.000</span> <span class="text-xs font-normal text-slate-500">kWh</span></div>
      <p class="text-[10px] sm:text-[11px] text-slate-400 mt-1">Reset jam 00:00</p>
    </div>

    <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-3 sm:p-4 shadow-sm hover:border-emerald-500 transition-colors">
      <div class="flex items-center justify-between text-slate-500 mb-1">
        <span class="text-xs font-semibold">Produksi Bulan Ini</span>
        <span class="p-1.5 bg-emerald-500/10 rounded-lg text-emerald-500">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
        </span>
      </div>
      <div class="text-lg sm:text-2xl font-black text-emerald-500 tracking-tight"><span id="val-monthly-kwh">0.0</span> <span class="text-xs font-normal text-slate-500">kWh</span></div>
      <p class="text-[10px] sm:text-[11px] text-slate-400 mt-1">Bulan berjalan</p>
    </div>

    <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-3 sm:p-4 shadow-sm hover:border-cyan-500 transition-colors">
      <div class="flex items-center justify-between text-slate-500 mb-1">
        <span class="text-xs font-semibold">Produksi Tahun Ini</span>
        <span class="p-1.5 bg-cyan-500/10 rounded-lg text-cyan-500">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
        </span>
      </div>
      <div class="text-lg sm:text-2xl font-black text-cyan-500 tracking-tight"><span id="val-yearly-kwh">0.0</span> <span class="text-xs font-normal text-slate-500">kWh</span></div>
      <p class="text-[10px] sm:text-[11px] text-slate-400 mt-1">Tahun berjalan</p>
    </div>

    <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-3 sm:p-4 shadow-sm hover:border-purple-500 transition-colors">
      <div class="flex items-center justify-between text-slate-500 mb-1">
        <span class="text-xs font-semibold">Total Lifetime</span>
        <span class="p-1.5 bg-purple-500/10 rounded-lg text-purple-500">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        </span>
      </div>
      <div class="text-lg sm:text-2xl font-black text-purple-500 tracking-tight"><span id="val-total-kwh">0.0</span> <span class="text-xs font-normal text-slate-500">kWh</span></div>
      <p class="text-[10px] sm:text-[11px] text-slate-400 mt-1">Sejak dipasang</p>
    </div>

  </div>

  <!-- 3. RINCIAN TERMAL & STATUS KIPAS -->
  <div class="grid grid-cols-1 md:grid-cols-2 gap-3 sm:gap-6">
    
    <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-4 sm:p-5 shadow-sm">
      <h3 class="text-xs sm:text-sm font-bold text-slate-900 dark:text-white mb-3 flex items-center gap-2">
        <svg class="w-4 h-4 text-rose-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
        Suhu Termal Inverter (HTEMP)
      </h3>
      <div class="grid grid-cols-2 gap-2 sm:gap-3">
        <div class="bg-slate-50 dark:bg-slate-950 p-2 sm:p-3 rounded-xl border border-slate-200 dark:border-slate-800">
          <span class="text-[10px] sm:text-[11px] text-slate-400 block">Suhu Inti Heatsink</span>
          <b id="val-temp-core" class="text-sm sm:text-lg text-slate-900 dark:text-white font-mono">0.0 °C</b>
        </div>
        <div class="bg-slate-50 dark:bg-slate-950 p-2 sm:p-3 rounded-xl border border-slate-200 dark:border-slate-800">
          <span class="text-[10px] sm:text-[11px] text-slate-400 block">Suhu Boost DC-DC</span>
          <b id="val-temp-boost" class="text-sm sm:text-lg text-slate-900 dark:text-white font-mono">0.0 °C</b>
        </div>
        <div class="bg-slate-50 dark:bg-slate-950 p-2 sm:p-3 rounded-xl border border-slate-200 dark:border-slate-800">
          <span class="text-[10px] sm:text-[11px] text-slate-400 block">Suhu Trafo</span>
          <b id="val-temp-trafo" class="text-sm sm:text-lg text-slate-900 dark:text-white font-mono">0.0 °C</b>
        </div>
        <div class="bg-slate-50 dark:bg-slate-950 p-2 sm:p-3 rounded-xl border border-slate-200 dark:border-slate-800">
          <span class="text-[10px] sm:text-[11px] text-slate-400 block">Suhu Heatsink PV</span>
          <b id="val-temp-pv" class="text-sm sm:text-lg text-slate-900 dark:text-white font-mono">0.0 °C</b>
        </div>
      </div>
    </div>

    <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-4 sm:p-5 shadow-sm">
      <h3 class="text-xs sm:text-sm font-bold text-slate-900 dark:text-white mb-3 flex items-center gap-2">
        <svg class="w-4 h-4 text-cyan-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        Kecepatan Kipas Pendingin
      </h3>
      <div class="space-y-3 pt-1">
        <div>
          <div class="flex justify-between text-xs mb-1">
            <span class="text-slate-500">Kipas 1 (Main Heatsink Fan)</span>
            <b id="val-fan1" class="text-cyan-600 dark:text-cyan-400 font-mono">0 %</b>
          </div>
          <div class="w-full bg-slate-200 dark:bg-slate-800 rounded-full h-2 overflow-hidden">
            <div id="bar-fan1" class="bg-cyan-500 h-2 rounded-full transition-all duration-300" style="width: 0%"></div>
          </div>
        </div>
        <div>
          <div class="flex justify-between text-xs mb-1">
            <span class="text-slate-500">Kipas 2 (Secondary Fan)</span>
            <b id="val-fan2" class="text-cyan-600 dark:text-cyan-400 font-mono">0 %</b>
          </div>
          <div class="w-full bg-slate-200 dark:bg-slate-800 rounded-full h-2 overflow-hidden">
            <div id="bar-fan2" class="bg-cyan-500 h-2 rounded-full transition-all duration-300" style="width: 0%"></div>
          </div>
        </div>
      </div>
    </div>

  </div>

</div>

<!-- SCRIPT POLLING DATA REALTIME DENGAN DETEKTOR ONLINE/OFFLINE ESP32 -->
<script>
  let lastTimestamp = null;
  let currentDiffSec = null;
  const ESP32_TIMEOUT_SEC = 25; // Jika tidak ada data dalam 25 detik -> ESP32 Terdeteksi OFFLINE

  // Update Tampilan Detektor Status Mikrokontroler (ESP32-C3)
  function updateMcuStatus(diffSec) {
    const mcuBadge = document.getElementById('mcu-badge');
    const mcuDot = document.getElementById('mcu-dot');
    const mcuText = document.getElementById('mcu-text');
    const mcuHeartbeat = document.getElementById('mcu-heartbeat-pill');
    const statusPing = document.getElementById('status-ping');
    const statusDot = document.getElementById('status-dot');
    const offlineBanner = document.getElementById('mcu-offline-banner');
    const offlineMsg = document.getElementById('mcu-offline-msg');

    if (diffSec !== null && diffSec <= ESP32_TIMEOUT_SEC) {
      // ================= KONDISI ONLINE (ESP32 Aktif) =================
      mcuBadge.className = "text-xs font-bold px-2.5 py-0.5 rounded-full bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border border-emerald-500/30 flex items-center gap-1.5 shadow-sm";
      mcuDot.className = "w-2 h-2 rounded-full bg-emerald-500 animate-pulse";
      mcuText.innerText = "ESP32-C3: ONLINE";

      statusPing.className = "animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75";
      statusDot.className = "relative inline-flex rounded-full h-3.5 w-3.5 bg-emerald-500";

      mcuHeartbeat.innerText = "Heartbeat: " + diffSec + " dtk lalu";
      mcuHeartbeat.className = "px-2.5 py-1 rounded-lg bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 font-mono text-[11px] border border-emerald-500/20 font-semibold";

      if (offlineBanner) offlineBanner.classList.add('hidden');

    } else {
      // ================= KONDISI OFFLINE (ESP32 Mati / Terputus) =================
      mcuBadge.className = "text-xs font-bold px-2.5 py-0.5 rounded-full bg-rose-500/10 text-rose-600 dark:text-rose-400 border border-rose-500/30 flex items-center gap-1.5 shadow-sm";
      mcuDot.className = "w-2 h-2 rounded-full bg-rose-500";
      mcuText.innerText = "ESP32-C3: OFFLINE";

      statusPing.className = "hidden";
      statusDot.className = "relative inline-flex rounded-full h-3.5 w-3.5 bg-rose-500";

      let timeText = "Belum Ada Data";
      if (diffSec !== null) {
        const min = Math.floor(diffSec / 60);
        timeText = min > 0 ? min + " menit lalu" : diffSec + " detik lalu";
      }

      mcuHeartbeat.innerText = "Offline (" + timeText + ")";
      mcuHeartbeat.className = "px-2.5 py-1 rounded-lg bg-rose-500/10 text-rose-600 dark:text-rose-400 font-mono text-[11px] border border-rose-500/20 font-semibold";

      if (offlineBanner) {
        offlineBanner.classList.remove('hidden');
        if (offlineMsg) {
          offlineMsg.innerText = "Paket terakhir tercatat " + timeText + ". Seluruh garis animasi dialihkan ke status Statik/Standby hingga sinyal ESP32 terhubung kembali.";
        }
      }
    }
  }

  // Setel semua pipa ke kondisi STATIK (abu-abu diam tanpa animasi) saat daya 0 atau offline
  function setAllLinesToStatic() {
    // 1. PV Line
    const flowPvLine = document.getElementById('flow-pv-line');
    if (flowPvLine) {
      flowPvLine.setAttribute('class', 'flow-line-static');
      flowPvLine.setAttribute('marker-end', 'url(#arrow-grey)');
    }
    const flowPvStatus = document.getElementById('flow-pv-status');
    if (flowPvStatus) {
      flowPvStatus.innerText = "Malam / 0W";
      flowPvStatus.className = "hidden sm:inline-block text-[9px] px-1.5 py-0.2 rounded bg-slate-200 dark:bg-slate-800 text-slate-500 mt-0.5 font-medium";
    }
    const cardPv = document.getElementById('card-node-pv');
    if (cardPv) cardPv.classList.remove('pulse-glow-amber');

    // 2. PLN Grid Line (Melingkar naik ke Inverter)
    const flowGridLine = document.getElementById('flow-grid-line');
    if (flowGridLine) {
      flowGridLine.setAttribute('class', 'flow-line-static');
      flowGridLine.setAttribute('marker-end', 'url(#arrow-grey)');
    }
    const flowGridStatus = document.getElementById('flow-grid-status');
    if (flowGridStatus) {
      flowGridStatus.innerText = "Standby / 0V";
      flowGridStatus.className = "hidden sm:inline-block text-[9px] px-1.5 py-0.2 rounded bg-slate-200 dark:bg-slate-800 text-slate-500 mt-0.5 font-medium";
    }
    const cardGrid = document.getElementById('card-node-grid');
    if (cardGrid) cardGrid.classList.remove('pulse-glow-blue');

    // 3. Battery Line (Vertikal Inverter <-> Baterai)
    const flowBattLine = document.getElementById('flow-batt-line');
    if (flowBattLine) {
      flowBattLine.setAttribute('class', 'flow-line-static');
      flowBattLine.setAttribute('marker-end', 'url(#arrow-grey)');
    }
    const battFlowBadge = document.getElementById('badge-batt-flow');
    if (battFlowBadge) {
      battFlowBadge.innerText = "⚡ Standby (0.0 A)";
      battFlowBadge.className = "text-[7px] sm:text-[10px] font-bold px-1.5 sm:px-2 py-0.2 rounded-full bg-slate-200 dark:bg-slate-800 text-slate-500 border border-slate-300 dark:border-slate-700 transition-all z-10 shadow-sm";
    }
    const battPill = document.getElementById('val-batt-status-pill');
    if (battPill) {
      battPill.innerText = "Standby";
      battPill.className = "text-[7px] sm:text-xs font-bold px-1 sm:px-1.5 py-0.2 rounded bg-slate-200 dark:bg-slate-800 text-slate-500";
    }
    const cardBatt = document.getElementById('card-node-batt');
    if (cardBatt) cardBatt.classList.remove('pulse-glow-emerald', 'pulse-glow-amber');

    // 4. Load Line (Inverter ➔ Beban Rumah)
    const flowLoadLine = document.getElementById('flow-load-line');
    if (flowLoadLine) {
      flowLoadLine.setAttribute('class', 'flow-line-static');
      flowLoadLine.setAttribute('marker-end', 'url(#arrow-grey)');
    }
    const flowLoadStatus = document.getElementById('flow-load-status');
    if (flowLoadStatus) {
      flowLoadStatus.innerText = "Nol Beban";
      flowLoadStatus.className = "hidden sm:inline-block text-[9px] px-1.5 py-0.2 rounded bg-slate-200 dark:bg-slate-800 text-slate-500 mt-0.5 font-medium";
    }
    const cardLoad = document.getElementById('card-node-load');
    if (cardLoad) cardLoad.classList.remove('pulse-glow-cyan');
  }

  // Polling AJAX Continuous setiap 2 Detik
  async function fetchLatestData() {
    try {
      const response = await fetch('api.php?action=latest&t=' + Date.now());
      const res = await response.json();
      
      if (res.status === 'success' && res.data) {
        const d = res.data;
        lastTimestamp = new Date(d.created_at).getTime();

        // Gunakan diff_seconds dari API server jika tersedia, atau hitung lokal
        currentDiffSec = (res.diff_seconds !== undefined && res.diff_seconds !== null) 
          ? parseInt(res.diff_seconds) 
          : Math.max(0, Math.floor((Date.now() - lastTimestamp) / 1000));

        // Update Status ESP32 Online / Offline
        updateMcuStatus(currentDiffSec);

        // Jika ESP32 Offline (> 25 detik), garis harus statik
        const isMcuOnline = (currentDiffSec <= ESP32_TIMEOUT_SEC);

        document.getElementById('mode-badge').innerText = d.operating_mode || "Normal Mode";
        document.getElementById('last-update').innerText = d.created_at;

        // 1. Solar PV Logic
        const pvW = Math.round(d.pv_power || 0);
        const pvV = parseFloat(d.pv_voltage || 0).toFixed(1);
        const pvA = parseFloat(d.pv_current || 0).toFixed(1);

        document.getElementById('val-pv-power').innerText = pvW;
        document.getElementById('val-pv-volt').innerText = pvV + "V";
        document.getElementById('val-pv-curr').innerText = pvA + "A";
        document.getElementById('flow-pv-kw').innerText = (pvW / 1000).toFixed(2) + " kW";

        const flowPvLine = document.getElementById('flow-pv-line');
        const flowPvStatus = document.getElementById('flow-pv-status');
        const cardPv = document.getElementById('card-node-pv');

        if (isMcuOnline && pvW > 10) {
          // AKTIF: Garis mengalir warna kuning/emas dengan panah amber
          flowPvLine.setAttribute('class', 'flow-pv-active');
          flowPvLine.setAttribute('marker-end', 'url(#arrow-amber)');
          flowPvStatus.innerText = "Aktif (" + pvW + "W)";
          flowPvStatus.className = "hidden sm:inline-block text-[9px] px-1.5 py-0.2 rounded bg-amber-500/20 text-amber-500 font-bold border border-amber-500/30 mt-0.5";
          cardPv.classList.add('pulse-glow-amber');
        } else {
          // STATIK: Garis abu-abu diam
          flowPvLine.setAttribute('class', 'flow-line-static');
          flowPvLine.setAttribute('marker-end', 'url(#arrow-grey)');
          flowPvStatus.innerText = "Malam / 0W";
          flowPvStatus.className = "hidden sm:inline-block text-[9px] px-1.5 py-0.2 rounded bg-slate-200 dark:bg-slate-800 text-slate-500 mt-0.5 font-medium";
          cardPv.classList.remove('pulse-glow-amber');
        }

        // 2. Jaringan PLN Grid Logic (MELENGKUNG / MELINGKAR NAIK KE INVERTER)
        const gridW = Math.round(d.mains_power || 0);
        const gridV = parseFloat(d.ac_input_voltage || 0).toFixed(1);
        const gridHz = parseFloat(d.mains_freq || 0).toFixed(1);
        const opMode = (d.operating_mode || "").toLowerCase();
        const chgCurrent = parseFloat(d.charging_current || 0);

        document.getElementById('val-grid-power').innerText = gridW;
        document.getElementById('val-grid-volt').innerText = gridV + "V";
        document.getElementById('val-grid-freq').innerText = gridHz + "Hz";
        document.getElementById('flow-grid-kw').innerText = (gridW / 1000).toFixed(2) + " kW";

        const flowGridLine = document.getElementById('flow-grid-line');
        const flowGridStatus = document.getElementById('flow-grid-status');
        const cardGrid = document.getElementById('card-node-grid');

        // Deteksi pakai PLN: daya PLN > 10W, atau mode Line/Bypass/Utility, atau charging baterai dari PLN
        const isPlnActive = (gridW > 10) || 
                            (parseFloat(gridV) > 150 && (opMode.includes('line') || opMode.includes('bypass') || opMode.includes('grid') || opMode.includes('utility'))) ||
                            (parseFloat(gridV) > 150 && chgCurrent > 0.5 && pvW < 20);

        if (isMcuOnline && isPlnActive) {
          // AKTIF: Garis melingkar naik ke inverter menyala biru mengalir dengan panah biru
          flowGridLine.setAttribute('class', 'flow-grid-active');
          flowGridLine.setAttribute('marker-end', 'url(#arrow-blue)');
          flowGridStatus.innerText = "Menyuplai (" + (gridW > 0 ? gridW + "W" : "Bypass") + ")";
          flowGridStatus.className = "hidden sm:inline-block text-[9px] px-1.5 py-0.2 rounded bg-blue-500/20 text-blue-500 font-bold border border-blue-500/30 mt-0.5";
          cardGrid.classList.add('pulse-glow-blue');
        } else {
          // STATIK: Garis abu-abu diam
          flowGridLine.setAttribute('class', 'flow-line-static');
          flowGridLine.setAttribute('marker-end', 'url(#arrow-grey)');
          flowGridStatus.innerText = parseFloat(gridV) > 100 ? "Standby (" + gridV + "V)" : "Terputus / 0V";
          flowGridStatus.className = "hidden sm:inline-block text-[9px] px-1.5 py-0.2 rounded bg-slate-200 dark:bg-slate-800 text-slate-500 mt-0.5 font-medium";
          cardGrid.classList.remove('pulse-glow-blue');
        }

        // 3. Beban Rumah (AC Load) Logic
        const loadW = Math.round(d.output_active_power || 0);
        const loadPct = Math.round(d.output_load_percent || 0);
        const acV = parseFloat(d.ac_output_voltage || 0).toFixed(1);
        const acHz = parseFloat(d.ac_output_freq || 0).toFixed(1);

        document.getElementById('val-load-power').innerText = loadW;
        document.getElementById('val-load-pct').innerText = loadPct + "%";
        document.getElementById('bar-load-pct').style.width = Math.min(100, Math.max(0, loadPct)) + "%";
        document.getElementById('val-ac-volt').innerText = acV + "V";
        document.getElementById('val-ac-freq').innerText = acHz + "Hz";
        document.getElementById('flow-load-kw').innerText = (loadW / 1000).toFixed(2) + " kW";

        const flowLoadLine = document.getElementById('flow-load-line');
        const flowLoadStatus = document.getElementById('flow-load-status');
        const cardLoad = document.getElementById('card-node-load');

        if (isMcuOnline && loadW > 10) {
          // AKTIF: Garis mengalir warna cyan dengan panah cyan
          flowLoadLine.setAttribute('class', 'flow-load-active');
          flowLoadLine.setAttribute('marker-end', 'url(#arrow-cyan)');
          flowLoadStatus.innerText = "Menyuplai (" + loadPct + "%)";
          flowLoadStatus.className = "hidden sm:inline-block text-[9px] px-1.5 py-0.2 rounded bg-cyan-500/20 text-cyan-500 font-bold border border-cyan-500/30 mt-0.5";
          cardLoad.classList.add('pulse-glow-cyan');
        } else {
          // STATIK: Garis abu-abu diam
          flowLoadLine.setAttribute('class', 'flow-line-static');
          flowLoadLine.setAttribute('marker-end', 'url(#arrow-grey)');
          flowLoadStatus.innerText = "Nol Beban";
          flowLoadStatus.className = "hidden sm:inline-block text-[9px] px-1.5 py-0.2 rounded bg-slate-200 dark:bg-slate-800 text-slate-500 mt-0.5 font-medium";
          cardLoad.classList.remove('pulse-glow-cyan');
        }

        // 4. Baterai 48V (Dua Arah: Charging Hijau vs Discharging Amber)
        const soc = Math.round(d.battery_capacity || 0);
        const battV = parseFloat(d.battery_voltage || 0).toFixed(1);
        const busV = Math.round(d.bus_voltage || 0);
        const disCurrent = parseFloat(d.discharge_current || 0);

        document.getElementById('val-batt-soc').innerText = soc;
        document.getElementById('bar-batt-soc').style.width = Math.min(100, Math.max(0, soc)) + "%";
        document.getElementById('val-batt-volt').innerText = battV + "V";
        document.getElementById('val-bus-volt').innerText = busV + "V";

        const battLine = document.getElementById('flow-batt-line');
        const battFlowBadge = document.getElementById('badge-batt-flow');
        const battPill = document.getElementById('val-batt-status-pill');

        if (isMcuOnline && (chgCurrent > 0.5 || disCurrent > 0.5)) {
          if (chgCurrent > 0.5) {
            // MENGISI (CHARGING): Aliran turun ke baterai (Hijau)
            battPill.innerText = "Charging";
            battPill.className = "text-[7px] sm:text-xs font-bold px-1 sm:px-1.5 py-0.2 rounded bg-emerald-500/20 text-emerald-600 dark:text-emerald-400 border border-emerald-500/30";

            battFlowBadge.innerText = "🟢 Mengisi (+" + chgCurrent.toFixed(1) + " A)";
            battFlowBadge.className = "text-[7px] sm:text-[10px] font-bold px-1.5 sm:px-2 py-0.2 rounded-full bg-emerald-500/20 text-emerald-600 dark:text-emerald-400 border border-emerald-500/30 transition-all z-10 shadow-sm";

            if (battLine) {
              battLine.setAttribute('class', 'flow-batt-charge');
              battLine.setAttribute('marker-end', 'url(#arrow-green-down)');
            }
          } else {
            // MENGOSONGKAN (DISCHARGING): Aliran naik ke inverter (Amber)
            battPill.innerText = "Discharge";
            battPill.className = "text-[7px] sm:text-xs font-bold px-1 sm:px-1.5 py-0.2 rounded bg-amber-500/20 text-amber-600 dark:text-amber-400 border border-amber-500/30";

            battFlowBadge.innerText = "🟠 Mengosongkan (-" + disCurrent.toFixed(1) + " A)";
            battFlowBadge.className = "text-[7px] sm:text-[10px] font-bold px-1.5 sm:px-2 py-0.2 rounded-full bg-amber-500/20 text-amber-600 dark:text-amber-400 border border-amber-500/30 transition-all z-10 shadow-sm";

            if (battLine) {
              battLine.setAttribute('class', 'flow-batt-discharge');
              battLine.setAttribute('marker-end', 'url(#arrow-amber-up)');
            }
          }

        } else {
          // STATIK: Standby / Idle, garis abu-abu diam
          battPill.innerText = "Standby";
          battPill.className = "text-[7px] sm:text-xs font-bold px-1 sm:px-1.5 py-0.2 rounded bg-slate-200 dark:bg-slate-800 text-slate-500";

          battFlowBadge.innerText = "⚡ Standby (0.0 A)";
          battFlowBadge.className = "text-[7px] sm:text-[10px] font-bold px-1.5 sm:px-2 py-0.2 rounded-full bg-slate-200 dark:bg-slate-800 text-slate-500 border border-slate-300 dark:border-slate-700 transition-all z-10 shadow-sm";

          if (battLine) {
            battLine.setAttribute('class', 'flow-line-static');
            battLine.setAttribute('marker-end', 'url(#arrow-grey)');
          }
        }

        // 5. Inverter Health (Suhu & Kipas)
        const invTemp = parseFloat(d.inverter_temp || 0).toFixed(1);
        const fanSpeed = Math.round(d.fan1_speed || 0);

        document.getElementById('val-inv-temp').innerText = invTemp + "°C";
        document.getElementById('val-fan-speed').innerText = fanSpeed + "%";

        // Rincian Termal
        document.getElementById('val-temp-core').innerText = invTemp + " °C";
        document.getElementById('val-temp-boost').innerText = parseFloat(d.boost_temp || 0).toFixed(1) + " °C";
        document.getElementById('val-temp-trafo').innerText = parseFloat(d.trafo_temp || 0).toFixed(1) + " °C";
        document.getElementById('val-temp-pv').innerText = parseFloat(d.pv_temp || 0).toFixed(1) + " °C";

        // Rincian Kipas
        const fan1 = Math.round(d.fan1_speed || 0);
        const fan2 = Math.round(d.fan2_speed || 0);
        document.getElementById('val-fan1').innerText = fan1 + " %";
        document.getElementById('val-fan2').innerText = fan2 + " %";
        document.getElementById('bar-fan1').style.width = fan1 + "%";
        document.getElementById('bar-fan2').style.width = fan2 + "%";

        // 6. Akumulator Energi Inverter
        document.getElementById('val-daily-kwh').innerText = parseFloat(d.daily_kwh || 0).toFixed(3);
        document.getElementById('val-monthly-kwh').innerText = parseFloat(d.monthly_kwh || 0).toFixed(1);
        document.getElementById('val-yearly-kwh').innerText = parseFloat(d.yearly_kwh || 0).toFixed(1);
        document.getElementById('val-total-kwh').innerText = parseFloat(d.total_kwh || 0).toFixed(1);

      } else {
        // Data kosong di database
        setAllLinesToStatic();
        updateMcuStatus(null);
        document.getElementById('mode-badge').innerText = "Belum Ada Data";
      }
    } catch (err) {
      console.warn("Polling error:", err);
      setAllLinesToStatic();
      updateMcuStatus(null);
    }
  }

  // Hitung dan perbarui status deteksi setiap 1 detik
  function updateTimerTick() {
    if (currentDiffSec !== null) {
      currentDiffSec++;
      updateMcuStatus(currentDiffSec);
    }

    if (!lastTimestamp) {
      document.getElementById('relative-time').innerText = "menunggu ESP32...";
      return;
    }
    const diffSec = currentDiffSec !== null ? currentDiffSec : Math.round((Date.now() - lastTimestamp) / 1000);
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
  updateMcuStatus(null);

  // Mulai polling continuous setiap 2 detik
  fetchLatestData();
  setInterval(fetchLatestData, 2000);

  // Interval detak timer lokal setiap 1 detik untuk heartbeat real-time
  setInterval(updateTimerTick, 1000);
</script>

<?php
// Footer sederhana jika ada
?>
</body>
</html>
