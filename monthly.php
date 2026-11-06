<?php
require_once __DIR__ . '/config.php';
$pageTitle = "Laporan Pemakaian Bulanan • " . (defined('APP_NAME') ? APP_NAME : 'PowMr 6.5kW Monitor');
require_once __DIR__ . '/navbar.php';

$selectedMonth = $_GET['month'] ?? date('Y-m'); // Format YYYY-MM
?>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 space-y-6">

  <!-- Header & Month Picker -->
  <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-4 shadow-sm flex flex-col md:flex-row md:items-center justify-between gap-4">
    <div>
      <h1 class="text-lg md:text-xl font-bold text-slate-900 dark:text-white flex items-center gap-2">
        <svg class="w-6 h-6 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
        Laporan Pemakaian & Produksi Bulanan
      </h1>
      <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Analisis akumulasi energi kWh harian, tren bulanan, dan total penghematan listrik PLN</p>
    </div>

    <!-- Filter Bulan -->
    <form method="GET" class="flex items-center gap-2">
      <label class="text-xs font-semibold text-slate-600 dark:text-slate-300">Pilih Bulan:</label>
      <input type="month" name="month" value="<?= htmlspecialchars($selectedMonth) ?>" class="bg-slate-100 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 text-slate-800 dark:text-slate-200 rounded-xl px-3 py-1.5 text-xs font-medium focus:outline-none focus:border-amber-500" onchange="this.form.submit()">
      <button type="submit" class="px-3 py-1.5 bg-emerald-500 hover:bg-emerald-600 text-white rounded-xl text-xs font-semibold transition-colors">
        Tampilkan
      </button>
    </form>
  </div>

  <!-- Summary KPI Cards -->
  <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
    
    <!-- Total Produksi Bulan Ini -->
    <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-4 shadow-sm">
      <div class="flex items-center justify-between text-slate-500 mb-1">
        <span class="text-xs font-semibold">Total Produksi Solar</span>
        <span class="p-1.5 bg-emerald-500/10 rounded-lg text-emerald-500">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
        </span>
      </div>
      <div class="text-2xl font-black text-emerald-500"><span id="sum-month-kwh">0.0</span> <span class="text-xs font-normal text-slate-500">kWh</span></div>
      <p class="text-[11px] text-slate-400 mt-1">Periode <?= date('F Y', strtotime($selectedMonth . '-01')) ?></p>
    </div>

    <!-- Estimasi Penghematan Tagihan Listrik -->
    <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-4 shadow-sm">
      <div class="flex items-center justify-between text-slate-500 mb-1">
        <span class="text-xs font-semibold">Total Hemat Biaya PLN</span>
        <span class="p-1.5 bg-amber-500/10 rounded-lg text-amber-500">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        </span>
      </div>
      <div class="text-2xl font-black text-amber-500">Rp <span id="sum-month-savings">0</span></div>
      <p class="text-[11px] text-slate-400 mt-1">Tarif Rp <?= number_format(PLN_TARIFF_PER_KWH, 0, ',', '.') ?>/kWh</p>
    </div>

    <!-- Rata-rata Harian -->
    <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-4 shadow-sm">
      <div class="flex items-center justify-between text-slate-500 mb-1">
        <span class="text-xs font-semibold">Rata-rata Produksi</span>
        <span class="p-1.5 bg-cyan-500/10 rounded-lg text-cyan-500">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/></svg>
        </span>
      </div>
      <div class="text-2xl font-black text-cyan-500"><span id="sum-avg-daily">0.0</span> <span class="text-xs font-normal text-slate-500">kWh/hari</span></div>
      <p class="text-[11px] text-slate-400 mt-1">Rata-rata hari aktif</p>
    </div>

    <!-- Hari Tertinggi -->
    <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-4 shadow-sm">
      <div class="flex items-center justify-between text-slate-500 mb-1">
        <span class="text-xs font-semibold">Produksi Tertinggi</span>
        <span class="p-1.5 bg-purple-500/10 rounded-lg text-purple-500">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z"/></svg>
        </span>
      </div>
      <div class="text-2xl font-black text-purple-500"><span id="sum-best-day-kwh">0.0</span> <span class="text-xs font-normal text-slate-500">kWh</span></div>
      <p class="text-[11px] text-slate-400 mt-1">Tercapai tgl <span id="sum-best-date">-</span></p>
    </div>

  </div>

  <!-- Bar Chart: Produksi Solar per Hari -->
  <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-5 shadow-sm">
    <div class="flex items-center justify-between mb-4">
      <div>
        <h2 class="text-base font-bold text-slate-900 dark:text-white">Diagram Batang Produksi Surya Harian (kWh)</h2>
        <p class="text-xs text-slate-500">Performa energi per tanggal di bulan <?= date('F Y', strtotime($selectedMonth . '-01')) ?></p>
      </div>
    </div>
    
    <div class="h-80 w-full">
      <canvas id="chartMonthly"></canvas>
    </div>
  </div>

  <!-- Tabel Rincian Data Harian Bulan Ini -->
  <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-5 shadow-sm">
    <h3 class="text-sm font-bold text-slate-900 dark:text-white mb-3">Tabel Rincian Produksi per Tanggal</h3>
    <div class="overflow-x-auto">
      <table class="w-full text-left text-xs">
        <thead class="bg-slate-50 dark:bg-slate-950 text-slate-600 dark:text-slate-400 border-b border-slate-200 dark:border-slate-800 font-semibold uppercase tracking-wider">
          <tr>
            <th class="px-4 py-3">Tanggal</th>
            <th class="px-4 py-3">Produksi Solar (kWh)</th>
            <th class="px-4 py-3">Puncak Daya PV (W)</th>
            <th class="px-4 py-3">Beban Rumah Rata-rata (W)</th>
            <th class="px-4 py-3">Estimasi Penghematan (Rp)</th>
          </tr>
        </thead>
        <tbody id="table-monthly-body" class="divide-y divide-slate-100 dark:divide-slate-800 text-slate-700 dark:text-slate-300">
          <tr><td colspan="5" class="text-center py-6 text-slate-400">Memuat data bulanan...</td></tr>
        </tbody>
      </table>
    </div>
  </div>

