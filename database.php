<?php
/**
 * Pengelola Koneksi Database MySQL (MariaDB / phpMyAdmin)
 * Otomatis inisialisasi database dan tabel solar_telemetry
 * Lokasi: Root Directory
 */

require_once __DIR__ . '/config.php';

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

        // 2. Inisialisasi Tabel MySQL solar_telemetry jika belum ada
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

        return $pdo;

    } catch (PDOException $e) {
        error_log("MySQL Database Connection Error: " . $e->getMessage());
        return null;
    }
}
