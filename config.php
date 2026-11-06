<?php
/**
 * Konfigurasi Utama Aplikasi Monitoring Solar Inverter PowMr 6.5kW
 * Lokasi: Root Directory (Langsung deploy ke /www/wwwroot/plts.shawir.id)
 */

// Zona Waktu (WIB / WITA / WIT)
date_default_timezone_set('Asia/Jakarta');

// Kunci Keamanan API (Harus sama dengan API_KEY di sketch Arduino IDE)
define('API_SECRET_KEY', 'powmr_secret_key_123');

// Pengaturan Database
// Pilihan: 'sqlite' (otomatis tanpa install server database) atau 'mysql'
define('DB_DRIVER', 'sqlite'); 

// Jika menggunakan SQLite:
define('SQLITE_FILE', __DIR__ . '/solar_data.sqlite');

// Jika menggunakan MySQL / MariaDB (misal di aaPanel / cPanel / XAMPP):
define('DB_HOST', 'localhost');
define('DB_PORT', '3306');
define('DB_NAME', 'powmr_monitoring');
define('DB_USER', 'root');
define('DB_PASS', '');

// Tarif Listrik PLN per kWh (Tarif Dasar Listrik R1 1300-2200VA: Rp 1.444,70)
// Digunakan untuk kalkulasi otomatis penghematan biaya listrik dalam Rupiah
define('PLN_TARIFF_PER_KWH', 1444.70);

// Akun Admin Dashboard
define('ADMIN_USER', 'admin');
define('ADMIN_PASSWORD', 'admin123'); // Ganti password sesuai kebutuhan

// Pengaturan Akses Dashboard
// false = Dashboard dapat dilihat publik, login admin hanya untuk kontrol sistem/demo
// true  = Seluruh halaman wajib login admin
define('REQUIRE_LOGIN_TO_VIEW', false);

// Nama dan Identitas Sistem
define('APP_NAME', 'PowMr 6.5kW Solar Energy Monitor');
define('APP_DEVICE_MODEL', 'POW-HVM6.5K-48V-E');
define('APP_VERSION', '2.0.0');
