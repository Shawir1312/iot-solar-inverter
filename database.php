<?php
/**
 * Pengelola Koneksi Database MySQL (MariaDB / phpMyAdmin)
 * Otomatis inisialisasi database dan tabel solar_telemetry
 * Lokasi: Root Directory
 */

require_once __DIR__ . '/config.php';

$GLOBALS['db_last_error'] = '';

function getDbLastError() {
    return $GLOBALS['db_last_error'] ?? '';
}

function getDbConnection() {
    static $pdo = null;
    if ($pdo !== null) {
        return $pdo;
    }

    try {
        $port = defined('DB_PORT') ? DB_PORT : '3306';
        
        // 1. Coba koneksi langsung ke database MySQL yang sudah ada
        try {
            $dsnDb = "mysql:host=" . DB_HOST . ";port=" . $port . ";dbname=" . DB_NAME . ";charset=utf8mb4";
            $pdo = new PDO($dsnDb, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);
        } catch (PDOException $e) {
            // Jika database belum ada di MySQL server, konek ke server lalu buat database otomatis
            $dsnServer = "mysql:host=" . DB_HOST . ";port=" . $port . ";charset=utf8mb4";
            $pdo = new PDO($dsnServer, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]);
            $pdo->exec("CREATE DATABASE IF NOT EXISTS `" . DB_NAME . "` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
            $pdo->exec("USE `" . DB_NAME . "`");
        }

        // 2. Inisialisasi Tabel MySQL solar_telemetry, inverter_settings, inverter_commands
        // Menggunakan flag file untuk menghindari DDL lock di setiap request
        $initFlag = __DIR__ . '/.db_initialized';
        $settingsFlag = __DIR__ . '/.db_settings_initialized';

        if (!file_exists($initFlag)) {
            $initSql = "
            CREATE TABLE IF NOT EXISTS `solar_telemetry` (
                `id` INT AUTO_INCREMENT PRIMARY KEY,
                `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
                `operating_mode` VARCHAR(64) DEFAULT 'Normal',
                `status_code` VARCHAR(16) DEFAULT '00',
                `pv_power` FLOAT DEFAULT 0,
                `pv_voltage` FLOAT DEFAULT 0,
                `pv_current` FLOAT DEFAULT 0,
                `battery_voltage` FLOAT DEFAULT 0,
                `battery_capacity` INT DEFAULT 0,
                `charging_current` FLOAT DEFAULT 0,
                `discharge_current` FLOAT DEFAULT 0,
                `bus_voltage` FLOAT DEFAULT 0,
                `output_active_power` FLOAT DEFAULT 0,
                `output_apparent_power` FLOAT DEFAULT 0,
                `output_load_percent` INT DEFAULT 0,
                `ac_output_voltage` FLOAT DEFAULT 0,
                `ac_output_freq` FLOAT DEFAULT 0,
                `ac_input_voltage` FLOAT DEFAULT 0,
                `mains_freq` FLOAT DEFAULT 0,
                `mains_power` FLOAT DEFAULT 0,
                `inverter_temp` FLOAT DEFAULT 0,
                `boost_temp` FLOAT DEFAULT 0,
                `trafo_temp` FLOAT DEFAULT 0,
                `pv_temp` FLOAT DEFAULT 0,
                `fan1_speed` INT DEFAULT 0,
                `fan2_speed` INT DEFAULT 0,
                `daily_kwh` FLOAT DEFAULT 0,
                `monthly_kwh` FLOAT DEFAULT 0,
                `yearly_kwh` FLOAT DEFAULT 0,
                `total_kwh` FLOAT DEFAULT 0,
                INDEX `idx_created_at` (`created_at`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
            ";
            $pdo->exec($initSql);
            @file_put_contents($initFlag, date('Y-m-d H:i:s'));
        }

        if (!file_exists($settingsFlag)) {
            $settingsSql = "
            CREATE TABLE IF NOT EXISTS `inverter_settings` (
                `id` INT PRIMARY KEY DEFAULT 1,
                `output_source_priority` VARCHAR(32) DEFAULT 'SBU',
                `charger_source_priority` VARCHAR(32) DEFAULT 'SNU',
                `max_ac_charge_current` INT DEFAULT 10,
                `max_total_charge_current` INT DEFAULT 60,
                `battery_type` VARCHAR(32) DEFAULT 'USE',
                `back_to_grid_voltage` FLOAT DEFAULT 48.0,
                `back_to_discharge_voltage` FLOAT DEFAULT 54.0,
                `cutoff_voltage` FLOAT DEFAULT 42.0,
                `bulk_voltage` FLOAT DEFAULT 56.4,
                `float_voltage` FLOAT DEFAULT 54.0,
                `buzzer` TINYINT DEFAULT 1,
                `backlight` TINYINT DEFAULT 1,
                `return_home` TINYINT DEFAULT 1,
                `overload_restart` TINYINT DEFAULT 1,
                `overtemp_restart` TINYINT DEFAULT 1,
                `raw_qpiri` VARCHAR(255) DEFAULT '',
                `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

            CREATE TABLE IF NOT EXISTS `inverter_commands` (
                `id` INT AUTO_INCREMENT PRIMARY KEY,
                `command_code` VARCHAR(64) NOT NULL,
                `command_label` VARCHAR(128) NOT NULL,
                `parameter_value` VARCHAR(64) DEFAULT '',
                `status` ENUM('pending', 'sent', 'success', 'failed') DEFAULT 'pending',
                `response` VARCHAR(64) DEFAULT '',
                `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
                `executed_at` DATETIME NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

            INSERT IGNORE INTO `inverter_settings` (`id`, `output_source_priority`, `charger_source_priority`, `max_ac_charge_current`, `max_total_charge_current`, `battery_type`, `back_to_grid_voltage`, `back_to_discharge_voltage`, `cutoff_voltage`, `bulk_voltage`, `float_voltage`)
            VALUES (1, 'SBU', 'SNU', 10, 60, 'USE', 48.0, 54.0, 42.0, 56.4, 54.0);
            ";
            $pdo->exec($settingsSql);
            @file_put_contents($settingsFlag, date('Y-m-d H:i:s'));
        }

        return $pdo;

    } catch (PDOException $e) {
        $GLOBALS['db_last_error'] = $e->getMessage();
        error_log("MySQL Database Connection Error: " . $e->getMessage());
        return null;
    }
}
