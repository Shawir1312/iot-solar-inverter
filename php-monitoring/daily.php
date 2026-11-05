<?php
require_once __DIR__ . '/config.php';
$pageTitle = "Laporan Pemakaian Harian • " . (defined('APP_NAME') ? APP_NAME : 'PowMr 6.5kW Monitor');
require_once __DIR__ . '/navbar.php';

$selectedDate = $_GET['date'] ?? date('Y-m-d');
?>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 space-y-6">

  <!-- Header & Date Picker -->
  <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-4 shadow-sm flex flex-col md:flex-row md:items-center justify-between gap-4">
    <div>
      <h1 class="text-lg md:text-xl font-bold text-slate-900 dark:text-white flex items-center gap-2">
        <svg class="w-6 h-6 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
        Laporan Pemakaian & Produksi Harian
      </h1>
      <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Analisis kurva daya per jam (00:00 - 23:59) dan efisiensi panel surya</p>
    </div>

    <!-- Filter Tanggal -->
    <form method="GET" class="flex items-center gap-2">
      <label class="text-xs font-semibold text-slate-600 dark:text-slate-300">Pilih Tanggal:</label>
      <input type="date" name="date" value="<?= htmlspecialchars($selectedDate) ?>" class="bg-slate-100 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 text-slate-800 dark:text-slate-200 rounded-xl px-3 py-1.5 text-xs font-medium focus:outline-none focus:border-amber-500" onchange="this.form.submit()">
      <button type="submit" class="px-3 py-1.5 bg-amber-500 hover:bg-amber-600 text-white rounded-xl text-xs font-semibold transition-colors">
        Tampilkan
      </button>
    </form>
  </div>

  <!-- Summary KPI Cards -->
  <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
    
    <!-- Total Produksi Harian -->
    <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-4 shadow-sm">
      <div class="flex items-center justify-between text-slate-500 mb-1">
        <span class="text-xs font-semibold">Total Produksi Solar</span>
        <span class="p-1.5 bg-amber-500/10 rounded-lg text-amber-500">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
        </span>
      </div>
      <div class="text-2xl font-black text-amber-500"><span id="sum-daily-kwh">0.00</span> <span class="text-xs font-normal text-slate-500">kWh</span></div>
      <p class="text-[11px] text-slate-400 mt-1">Dihasilkan pada <?= date('d M Y', strtotime($selectedDate)) ?></p>
    </div>

    <!-- Puncak Daya Solar PV -->
    <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-4 shadow-sm">
      <div class="flex items-center justify-between text-slate-500 mb-1">
        <span class="text-xs font-semibold">Puncak Daya Solar (Peak)</span>
        <span class="p-1.5 bg-rose-500/10 rounded-lg text-rose-500">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/></svg>
        </span>
      </div>
      <div class="text-2xl font-black text-rose-500"><span id="sum-peak-pv">0</span> <span class="text-xs font-normal text-slate-500">Watt</span></div>
      <p class="text-[11px] text-slate-400 mt-1">Daya instan tertinggi panel</p>
    </div>

    <!-- Rata-rata Beban Rumah -->
    <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-4 shadow-sm">
      <div class="flex items-center justify-between text-slate-500 mb-1">
        <span class="text-xs font-semibold">Beban Rata-rata Rumah</span>
        <span class="p-1.5 bg-cyan-500/10 rounded-lg text-cyan-500">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
        </span>
      </div>
      <div class="text-2xl font-black text-cyan-500"><span id="sum-avg-load">0</span> <span class="text-xs font-normal text-slate-500">Watt</span></div>
      <p class="text-[11px] text-slate-400 mt-1">Konsumsi rata-rata listrik</p>
    </div>

    <!-- Estimasi Penghematan Rp -->
    <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-4 shadow-sm">
      <div class="flex items-center justify-between text-slate-500 mb-1">
        <span class="text-xs font-semibold">Hemat Tagihan Listrik</span>
        <span class="p-1.5 bg-emerald-500/10 rounded-lg text-emerald-500">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        </span>
      </div>
      <div class="text-2xl font-black text-emerald-500">Rp <span id="sum-savings">0</span></div>
      <p class="text-[11px] text-slate-400 mt-1">Berdasarkan tarif PLN Rp <?= number_format(PLN_TARIFF_PER_KWH, 0, ',', '.') ?>/kWh</p>
    </div>

  </div>

  <!-- Grafik Kurva Per Jam (Solar PV vs Beban Rumah) -->
  <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-5 shadow-sm">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 mb-4">
      <div>
        <h2 class="text-base font-bold text-slate-900 dark:text-white">Kurva Daya 24 Jam (Solar PV vs Beban Rumah)</h2>
        <p class="text-xs text-slate-500">Grafik perbandingan produksi surya dengan pemakaian peralatan listrik</p>
      </div>
      <div class="flex items-center gap-3 text-xs">
        <span class="flex items-center gap-1.5"><span class="w-3 h-3 rounded-full bg-amber-500 inline-block"></span> Solar PV (W)</span>
        <span class="flex items-center gap-1.5"><span class="w-3 h-3 rounded-full bg-cyan-500 inline-block"></span> Beban Rumah (W)</span>
      </div>
    </div>
    
    <div class="h-80 w-full">
      <canvas id="chartDaily"></canvas>
    </div>
  </div>

  <!-- Tabel Rincian Data Jam per Jam -->
  <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-5 shadow-sm">
    <h3 class="text-sm font-bold text-slate-900 dark:text-white mb-3">Tabel Rincian Telemetri per Jam</h3>
    <div class="overflow-x-auto">
      <table class="w-full text-left text-xs">
        <thead class="bg-slate-50 dark:bg-slate-950 text-slate-600 dark:text-slate-400 border-b border-slate-200 dark:border-slate-800 font-semibold uppercase tracking-wider">
          <tr>
            <th class="px-4 py-3">Jam</th>
            <th class="px-4 py-3">Rata-rata Solar (W)</th>
            <th class="px-4 py-3">Puncak Solar (W)</th>
            <th class="px-4 py-3">Beban Rumah (W)</th>
            <th class="px-4 py-3">Rata-rata Baterai SOC</th>
            <th class="px-4 py-3">Voltase Baterai</th>
            <th class="px-4 py-3">Akumulasi Harian</th>
          </tr>
        </thead>
        <tbody id="table-hourly-body" class="divide-y divide-slate-100 dark:divide-slate-800 text-slate-700 dark:text-slate-300">
          <tr><td colspan="7" class="text-center py-6 text-slate-400">Memuat data harian...</td></tr>
        </tbody>
      </table>
    </div>
  </div>