</div>

<!-- Script Load Data & Render Bar Chart -->
<script>
  let monthlyChart = null;
  const TARIFF_PLN = <?= PLN_TARIFF_PER_KWH ?>;

  async function loadMonthlyReport() {
    const month = "<?= htmlspecialchars($selectedMonth) ?>";
    try {
      const res = await fetch(`api.php?action=monthly&month=${month}`);
      const json = await res.json();

      if (json.status === 'success') {
        const days = json.days || [];
        const totalKwh = parseFloat(json.total_month_kwh || 0);
        const savingsRp = parseFloat(json.savings_rp || 0);

        document.getElementById('sum-month-kwh').innerText = totalKwh.toFixed(1);
        document.getElementById('sum-month-savings').innerText = Math.round(savingsRp).toLocaleString('id-ID');

        let maxKwh = 0;
        let bestDate = '-';
        let tbodyHtml = '';

        const labels = [];
        const dataKwh = [];

        days.forEach(d => {
          const kwh = parseFloat(d.daily_kwh || 0);
          labels.push("Tgl " + d.day);
          dataKwh.push(kwh);

          if (kwh > maxKwh) {
            maxKwh = kwh;
            bestDate = d.full_date;
          }

          const daySavings = kwh * TARIFF_PLN;

          tbodyHtml += `
            <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-800/50">
              <td class="px-4 py-2.5 font-bold font-mono text-slate-900 dark:text-white">${d.full_date}</td>
              <td class="px-4 py-2.5 text-emerald-500 font-bold text-sm">${kwh.toFixed(2)} kWh</td>
              <td class="px-4 py-2.5 text-amber-500 font-medium">${Math.round(d.peak_pv_power)} W</td>
              <td class="px-4 py-2.5 text-cyan-500 font-medium">${Math.round(d.avg_load_power)} W</td>
              <td class="px-4 py-2.5 font-semibold text-slate-900 dark:text-slate-200">Rp ${Math.round(daySavings).toLocaleString('id-ID')}</td>
            </tr>
          `;
        });

        const avgDaily = days.length > 0 ? (totalKwh / days.length) : 0;
        document.getElementById('sum-avg-daily').innerText = avgDaily.toFixed(1);
        document.getElementById('sum-best-day-kwh').innerText = maxKwh.toFixed(1);
        document.getElementById('sum-best-date').innerText = bestDate;

        if (days.length > 0) {
          document.getElementById('table-monthly-body').innerHTML = tbodyHtml;
        } else {
          document.getElementById('table-monthly-body').innerHTML = `
            <tr><td colspan="5" class="text-center py-8 text-slate-400">
              Belum ada rekaman data di bulan ini. Gunakan tombol <b>"Generate Data Contoh"</b> di menu dashboard jika ingin melihat visualisasi grafik bulanan.
            </td></tr>
          `;
        }

        renderBarChart(labels, dataKwh);
      }
    } catch (e) {
      console.error(e);
    }
  }

  function renderBarChart(labels, data) {
    const ctx = document.getElementById('chartMonthly').getContext('2d');
    const isDark = document.documentElement.classList.contains('dark');
    const gridColor = isDark ? 'rgba(255, 255, 255, 0.08)' : 'rgba(0, 0, 0, 0.08)';
    const textColor = isDark ? '#94a3b8' : '#64748b';

    if (monthlyChart) monthlyChart.destroy();

    monthlyChart = new Chart(ctx, {
      type: 'bar',
      data: {
        labels: labels,
        datasets: [{
          label: 'Produksi Harian (kWh)',
          data: data,
          backgroundColor: '#10b981',
          hoverBackgroundColor: '#059669',
          borderRadius: 6
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          legend: { display: false }
        },
        scales: {
          x: {
            grid: { display: false },
            ticks: { color: textColor, font: { size: 11 } }
          },
          y: {
            grid: { color: gridColor },
            ticks: {
              color: textColor,
              callback: val => val + ' kWh',
              font: { size: 11 }
            }
          }
        }
      }
    });
  }

  loadMonthlyReport();
</script>

</body>
</html>

