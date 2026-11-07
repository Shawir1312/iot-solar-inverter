<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/auth.php';

$pageTitle = "Realtime Power Flow • " . (defined('APP_NAME') ? APP_NAME : 'PowMr 6.5kW Monitor');
require_once __DIR__ . '/navbar.php';
?>

<div class="max-w-7xl mx-auto px-3 sm:px-6 lg:px-8 py-3 sm:py-5 space-y-4 sm:space-y-5">

  <!-- ======================================================== -->
  <!-- 1. HERO TITLE SECTION & VILLA SOLAR HOUSE BACKDROP       -->
  <!-- ======================================================== -->
  <div class="relative overflow-hidden rounded-3xl p-4 sm:p-6 bg-gradient-to-r from-[#0a1638] via-[#091842] to-[#0d276b]/70 border border-blue-500/25 shadow-2xl flex flex-col md:flex-row md:items-center justify-between gap-4">
    
    <!-- Hero Left: Lightning Icon Badge + Title -->
    <div class="relative z-10 flex items-center gap-3.5 sm:gap-4">
      <!-- Circular Glowing Amber Icon -->
      <div class="w-12 h-12 sm:w-14 sm:h-14 rounded-full bg-gradient-to-tr from-amber-500/20 to-amber-400/40 border-2 border-amber-400/50 flex items-center justify-center text-amber-400 shadow-[0_0_25px_rgba(245,158,11,0.4)] flex-shrink-0">
        <svg class="w-7 h-7 sm:w-8 sm:h-8" fill="currentColor" viewBox="0 0 20 20">
          <path fill-rule="evenodd" d="M11.3 1.046A1 1 0 0112 2v5h4a1 1 0 01.82 1.573l-7 10A1 1 0 018 18v-5H4a1 1 0 01-.82-1.573l7-10a1 1 0 011.12-.38z" clip-rule="evenodd" />
        </svg>
      </div>
      <div>
        <h1 class="text-xl sm:text-2xl lg:text-3xl font-extrabold text-white tracking-tight leading-tight">
          PLTS Monitoring System
        </h1>
        <p class="text-xs sm:text-sm text-slate-300 font-medium mt-0.5">
          PowMr 6.5kW Hybrid Inverter
        </p>
      </div>
    </div>

    <!-- Hero Right: Solar Villa House Graphic with Sunburst (Exact match to Mockup) -->
    <div class="absolute right-0 top-0 bottom-0 w-80 sm:w-96 md:w-[480px] pointer-events-none opacity-85 hidden sm:block">
      <svg class="w-full h-full object-cover" viewBox="0 0 500 200" fill="none" preserveAspectRatio="xMaxYMid meet">
        <defs>
          <linearGradient id="fade-left" x1="0" y1="0" x2="200" y2="0" gradientUnits="userSpaceOnUse">
            <stop offset="0" stop-color="#0a1638" stop-opacity="1"/>
            <stop offset="0.6" stop-color="#0a1638" stop-opacity="0.5"/>
            <stop offset="1" stop-color="#0a1638" stop-opacity="0"/>
          </linearGradient>
          <linearGradient id="villa-roof" x1="150" y1="50" x2="350" y2="150" gradientUnits="userSpaceOnUse">
            <stop stop-color="#0284c7"/>
            <stop offset="1" stop-color="#075985"/>
          </linearGradient>
          <radialGradient id="sun-glow" cx="380" cy="50" r="100" gradientUnits="userSpaceOnUse">
            <stop stop-color="#fef08a" stop-opacity="0.9"/>
            <stop offset="0.4" stop-color="#f59e0b" stop-opacity="0.5"/>
            <stop offset="1" stop-color="#0a1638" stop-opacity="0"/>
          </radialGradient>
        </defs>

        <!-- Sun Glow Flare in Sky -->
        <circle cx="380" cy="50" r="100" fill="url(#sun-glow)"/>
        <line x1="380" y1="0" x2="380" y2="100" stroke="#fef08a" stroke-width="1.5" stroke-opacity="0.6"/>
        <line x1="330" y1="50" x2="430" y2="50" stroke="#fef08a" stroke-width="1.5" stroke-opacity="0.6"/>
        <line x1="345" y1="15" x2="415" y2="85" stroke="#fef08a" stroke-width="1.5" stroke-opacity="0.6"/>
        <line x1="345" y1="85" x2="415" y2="15" stroke="#fef08a" stroke-width="1.5" stroke-opacity="0.6"/>

        <!-- Villa Architecture (Modern House with Solar Rooftop) -->
        <polygon points="260,190 260,105 380,45 490,95 490,190" fill="#1e293b" opacity="0.95"/>
        <rect x="290" y="115" width="45" height="35" rx="4" fill="#fef08a" opacity="0.9" filter="drop-shadow(0 0 6px #eab308)"/>
        <rect x="360" y="115" width="45" height="35" rx="4" fill="#fef08a" opacity="0.9" filter="drop-shadow(0 0 6px #eab308)"/>
        <rect x="425" y="115" width="40" height="35" rx="4" fill="#fef08a" opacity="0.9" filter="drop-shadow(0 0 6px #eab308)"/>

        <!-- Photovoltaic Solar Rooftop Panel -->
        <polygon points="265,102 380,45 485,92 380,108" fill="url(#villa-roof)" stroke="#38bdf8" stroke-width="1.5"/>
        <!-- Rooftop Solar Grid Lines -->
        <line x1="322" y1="74" x2="432" y2="99" stroke="#7dd3fc" stroke-width="1"/>
        <line x1="345" y1="62" x2="455" y2="87" stroke="#7dd3fc" stroke-width="1"/>
        <line x1="300" y1="86" x2="410" y2="111" stroke="#7dd3fc" stroke-width="1"/>
        <line x1="320" y1="88" x2="350" y2="58" stroke="#7dd3fc" stroke-width="1"/>
        <line x1="360" y1="97" x2="390" y2="67" stroke="#7dd3fc" stroke-width="1"/>
        <line x1="400" y1="104" x2="430" y2="76" stroke="#7dd3fc" stroke-width="1"/>

        <!-- Green Lawn & Trees Foreground -->
        <ellipse cx="380" cy="195" rx="140" ry="20" fill="#065f46" opacity="0.9"/>
        <path d="M220 190 C 210 165, 210 145, 230 130 C 250 145, 250 165, 240 190 Z" fill="#10b981" opacity="0.85"/>
        <path d="M200 190 C 190 170, 190 155, 205 145 C 220 155, 220 170, 210 190 Z" fill="#047857" opacity="0.85"/>

        <!-- Gradient Mask to Fade Seamlessly on the Left -->
        <rect x="0" y="0" width="220" height="200" fill="url(#fade-left)"/>
      </svg>
    </div>

    <!-- Hero Right Status Badges (Mobile / Inline) -->
    <div class="relative z-10 flex items-center gap-2 self-start sm:self-auto flex-wrap text-xs">
      <span id="mode-badge" class="px-3 py-1 rounded-full bg-amber-500/15 text-amber-300 border border-amber-500/30 font-semibold shadow-sm text-xs">
        Standby
      </span>
      <span id="mcu-heartbeat-pill" class="px-3 py-1 rounded-full bg-slate-900/80 text-slate-300 border border-slate-700/60 font-mono text-xs">
        Heartbeat: -
      </span>
      <span class="text-[11px] text-slate-400 hidden lg:inline">
        Log: <b id="last-update" class="text-slate-200 font-mono">-</b>
      </span>
    </div>

  </div>

  <!-- Banner Peringatan Saat Mikrokontroler ESP32 OFFLINE -->
  <div id="mcu-offline-banner" class="hidden bg-rose-950/40 border border-rose-500/40 text-rose-300 rounded-2xl p-3.5 sm:p-4 text-xs sm:text-sm flex items-center justify-between gap-3 shadow-lg shadow-rose-950/50 transition-all duration-300">
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
  <div class="glass-panel rounded-3xl p-4 sm:p-6 lg:p-7 shadow-2xl relative overflow-hidden">
    
    <!-- Header Diagram Badge (Exact match: blue pill badge with cyan bolt) -->
    <div class="flex items-center justify-between mb-4 sm:mb-6">
      <div class="inline-flex items-center gap-1.5 px-3 py-1 rounded-lg bg-blue-600/25 border border-blue-500/40 text-blue-400 font-bold text-xs shadow-sm">
        <svg class="w-4 h-4 text-cyan-400" fill="currentColor" viewBox="0 0 20 20">
          <path fill-rule="evenodd" d="M11.3 1.046A1 1 0 0112 2v5h4a1 1 0 01.82 1.573l-7 10A1 1 0 018 18v-5H4a1 1 0 01-.82-1.573l7-10a1 1 0 011.12-.38z" clip-rule="evenodd" />
        </svg>
        <span>Diagram Aliran Daya</span>
      </div>
    </div>

    <!-- Canvas Skematik 5-Kolom (Left Cards - Left Pipes - Center Hub - Right Pipe - Right Card) -->
    <div class="w-full overflow-x-auto sm:overflow-visible">
      <div class="grid grid-cols-12 gap-1.5 sm:gap-3 lg:gap-4 items-center min-w-[340px] max-w-6xl mx-auto py-1 sm:py-2">
        
        <!-- KOLOM 1 (Cols 1-3): Sumber Energi (Solar PV Atas & PLN Grid Bawah) -->
        <div class="col-span-3 space-y-3 sm:space-y-5">
          
          <!-- Node 1: Solar Panel PV (Top-Left, Amber Border) -->
          <div id="card-node-pv" class="bg-[#0c1b3d] border-2 border-amber-500 rounded-2xl p-2.5 sm:p-4 shadow-[0_0_20px_rgba(245,158,11,0.25)] transition-all duration-300">
            <!-- Header Node -->
            <div class="flex items-center gap-1.5 mb-1 sm:mb-1.5">
              <span class="text-amber-400">☀️</span>
              <span class="text-[10px] sm:text-xs font-bold text-white uppercase tracking-wider">Solar PV</span>
            </div>

            <!-- Body Node: 3D Solar Panel Icon + Value kW -->
            <div class="flex items-center gap-2 sm:gap-3">
              <!-- Angled Solar Photovoltaic Panel Icon -->
              <div class="w-9 h-9 sm:w-12 sm:h-12 flex-shrink-0">
                <svg class="w-full h-full drop-shadow-[0_0_8px_rgba(56,189,248,0.4)]" viewBox="0 0 40 40" fill="none">
                  <path d="M5 32L15 12H35L26 32H5Z" fill="#0284c7" stroke="#38bdf8" stroke-width="1.2" stroke-linejoin="round"/>
                  <line x1="10" y1="22" x2="30" y2="22" stroke="#7dd3fc" stroke-width="0.8"/>
                  <line x1="17" y1="12" x2="11" y2="32" stroke="#7dd3fc" stroke-width="0.8"/>
                  <line x1="25" y1="12" x2="19" y2="32" stroke="#7dd3fc" stroke-width="0.8"/>
                </svg>
              </div>

              <!-- Big Value kW -->
              <div>
                <div class="text-sm sm:text-2xl font-black text-white tracking-tight leading-none">
                  <span id="val-pv-power-kw">1.28</span> <span class="text-[10px] sm:text-xs font-semibold text-slate-300">kW</span>
                </div>
                <div class="text-[8px] sm:text-[11px] text-slate-300 font-mono mt-1 whitespace-nowrap">
                  ≈ <span id="val-pv-volt">229.6</span> V &nbsp;<span id="val-pv-curr">5.6</span> A
                </div>
              </div>
            </div>
          </div>

          <!-- Node 2: PLN Grid Input (Bottom-Left, Blue Border) -->
          <div id="card-node-grid" class="bg-[#0c1b3d] border-2 border-blue-500 rounded-2xl p-2.5 sm:p-4 shadow-[0_0_20px_rgba(59,130,246,0.25)] transition-all duration-300">
            <!-- Header Node -->
            <div class="flex items-center gap-1.5 mb-1 sm:mb-1.5">
              <span class="text-blue-400">⚡</span>
              <span class="text-[10px] sm:text-xs font-bold text-white uppercase tracking-wider">PLN Grid</span>
            </div>

            <!-- Body Node: Transmission Tower Icon + Value kW -->
            <div class="flex items-center gap-2 sm:gap-3">
              <!-- Stylized Transmission Pylon Tower Icon -->
              <div class="w-9 h-9 sm:w-12 sm:h-12 flex-shrink-0 flex items-center justify-center">
                <svg class="w-8 h-8 sm:w-10 sm:h-10 text-cyan-400 drop-shadow-[0_0_8px_rgba(6,182,212,0.5)]" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 2v20M7 8h10M5 14h14M8 8l4 6 4-6M7 14l5 8 5-8M4 22h16"/>
                </svg>
              </div>

              <!-- Big Value kW -->
              <div>
                <div class="text-sm sm:text-2xl font-black text-white tracking-tight leading-none">
                  <span id="val-grid-power-kw">0.00</span> <span class="text-[10px] sm:text-xs font-semibold text-slate-300">kW</span>
                </div>
                <div class="text-[8px] sm:text-[11px] text-slate-300 font-mono mt-1 whitespace-nowrap">
                  <span id="val-grid-volt">0.0</span> V &nbsp;<span id="val-grid-freq">0.0</span> Hz
                </div>
              </div>
            </div>
          </div>

        </div>

        <!-- KOLOM 2 (Cols 4-5): Pipa Kiri (PV Lurus & PLN Melengkung Naik ke Inverter) -->
        <div class="col-span-1 sm:col-span-2 flex flex-col justify-between h-full py-2 relative min-h-[190px] sm:min-h-[260px]">
          
          <!-- SVG Canvas Pipa Kiri (Pure Glowing Lines & Arrows, No Mid Clutter) -->
          <svg class="absolute inset-0 w-full h-full overflow-visible pointer-events-none" viewBox="0 0 100 240" preserveAspectRatio="none">
            <defs>
              <marker id="arrow-amber" viewBox="0 0 10 10" refX="6" refY="5" markerWidth="6" markerHeight="6" orient="auto">
                <path d="M 0 1 L 9 5 L 0 9 z" fill="#f59e0b"/>
              </marker>
              <marker id="arrow-blue" viewBox="0 0 10 10" refX="6" refY="5" markerWidth="6" markerHeight="6" orient="auto">
                <path d="M 0 1 L 9 5 L 0 9 z" fill="#3b82f6"/>
              </marker>
              <marker id="arrow-grey" viewBox="0 0 10 10" refX="6" refY="5" markerWidth="6" markerHeight="6" orient="auto">
                <path d="M 0 1 L 9 5 L 0 9 z" fill="#475569"/>
              </marker>
            </defs>

            <!-- 1. Pipa PV ➔ Inverter (Lurus Horizontal dengan Arrowhead Emas) -->
            <line x1="0" y1="48" x2="94" y2="48" class="pipe-tube" vector-effect="non-scaling-stroke" />
            <line id="flow-pv-line" x1="0" y1="48" x2="94" y2="48" class="flow-pv-active" marker-end="url(#arrow-amber)" vector-effect="non-scaling-stroke" />

            <!-- 2. Pipa PLN ➔ Inverter (Melengkung 90° Naik ke Inverter dengan Arrowhead Biru) -->
            <path d="M 0,192 C 45,192 45,88 94,88" class="pipe-tube" fill="none" vector-effect="non-scaling-stroke" />
            <path id="flow-grid-line" d="M 0,192 C 45,192 45,88 94,88" class="flow-line-static" fill="none" marker-end="url(#arrow-grey)" vector-effect="non-scaling-stroke" />
          </svg>

        </div>

        <!-- KOLOM 3 (Cols 6-8): Pusat Hub Inverter (Putih) & Baterai 48V (Bawah) -->
        <div class="col-span-4 sm:col-span-3 space-y-1.5 sm:space-y-3 flex flex-col items-center">
          
          <!-- Node Pusat: Hybrid Inverter PowMr 6.5kW (Frosted White / Silver Card) -->
          <div id="card-node-inv" class="w-full bg-gradient-to-b from-white to-[#cbd5e1] text-slate-900 border-2 border-white rounded-2xl p-3 sm:p-4 shadow-2xl text-center relative transition-all duration-300">
            
            <!-- Orange Wave AC Icon in Center Top -->
            <div class="w-8 h-8 sm:w-11 sm:h-11 mx-auto bg-amber-500 rounded-xl flex items-center justify-center text-white mb-1 shadow-md">
              <svg class="w-5 h-5 sm:w-6 sm:h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M3 12c3-4 6-4 9 0s6 4 9 0"/>
              </svg>
            </div>
            
            <h3 class="font-black text-slate-950 text-xs sm:text-base leading-tight">PowMr 6.5kW</h3>
            <p class="text-[8px] sm:text-[11px] text-slate-600 font-bold">48V Hybrid</p>

            <!-- Status Suhu & Kipas Inverter Card Mini Bar -->
            <div class="mt-2 grid grid-cols-2 gap-1 text-[8px] sm:text-xs pt-1.5 border-t border-slate-300">
              <div class="text-left flex items-center gap-1">
                <span>🌡️</span>
                <b id="val-inv-temp" class="text-emerald-700 font-mono font-bold">48.0°C</b>
              </div>
              <div class="text-right flex items-center justify-end gap-1">
                <span>🌀</span>
                <b id="val-fan-speed" class="text-cyan-700 font-mono font-bold">30%</b>
              </div>
            </div>
          </div>

          <!-- Pipa Aliran Vertikal: Inverter ◄══► Baterai 48V -->
          <div class="flex flex-col items-center justify-center w-full py-0.5 relative">
            <svg class="w-8 h-8 sm:h-9 overflow-visible" viewBox="0 0 20 34">
              <defs>
                <marker id="arrow-green-down" viewBox="0 0 10 10" refX="5" refY="6" markerWidth="6" markerHeight="6" orient="auto">
                  <path d="M 1 0 L 5 9 L 9 0 z" fill="#10b981"/>
                </marker>
                <marker id="arrow-green-up" viewBox="0 0 10 10" refX="5" refY="3" markerWidth="6" markerHeight="6" orient="auto-start-reverse">
                  <path d="M 1 9 L 5 0 L 9 9 z" fill="#10b981"/>
                </marker>
                <marker id="arrow-amber-up" viewBox="0 0 10 10" refX="5" refY="3" markerWidth="6" markerHeight="6" orient="auto-start-reverse">
                  <path d="M 1 9 L 5 0 L 9 9 z" fill="#f59e0b"/>
                </marker>
              </defs>
              <line x1="10" y1="0" x2="10" y2="34" class="pipe-tube" vector-effect="non-scaling-stroke" />
              <line id="flow-batt-line" x1="10" y1="0" x2="10" y2="34" class="flow-batt-charge" marker-end="url(#arrow-green-up)" vector-effect="non-scaling-stroke" />
            </svg>
          </div>

          <!-- Node 3: Baterai 48V (Bawah, Green Border) -->
          <div id="card-node-batt" class="w-full bg-[#0c1b3d] border-2 border-emerald-500 rounded-2xl p-2.5 sm:p-4 shadow-[0_0_20px_rgba(16,185,129,0.25)] transition-all duration-300">
            <!-- Header Node -->
            <div class="flex items-center gap-1.5 mb-1 sm:mb-1.5">
              <span class="text-xs font-bold text-white uppercase tracking-wider">Baterai 48V</span>
            </div>

            <!-- Body Node: Battery Icon + Value SOC % -->
            <div class="flex items-center gap-2 sm:gap-3">
              <!-- Green Rounded Battery Icon Box -->
              <div class="w-9 h-9 sm:w-11 sm:h-11 rounded-xl bg-emerald-500/20 border border-emerald-500/40 text-emerald-400 flex items-center justify-center flex-shrink-0 shadow-[0_0_10px_rgba(16,185,129,0.4)]">
                <svg class="w-5 h-5 sm:w-6 sm:h-6" fill="currentColor" viewBox="0 0 20 20">
                  <path fill-rule="evenodd" d="M11.3 1.046A1 1 0 0112 2v5h4a1 1 0 01.82 1.573l-7 10A1 1 0 018 18v-5H4a1 1 0 01-.82-1.573l7-10a1 1 0 011.12-.38z" clip-rule="evenodd" />
                </svg>
              </div>

              <!-- Big Value % + Progress Bar -->
              <div class="flex-1">
                <div class="text-sm sm:text-2xl font-black text-white tracking-tight leading-none">
                  <span id="val-batt-soc">79</span><span class="text-[10px] sm:text-sm font-bold text-white">%</span>
                </div>
                <!-- Green Progress Bar below % -->
                <div class="w-full bg-slate-800 rounded-full h-1.5 sm:h-2 mt-1 sm:mt-1.5 overflow-hidden">
                  <div id="bar-batt-soc" class="bg-gradient-to-r from-emerald-500 to-teal-400 h-full rounded-full transition-all duration-500" style="width: 79%"></div>
                </div>
                <div class="text-[8px] sm:text-[11px] text-slate-300 font-mono mt-1 whitespace-nowrap">
                  <span id="val-batt-volt">50.1</span> V &nbsp;|&nbsp; <span id="val-batt-watts">378</span> W
                </div>
              </div>
            </div>
          </div>

        </div>

        <!-- KOLOM 4 (Col 9): Pipa Inverter ➔ Beban Rumah -->
        <div class="col-span-1 sm:col-span-1 flex flex-col items-center justify-center relative min-h-[190px] sm:min-h-[260px]">
          <svg class="absolute inset-0 w-full h-full overflow-visible pointer-events-none" viewBox="0 0 60 240" preserveAspectRatio="none">
            <defs>
              <marker id="arrow-cyan" viewBox="0 0 10 10" refX="6" refY="5" markerWidth="6" markerHeight="6" orient="auto">
                <path d="M 0 1 L 9 5 L 0 9 z" fill="#06b6d4"/>
              </marker>
            </defs>
            <!-- Pipa dari Inverter ke Beban Rumah dengan Arrowhead Cyan -->
            <line x1="0" y1="68" x2="54" y2="68" class="pipe-tube" vector-effect="non-scaling-stroke" />
            <line id="flow-load-line" x1="0" y1="68" x2="54" y2="68" class="flow-load-active" marker-end="url(#arrow-cyan)" vector-effect="non-scaling-stroke" />
          </svg>
        </div>

        <!-- KOLOM 5 (Cols 10-12): Beban Rumah (Right, Cyan Border) -->
        <div class="col-span-3 sm:col-span-3">
          
          <!-- Node 4: Beban Rumah / Home Load -->
          <div id="card-node-load" class="bg-[#0c1b3d] border-2 border-cyan-400 rounded-2xl p-2.5 sm:p-4 shadow-[0_0_20px_rgba(6,182,212,0.25)] transition-all duration-300">
            <!-- Header Node -->
            <div class="flex items-center gap-1.5 mb-1 sm:mb-1.5">
              <span class="text-cyan-400">🏠</span>
              <span class="text-[10px] sm:text-xs font-bold text-white uppercase tracking-wider">Beban Rumah</span>
            </div>

            <!-- Body Node: Modern House Icon Box + Value kW -->
            <div class="flex items-center gap-2 sm:gap-3">
              <!-- Cyan Rounded House Icon Box -->
              <div class="w-9 h-9 sm:w-12 sm:h-12 rounded-xl bg-cyan-500/20 border border-cyan-500/40 text-cyan-300 flex items-center justify-center flex-shrink-0 shadow-[0_0_10px_rgba(6,182,212,0.4)]">
                <svg class="w-5 h-5 sm:w-7 sm:h-7" fill="currentColor" viewBox="0 0 24 24">
                  <path d="M10 20v-6h4v6h5v-8h3L12 3 2 12h3v8z"/>
                </svg>
              </div>

              <!-- Big Value kW -->
              <div>
                <div class="text-sm sm:text-2xl font-black text-white tracking-tight leading-none">
                  <span id="val-load-power-kw">0.84</span> <span class="text-[10px] sm:text-xs font-semibold text-slate-300">kW</span>
                </div>
                <div class="text-[8px] sm:text-[11px] text-slate-300 font-mono mt-1 whitespace-nowrap">
                  ≈ <span id="val-ac-volt">219.8</span> V &nbsp;<span id="val-load-curr">3.8</span> A
                </div>
              </div>
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
    <div class="glass-panel glass-panel-hover rounded-2xl p-4 sm:p-5 relative overflow-hidden flex flex-col justify-between">
      <div class="flex items-center justify-between mb-1">
        <div class="flex items-center gap-2">
          <!-- Yellow Glowing Sun Icon Circle -->
          <div class="w-8 h-8 rounded-full bg-amber-500/20 text-amber-400 flex items-center justify-center border border-amber-500/40 shadow-[0_0_10px_rgba(245,158,11,0.3)]">
            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 2a1 1 0 011 1v1a1 1 0 11-2 0V3a1 1 0 011-1zm4 8a4 4 0 11-8 0 4 4 0 018 0zm-.464 4.95l.707.707a1 1 0 001.414-1.414l-.707-.707a1 1 0 00-1.414 1.414zm2.12-10.607a1 1 0 010 1.414l-.706.707a1 1 0 11-1.414-1.414l.707-.707a1 1 0 011.414 0zM17 11a1 1 0 100-2h-1a1 1 0 100 2h1zm-7 4a1 1 0 011 1v1a1 1 0 11-2 0v-1a1 1 0 011-1zM5.05 6.464A1 1 0 106.465 5.05l-.708-.707a1 1 0 00-1.414 1.414l.707.707zm1.414 8.486l-.707.707a1 1 0 01-1.414-1.414l.707-.707a1 1 0 011.414 1.414zM4 11a1 1 0 100-2H3a1 1 0 000 2h1z" clip-rule="evenodd"/></svg>
          </div>
          <span class="text-xs font-semibold text-slate-300">Energi Hari Ini</span>
        </div>
        <span class="px-2 py-0.5 rounded-full bg-emerald-500/20 text-emerald-400 border border-emerald-500/40 text-[10px] font-bold">↑ +12%</span>
      </div>

      <div class="text-xl sm:text-3xl font-black text-white tracking-tight my-1">
        <span id="val-daily-kwh">5.12</span> <span class="text-xs sm:text-sm font-semibold text-slate-300">kWh</span>
      </div>

      <!-- Mini Sparkline Wave Amber -->
      <div class="w-full h-8 sm:h-9 mt-1">
        <svg class="w-full h-full overflow-visible" viewBox="0 0 100 30" preserveAspectRatio="none">
          <defs>
            <linearGradient id="spark-amber" x1="0" y1="0" x2="0" y2="1">
              <stop stop-color="#f59e0b" stop-opacity="0.3"/>
              <stop offset="1" stop-color="#f59e0b" stop-opacity="0.0"/>
            </linearGradient>
          </defs>
          <path d="M 0,25 Q 20,28 35,18 T 70,12 T 100,6 L 100,30 L 0,30 Z" fill="url(#spark-amber)"/>
          <path d="M 0,25 Q 20,28 35,18 T 70,12 T 100,6" fill="none" stroke="#f59e0b" stroke-width="2.5" stroke-linecap="round"/>
        </svg>
      </div>
    </div>

    <!-- Card 2: Energi Bulan Ini -->
    <div class="glass-panel glass-panel-hover rounded-2xl p-4 sm:p-5 relative overflow-hidden flex flex-col justify-between">
      <div class="flex items-center justify-between mb-1">
        <div class="flex items-center gap-2">
          <!-- Blue Glowing Calendar Icon Circle -->
          <div class="w-8 h-8 rounded-full bg-blue-500/20 text-blue-400 flex items-center justify-center border border-blue-500/40 shadow-[0_0_10px_rgba(59,130,246,0.3)]">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
          </div>
          <span class="text-xs font-semibold text-slate-300">Energi Bulan Ini</span>
        </div>
        <span class="px-2 py-0.5 rounded-full bg-emerald-500/20 text-emerald-400 border border-emerald-500/40 text-[10px] font-bold">↑ +8%</span>
      </div>

      <div class="text-xl sm:text-3xl font-black text-white tracking-tight my-1">
        <span id="val-monthly-kwh">128.75</span> <span class="text-xs sm:text-sm font-semibold text-slate-300">kWh</span>
      </div>

      <!-- Mini Sparkline Wave Blue -->
      <div class="w-full h-8 sm:h-9 mt-1">
        <svg class="w-full h-full overflow-visible" viewBox="0 0 100 30" preserveAspectRatio="none">
          <defs>
            <linearGradient id="spark-blue" x1="0" y1="0" x2="0" y2="1">
              <stop stop-color="#3b82f6" stop-opacity="0.3"/>
              <stop offset="1" stop-color="#3b82f6" stop-opacity="0.0"/>
            </linearGradient>
          </defs>
          <path d="M 0,22 Q 25,28 45,15 T 80,18 T 100,8 L 100,30 L 0,30 Z" fill="url(#spark-blue)"/>
          <path d="M 0,22 Q 25,28 45,15 T 80,18 T 100,8" fill="none" stroke="#3b82f6" stroke-width="2.5" stroke-linecap="round"/>
        </svg>
      </div>
    </div>

    <!-- Card 3: Energi Tahun Ini -->
    <div class="glass-panel glass-panel-hover rounded-2xl p-4 sm:p-5 relative overflow-hidden flex flex-col justify-between">
      <div class="flex items-center justify-between mb-1">
        <div class="flex items-center gap-2">
          <!-- Green Glowing Chart Icon Circle -->
          <div class="w-8 h-8 rounded-full bg-emerald-500/20 text-emerald-400 flex items-center justify-center border border-emerald-500/40 shadow-[0_0_10px_rgba(16,185,129,0.3)]">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
          </div>
          <span class="text-xs font-semibold text-slate-300">Energi Tahun Ini</span>
        </div>
        <span class="px-2 py-0.5 rounded-full bg-emerald-500/20 text-emerald-400 border border-emerald-500/40 text-[10px] font-bold">↑ +15%</span>
      </div>

      <div class="text-xl sm:text-3xl font-black text-white tracking-tight my-1">
        <span id="val-yearly-kwh">1,246</span> <span class="text-xs sm:text-sm font-semibold text-slate-300">kWh</span>
      </div>

      <!-- Mini Sparkline Wave Green -->
      <div class="w-full h-8 sm:h-9 mt-1">
        <svg class="w-full h-full overflow-visible" viewBox="0 0 100 30" preserveAspectRatio="none">
          <defs>
            <linearGradient id="spark-green" x1="0" y1="0" x2="0" y2="1">
              <stop stop-color="#10b981" stop-opacity="0.3"/>
              <stop offset="1" stop-color="#10b981" stop-opacity="0.0"/>
            </linearGradient>
          </defs>
          <path d="M 0,26 Q 20,22 40,16 T 75,20 T 100,6 L 100,30 L 0,30 Z" fill="url(#spark-green)"/>
          <path d="M 0,26 Q 20,22 40,16 T 75,20 T 100,6" fill="none" stroke="#10b981" stroke-width="2.5" stroke-linecap="round"/>
        </svg>
      </div>
    </div>

    <!-- Card 4: Total Seumur Hidup -->
    <div class="glass-panel glass-panel-hover rounded-2xl p-4 sm:p-5 relative overflow-hidden flex flex-col justify-between">
      <div class="flex items-center justify-between mb-1">
        <div class="flex items-center gap-2">
          <!-- Golden/Purple Glowing Bolt Icon Circle -->
          <div class="w-8 h-8 rounded-full bg-purple-500/20 text-purple-400 flex items-center justify-center border border-purple-500/40 shadow-[0_0_10px_rgba(168,85,247,0.3)]">
            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M11.3 1.046A1 1 0 0112 2v5h4a1 1 0 01.82 1.573l-7 10A1 1 0 018 18v-5H4a1 1 0 01-.82-1.573l7-10a1 1 0 011.12-.38z" clip-rule="evenodd"/></svg>
          </div>
          <span class="text-xs font-semibold text-slate-300">Total Seumur Hidup</span>
        </div>
      </div>

      <div class="text-xl sm:text-3xl font-black text-white tracking-tight my-1">
        <span id="val-total-kwh">12,386</span> <span class="text-xs sm:text-sm font-semibold text-slate-300">kWh</span>
      </div>

      <!-- Mini Sparkline Wave Purple -->
      <div class="w-full h-8 sm:h-9 mt-1">
        <svg class="w-full h-full overflow-visible" viewBox="0 0 100 30" preserveAspectRatio="none">
          <defs>
            <linearGradient id="spark-purple" x1="0" y1="0" x2="0" y2="1">
              <stop stop-color="#a855f7" stop-opacity="0.3"/>
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
  <div class="grid grid-cols-1 md:grid-cols-2 gap-4 sm:gap-5">
    
    <!-- Left Card: Suhu & Kecepatan Kipas -->
    <div class="glass-panel rounded-3xl p-4 sm:p-6 relative overflow-hidden">
      <!-- Watermark Fan Silhouette Background -->
      <div class="absolute -right-6 -bottom-6 w-44 h-44 text-slate-800/30 pointer-events-none">
        <svg class="w-full h-full fan-spinning opacity-15" fill="currentColor" viewBox="0 0 24 24">
          <path d="M12 2a10 10 0 1010 10A10 10 0 0012 2zm1 3.1a4.9 4.9 0 013.9 3.9H13zm-2 0v3.9H7.1A4.9 4.9 0 0111 5.1zM5.1 11H9v2H5.1A4.9 4.9 0 015.1 11zm2 3.9H11v3.9a4.9 4.9 0 01-3.9-3.9zm3.9 3.9V15h3.9a4.9 4.9 0 01-3.9 3.9zm3.9-5.9h3.9a4.9 4.9 0 010 2h-3.9z"/>
        </svg>
      </div>

      <!-- Header Card -->
      <div class="flex items-center gap-2 mb-4">
        <span class="text-blue-400">🌡️</span>
        <h3 class="text-sm sm:text-base font-bold text-white">Suhu & Kecepatan Kipas</h3>
      </div>

      <!-- Two Large Visual Gauges -->
      <div class="grid grid-cols-2 gap-4 relative z-10">
        
        <!-- Gauge 1: HTEMP (Suhu Inverter) -->
        <div class="bg-[#091535]/80 border border-amber-500/30 rounded-2xl p-4 flex flex-col justify-between">
          <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-full bg-amber-500/20 border border-amber-500/40 text-amber-400 flex items-center justify-center flex-shrink-0 shadow-[0_0_12px_rgba(245,158,11,0.3)]">
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </div>
            <div>
              <span class="text-[11px] font-semibold text-slate-400 uppercase tracking-wider block">HTEMP</span>
              <b id="val-htemp" class="text-xl sm:text-2xl font-black text-white font-mono">48.0°C</b>
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
              <b id="val-fan-pct" class="text-xl sm:text-2xl font-black text-white font-mono">30%</b>
            </div>
          </div>

          <!-- Progress Bar Cyan -->
          <div class="w-full bg-slate-800 rounded-full h-2 mt-3 overflow-hidden border border-slate-700">
            <div id="bar-fan-pct" class="bg-gradient-to-r from-cyan-500 to-blue-400 h-full rounded-full transition-all duration-500" style="width: 30%"></div>
          </div>
        </div>

      </div>

    </div>

    <!-- Right Card: Informasi Sistem & 3D Glowing Battery Hologram -->
    <div class="glass-panel rounded-3xl p-4 sm:p-6 relative overflow-hidden flex flex-col justify-between">
      
      <!-- Header Card -->
      <div class="flex items-center gap-2 mb-3">
        <div class="w-5 h-5 rounded-full bg-cyan-500/20 border border-cyan-500/40 text-cyan-400 flex items-center justify-center text-xs font-bold">i</div>
        <h3 class="text-sm sm:text-base font-bold text-white">Informasi Sistem</h3>
      </div>

      <!-- Content Split: Telemetry Specs Left & 3D Glowing Battery Hologram Right -->
      <div class="grid grid-cols-12 gap-3 items-center">
        
        <!-- Left 7 Cols: Telemetry Specs List -->
        <div class="col-span-7 space-y-2 text-xs">
          <div class="flex justify-between items-center py-1 border-b border-blue-500/10">
            <span class="text-slate-400">Tegangan Baterai</span>
            <span class="text-slate-400">: &nbsp;<b id="info-batt-volt" class="text-white font-mono">50.1 V</b></span>
          </div>
          <div class="flex justify-between items-center py-1 border-b border-blue-500/10">
            <span class="text-slate-400">Daya Baterai</span>
            <span class="text-slate-400">: &nbsp;<b id="info-batt-watts" class="text-white font-mono">378 W</b></span>
          </div>
          <div class="flex justify-between items-center py-1 border-b border-blue-500/10">
            <span class="text-slate-400">Daya Beban</span>
            <span class="text-slate-400">: &nbsp;<b id="info-load-power" class="text-white font-mono">0.84 kW</b></span>
          </div>
        </div>

        <!-- Right 5 Cols: 3D Glowing Green Battery Hologram Pedestal Visual -->
        <div class="col-span-5 flex flex-col items-center justify-center relative py-1">
          
          <!-- Hologram SVG Graphic -->
          <div class="relative w-28 h-32 flex items-center justify-center">
            
            <!-- Neon Glowing Floor Rings -->
            <svg class="absolute inset-0 w-full h-full overflow-visible drop-shadow-[0_0_15px_rgba(16,185,129,0.5)]" viewBox="0 0 100 120" fill="none">
              <defs>
                <linearGradient id="holo-batt" x1="0" y1="20" x2="100" y2="90" gradientUnits="userSpaceOnUse">
                  <stop stop-color="#10b981" stop-opacity="0.9"/>
                  <stop offset="0.5" stop-color="#059669" stop-opacity="0.7"/>
                  <stop offset="1" stop-color="#064e3b" stop-opacity="0.95"/>
                </linearGradient>
                <radialGradient id="ring-glow" cx="50" cy="100" r="45" gradientUnits="userSpaceOnUse">
                  <stop stop-color="#10b981" stop-opacity="0.7"/>
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
        headerPill.className = "flex items-center gap-2 px-3 py-1 rounded-full bg-emerald-500/10 border border-emerald-500/30 text-emerald-400 font-semibold shadow-sm";
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
        headerPill.className = "flex items-center gap-2 px-3 py-1 rounded-full bg-rose-500/10 border border-rose-500/30 text-rose-400 font-semibold shadow-sm";
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

    // 2. PLN Line
    const flowGridLine = document.getElementById('flow-grid-line');
    if (flowGridLine) {
      flowGridLine.setAttribute('class', 'flow-line-static');
      flowGridLine.setAttribute('marker-end', 'url(#arrow-grey)');
    }

    // 3. Batt Line
    const flowBattLine = document.getElementById('flow-batt-line');
    if (flowBattLine) {
      flowBattLine.setAttribute('class', 'flow-line-static');
      flowBattLine.setAttribute('marker-end', 'url(#arrow-grey)');
    }

    // 4. Load Line
    const flowLoadLine = document.getElementById('flow-load-line');
    if (flowLoadLine) {
      flowLoadLine.setAttribute('class', 'flow-line-static');
      flowLoadLine.setAttribute('marker-end', 'url(#arrow-grey)');
    }
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
        document.getElementById('val-pv-volt').innerText = pvV;
        document.getElementById('val-pv-curr').innerText = pvA;

        const flowPvLine = document.getElementById('flow-pv-line');
        if (isMcuOnline && pvW > 10) {
          flowPvLine.setAttribute('class', 'flow-pv-active');
          flowPvLine.setAttribute('marker-end', 'url(#arrow-amber)');
        } else {
          flowPvLine.setAttribute('class', 'flow-line-static');
          flowPvLine.setAttribute('marker-end', 'url(#arrow-grey)');
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
        document.getElementById('val-grid-volt').innerText = gridV;
        document.getElementById('val-grid-freq').innerText = gridHz;

        const flowGridLine = document.getElementById('flow-grid-line');
        const isPlnActive = (gridW > 10) || isPlnConnected;

        if (isMcuOnline && isPlnActive) {
          flowGridLine.setAttribute('class', 'flow-grid-active');
          flowGridLine.setAttribute('marker-end', 'url(#arrow-blue)');
        } else {
          flowGridLine.setAttribute('class', 'flow-line-static');
          flowGridLine.setAttribute('marker-end', 'url(#arrow-grey)');
        }

        // 3. Beban Rumah Logic
        const isLineMode = opMode.includes('line') || opMode.includes('bypass') || opMode.includes('mains');
        const loadKW = (loadW / 1000).toFixed(2);
        const loadAmp = (loadW / (parseFloat(acV) > 0 ? parseFloat(acV) : 220)).toFixed(1);

        document.getElementById('val-load-power-kw').innerText = loadKW;
        document.getElementById('val-ac-volt').innerText = acV;
        document.getElementById('val-load-curr').innerText = loadAmp;

        const flowLoadLine = document.getElementById('flow-load-line');
        if (isMcuOnline && (loadW > 10 || (isLineMode && parseFloat(acV) > 150))) {
          flowLoadLine.setAttribute('class', 'flow-load-active');
          flowLoadLine.setAttribute('marker-end', 'url(#arrow-cyan)');
        } else {
          flowLoadLine.setAttribute('class', 'flow-line-static');
          flowLoadLine.setAttribute('marker-end', 'url(#arrow-grey)');
        }

        // 4. Baterai 48V Logic
        const soc = Math.round(d.battery_capacity || 0);
        const battV = parseFloat(d.battery_voltage || 0).toFixed(1);
        const disCurrent = parseFloat(d.discharge_current || 0);
        const battWatts = Math.round(parseFloat(battV) * Math.max(chgCurrent, disCurrent));

        document.getElementById('val-batt-soc').innerText = soc;
        document.getElementById('bar-batt-soc').style.width = Math.min(100, Math.max(0, soc)) + "%";
        document.getElementById('val-batt-volt').innerText = battV;
        document.getElementById('val-batt-watts').innerText = battWatts;

        const battLine = document.getElementById('flow-batt-line');
        if (isMcuOnline && (chgCurrent > 0.5 || disCurrent > 0.5)) {
          if (chgCurrent > 0.5) {
            if (battLine) {
              battLine.setAttribute('class', 'flow-batt-charge');
              battLine.setAttribute('marker-end', 'url(#arrow-green-down)');
            }
          } else {
            if (battLine) {
              battLine.setAttribute('class', 'flow-batt-discharge');
              battLine.setAttribute('marker-end', 'url(#arrow-amber-up)');
            }
          }
        } else {
          if (battLine) {
            battLine.setAttribute('class', 'flow-line-static');
            battLine.setAttribute('marker-end', 'url(#arrow-grey)');
          }
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

        // 6. Informasi Sistem
        document.getElementById('info-batt-volt').innerText = battV + " V";
        document.getElementById('info-batt-watts').innerText = battWatts + " W";
        document.getElementById('info-load-power').innerText = loadKW + " kW";

        // 7. Akumulator Energi (4 Cards Row)
        document.getElementById('val-daily-kwh').innerText = parseFloat(d.daily_kwh || 0).toFixed(2);
        document.getElementById('val-monthly-kwh').innerText = parseFloat(d.monthly_kwh || 0).toFixed(2);
        document.getElementById('val-yearly-kwh').innerText = Math.round(parseFloat(d.yearly_kwh || 0)).toLocaleString();
        document.getElementById('val-total-kwh').innerText = Math.round(parseFloat(d.total_kwh || 0)).toLocaleString();

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
