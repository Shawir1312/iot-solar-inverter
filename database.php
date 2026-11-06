<?php
/**
 * Pengelola Koneksi Database (PDO SQLite / MySQL)
 * Lokasi: Root Directory
 */

require_once __DIR__ . '/config.php';

function getDbConnection() {
    static $pdo = null;
    if ($pdo !== null) {
        return $pdo;
    }

    try {
        if (DB_DRIVER === 'sqlite') {
            $pdo = new PDO('sqlite:' . SQLITE_FILE);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            
            // Inisialisasi Tabel SQLite jika belum ada
            $initSql = "
            CREATE TABLE IF NOT EXISTS solar_telemetry (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                operating_mode TEXT,
                status_code TEXT,
                pv_power REAL,
                pv_voltage REAL,
                pv_current REAL,
                battery_voltage REAL,
                battery_capacity INTEGER,
                charging_current REAL,
                discharge_current REAL,
                bus_voltage REAL,
                output_active_power REAL,
                output_apparent_power REAL,
                output_load_percent INTEGER,
                ac_output_voltage REAL,
                ac_output_freq REAL,
                ac_input_voltage REAL,
                mains_freq REAL,
                mains_power REAL,
                inverter_temp REAL,
                boost_temp REAL,
                trafo_temp REAL,
                pv_temp REAL,
                fan1_speed INTEGER,
                fan2_speed INTEGER,
                daily_kwh REAL,
                monthly_kwh REAL,
                yearly_kwh REAL,
                total_kwh REAL
            );
            CREATE INDEX IF NOT EXISTS idx_telemetry_created_at ON solar_telemetry (created_at);
            ";
            $pdo->exec($initSql);

        } else {
            // MySQL Connection
            $port = defined('DB_PORT') ? DB_PORT : '3306';
            $dsn = "mysql:host=" . DB_HOST . ";port=" . $port . ";dbname=" . DB_NAME . ";charset=utf8mb4";
            $pdo = new PDO($dsn, DB_USER, DB_PASS);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            
            // Inisialisasi Tabel MySQL jika belum ada
            $initSql = "
            CREATE TABLE IF NOT EXISTS solar_telemetry (
                id INT AUTO_INCREMENT PRIMARY KEY,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                operating_mode VARCHAR(64),
                status_code VARCHAR(16),
                pv_power FLOAT,
                pv_voltage FLOAT,
                pv_current FLOAT,
                battery_voltage FLOAT,
                battery_capacity INT,
                charging_current FLOAT,
                discharge_current FLOAT,
                bus_voltage FLOAT,
                output_active_power FLOAT,
                output_apparent_power FLOAT,
                output_load_percent INT,
                ac_output_voltage FLOAT,
                ac_output_freq FLOAT,
                ac_input_voltage FLOAT,
                mains_freq FLOAT,
                mains_power FLOAT,
                inverter_temp FLOAT,
                boost_temp FLOAT,
                trafo_temp FLOAT,
                pv_temp FLOAT,
                fan1_speed INT,
                fan2_speed INT,
                daily_kwh FLOAT,
                monthly_kwh FLOAT,
                yearly_kwh FLOAT,
                total_kwh FLOAT,
                INDEX idx_created_at (created_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
            ";
            $pdo->exec($initSql);
        }

        return $pdo;
    } catch (PDOException $e) {
        error_log("Database Connection Error: " . $e->getMessage());
        return null;
    }
}