</div>

<!-- Script Load Data & Render Chart -->
<script>
  let dailyChart = null;

  async function loadDailyReport() {
    const date = "<?= htmlspecialchars($selectedDate) ?>";
    try {
      const res = await fetch(`api.php?action=daily&date=${date}`);
      const json = await res.json();

      if (json.status === 'success') {
        const sum = json.summary;
        document.getElementById('sum-daily-kwh').innerText = parseFloat(sum.total_daily_kwh).toFixed(2);
        document.getElementById('sum-peak-pv').innerText = Math.round(sum.peak_pv_power);
        document.getElementById('sum-avg-load').innerText = Math.round(sum.avg_load_power);
        document.getElementById('sum-savings').innerText = Math.round(sum.savings_rp).toLocaleString('id-ID');

        const hours = [];
        const pvData = [];
        const loadData = [];
        const rows = json.hourly;

        // Siapkan array 24 jam (00 s/d 23)
        const hourMap = {};
        rows.forEach(r => { hourMap[r.hour] = r; });

        let tbodyHtml = '';

        for (let h = 0; h < 24; h++) {
          const hStr = h.toString().padStart(2, '0');
          hours.push(hStr + ':00');

          if (hourMap[hStr]) {
            const r = hourMap[hStr];
            pvData.push(Math.round(r.avg_pv_power));
            loadData.push(Math.round(r.avg_load_power));

            tbodyHtml += `
              <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-800/50">
                <td class="px-4 py-2.5 font-bold font-mono text-slate-900 dark:text-white">${hStr}:00</td>
                <td class="px-4 py-2.5 text-amber-500 font-semibold">${Math.round(r.avg_pv_power)} W</td>
                <td class="px-4 py-2.5 text-rose-500 font-semibold">${Math.round(r.peak_pv_power)} W</td>
                <td class="px-4 py-2.5 text-cyan-500 font-semibold">${Math.round(r.avg_load_power)} W</td>
                <td class="px-4 py-2.5 font-medium">${Math.round(r.avg_battery_soc)}%</td>
                <td class="px-4 py-2.5">${parseFloat(r.avg_battery_volt).toFixed(1)} V</td>
                <td class="px-4 py-2.5 font-bold text-emerald-500">${parseFloat(r.max_daily_kwh || 0).toFixed(3)} kWh</td>
              </tr>
            `;
          } else {
            pvData.push(0);
            loadData.push(0);
          }
        }

        if (rows.length > 0) {
          document.getElementById('table-hourly-body').innerHTML = tbodyHtml;
        } else {
          document.getElementById('table-hourly-body').innerHTML = `
            <tr><td colspan="7" class="text-center py-8 text-slate-400">
              Tidak ada data log pada tanggal ini. Klik <b>"Generate Data Contoh"</b> di menu dashboard jika ingin melihat simulasi grafik.
            </td></tr>
          `;
        }

        renderChart(hours, pvData, loadData);
      }
    } catch (e) {
      console.error(e);
    }
  }

  function renderChart(labels, pv, load) {
    const ctx = document.getElementById('chartDaily').getContext('2d');
    const isDark = document.documentElement.classList.contains('dark');
    const gridColor = isDark ? 'rgba(255, 255, 255, 0.08)' : 'rgba(0, 0, 0, 0.08)';
    const textColor = isDark ? '#94a3b8' : '#64748b';

    if (dailyChart) dailyChart.destroy();

    dailyChart = new Chart(ctx, {
      type: 'line',
      data: {
        labels: labels,
        datasets: [
          {
            label: 'Solar PV (Watt)',
            data: pv,
            borderColor: '#f59e0b',
            backgroundColor: 'rgba(245, 158, 11, 0.15)',
            fill: true,
            tension: 0.35,
            borderWidth: 2.5,
            pointRadius: 2
          },
          {
            label: 'Beban Rumah (Watt)',
            data: load,
            borderColor: '#06b6d4',
            backgroundColor: 'rgba(6, 182, 212, 0.05)',
            fill: true,
            tension: 0.35,
            borderWidth: 2,
            pointRadius: 2
          }
        ]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          legend: { display: false }
        },
        scales: {
          x: {
            grid: { color: gridColor },
            ticks: { color: textColor, font: { size: 11 } }
          },
          y: {
            grid: { color: gridColor },
            ticks: {
              color: textColor,
              callback: val => val + ' W',
              font: { size: 11 }
            }
          }
        }
      }
    });
  }

  loadDailyReport();
</script>

</body>
</html>

