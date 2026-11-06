<?php
require_once __DIR__ . '/config.php';
$pageTitle = "Riwayat Log Telemetri • " . (defined('APP_NAME') ? APP_NAME : 'PowMr 6.5kW Monitor');
require_once __DIR__ . '/navbar.php';

// Handle Export CSV
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    require_once __DIR__ . '/database.php';
    $pdo = getDbConnection();
    $stmt = $pdo->query("SELECT * FROM solar_telemetry ORDER BY id DESC LIMIT 5000");
    $rows = $stmt->fetchAll();

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=solar_telemetry_export_' . date('Ymd_His') . '.csv');

    $output = fopen('php://output', 'w');
    if (!empty($rows)) {
        fputcsv($output, array_keys($rows[0]));
        foreach ($rows as $row) {
            fputcsv($output, $row);
        }
    }
    fclose($output);
    exit;
}
?>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 space-y-6">

  <!-- Header -->
  <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-4 shadow-sm flex flex-col md:flex-row md:items-center justify-between gap-4">
    <div>
      <h1 class="text-lg md:text-xl font-bold text-slate-900 dark:text-white flex items-center gap-2">
        <svg class="w-6 h-6 text-cyan-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
        Riwayat Log Telemetri Lengkap
      </h1>
      <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Daftar rekaman data mentah inverter yang dikirim oleh ESP32-C3 Mini</p>
    </div>

    <div class="flex items-center gap-3">
      <a href="history.php?export=csv" class="px-3.5 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-xs font-semibold flex items-center gap-1.5 transition-colors shadow-sm">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
        Download CSV
      </a>
      <button onclick="loadHistory()" class="px-3 py-1.5 bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 rounded-xl text-xs font-medium border border-slate-300 dark:border-slate-700 transition-colors">
        Refresh
      </button>
    </div>
  </div>

  <!-- Tabel Riwayat Data -->
  <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-5 shadow-sm">
    <div class="overflow-x-auto">
      <table class="w-full text-left text-xs whitespace-nowrap">
        <thead class="bg-slate-50 dark:bg-slate-950 text-slate-600 dark:text-slate-400 border-b border-slate-200 dark:border-slate-800 font-semibold uppercase tracking-wider">
          <tr>
            <th class="px-3.5 py-3">Waktu (Log)</th>
            <th class="px-3.5 py-3">Mode Inverter</th>
            <th class="px-3.5 py-3">Solar PV (W)</th>
            <th class="px-3.5 py-3">Volt / Arus PV</th>
            <th class="px-3.5 py-3">Baterai 48V</th>
            <th class="px-3.5 py-3">Arus Baterai</th>
            <th class="px-3.5 py-3">Beban Rumah</th>
            <th class="px-3.5 py-3">Volt AC Rumah</th>
            <th class="px-3.5 py-3">PLN Grid</th>
            <th class="px-3.5 py-3">Suhu Inti</th>
            <th class="px-3.5 py-3">Kipas</th>
            <th class="px-3.5 py-3">Harian (kWh)</th>
            <th class="px-3.5 py-3">Total (kWh)</th>
          </tr>
        </thead>
        <tbody id="table-history-body" class="divide-y divide-slate-100 dark:divide-slate-800 text-slate-700 dark:text-slate-300">
          <tr><td colspan="13" class="text-center py-8 text-slate-400">Memuat log telemetri...</td></tr>
        </tbody>
      </table>
    </div>
  </div>

</div>

<!-- Script Load History Data -->
<script>
  async function loadHistory() {
    try {
      const res = await fetch('api.php?action=history&limit=150');
      const json = await res.json();

      if (json.status === 'success' && json.data.length > 0) {
        let html = '';
        json.data.forEach(r => {
          const chg = parseFloat(r.charging_current);
          const dis = parseFloat(r.discharge_current);
          let battCurrText = '0.0 A';
          let battCurrColor = 'text-slate-400';
          if (chg > 0) {
            battCurrText = '+' + chg.toFixed(1) + ' A';
            battCurrColor = 'text-emerald-500 font-semibold';
          } else if (dis > 0) {
            battCurrText = '-' + dis.toFixed(1) + ' A';
            battCurrColor = 'text-amber-500 font-semibold';
          }

          html += `
            <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-800/50">
              <td class="px-3.5 py-2 font-mono text-[11px] text-slate-500">${r.created_at}</td>
              <td class="px-3.5 py-2 font-medium">${r.operating_mode}</td>
              <td class="px-3.5 py-2 font-bold text-amber-500">${Math.round(r.pv_power)} W</td>
              <td class="px-3.5 py-2 text-slate-500">${parseFloat(r.pv_voltage).toFixed(1)}V / ${parseFloat(r.pv_current).toFixed(1)}A</td>
              <td class="px-3.5 py-2 font-bold text-emerald-500">${r.battery_capacity}% (${parseFloat(r.battery_voltage).toFixed(1)}V)</td>
              <td class="px-3.5 py-2 ${battCurrColor}">${battCurrText}</td>
              <td class="px-3.5 py-2 font-bold text-cyan-500">${Math.round(r.output_active_power)} W (${r.output_load_percent}%)</td>
              <td class="px-3.5 py-2 text-slate-500">${parseFloat(r.ac_output_voltage).toFixed(1)} V</td>
              <td class="px-3.5 py-2 text-blue-500">${Math.round(r.mains_power)} W (${parseFloat(r.ac_input_voltage).toFixed(1)}V)</td>
              <td class="px-3.5 py-2">${parseFloat(r.inverter_temp).toFixed(1)} °C</td>
              <td class="px-3.5 py-2">${r.fan1_speed}%</td>
              <td class="px-3.5 py-2 font-bold text-emerald-500">${parseFloat(r.daily_kwh).toFixed(3)}</td>
              <td class="px-3.5 py-2 font-bold text-purple-500">${parseFloat(r.total_kwh).toFixed(1)}</td>
            </tr>
          `;
        });
        document.getElementById('table-history-body').innerHTML = html;
      } else {
        document.getElementById('table-history-body').innerHTML = `
          <tr><td colspan="13" class="text-center py-8 text-slate-400">
            Belum ada data log yang tersimpan di database.
          </td></tr>
        `;
      }
    } catch (e) {
      console.error(e);
    }
  }

  loadHistory();
</script>

</body>
</html>

