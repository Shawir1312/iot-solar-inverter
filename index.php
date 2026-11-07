<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/auth.php';

$pageTitle = "Realtime Power Flow • " . (defined('APP_NAME') ? APP_NAME : 'PowMr 6.5kW Monitor');
require_once __DIR__ . '/navbar.php';
?>

<div class="max-w-7xl mx-auto px-3 sm:px-6 lg:px-8 py-3 sm:py-6 space-y-4 sm:space-y-6">

  <!-- ======================================================== -->
  <!-- 1. HERO TITLE SECTION                                   -->
  <!-- ======================================================== -->
  <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pt-1">
    
    <!-- Hero Left: Lightning Icon Badge + Title -->
    <div class="flex items-center gap-3.5">
      <!-- Circular Glowing Amber Icon -->
      <div class="w-11 h-11 sm:w-13 sm:h-13 rounded-2xl bg-gradient-to-tr from-amber-500/20 to-amber-400/40 border border-amber-400/40 flex items-center justify-center text-amber-400 shadow-[0_0_20px_rgba(245,158,11,0.35)] flex-shrink-0">
        <svg class="w-6 h-6 sm:w-7 sm:h-7" fill="currentColor" viewBox="0 0 20 20">
          <path fill-rule="evenodd" d="M11.3 1.046A1 1 0 0112 2v5h4a1 1 0 01.82 1.573l-7 10A1 1 0 018 18v-5H4a1 1 0 01-.82-1.573l7-10a1 1 0 011.12-.38z" clip-rule="evenodd" />
        </svg>
      </div>
      <div>
        <h1 class="text-xl sm:text-2xl lg:text-3xl font-extrabold text-white tracking-tight flex items-center gap-2">
          <span>PLTS Monitoring System</span>
        </h1>
        <p class="text-xs sm:text-sm text-slate-400 font-medium">PowMr 6.5kW Hybrid Inverter</p>
      </div>
    </div>

    <!-- Hero Right: Live Status Badge & Timestamp -->
    <div class="flex items-center gap-2 self-start sm:self-auto flex-wrap text-xs">
      <!-- Mode Operasi Inverter Pill -->
      <span id="mode-badge" class="px-3 py-1 rounded-full bg-amber-500/10 text-amber-400 border border-amber-500/30 font-semibold shadow-sm text-xs">
        Standby
      </span>
      <!-- Heartbeat Pill -->
      <span id="mcu-heartbeat-pill" class="px-3 py-1 rounded-full bg-slate-900/80 text-slate-400 border border-slate-700/60 font-mono text-xs">
        Heartbeat: -
      </span>
      <span class="text-[11px] text-slate-500 hidden lg:inline">
        Log: <b id="last-update" class="text-slate-300 font-mono">-</b>
      </span>
    </div>

  </div>

  <!-- Banner Peringatan Saat Mikrokontroler ESP32 OFFLINE -->
  <div id="mcu-offline-banner" class="hidden bg-rose-950/40 border border-rose-500/40 text-rose-300 rounded-2xl p-3 sm:p-4 text-xs sm:text-sm flex items-center justify-between gap-3 shadow-lg shadow-rose-950/50 transition-all duration-300">
    <div class="flex items-center gap-3">
      <div class="p-2 bg-rose-500/20 text-rose-400 border border-rose-500/40 rounded-xl flex-shrink-0">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
      </div>
      <div>
        <b class="text-rose-200">Mikrokontroler ESP32-C3 OFFLINE:</b> <span id="mcu-offline-msg">Belum ada data telemetri baru diterima dalam 25 detik terakhir. Pastikan ESP32 terhubung ke WiFi dan daya aktif.</span>
      </div>
    </div>
    <span class="px-2.5 py-1 rounded-lg bg-rose-500/20 text-rose-300 font-bold text-[10px] uppercase border border-rose-500/30 flex-shrink-0">Standby</span>
  </div>

  <!-- ======================================================== -->
  <!-- 2. DIAGRAM ALIRAN DAYA SEJATI (LIVE POWER FLOW)          -->
  <!-- ======================================================== -->
  <div class="glass-panel rounded-3xl p-3.5 sm:p-6 lg:p-7 shadow-2xl relative overflow-hidden">
    
    <!-- Header Diagram Badge -->
    <div class="flex items-center justify-between mb-3 sm:mb-6 border-b border-blue-500/15 pb-3">
      <div class="flex items-center gap-2.5">
        <div class="inline-flex items-center gap-1.5 px-3 py-1 rounded-lg bg-blue-600/20 border border-blue-500/40 text-blue-400 font-bold text-xs shadow-sm">
          <svg class="w-4 h-4 text-cyan-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
          </svg>
          <span>Diagram Aliran Daya</span>
        </div>
        <p class="hidden sm:inline text-xs text-slate-400">Visualisasi aliran energi PV, PLN Grid ➔ Inverter ◄══► Baterai 48V ➔ Beban Rumah</p>
      </div>
      <div class="flex items-center gap-1.5 text-xs text-slate-400 font-medium">
        <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
        <span class="font-mono text-[11px]">Live 2s</span>
      </div>
    </div>

    <!-- Canvas Skematik 5-Kolom (Left Cards - Left Pipes - Center Hub - Right Pipe - Right Card) -->
    <div class="w-full overflow-x-auto sm:overflow-visible">
      <div class="grid grid-cols-12 gap-1 sm:gap-3 lg:gap-4 items-center min-w-[340px] max-w-6xl mx-auto py-1 sm:py-3">
        
        <!-- KOLOM 1 (Cols 1-3): Sumber Energi (Solar PV Atas & Jaringan PLN Bawah) -->
        <div class="col-span-3 space-y-2 sm:space-y-4">
          
          <!-- Node 1: Solar Panel PV -->
          <div id="card-node-pv" class="bg-gradient-to-br from-[#121e42] to-[#0a1226] border-2 border-amber-500/50 rounded-xl sm:rounded-2xl p-2 sm:p-4 shadow-[0_0_20px_rgba(245,158,11,0.15)] transition-all duration-300">
            <div class="flex items-center justify-between mb-1">
              <span class="text-[9px] sm:text-xs font-bold text-amber-400 uppercase tracking-wider flex items-center gap-1">
                <span class="w-1.5 h-1.5 rounded-full bg-amber-400"></span>
                <span class="truncate">Solar PV</span>
              </span>
              <div class="p-1 sm:p-1.5 bg-amber-500/10 text-amber-400 rounded-lg border border-amber-500/20">
                <svg class="w-3.5 h-3.5 sm:w-5 sm:h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z" />
                </svg>
              </div>
            </div>

            <!-- Big kW Display -->
            <div class="text-sm sm:text-2xl font-black text-amber-400 tracking-tight leading-tight">
              <span id="val-pv-power-kw">0.00</span> <span class="text-[9px] sm:text-xs font-normal text-slate-400">kW</span>
            </div>

            <div class="mt-1 pt-1 border-t border-blue-500/20 grid grid-cols-2 text-[8px] sm:text-xs text-slate-400">
              <div class="truncate">≈ <b id="val-pv-volt" class="text-slate-200 font-mono">0.0 V</b></div>
              <div class="truncate text-right"><b id="val-pv-curr" class="text-slate-200 font-mono">0.0 A</b></div>
            </div>
          </div>

          <!-- Node 2: PLN Grid Input (Bawah) -->
          <div id="card-node-grid" class="bg-gradient-to-br from-[#121e42] to-[#0a1226] border-2 border-blue-500/50 rounded-xl sm:rounded-2xl p-2 sm:p-4 shadow-[0_0_20px_rgba(59,130,246,0.15)] transition-all duration-300">
            <div class="flex items-center justify-between mb-1">
              <span class="text-[9px] sm:text-xs font-bold text-blue-400 uppercase tracking-wider flex items-center gap-1">
                <span class="w-1.5 h-1.5 rounded-full bg-blue-400"></span>
                <span class="truncate">PLN Grid</span>
              </span>
              <div class="p-1 sm:p-1.5 bg-blue-500/10 text-blue-400 rounded-lg border border-blue-500/20">
                <svg class="w-3.5 h-3.5 sm:w-5 sm:h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7zM7 21h10"/>
                </svg>
              </div>
            </div>

            <!-- Big kW Display -->
            <div class="text-sm sm:text-2xl font-black text-blue-400 tracking-tight leading-tight">
              <span id="val-grid-power-kw">0.00</span> <span class="text-[9px] sm:text-xs font-normal text-slate-400">kW</span>
            </div>

            <div class="mt-1 pt-1 border-t border-blue-500/20 grid grid-cols-2 text-[8px] sm:text-xs text-slate-400">
              <div class="truncate"><b id="val-grid-volt" class="text-slate-200 font-mono">0.0 V</b></div>
              <div class="truncate text-right"><b id="val-grid-freq" class="text-slate-200 font-mono">0.0 Hz</b></div>
            </div>
          </div>

        </div>

        <!-- KOLOM 2 (Cols 4-5): Pipa Kiri (PV Lurus & PLN Melengkung Naik ke Inverter) -->
        <div class="col-span-1 sm:col-span-2 flex flex-col justify-between h-full py-1 sm:py-4 relative min-h-[190px] sm:min-h-[260px]">
          
          <!-- SVG Canvas Pipa Kiri -->
          <svg class="absolute inset-0 w-full h-full overflow-visible pointer-events-none" viewBox="0 0 100 240" preserveAspectRatio="none">
            <defs>
              <marker id="arrow-amber" viewBox="0 0 10 10" refX="6" refY="5" markerWidth="6" markerHeight="6" orient="auto">
                <path d="M 0 1 L 8 5 L 0 9 z" fill="#f59e0b"/>
              </marker>
              <marker id="arrow-blue" viewBox="0 0 10 10" refX="6" refY="5" markerWidth="6" markerHeight="6" orient="auto">
                <path d="M 0 1 L 8 5 L 0 9 z" fill="#3b82f6"/>
              </marker>
              <marker id="arrow-grey" viewBox="0 0 10 10" refX="6" refY="5" markerWidth="6" markerHeight="6" orient="auto">
                <path d="M 0 1 L 8 5 L 0 9 z" fill="#475569"/>
              </marker>
            </defs>

            <!-- 1. Pipa PV ➔ Inverter -->
            <line x1="0" y1="45" x2="92" y2="45" class="pipe-tube" vector-effect="non-scaling-stroke" />
            <line id="flow-pv-line" x1="0" y1="45" x2="92" y2="45" class="flow-line-static" marker-end="url(#arrow-grey)" vector-effect="non-scaling-stroke" />

            <!-- 2. Pipa PLN ➔ Inverter (Melengkung Naik ke Inverter) -->
            <path d="M 0,195 C 45,195 45,85 92,85" class="pipe-tube" fill="none" vector-effect="non-scaling-stroke" />
            <path id="flow-grid-line" d="M 0,195 C 45,195 45,85 92,85" class="flow-line-static" fill="none" marker-end="url(#arrow-grey)" vector-effect="non-scaling-stroke" />
          </svg>

          <!-- Badge PV Flow Info -->
          <div class="relative z-10 flex flex-col items-center justify-center -mt-1 sm:mt-1">
            <span id="flow-pv-kw" class="text-[7px] sm:text-xs font-mono font-bold text-amber-400 bg-[#091535]/95 px-1.5 py-0.5 rounded shadow-sm border border-amber-500/30">0.00 kW</span>
            <span id="flow-pv-status" class="hidden sm:inline-block text-[9px] px-1.5 py-0.2 rounded bg-slate-800 text-slate-400 mt-0.5 font-medium border border-slate-700">Malam / 0W</span>
          </div>

          <!-- Badge PLN Flow Info -->
          <div class="relative z-10 flex flex-col items-center justify-center mb-1 sm:mb-2">
            <span id="flow-grid-kw" class="text-[7px] sm:text-xs font-mono font-bold text-blue-400 bg-[#091535]/95 px-1.5 py-0.5 rounded shadow-sm border border-blue-500/30">0.00 kW</span>
            <span id="flow-grid-status" class="hidden sm:inline-block text-[9px] px-1.5 py-0.2 rounded bg-slate-800 text-slate-400 mt-0.5 font-medium border border-slate-700">Standby / 0V</span>
          </div>

        </div>

        <!-- KOLOM 3 (Cols 6-8): Pusat Hub Inverter & Baterai -->
        <div class="col-span-4 sm:col-span-3 space-y-1.5 sm:space-y-3 flex flex-col items-center">
          
          <!-- Node Pusat: Hybrid Inverter PowMr 6.5kW (Frosted Crisp Silver Card) -->
          <div id="card-node-inv" class="w-full bg-gradient-to-b from-[#f8fafc] to-[#cbd5e1] text-slate-900 border-2 border-white/80 rounded-xl sm:rounded-2xl p-2 sm:p-4 shadow-2xl text-center relative transition-all duration-300">
            
            <!-- Orange Wave AC Icon in Center Top -->
            <div class="w-8 h-8 sm:w-11 sm:h-11 mx-auto bg-amber-500/20 text-amber-600 rounded-xl flex items-center justify-center mb-1 border border-amber-500/40 shadow-sm">
              <svg class="w-5 h-5 sm:w-6 sm:h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M3 12c3-4 6-4 9 0s6 4 9 0"/>
              </svg>
            </div>
            
            <h3 class="font-extrabold text-slate-900 text-xs sm:text-base leading-tight">PowMr 6.5kW</h3>
            <p class="text-[8px] sm:text-[11px] text-slate-600 font-mono font-semibold">48V Hybrid</p>

            <!-- Status Suhu & Kipas Inverter Card Mini Bar -->
            <div class="mt-1.5 sm:mt-2.5 grid grid-cols-2 gap-1 text-[8px] sm:text-xs bg-white/80 p-1 sm:p-1.5 rounded-lg border border-slate-300">
              <div class="text-left flex items-center gap-1">
                <span>🌡️</span>
                <b id="val-inv-temp" class="text-emerald-700 font-mono">0.0°C</b>
              </div>
              <div class="text-right flex items-center justify-end gap-1">
                <span>🌀</span>
                <b id="val-fan-speed" class="text-cyan-700 font-mono">0%</b>
              </div>
            </div>
          </div>

          <!-- Pipa Aliran Vertikal: Inverter ◄══► Baterai 48V (Dua Arah) -->
          <div class="flex flex-col items-center justify-center w-full py-0.5 relative">
            <div id="badge-batt-flow" class="text-[7px] sm:text-[10px] font-bold px-2 py-0.5 rounded-full bg-slate-800 text-slate-400 border border-slate-700 transition-all z-10 shadow-sm">
              ⚡ Standby (0.0 A)
            </div>
            
            <svg class="w-6 h-6 sm:h-8 my-0.5 overflow-visible" viewBox="0 0 20 34">
              <defs>
                <marker id="arrow-green-down" viewBox="0 0 10 10" refX="5" refY="6" markerWidth="6" markerHeight="6" orient="auto">
                  <path d="M 1 0 L 5 8 L 9 0 z" fill="#10b981"/>
                </marker>
                <marker id="arrow-amber-up" viewBox="0 0 10 10" refX="5" refY="2" markerWidth="6" markerHeight="6" orient="auto-start-reverse">
                  <path d="M 1 8 L 5 0 L 9 8 z" fill="#f59e0b"/>
                </marker>
              </defs>
              <line x1="10" y1="0" x2="10" y2="32" class="pipe-tube" vector-effect="non-scaling-stroke" />
              <line id="flow-batt-line" x1="10" y1="0" x2="10" y2="32" class="flow-line-static" marker-end="url(#arrow-grey)" vector-effect="non-scaling-stroke" />
            </svg>
          </div>

          <!-- Node 3: Baterai 48V (Bawah) -->
          <div id="card-node-batt" class="w-full bg-gradient-to-br from-[#121e42] to-[#0a1226] border-2 border-emerald-500/50 rounded-xl sm:rounded-2xl p-2 sm:p-4 shadow-[0_0_20px_rgba(16,185,129,0.15)] transition-all duration-300">
            <div class="flex items-center justify-between mb-1">
              <span class="text-[9px] sm:text-xs font-bold text-emerald-400 uppercase tracking-wider flex items-center gap-1">
                <span class="w-1.5 h-1.5 rounded-full bg-emerald-400"></span>
                <span class="truncate">Baterai 48V</span>
              </span>
              <div class="p-1 sm:p-1.5 bg-emerald-500/10 text-emerald-400 rounded-lg border border-emerald-500/20">
                <svg class="w-3.5 h-3.5 sm:w-5 sm:h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                </svg>
              </div>
            </div>

            <!-- SOC % dan Status -->
            <div class="flex items-baseline justify-between">
              <div class="text-sm sm:text-2xl font-black text-emerald-400 tracking-tight leading-tight">
                <span id="val-batt-soc">0</span><span class="text-[10px] sm:text-sm font-bold">%</span>
              </div>
              <span id="val-batt-status-pill" class="text-[8px] sm:text-xs font-bold px-1.5 py-0.5 rounded bg-slate-800 text-slate-400 border border-slate-700">Standby</span>
            </div>

            <!-- Progress Bar SOC -->
            <div class="w-full bg-slate-800 rounded-full h-1.5 sm:h-2 my-1 sm:my-2 overflow-hidden border border-slate-700">
              <div id="bar-batt-soc" class="bg-gradient-to-r from-emerald-500 to-teal-400 h-full rounded-full transition-all duration-500" style="width: 0%"></div>
            </div>

            <div class="grid grid-cols-2 text-[8px] sm:text-xs text-slate-400 pt-0.5">
              <div class="truncate"><b id="val-batt-volt" class="text-slate-200 font-mono">0.0 V</b></div>
              <div class="truncate text-right"><b id="val-batt-watts" class="text-slate-200 font-mono">0 W</b></div>
            </div>
          </div>

        </div>

        <!-- KOLOM 4 (Col 9): Pipa Inverter ➔ Beban Rumah -->
        <div class="col-span-1 sm:col-span-1 flex flex-col items-center justify-center relative min-h-[190px] sm:min-h-[260px]">
          <svg class="absolute inset-0 w-full h-full overflow-visible pointer-events-none" viewBox="0 0 60 240" preserveAspectRatio="none">
            <defs>
              <marker id="arrow-cyan" viewBox="0 0 10 10" refX="6" refY="5" markerWidth="6" markerHeight="6" orient="auto">
                <path d="M 0 1 L 8 5 L 0 9 z" fill="#06b6d4"/>
              </marker>
            </defs>
            <!-- Pipa dari Inverter (Y=65) ke Beban Rumah (Y=65) -->
            <line x1="0" y1="65" x2="52" y2="65" class="pipe-tube" vector-effect="non-scaling-stroke" />
            <line id="flow-load-line" x1="0" y1="65" x2="52" y2="65" class="flow-line-static" marker-end="url(#arrow-grey)" vector-effect="non-scaling-stroke" />
          </svg>

          <!-- Badge Beban pada Pipa -->
          <div class="relative z-10 flex flex-col items-center justify-center -mt-16 sm:-mt-24">
            <span id="flow-load-kw" class="text-[7px] sm:text-xs font-mono font-bold text-cyan-400 bg-[#091535]/95 px-1.5 py-0.5 rounded shadow-sm border border-cyan-500/30">0.00 kW</span>
            <span id="flow-load-status" class="hidden sm:inline-block text-[9px] px-1.5 py-0.2 rounded bg-slate-800 text-slate-400 mt-0.5 font-medium border border-slate-700">Nol Beban</span>
          </div>
        </div>

        <!-- KOLOM 5 (Cols 10-12): Beban Rumah (AC Load) -->
        <div class="col-span-3 sm:col-span-3">
          
          <!-- Node 4: Beban Rumah / Home Load -->
          <div id="card-node-load" class="bg-gradient-to-br from-[#121e42] to-[#0a1226] border-2 border-cyan-500/50 rounded-xl sm:rounded-2xl p-2 sm:p-4 shadow-[0_0_20px_rgba(6,182,212,0.15)] transition-all duration-300">
            <div class="flex items-center justify-between mb-1">
              <span class="text-[9px] sm:text-xs font-bold text-cyan-400 uppercase tracking-wider flex items-center gap-1">
                <span class="w-1.5 h-1.5 rounded-full bg-cyan-400"></span>
                <span class="truncate">Beban Rumah</span>
              </span>
              <div class="p-1 sm:p-1.5 bg-cyan-500/10 text-cyan-400 rounded-lg border border-cyan-500/20">
                <svg class="w-3.5 h-3.5 sm:w-5 sm:h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                </svg>
              </div>
            </div>
            
            <!-- Big kW Display -->
            <div class="text-sm sm:text-2xl font-black text-cyan-400 tracking-tight leading-tight">
              <span id="val-load-power-kw">0.00</span> <span class="text-[9px] sm:text-xs font-normal text-slate-400">kW</span>
            </div>

            <div class="mt-1 pt-1 border-t border-blue-500/20 grid grid-cols-2 text-[8px] sm:text-xs text-slate-400">
              <div class="truncate">≈ <b id="val-ac-volt" class="text-slate-200 font-mono">0.0 V</b></div>
              <div class="truncate text-right"><b id="val-load-curr" class="text-slate-200 font-mono">0.0 A</b></div>
            </div>
          </div>

        </div>

      </div>
    </div>

  </div>

  <!-- ======================================================== -->
  <!-- 3. 4 ENERGY PRODUCTION METRIC CARDS (SPARKLINES)         -->
  <!-- ======================================================== -->
  <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4">
    
    <!-- Card 1: Energi Hari Ini -->
    <div class="glass-panel glass-panel-hover rounded-2xl p-3.5 sm:p-5 relative overflow-hidden">
      <div class="flex items-center justify-between text-slate-400 mb-1.5">
        <div class="flex items-center gap-2">
          <div class="p-1.5 rounded-lg bg-amber-500/10 text-amber-400 border border-amber-500/20">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"/>
            </svg>
          </div>
          <span class="text-xs font-semibold text-slate-300">Energi Hari Ini</span>
        </div>
        <span class="px-2 py-0.5 rounded-full bg-emerald-500/15 text-emerald-400 border border-emerald-500/30 text-[10px] font-bold">↑ +12%</span>
      </div>

      <div class="text-xl sm:text-3xl font-black text-amber-400 tracking-tight my-1">
        <span id="val-daily-kwh">0.00</span> <span class="text-xs sm:text-sm font-normal text-slate-400">kWh</span>
      </div>

      <!-- Mini Sparkline Wave Amber -->
      <div class="w-full h-8 sm:h-10 mt-2">
        <svg class="w-full h-full overflow-visible" viewBox="0 0 100 30" preserveAspectRatio="none">
          <defs>
            <linearGradient id="spark-amber" x1="0" y1="0" x2="0" y2="1">
              <stop stop-color="#f59e0b" stop-opacity="0.35"/>
              <stop offset="1" stop-color="#f59e0b" stop-opacity="0.0"/>
            </linearGradient>
          </defs>
          <path d="M 0,25 Q 20,28 35,18 T 70,12 T 100,5 L 100,30 L 0,30 Z" fill="url(#spark-amber)"/>
          <path d="M 0,25 Q 20,28 35,18 T 70,12 T 100,5" fill="none" stroke="#f59e0b" stroke-width="2.5" stroke-linecap="round"/>
        </svg>
      </div>
    </div>

    <!-- Card 2: Energi Bulan Ini -->
    <div class="glass-panel glass-panel-hover rounded-2xl p-3.5 sm:p-5 relative overflow-hidden">
      <div class="flex items-center justify-between text-slate-400 mb-1.5">
        <div class="flex items-center gap-2">
          <div class="p-1.5 rounded-lg bg-blue-500/10 text-blue-400 border border-blue-500/20">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
            </svg>
          </div>
          <span class="text-xs font-semibold text-slate-300">Energi Bulan Ini</span>
        </div>
        <span class="px-2 py-0.5 rounded-full bg-emerald-500/15 text-emerald-400 border border-emerald-500/30 text-[10px] font-bold">↑ +8%</span>
      </div>

      <div class="text-xl sm:text-3xl font-black text-blue-400 tracking-tight my-1">
        <span id="val-monthly-kwh">0.00</span> <span class="text-xs sm:text-sm font-normal text-slate-400">kWh</span>
      </div>

      <!-- Mini Sparkline Wave Blue -->
      <div class="w-full h-8 sm:h-10 mt-2">
        <svg class="w-full h-full overflow-visible" viewBox="0 0 100 30" preserveAspectRatio="none">
          <defs>
            <linearGradient id="spark-blue" x1="0" y1="0" x2="0" y2="1">
              <stop stop-color="#3b82f6" stop-opacity="0.35"/>
              <stop offset="1" stop-color="#3b82f6" stop-opacity="0.0"/>
            </linearGradient>
          </defs>
          <path d="M 0,22 Q 25,28 45,15 T 80,18 T 100,8 L 100,30 L 0,30 Z" fill="url(#spark-blue)"/>
          <path d="M 0,22 Q 25,28 45,15 T 80,18 T 100,8" fill="none" stroke="#3b82f6" stroke-width="2.5" stroke-linecap="round"/>
        </svg>
      </div>
    </div>

    <!-- Card 3: Energi Tahun Ini -->
    <div class="glass-panel glass-panel-hover rounded-2xl p-3.5 sm:p-5 relative overflow-hidden">
      <div class="flex items-center justify-between text-slate-400 mb-1.5">
        <div class="flex items-center gap-2">
          <div class="p-1.5 rounded-lg bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
            </svg>
          </div>
          <span class="text-xs font-semibold text-slate-300">Energi Tahun Ini</span>
        </div>
        <span class="px-2 py-0.5 rounded-full bg-emerald-500/15 text-emerald-400 border border-emerald-500/30 text-[10px] font-bold">↑ +15%</span>
      </div>

      <div class="text-xl sm:text-3xl font-black text-emerald-400 tracking-tight my-1">
        <span id="val-yearly-kwh">0.00</span> <span class="text-xs sm:text-sm font-normal text-slate-400">kWh</span>
      </div>

      <!-- Mini Sparkline Wave Green -->
      <div class="w-full h-8 sm:h-10 mt-2">
        <svg class="w-full h-full overflow-visible" viewBox="0 0 100 30" preserveAspectRatio="none">
          <defs>
            <linearGradient id="spark-green" x1="0" y1="0" x2="0" y2="1">
              <stop stop-color="#10b981" stop-opacity="0.35"/>
              <stop offset="1" stop-color="#10b981" stop-opacity="0.0"/>
            </linearGradient>
          </defs>
          <path d="M 0,26 Q 20,22 40,16 T 75,20 T 100,6 L 100,30 L 0,30 Z" fill="url(#spark-green)"/>
          <path d="M 0,26 Q 20,22 40,16 T 75,20 T 100,6" fill="none" stroke="#10b981" stroke-width="2.5" stroke-linecap="round"/>
        </svg>
      </div>
    </div>

    <!-- Card 4: Total Seumur Hidup -->
    <div class="glass-panel glass-panel-hover rounded-2xl p-3.5 sm:p-5 relative overflow-hidden">
      <div class="flex items-center justify-between text-slate-400 mb-1.5">
        <div class="flex items-center gap-2">
          <div class="p-1.5 rounded-lg bg-purple-500/10 text-purple-400 border border-purple-500/20">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
            </svg>
          </div>
          <span class="text-xs font-semibold text-slate-300">Total Seumur Hidup</span>
        </div>
      </div>

      <div class="text-xl sm:text-3xl font-black text-purple-400 tracking-tight my-1">
        <span id="val-total-kwh">0.00</span> <span class="text-xs sm:text-sm font-normal text-slate-400">kWh</span>
      </div>

      <!-- Mini Sparkline Wave Purple -->
      <div class="w-full h-8 sm:h-10 mt-2">
        <svg class="w-full h-full overflow-visible" viewBox="0 0 100 30" preserveAspectRatio="none">
          <defs>
            <linearGradient id="spark-purple" x1="0" y1="0" x2="0" y2="1">
              <stop stop-color="#a855f7" stop-opacity="0.35"/>
              <stop offset="1" stop-color="#a855f7" stop-opacity="0.0"/>
            </linearGradient>
          </defs>
          <path d="M 0,24 Q 30,12 55,22 T 85,10 T 100,4 L 100,30 L 0,30 Z" fill="url(#spark-purple)"/>
          <path d="M 0,24 Q 30,12 55,22 T 85,10 T 100,4" fill="none" stroke="#a855f7" stroke-width="2.5" stroke-linecap="round"/>
        </svg>
      </div>
    </div>

  </div>

  <!-- ======================================================== -->
  <!-- 4. BOTTOM TWO CARDS (SUHU/KIPAS & INFORMASI SISTEM)      -->
  <!-- ======================================================== -->
  <div class="grid grid-cols-1 md:grid-cols-2 gap-4 sm:gap-6">
    
    <!-- Left Card: Suhu & Kecepatan Kipas -->
    <div class="glass-panel rounded-3xl p-4 sm:p-6 relative overflow-hidden">
      <!-- Watermark Fan Background Silhouette -->
      <div class="absolute -right-6 -bottom-6 w-44 h-44 text-slate-800/30 pointer-events-none">
        <svg class="w-full h-full fan-spinning opacity-20" fill="currentColor" viewBox="0 0 24 24">
          <path d="M12 2a10 10 0 1010 10A10 10 0 0012 2zm1 3.1a4.9 4.9 0 013.9 3.9H13zm-2 0v3.9H7.1A4.9 4.9 0 0111 5.1zM5.1 11H9v2H5.1A4.9 4.9 0 015.1 11zm2 3.9H11v3.9a4.9 4.9 0 01-3.9-3.9zm3.9 3.9V15h3.9a4.9 4.9 0 01-3.9 3.9zm3.9-5.9h3.9a4.9 4.9 0 010 2h-3.9z"/>
        </svg>
      </div>

      <!-- Header Card -->
      <div class="flex items-center gap-2 mb-4">
        <div class="p-1.5 rounded-lg bg-blue-500/10 text-blue-400 border border-blue-500/20">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
          </svg>
        </div>
        <h3 class="text-sm sm:text-base font-bold text-white">Suhu & Kecepatan Kipas</h3>
      </div>

      <!-- Two Large Visual Gauges -->
      <div class="grid grid-cols-2 gap-4 relative z-10">
        
        <!-- Gauge 1: HTEMP (Suhu Inverter) -->
        <div class="bg-[#091535]/80 border border-amber-500/30 rounded-2xl p-4 flex flex-col justify-between">
          <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-full bg-amber-500/20 border border-amber-500/40 text-amber-400 flex items-center justify-center flex-shrink-0 shadow-[0_0_12px_rgba(245,158,11,0.3)]">
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
              </svg>
            </div>
            <div>
              <span class="text-[11px] font-semibold text-slate-400 uppercase tracking-wider block">HTEMP</span>
              <b id="val-htemp" class="text-xl sm:text-2xl font-black text-amber-400 font-mono">0.0°C</b>
            </div>
          </div>

          <!-- Progress Bar Orange -->
          <div class="w-full bg-slate-800 rounded-full h-2 mt-3 overflow-hidden border border-slate-700">
            <div id="bar-htemp" class="bg-gradient-to-r from-amber-500 to-orange-400 h-full rounded-full transition-all duration-500" style="width: 48%"></div>
          </div>
        </div>

        <!-- Gauge 2: Kecepatan Kipas Pendingin -->
        <div class="bg-[#091535]/80 border border-cyan-500/30 rounded-2xl p-4 flex flex-col justify-between">
          <div class="flex items-center gap-3">
            <div id="fan-icon-circle" class="w-10 h-10 rounded-full bg-cyan-500/20 border border-cyan-500/40 text-cyan-400 flex items-center justify-center flex-shrink-0 shadow-[0_0_12px_rgba(6,182,212,0.3)]">
              <svg class="w-5 h-5 fan-spinning" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
              </svg>
            </div>
            <div>
              <span class="text-[11px] font-semibold text-slate-400 uppercase tracking-wider block truncate">Kecepatan Kipas</span>
              <b id="val-fan-pct" class="text-xl sm:text-2xl font-black text-cyan-400 font-mono">0%</b>
            </div>
          </div>

          <!-- Progress Bar Cyan -->
          <div class="w-full bg-slate-800 rounded-full h-2 mt-3 overflow-hidden border border-slate-700">
            <div id="bar-fan-pct" class="bg-gradient-to-r from-cyan-500 to-blue-400 h-full rounded-full transition-all duration-500" style="width: 30%"></div>
          </div>
        </div>

      </div>

      <!-- Detail Sub-Sensor -->
      <div class="mt-4 pt-3 border-t border-blue-500/10 grid grid-cols-4 gap-2 text-center text-[10px] text-slate-400">
        <div>
          <span class="block text-slate-500">Heatsink</span>
          <b id="val-temp-core" class="text-slate-200 font-mono">0.0°C</b>
        </div>
        <div>
          <span class="block text-slate-500">Boost</span>
          <b id="val-temp-boost" class="text-slate-200 font-mono">0.0°C</b>
        </div>
        <div>
          <span class="block text-slate-500">Trafo</span>
          <b id="val-temp-trafo" class="text-slate-200 font-mono">0.0°C</b>
        </div>
        <div>
          <span class="block text-slate-500">PV Heat</span>
          <b id="val-temp-pv" class="text-slate-200 font-mono">0.0°C</b>
        </div>
      </div>

    </div>

    <!-- Right Card: Informasi Sistem & 3D Glowing Battery Hologram -->
    <div class="glass-panel rounded-3xl p-4 sm:p-6 relative overflow-hidden flex flex-col justify-between">
      
      <!-- Header Card -->
      <div class="flex items-center gap-2 mb-3">
        <div class="p-1.5 rounded-lg bg-cyan-500/10 text-cyan-400 border border-cyan-500/20">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
          </svg>
        </div>
        <h3 class="text-sm sm:text-base font-bold text-white">Informasi Sistem</h3>
      </div>

      <!-- Content Split: Telemetry Specs Left & 3D Glowing Battery Hologram Right -->
      <div class="grid grid-cols-12 gap-3 items-center">
        
        <!-- Left 7 Cols: Telemetry Specs List -->
        <div class="col-span-7 space-y-2 text-xs">
          <div class="flex justify-between items-center py-1 border-b border-blue-500/10">
            <span class="text-slate-400">Tegangan Baterai</span>
            <b id="info-batt-volt" class="text-slate-200 font-mono">0.0 V</b>
          </div>
          <div class="flex justify-between items-center py-1 border-b border-blue-500/10">
            <span class="text-slate-400">Daya Baterai</span>
            <b id="info-batt-watts" class="text-slate-200 font-mono">0 W</b>
          </div>
          <div class="flex justify-between items-center py-1 border-b border-blue-500/10">
            <span class="text-slate-400">Daya Beban</span>
            <b id="info-load-power" class="text-slate-200 font-mono">0.00 kW</b>
          </div>
          <div class="flex justify-between items-center py-1 border-b border-blue-500/10">
            <span class="text-slate-400">Tegangan AC Keluar</span>
            <b id="info-ac-volt" class="text-slate-200 font-mono">0.0 V</b>
          </div>
          <div class="flex justify-between items-center py-1">
            <span class="text-slate-400">Frekuensi AC</span>
            <b id="info-ac-freq" class="text-slate-200 font-mono">0.0 Hz</b>
          </div>
        </div>

        <!-- Right 5 Cols: 3D Glowing Green Battery Hologram Pedestal Visual -->
        <div class="col-span-5 flex flex-col items-center justify-center relative py-2">
          
          <!-- Hologram SVG Graphic -->
          <div class="relative w-28 h-32 flex items-center justify-center">
            
            <!-- Neon Glowing Floor Rings -->
            <svg class="absolute inset-0 w-full h-full overflow-visible drop-shadow-[0_0_15px_rgba(16,185,129,0.5)]" viewBox="0 0 100 120" fill="none">
              <defs>
                <!-- Battery Gradient -->
                <linearGradient id="holo-batt" x1="0" y1="20" x2="100" y2="90" gradientUnits="userSpaceOnUse">
                  <stop stop-color="#10b981" stop-opacity="0.85"/>
                  <stop offset="0.5" stop-color="#059669" stop-opacity="0.6"/>
                  <stop offset="1" stop-color="#064e3b" stop-opacity="0.9"/>
                </linearGradient>
                <radialGradient id="ring-glow" cx="50" cy="100" r="45" gradientUnits="userSpaceOnUse">
                  <stop stop-color="#10b981" stop-opacity="0.6"/>
                  <stop offset="1" stop-color="#10b981" stop-opacity="0"/>
                </radialGradient>
              </defs>

              <!-- Radial Aura Pedestal Glow -->
              <ellipse cx="50" cy="102" rx="42" ry="14" fill="url(#ring-glow)"/>
              
              <!-- Outer Glowing Ring -->
              <ellipse cx="50" cy="102" rx="38" ry="10" stroke="#10b981" stroke-width="1.8" stroke-dasharray="3, 3" opacity="0.8"/>
              <!-- Inner Glowing Ring -->
              <ellipse cx="50" cy="102" rx="26" ry="6.5" stroke="#34d399" stroke-width="2"/>

              <!-- Battery Top Cap -->
              <rect x="42" y="18" width="16" height="5" rx="2" fill="#34d399" stroke="#10b981" stroke-width="1"/>

              <!-- Battery Main Cylindrical Cell -->
              <rect x="26" y="23" width="48" height="70" rx="10" fill="url(#holo-batt)" stroke="#34d399" stroke-width="2"/>

              <!-- Reflection highlight strip on left -->
              <rect x="30" y="27" width="5" height="62" rx="2.5" fill="#ffffff" opacity="0.45"/>

              <!-- Central Glowing Lightning Bolt -->
              <path d="M53 35 L40 58 L48 58 L45 81 L60 55 L52 55 Z" fill="#ffffff" filter="drop-shadow(0 0 6px #6ee7b7)"/>
            </svg>

          </div>

          <span class="text-[10px] text-emerald-400 font-bold tracking-wider uppercase mt-1">48V Battery Hub</span>
        </div>

      </div>

    </div>

  </div>

