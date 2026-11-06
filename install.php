<?php
/**
 * 1-Click Installer Database phpMyAdmin (MySQL / MariaDB)
 * Aplikasi Monitoring Solar Inverter PowMr 6.5kW
 */

$pageTitle = "Instalasi Database phpMyAdmin (1-Klik)";
$error = '';
$success = false;
$steps = [];

// Nilai default untuk XAMPP / phpMyAdmin lokal
$dbHost = $_POST['db_host'] ?? 'localhost';
$dbPort = $_POST['db_port'] ?? '3306';
$dbUser = $_POST['db_user'] ?? 'root';
$dbPass = $_POST['db_pass'] ?? '';
$dbName = $_POST['db_name'] ?? 'powmr_monitoring';
$fillSample = isset($_POST['fill_sample']);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['install'])) {
    try {
        // 1. Tes koneksi ke MySQL Server
        $dsnServer = "mysql:host={$dbHost};port={$dbPort};charset=utf8mb4";
        $pdoServer = new PDO($dsnServer, $dbUser, $dbPass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]);
        $steps[] = "Terhubung ke server MySQL ({$dbHost}:{$dbPort})";

        // 2. Buat Database jika belum ada
        $pdoServer->exec("CREATE DATABASE IF NOT EXISTS `{$dbName}` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;");
        $steps[] = "Database <b>`{$dbName}`</b> berhasil dibuat / dipastikan siap di phpMyAdmin";

        // 3. Masuk ke Database dan buat tabel
        $dsnDb = "mysql:host={$dbHost};port={$dbPort};dbname={$dbName};charset=utf8mb4";
        $pdo = new PDO($dsnDb, $dbUser, $dbPass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]);

        $createTableSql = "
        CREATE TABLE IF NOT EXISTS `solar_telemetry` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
            `operating_mode` VARCHAR(64),
            `status_code` VARCHAR(16),
            `pv_power` FLOAT,
            `pv_voltage` FLOAT,
            `pv_current` FLOAT,
            `battery_voltage` FLOAT,
            `battery_capacity` INT,
            `charging_current` FLOAT,
            `discharge_current` FLOAT,
            `bus_voltage` FLOAT,
            `output_active_power` FLOAT,
            `output_apparent_power` FLOAT,
            `output_load_percent` INT,
            `ac_output_voltage` FLOAT,
            `ac_output_freq` FLOAT,
            `ac_input_voltage` FLOAT,
            `mains_freq` FLOAT,
            `mains_power` FLOAT,
            `inverter_temp` FLOAT,
            `boost_temp` FLOAT,
            `trafo_temp` FLOAT,
            `pv_temp` FLOAT,
            `fan1_speed` INT,
            `fan2_speed` INT,
            `daily_kwh` FLOAT,
            `monthly_kwh` FLOAT,
            `yearly_kwh` FLOAT,
            `total_kwh` FLOAT,
            INDEX `idx_created_at` (`created_at`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ";
        $pdo->exec($createTableSql);
        $steps[] = "Tabel <b>`solar_telemetry`</b> dan indeks performa berhasil dibuat";

        // 4. Isi data sampel jika dicentang
        if ($fillSample) {
            $checkCount = $pdo->query("SELECT COUNT(*) FROM `solar_telemetry`")->fetchColumn();
            if ($checkCount == 0) {
                $stmtInsert = $pdo->prepare("
                    INSERT INTO `solar_telemetry` (
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

                $baseLifetimeKwh = 3400.0;
                for ($d = 6; $d >= 0; $d--) {
                    $dateStr = date('Y-m-d', strtotime("-$d days"));
                    $accumDailyKwh = 0.0;
                    for ($h = 0; $h < 24; $h++) {
                        $recordTime = sprintf("%s %02d:15:00", $dateStr, $h);
                        $pvWatt = 0.0; $pvVolt = 0.0; $pvCurr = 0.0;
                        if ($h >= 6 && $h <= 18) {
                            $progress = ($h - 6) / 12.0;
                            $pvWatt = max(0.0, (sin($progress * M_PI) * 2900.0) + rand(-100, 100));
                            $pvVolt = 265.0 + rand(-10, 10);
                            $pvCurr = $pvWatt / max(1.0, $pvVolt);
                            $accumDailyKwh += ($pvWatt / 1000.0);
                        }
                        $loadWatt = 850.0 + rand(0, 500) + (($h >= 18 || $h <= 5) ? 650.0 : 0.0);
                        $loadPct = intval(($loadWatt / 6500.0) * 100);
                        $soc = ($pvWatt > $loadWatt) ? min(100, 75 + intval($h * 2)) : max(40, 95 - intval($h * 2.5));
                        $battVolt = ($pvWatt > $loadWatt) ? 53.6 : 49.2;
                        $chgCurr = ($pvWatt > $loadWatt) ? ($pvWatt - $loadWatt) / 52.0 : 0.0;
                        $disCurr = ($pvWatt < $loadWatt) ? ($loadWatt - $pvWatt) / 50.0 : 0.0;
                        $mode = ($pvWatt > 200 || $soc > 50) ? "Battery Mode (PLTS)" : "Line / Mains Mode (PLN)";

                        $stmtInsert->execute([
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
                            ':bus_voltage'          => 385.0 + rand(-5, 10),
                            ':output_active_power'  => round($loadWatt, 1),
                            ':output_apparent_power'=> round($loadWatt * 1.05, 1),
                            ':output_load_percent'  => $loadPct,
                            ':ac_output_voltage'    => 229.8 + (rand(-10, 10) / 10.0),
                            ':ac_output_freq'       => 50.0,
                            ':ac_input_voltage'     => 224.5 + (rand(-10, 10) / 10.0),
                            ':mains_freq'           => 50.0,
                            ':mains_power'          => ($mode === "Line / Mains Mode (PLN)") ? round($loadWatt, 1) : 0.0,
                            ':inverter_temp'        => 38.5 + (rand(-5, 5) / 10.0),
                            ':boost_temp'           => 42.0 + (rand(-10, 10) / 10.0),
                            ':trafo_temp'           => 40.0 + (rand(-5, 10) / 10.0),
                            ':pv_temp'              => 44.5 + (rand(-10, 10) / 10.0),
                            ':fan1_speed'           => $pvWatt > 1000 ? 60 : 35,
                            ':fan2_speed'           => $pvWatt > 1000 ? 60 : 35,
                            ':daily_kwh'            => round($accumDailyKwh, 3),
                            ':monthly_kwh'          => round(210.0 + ($d * 14.5) + $accumDailyKwh, 1),
                            ':yearly_kwh'           => round(1820.0 + ($d * 14.5) + $accumDailyKwh, 1),
                            ':total_kwh'            => round($baseLifetimeKwh + ($d * 14.5) + $accumDailyKwh, 1),
                        ]);
                    }
                }
                $steps[] = "168 baris data contoh 7 hari terakhir berhasil di-generate";
            }
        }

        // 5. Update file config.php otomatis ke MySQL
        $configFile = __DIR__ . '/config.php';
        $configContent = "<?php
/**
 * Konfigurasi Utama Aplikasi Monitoring Solar Inverter PowMr 6.5kW
 * Otomatis Dikonfigurasi oleh install.php pada " . date('Y-m-d H:i:s') . "
 */

// Zona Waktu
date_default_timezone_set('Asia/Jakarta');

// Kunci Keamanan API (Harus sama dengan API_KEY di sketch Arduino IDE)
define('API_SECRET_KEY', 'powmr_secret_key_123');

// Pengaturan Database: MySQL / phpMyAdmin
define('DB_DRIVER', 'mysql'); 

// Koneksi MySQL / MariaDB (phpMyAdmin)
define('DB_HOST', '{$dbHost}');
define('DB_PORT', '{$dbPort}');
define('DB_NAME', '{$dbName}');
define('DB_USER', '{$dbUser}');
define('DB_PASS', '{$dbPass}');

// Tarif Listrik PLN per kWh (Tarif R1 1300-2200VA: Rp 1.444,70)
define('PLN_TARIFF_PER_KWH', 1444.70);

// Akun Admin Dashboard
define('ADMIN_USER', 'admin');
define('ADMIN_PASSWORD', 'admin123');
define('REQUIRE_LOGIN_TO_VIEW', false);

// Nama Sistem
define('APP_NAME', 'PowMr 6.5kW Solar Energy Monitor');
define('APP_DEVICE_MODEL', 'POW-HVM6.5K-48V-E');
define('APP_VERSION', '2.0.0');
";
        file_put_contents($configFile, $configContent);
        $steps[] = "File <b>`config.php`</b> berhasil diperbarui dengan koneksi MySQL";

        $success = true;

    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="id" class="dark">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= htmlspecialchars($pageTitle) ?></title>
  <script src="https://cdn.tailwindcss.com"></script>
  <script>
    tailwind.config = {
      darkMode: 'class',
      theme: {
        extend: {
          colors: {
            solar: { 500: '#f59e0b', 600: '#d97706' }
          }
        }
      }
    }
    if (localStorage.theme === 'light') {
      document.documentElement.classList.remove('dark');
    }
  </script>
</head>
<body class="bg-slate-50 dark:bg-slate-950 text-slate-800 dark:text-slate-100 min-h-screen flex items-center justify-center p-4">

  <div class="max-w-xl w-full bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl p-6 sm:p-8 shadow-xl">
    
    <!-- Header -->
    <div class="text-center mb-6">
      <div class="inline-flex p-3 bg-amber-500/10 text-amber-500 rounded-2xl mb-3">
        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4m0 5c0 2.21-3.582 4-8 4s-8-1.79-8-4" />
        </svg>
      </div>
      <h1 class="text-xl sm:text-2xl font-black text-slate-900 dark:text-white">Installer Database phpMyAdmin</h1>
      <p class="text-xs sm:text-sm text-slate-500 dark:text-slate-400 mt-1">1-Klik Otomatis Buat Database & Tabel di MySQL / XAMPP</p>
    </div>

    <?php if ($success): ?>
      <!-- Tampilan Berhasil -->
      <div class="space-y-4">
        <div class="p-4 bg-emerald-500/10 border border-emerald-500/30 rounded-2xl text-emerald-600 dark:text-emerald-400">
          <div class="flex items-center gap-2 font-bold text-sm mb-2">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
            Instalasi Database Sukses!
          </div>
          <ul class="text-xs space-y-1.5 list-disc list-inside">
            <?php foreach ($steps as $st): ?>
              <li><?= $st ?></li>
            <?php endforeach; ?>
          </ul>
        </div>

        <div class="p-3 bg-slate-100 dark:bg-slate-800 rounded-xl text-xs text-slate-600 dark:text-slate-400">
          Database <b><?= htmlspecialchars($dbName) ?></b> sekarang sudah bisa dilihat dan dikelola langsung lewat 
          <a href="http://localhost/phpmyadmin" target="_blank" class="text-amber-500 hover:underline font-bold">phpMyAdmin (klik di sini)</a>.
        </div>

        <div class="pt-2 flex flex-col sm:flex-row gap-3">
          <a href="index.php" class="flex-1 text-center py-3 bg-amber-500 hover:bg-amber-600 text-white font-bold rounded-xl text-sm shadow-lg shadow-amber-500/25 transition-all">
            ⚡ Buka Dashboard Realtime
          </a>
          <a href="daily.php" class="flex-1 text-center py-3 bg-slate-200 dark:bg-slate-800 hover:bg-slate-300 dark:hover:bg-slate-700 text-slate-800 dark:text-slate-200 font-semibold rounded-xl text-sm transition-all">
            📅 Cek Pemakaian Harian
          </a>
        </div>
      </div>

    <?php else: ?>

      <?php if ($error): ?>
        <div class="mb-5 p-4 bg-rose-500/10 border border-rose-500/30 rounded-2xl text-rose-600 dark:text-rose-400 text-xs">
          <b>Gagal Menghubungkan:</b> <?= htmlspecialchars($error) ?>
          <p class="mt-1 text-slate-500">Pastikan service MySQL / Apache di XAMPP Control Panel sudah aktif (Running).</p>
        </div>
      <?php endif; ?>

      <!-- Form Instalasi 1-Klik -->
      <form method="POST" class="space-y-4 text-xs">
        
        <div>
          <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Host MySQL Server</label>
          <input type="text" name="db_host" value="<?= htmlspecialchars($dbHost) ?>" required class="w-full bg-slate-50 dark:bg-slate-950 border border-slate-300 dark:border-slate-700 rounded-xl px-3.5 py-2 text-slate-900 dark:text-slate-100 focus:outline-none focus:border-amber-500 font-mono">
          <span class="text-[11px] text-slate-400">Default: localhost</span>
        </div>

        <div class="grid grid-cols-2 gap-3">
          <div>
            <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Username phpMyAdmin</label>
            <input type="text" name="db_user" value="<?= htmlspecialchars($dbUser) ?>" required class="w-full bg-slate-50 dark:bg-slate-950 border border-slate-300 dark:border-slate-700 rounded-xl px-3.5 py-2 text-slate-900 dark:text-slate-100 focus:outline-none focus:border-amber-500 font-mono">
            <span class="text-[11px] text-slate-400">Default XAMPP: root</span>
          </div>
          <div>
            <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Password</label>
            <input type="password" name="db_pass" value="<?= htmlspecialchars($dbPass) ?>" placeholder="(kosongkan jika tanpa password)" class="w-full bg-slate-50 dark:bg-slate-950 border border-slate-300 dark:border-slate-700 rounded-xl px-3.5 py-2 text-slate-900 dark:text-slate-100 focus:outline-none focus:border-amber-500 font-mono">
            <span class="text-[11px] text-slate-400">Default XAMPP: kosong</span>
          </div>
        </div>

        <div>
          <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Nama Database Baru</label>
          <input type="text" name="db_name" value="<?= htmlspecialchars($dbName) ?>" required class="w-full bg-slate-50 dark:bg-slate-950 border border-slate-300 dark:border-slate-700 rounded-xl px-3.5 py-2 text-slate-900 dark:text-slate-100 focus:outline-none focus:border-amber-500 font-mono">
          <span class="text-[11px] text-slate-400">Database ini otomatis dibuatkan di phpMyAdmin Anda</span>
        </div>

        <!-- Checkbox Data Sampel -->
        <div class="p-3.5 bg-slate-100 dark:bg-slate-950/70 border border-slate-200 dark:border-slate-800 rounded-2xl flex items-center gap-3">
          <input type="checkbox" id="fill_sample" name="fill_sample" checked class="w-4 h-4 rounded text-amber-500 focus:ring-amber-500 border-slate-300 dark:border-slate-700">
          <label for="fill_sample" class="text-xs text-slate-700 dark:text-slate-300 cursor-pointer">
            <b class="block text-slate-900 dark:text-white">Isi Data Simulasi 7 Hari Terakhir</b>
            Membuat grafik harian & bulanan langsung tampil dengan data realistis
          </label>
        </div>

        <button type="submit" name="install" class="w-full py-3 bg-amber-500 hover:bg-amber-600 text-white font-bold rounded-xl text-sm shadow-lg shadow-amber-500/25 transition-all mt-2">
          ⚡ Mulai Install Database Sekarang (1-Klik)
        </button>

      </form>

    <?php endif; ?>

  </div>

</body>
</html>

