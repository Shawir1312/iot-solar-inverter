<?php
/**
 * Generator Data Simulasi / Contoh
 * Mengisi database dengan riwayat 7 hari terakhir agar grafik langsung tampil indah!
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/database.php';

$pdo = getDbConnection();
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['generate'])) {
    // Generate data 7 hari terakhir
    $now = time();
    $totalRecords = 0;
    
    // Siapkan statement insert
    $stmt = $pdo->prepare("
        INSERT INTO solar_telemetry (
            created_at, operating_mode, status_code,
            pv_power, pv_voltage, pv_current,
            battery_voltage, battery_capacity, charging_current, discharge_current, bus_voltage,
            output_active_power, output_apparent_power, output_load_percent,
            ac_output_voltage, ac_output_freq,
            ac_input_voltage, mains_freq, mains_power,
            inverter_temp, boost_temp, trafo_temp, pv_temp, fan1_speed, fan2_speed,
            daily_kwh, monthly_kwh, yearly_kwh, total_kwh
        ) VALUES (
            :created_at, :operating_mode, :status_code,
            :pv_power, :pv_voltage, :pv_current,
            :battery_voltage, :battery_capacity, :charging_current, :discharge_current, :bus_voltage,
            :output_active_power, :output_apparent_power, :output_load_percent,
            :ac_output_voltage, :ac_output_freq,
            :ac_input_voltage, :mains_freq, :mains_power,
            :inverter_temp, :boost_temp, :trafo_temp, :pv_temp, :fan1_speed, :fan2_speed,
            :daily_kwh, :monthly_kwh, :yearly_kwh, :total_kwh
        )
    ");

    $baseLifetimeKwh = 3200.0;

    for ($d = 6; $d >= 0; $d--) {
        $dayTimestamp = strtotime("-$d days");
        $dateStr = date('Y-m-d', $dayTimestamp);
        $accumDailyKwh = 0.0;

        // Simulasi tiap 1 jam (24 jam)
        for ($h = 0; $h < 24; $h++) {
            $recordTime = sprintf("%s %02d:15:00", $dateStr, $h);
            
            // Profil Solar PV: Matahari terbit jam 06:00, puncak jam 12:00, terbenam jam 18:00
            $pvWatt = 0.0;
            $pvVolt = 0.0;
            $pvCurr = 0.0;
            if ($h >= 6 && $h <= 18) {
                // Kurva lonceng sinus
                $progress = ($h - 6) / 12.0; // 0 sampai 1
                $sinFactor = sin($progress * M_PI);
                $pvWatt = max(0.0, ($sinFactor * 2800.0) + (rand(-100, 100)));
                $pvVolt = 260.0 + (rand(-10, 10));
                $pvCurr = $pvWatt / max(1.0, $pvVolt);
                $accumDailyKwh += ($pvWatt / 1000.0);
            }

            // Profil Beban Rumah (Load): siang 800-1500W, malam 1200-2200W
            $loadWatt = 800.0 + (rand(0, 500));
            if ($h >= 18 || $h <= 5) {
                $loadWatt += 600.0 + (rand(0, 400));
            }
            $loadPct = intval(($loadWatt / 6500.0) * 100);

            // Profil Baterai 48V
            $soc = 85;
            $battVolt = 53.2;
            $chgCurr = 0.0;
            $disCurr = 0.0;

            if ($pvWatt > $loadWatt) {
                // Solar surplus -> Charge baterai
                $surplusWatt = $pvWatt - $loadWatt;
                $chgCurr = $surplusWatt / 52.0;
                $soc = min(100, 75 + intval($h * 2));
                $battVolt = 53.5 + (rand(0, 10) / 10.0);
            } else {
                // Defisit -> Baterai discharge
                $deficitWatt = $loadWatt - $pvWatt;
                $disCurr = $deficitWatt / 50.0;
                $soc = max(40, 95 - intval(($h > 18 ? ($h - 18) * 4 : ($h + 6) * 3)));
                $battVolt = 48.5 + (rand(0, 20) / 10.0);
            }

            $mode = ($pvWatt > 200 || $soc > 50) ? "Battery Mode (PLTS)" : "Line / Mains Mode (PLN)";
            $mainsPower = ($mode === "Line / Mains Mode (PLN)") ? $loadWatt : 0.0;

            $stmt->execute([
                ':created_at'           => $recordTime,
                ':operating_mode'       => $mode,
                ':status_code'          => '00',
                ':pv_power'             => round($pvWatt, 1),
                ':pv_voltage'           => round($pvVolt, 1),
                ':pv_current'           => round($pvCurr, 2),
                ':battery_voltage'      => round($battVolt, 1),
                ':battery_capacity'     => $soc,
                ':charging_current'     => round($chgCurr, 1),
                ':discharge_current'    => round($disCurr, 1),
                ':bus_voltage'          => 380.0 + rand(-5, 10),
                ':output_active_power'  => round($loadWatt, 1),
                ':output_apparent_power'=> round($loadWatt * 1.05, 1),
                ':output_load_percent'  => $loadPct,
                ':ac_output_voltage'    => 229.5 + (rand(-10, 10) / 10.0),
                ':ac_output_freq'       => 50.0,
                ':ac_input_voltage'     => 224.0 + (rand(-15, 15) / 10.0),
                ':mains_freq'           => 50.0,
                ':mains_power'          => round($mainsPower, 1),
                ':inverter_temp'        => 38.0 + ($pvWatt > 1500 ? 5.0 : 0.0) + (rand(-5, 5) / 10.0),
                ':boost_temp'           => 42.0 + (rand(-10, 10) / 10.0),
                ':trafo_temp'           => 40.0 + (rand(-5, 10) / 10.0),
                ':pv_temp'              => 44.0 + (rand(-10, 10) / 10.0),
                ':fan1_speed'           => $pvWatt > 1000 ? 60 : 35,
                ':fan2_speed'           => $pvWatt > 1000 ? 60 : 35,
                ':daily_kwh'            => round($accumDailyKwh, 3),
                ':monthly_kwh'          => round(180.0 + ($d * 14.5) + $accumDailyKwh, 1),
                ':yearly_kwh'           => round(1650.0 + ($d * 14.5) + $accumDailyKwh, 1),
                ':total_kwh'            => round($baseLifetimeKwh + ($d * 14.5) + $accumDailyKwh, 1),
            ]);
            $totalRecords++;
        }
    }

    $message = "Berhasil membuat {$totalRecords} data simulasi (7 hari terakhir)! Silakan buka menu Laporan Harian atau Bulanan untuk melihat grafik.";
}

$pageTitle = "Generator Data Simulasi • " . APP_NAME;
require_once __DIR__ . '/navbar.php';
?>

<div class="max-w-2xl mx-auto px-4 py-12">
  <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl p-6 sm:p-8 shadow-md text-center">
    
    <div class="w-16 h-16 bg-amber-500/10 dark:bg-amber-500/20 text-amber-500 rounded-2xl flex items-center justify-center mx-auto mb-4">
      <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
    </div>

    <h2 class="text-xl font-bold text-slate-900 dark:text-white mb-2">Generator Data Percobaan (Simulasi)</h2>
    <p class="text-xs sm:text-sm text-slate-500 dark:text-slate-400 mb-6 leading-relaxed">
      Gunakan fitur ini untuk langsung mengisi database SQLite dengan data telemetri realistis 7 hari ke belakang (kurva produksi matahari, pemakaian beban rumah, baterai 48V, suhu, dan kWh), sehingga Anda bisa langsung menguji seluruh grafik harian dan bulanan tanpa harus menunggu perangkat ESP32 dirakit.
    </p>

    <?php if ($message): ?>
      <div class="mb-6 p-4 rounded-xl bg-emerald-500/10 border border-emerald-500/20 text-emerald-600 dark:text-emerald-400 text-xs sm:text-sm font-medium">
        <?= htmlspecialchars($message) ?>
      </div>
      <div class="flex flex-wrap justify-center gap-3 mb-6">
        <a href="index.php" class="px-4 py-2 bg-slate-900 dark:bg-white text-white dark:text-slate-900 rounded-xl text-xs font-semibold">Buka Realtime Monitor</a>
        <a href="daily.php" class="px-4 py-2 bg-amber-500 hover:bg-amber-600 text-white rounded-xl text-xs font-semibold">Buka Laporan Harian</a>
        <a href="monthly.php" class="px-4 py-2 bg-emerald-500 hover:bg-emerald-600 text-white rounded-xl text-xs font-semibold">Buka Laporan Bulanan</a>
      </div>
    <?php endif; ?>

    <form method="POST">
      <button type="submit" name="generate" class="w-full sm:w-auto px-6 py-3 bg-amber-500 hover:bg-amber-600 text-white rounded-xl text-sm font-bold shadow-lg shadow-amber-500/25 transition-all">
        ⚡ Buat Data Simulasi Sekarang
      </button>
    </form>

    <div class="mt-6 text-xs text-slate-400">
      Catatan: Data simulasi tidak akan merusak skema database Anda.
    </div>

  </div>
</div>

</body>
</html>