</div>

<!-- ======================================================== -->
<!-- SCRIPT POLLING REALTIME CONTINUOUS & ESP32 DETECTOR      -->
<!-- ======================================================== -->
<script>
  let lastTimestamp = null;
  let currentDiffSec = null;
  const ESP32_TIMEOUT_SEC = 25; // Jika tidak ada data dalam 25 detik -> ESP32 OFFLINE

  // Update Status Header & Detektor ESP32
  function updateMcuStatus(diffSec) {
    const offlineBanner = document.getElementById('mcu-offline-banner');
    const offlineMsg = document.getElementById('mcu-offline-msg');
    const headerPill = document.getElementById('header-status-pill');
    const headerPing = document.getElementById('header-ping-dot');
    const headerSolid = document.getElementById('header-solid-dot');
    const headerText = document.getElementById('header-status-text');
    const headerEsp32 = document.getElementById('header-esp32-text');
    const mcuHeartbeat = document.getElementById('mcu-heartbeat-pill');

    if (diffSec !== null && diffSec <= ESP32_TIMEOUT_SEC) {
      // ONLINE
      if (headerPill) {
        headerPill.className = "flex items-center gap-2 px-3 py-1.5 rounded-full bg-emerald-500/10 border border-emerald-500/30 text-emerald-400 font-semibold shadow-sm";
      }
      if (headerPing) headerPing.className = "animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75";
      if (headerSolid) headerSolid.className = "relative inline-flex rounded-full h-2.5 w-2.5 bg-emerald-500";
      if (headerText) headerText.innerText = "Online";
      if (headerEsp32) headerEsp32.innerText = "ESP32: Terhubung";

      if (mcuHeartbeat) {
        mcuHeartbeat.innerText = "Heartbeat: " + diffSec + " dtk lalu";
        mcuHeartbeat.className = "px-3 py-1 rounded-full bg-emerald-500/10 text-emerald-400 border border-emerald-500/30 font-mono text-xs";
      }

      if (offlineBanner) offlineBanner.classList.add('hidden');

    } else {
      // OFFLINE
      if (headerPill) {
        headerPill.className = "flex items-center gap-2 px-3 py-1.5 rounded-full bg-rose-500/10 border border-rose-500/30 text-rose-400 font-semibold shadow-sm";
      }
      if (headerPing) headerPing.className = "hidden";
      if (headerSolid) headerSolid.className = "relative inline-flex rounded-full h-2.5 w-2.5 bg-rose-500";
      if (headerText) headerText.innerText = "Offline";
      if (headerEsp32) headerEsp32.innerText = "ESP32: Terputus";

      let timeText = "Belum Ada Data";
      if (diffSec !== null) {
        const min = Math.floor(diffSec / 60);
        timeText = min > 0 ? min + " menit lalu" : diffSec + " detik lalu";
      }

      if (mcuHeartbeat) {
        mcuHeartbeat.innerText = "Offline (" + timeText + ")";
        mcuHeartbeat.className = "px-3 py-1 rounded-full bg-rose-500/10 text-rose-400 border border-rose-500/30 font-mono text-xs";
      }

      if (offlineBanner) {
        offlineBanner.classList.remove('hidden');
        if (offlineMsg) {
          offlineMsg.innerText = "Paket data telemetri terakhir diterima " + timeText + ". Garis animasi beralih ke mode standby hingga sinyal ESP32 terhubung.";
        }
      }
    }
  }

  // Setel Garis ke Statik saat Offline / Nol
  function setAllLinesToStatic() {
    // 1. PV Line
    const flowPvLine = document.getElementById('flow-pv-line');
    if (flowPvLine) {
      flowPvLine.setAttribute('class', 'flow-line-static');
      flowPvLine.setAttribute('marker-end', 'url(#arrow-grey)');
    }
    const flowPvStatus = document.getElementById('flow-pv-status');
    if (flowPvStatus) flowPvStatus.innerText = "Malam / 0W";
    const cardPv = document.getElementById('card-node-pv');
    if (cardPv) cardPv.classList.remove('pulse-glow-amber');

    // 2. PLN Line
    const flowGridLine = document.getElementById('flow-grid-line');
    if (flowGridLine) {
      flowGridLine.setAttribute('class', 'flow-line-static');
      flowGridLine.setAttribute('marker-end', 'url(#arrow-grey)');
    }
    const flowGridStatus = document.getElementById('flow-grid-status');
    if (flowGridStatus) flowGridStatus.innerText = "Standby / 0V";
    const cardGrid = document.getElementById('card-node-grid');
    if (cardGrid) cardGrid.classList.remove('pulse-glow-blue');

    // 3. Batt Line
    const flowBattLine = document.getElementById('flow-batt-line');
    if (flowBattLine) {
      flowBattLine.setAttribute('class', 'flow-line-static');
      flowBattLine.setAttribute('marker-end', 'url(#arrow-grey)');
    }
    const battFlowBadge = document.getElementById('badge-batt-flow');
    if (battFlowBadge) battFlowBadge.innerText = "⚡ Standby (0.0 A)";
    const battPill = document.getElementById('val-batt-status-pill');
    if (battPill) battPill.innerText = "Standby";
    const cardBatt = document.getElementById('card-node-batt');
    if (cardBatt) cardBatt.classList.remove('pulse-glow-emerald', 'pulse-glow-amber');

    // 4. Load Line
    const flowLoadLine = document.getElementById('flow-load-line');
    if (flowLoadLine) {
      flowLoadLine.setAttribute('class', 'flow-line-static');
      flowLoadLine.setAttribute('marker-end', 'url(#arrow-grey)');
    }
    const flowLoadStatus = document.getElementById('flow-load-status');
    if (flowLoadStatus) flowLoadStatus.innerText = "Nol Beban";
    const cardLoad = document.getElementById('card-node-load');
    if (cardLoad) cardLoad.classList.remove('pulse-glow-cyan');
  }

  // Polling AJAX Continuous ke api.php?action=latest
  async function fetchLatestData() {
    try {
      const response = await fetch('api.php?action=latest&t=' + Date.now());
      const res = await response.json();

      if (res.status === 'success' && res.data) {
        const d = res.data;
        lastTimestamp = new Date(d.created_at).getTime();

        currentDiffSec = (res.diff_seconds !== undefined && res.diff_seconds !== null)
          ? parseInt(res.diff_seconds)
          : Math.max(0, Math.floor((Date.now() - lastTimestamp) / 1000));

        updateMcuStatus(currentDiffSec);
        const isMcuOnline = (currentDiffSec <= ESP32_TIMEOUT_SEC);

        document.getElementById('mode-badge').innerText = d.operating_mode || "Normal";
        document.getElementById('last-update').innerText = d.created_at;

        // 1. Solar PV Logic
        const pvW = Math.round(d.pv_power || 0);
        const pvV = parseFloat(d.pv_voltage || 0).toFixed(1);
        const pvA = parseFloat(d.pv_current || 0).toFixed(1);
        const pvKW = (pvW / 1000).toFixed(2);

        document.getElementById('val-pv-power-kw').innerText = pvKW;
        document.getElementById('val-pv-volt').innerText = pvV + " V";
        document.getElementById('val-pv-curr').innerText = pvA + " A";
        document.getElementById('flow-pv-kw').innerText = pvKW + " kW";

        const flowPvLine = document.getElementById('flow-pv-line');
        const flowPvStatus = document.getElementById('flow-pv-status');
        const cardPv = document.getElementById('card-node-pv');

        if (isMcuOnline && pvW > 10) {
          flowPvLine.setAttribute('class', 'flow-pv-active');
          flowPvLine.setAttribute('marker-end', 'url(#arrow-amber)');
          flowPvStatus.innerText = "Aktif (" + pvW + "W)";
          flowPvStatus.className = "hidden sm:inline-block text-[9px] px-1.5 py-0.2 rounded bg-amber-500/20 text-amber-400 font-bold border border-amber-500/30 mt-0.5";
          cardPv.classList.add('pulse-glow-amber');
        } else {
          flowPvLine.setAttribute('class', 'flow-line-static');
          flowPvLine.setAttribute('marker-end', 'url(#arrow-grey)');
          flowPvStatus.innerText = "Malam / 0W";
          flowPvStatus.className = "hidden sm:inline-block text-[9px] px-1.5 py-0.2 rounded bg-slate-800 text-slate-400 mt-0.5 font-medium border border-slate-700";
          cardPv.classList.remove('pulse-glow-amber');
        }

        // 2. PLN Grid Logic
        let gridW = Math.round(d.mains_power || 0);
        const gridV = parseFloat(d.ac_input_voltage || 0).toFixed(1);
        const gridHz = parseFloat(d.mains_freq || 0).toFixed(1);
        const opMode = (d.operating_mode || "").toLowerCase();
        const chgCurrent = parseFloat(d.charging_current || 0);
        const battVoltVal = parseFloat(d.battery_voltage || 48.0);
        let loadW = Math.round(d.output_active_power || 0);
        let loadPct = Math.round(d.output_load_percent || 0);
        const acV = parseFloat(d.ac_output_voltage || 0).toFixed(1);
        const acHz = parseFloat(d.ac_output_freq || 0).toFixed(1);

        if (loadW <= 0 && parseFloat(d.output_apparent_power || 0) > 0) {
          loadW = Math.round(parseFloat(d.output_apparent_power) * 0.95);
        } else if (loadW <= 0 && loadPct > 0) {
          loadW = Math.round((loadPct * 6500) / 100);
        }

        const isPlnConnected = (parseFloat(gridV) > 150) || opMode.includes('line') || opMode.includes('bypass') || opMode.includes('grid') || opMode.includes('utility');
        const battChargeWatts = (chgCurrent > 0.5) ? (battVoltVal * chgCurrent) : 0;
        const gridForBatt = Math.max(0, battChargeWatts - pvW) * 1.10;

        if (isPlnConnected && loadW <= 0 && gridW > (gridForBatt + 15)) {
          loadW = Math.round(gridW - gridForBatt);
        }
        const expectedMinGridW = Math.round(loadW + gridForBatt);
        if (isPlnConnected && (gridW <= 0 || gridW < expectedMinGridW)) {
          gridW = expectedMinGridW;
        }

        const gridKW = (gridW / 1000).toFixed(2);
        document.getElementById('val-grid-power-kw').innerText = gridKW;
        document.getElementById('val-grid-volt').innerText = gridV + " V";
        document.getElementById('val-grid-freq').innerText = gridHz + " Hz";
        document.getElementById('flow-grid-kw').innerText = gridKW + " kW";

        const flowGridLine = document.getElementById('flow-grid-line');
        const flowGridStatus = document.getElementById('flow-grid-status');
        const cardGrid = document.getElementById('card-node-grid');
        const isPlnActive = (gridW > 10) || isPlnConnected;

        if (isMcuOnline && isPlnActive) {
          flowGridLine.setAttribute('class', 'flow-grid-active');
          flowGridLine.setAttribute('marker-end', 'url(#arrow-blue)');
          flowGridStatus.innerText = "Menyuplai (" + (gridW > 0 ? gridW + "W" : "Bypass") + ")";
          flowGridStatus.className = "hidden sm:inline-block text-[9px] px-1.5 py-0.2 rounded bg-blue-500/20 text-blue-400 font-bold border border-blue-500/30 mt-0.5";
          cardGrid.classList.add('pulse-glow-blue');
        } else {
          flowGridLine.setAttribute('class', 'flow-line-static');
          flowGridLine.setAttribute('marker-end', 'url(#arrow-grey)');
          flowGridStatus.innerText = parseFloat(gridV) > 100 ? "Standby (" + gridV + "V)" : "Terputus / 0V";
          flowGridStatus.className = "hidden sm:inline-block text-[9px] px-1.5 py-0.2 rounded bg-slate-800 text-slate-400 mt-0.5 font-medium border border-slate-700";
          cardGrid.classList.remove('pulse-glow-blue');
        }

        // 3. Beban Rumah Logic
        const isLineMode = opMode.includes('line') || opMode.includes('bypass') || opMode.includes('mains');
        const loadKW = (loadW / 1000).toFixed(2);
        const loadAmp = (loadW / (parseFloat(acV) > 0 ? parseFloat(acV) : 220)).toFixed(1);

        document.getElementById('val-load-power-kw').innerText = loadKW;
        document.getElementById('val-ac-volt').innerText = acV + " V";
        document.getElementById('val-load-curr').innerText = loadAmp + " A";
        document.getElementById('flow-load-kw').innerText = loadKW + " kW";

        const flowLoadLine = document.getElementById('flow-load-line');
        const flowLoadStatus = document.getElementById('flow-load-status');
        const cardLoad = document.getElementById('card-node-load');

        if (isMcuOnline && loadW > 10) {
          flowLoadLine.setAttribute('class', 'flow-load-active');
          flowLoadLine.setAttribute('marker-end', 'url(#arrow-cyan)');
          flowLoadStatus.innerText = "Menyuplai (" + loadW + "W)";
          flowLoadStatus.className = "hidden sm:inline-block text-[9px] px-1.5 py-0.2 rounded bg-cyan-500/20 text-cyan-400 font-bold border border-cyan-500/30 mt-0.5";
          cardLoad.classList.add('pulse-glow-cyan');
        } else if (isMcuOnline && isLineMode && parseFloat(acV) > 150) {
          flowLoadLine.setAttribute('class', 'flow-load-active');
          flowLoadLine.setAttribute('marker-end', 'url(#arrow-cyan)');
          flowLoadStatus.innerText = "Bypass PLN (" + acV + "V)";
          flowLoadStatus.className = "hidden sm:inline-block text-[9px] px-1.5 py-0.2 rounded bg-cyan-500/20 text-cyan-400 font-bold border border-cyan-500/30 mt-0.5";
          cardLoad.classList.add('pulse-glow-cyan');
        } else {
          flowLoadLine.setAttribute('class', 'flow-line-static');
          flowLoadLine.setAttribute('marker-end', 'url(#arrow-grey)');
          flowLoadStatus.innerText = "Nol Beban";
          flowLoadStatus.className = "hidden sm:inline-block text-[9px] px-1.5 py-0.2 rounded bg-slate-800 text-slate-400 mt-0.5 font-medium border border-slate-700";
          cardLoad.classList.remove('pulse-glow-cyan');
        }

        // 4. Baterai 48V Logic
        const soc = Math.round(d.battery_capacity || 0);
        const battV = parseFloat(d.battery_voltage || 0).toFixed(1);
        const disCurrent = parseFloat(d.discharge_current || 0);
        const battWatts = Math.round(parseFloat(battV) * Math.max(chgCurrent, disCurrent));

        document.getElementById('val-batt-soc').innerText = soc;
        document.getElementById('bar-batt-soc').style.width = Math.min(100, Math.max(0, soc)) + "%";
        document.getElementById('val-batt-volt').innerText = battV + " V";
        document.getElementById('val-batt-watts').innerText = battWatts + " W";

        const battLine = document.getElementById('flow-batt-line');
        const battFlowBadge = document.getElementById('badge-batt-flow');
        const battPill = document.getElementById('val-batt-status-pill');
        const cardBatt = document.getElementById('card-node-batt');

        if (isMcuOnline && (chgCurrent > 0.5 || disCurrent > 0.5)) {
          if (chgCurrent > 0.5) {
            battPill.innerText = "Charging";
            battPill.className = "text-[8px] sm:text-xs font-bold px-1.5 py-0.5 rounded bg-emerald-500/20 text-emerald-400 border border-emerald-500/30";
            battFlowBadge.innerText = "🟢 Mengisi (+" + chgCurrent.toFixed(1) + " A)";
            battFlowBadge.className = "text-[7px] sm:text-[10px] font-bold px-2 py-0.5 rounded-full bg-emerald-500/20 text-emerald-400 border border-emerald-500/30 transition-all z-10 shadow-sm";
            if (battLine) {
              battLine.setAttribute('class', 'flow-batt-charge');
              battLine.setAttribute('marker-end', 'url(#arrow-green-down)');
            }
            cardBatt.classList.add('pulse-glow-emerald');
            cardBatt.classList.remove('pulse-glow-amber');
          } else {
            battPill.innerText = "Discharge";
            battPill.className = "text-[8px] sm:text-xs font-bold px-1.5 py-0.5 rounded bg-amber-500/20 text-amber-400 border border-amber-500/30";
            battFlowBadge.innerText = "🟠 Mengosongkan (-" + disCurrent.toFixed(1) + " A)";
            battFlowBadge.className = "text-[7px] sm:text-[10px] font-bold px-2 py-0.5 rounded-full bg-amber-500/20 text-amber-400 border border-amber-500/30 transition-all z-10 shadow-sm";
            if (battLine) {
              battLine.setAttribute('class', 'flow-batt-discharge');
              battLine.setAttribute('marker-end', 'url(#arrow-amber-up)');
            }
            cardBatt.classList.add('pulse-glow-amber');
            cardBatt.classList.remove('pulse-glow-emerald');
          }
        } else {
          battPill.innerText = "Standby";
          battPill.className = "text-[8px] sm:text-xs font-bold px-1.5 py-0.5 rounded bg-slate-800 text-slate-400 border border-slate-700";
          battFlowBadge.innerText = "⚡ Standby (0.0 A)";
          battFlowBadge.className = "text-[7px] sm:text-[10px] font-bold px-2 py-0.5 rounded-full bg-slate-800 text-slate-400 border border-slate-700 transition-all z-10 shadow-sm";
          if (battLine) {
            battLine.setAttribute('class', 'flow-line-static');
            battLine.setAttribute('marker-end', 'url(#arrow-grey)');
          }
          cardBatt.classList.remove('pulse-glow-emerald', 'pulse-glow-amber');
        }

        // 5. Suhu & Kipas
        const invTemp = parseFloat(d.inverter_temp || 0).toFixed(1);
        const fanSpeed = Math.round(d.fan1_speed || 0);

        document.getElementById('val-inv-temp').innerText = invTemp + "°C";
        document.getElementById('val-fan-speed').innerText = fanSpeed + "%";
        document.getElementById('val-htemp').innerText = invTemp + "°C";
        document.getElementById('bar-htemp').style.width = Math.min(100, Math.max(0, parseFloat(invTemp))) + "%";
        document.getElementById('val-fan-pct').innerText = fanSpeed + "%";
        document.getElementById('bar-fan-pct').style.width = Math.min(100, Math.max(0, fanSpeed)) + "%";

        const fanCircle = document.getElementById('fan-icon-circle');
        if (fanCircle) {
          const fanSvg = fanCircle.querySelector('svg');
          if (fanSvg) {
            if (fanSpeed > 0) fanSvg.classList.add('fan-spinning');
            else fanSvg.classList.remove('fan-spinning');
          }
        }

        document.getElementById('val-temp-core').innerText = invTemp + "°C";
        document.getElementById('val-temp-boost').innerText = parseFloat(d.boost_temp || 0).toFixed(1) + "°C";
        document.getElementById('val-temp-trafo').innerText = parseFloat(d.trafo_temp || 0).toFixed(1) + "°C";
        document.getElementById('val-temp-pv').innerText = parseFloat(d.pv_temp || 0).toFixed(1) + "°C";

        // 6. Informasi Sistem
        document.getElementById('info-batt-volt').innerText = battV + " V";
        document.getElementById('info-batt-watts').innerText = battWatts + " W";
        document.getElementById('info-load-power').innerText = loadKW + " kW";
        document.getElementById('info-ac-volt').innerText = acV + " V";
        document.getElementById('info-ac-freq').innerText = acHz + " Hz";

        // 7. Akumulator Energi (4 Cards Row)
        document.getElementById('val-daily-kwh').innerText = parseFloat(d.daily_kwh || 0).toFixed(2);
        document.getElementById('val-monthly-kwh').innerText = parseFloat(d.monthly_kwh || 0).toFixed(2);
        document.getElementById('val-yearly-kwh').innerText = parseFloat(d.yearly_kwh || 0).toFixed(0);
        document.getElementById('val-total-kwh').innerText = parseFloat(d.total_kwh || 0).toFixed(0);

      } else {
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

  // Timer Tick Heartbeat Realtime
  function updateTimerTick() {
    if (currentDiffSec !== null) {
      currentDiffSec++;
      updateMcuStatus(currentDiffSec);
    }
  }

  setAllLinesToStatic();
  updateMcuStatus(null);
  fetchLatestData();
  setInterval(fetchLatestData, 2000);
  setInterval(updateTimerTick, 1000);
</script>

</main>
</div>
</body>
</html>
