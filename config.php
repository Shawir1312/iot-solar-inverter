<?php
/**
 * Konfigurasi Utama Aplikasi Monitoring Solar Inverter PowMr 6.5kW
 * Lokasi: Root Directory (http://plts.shawir.id)
 */

// Zona Waktu (WIB / WITA / WIT)
date_default_timezone_set('Asia/Jakarta');

// Kunci Keamanan API (Harus sama dengan API_KEY di sketch Arduino IDE)
define('API_SECRET_KEY', 'shawir1312');

// Pengaturan Database: MySQL Murni (MariaDB / phpMyAdmin)
define('DB_DRIVER', 'mysql'); 

// Pengaturan Koneksi MySQL / MariaDB (aaPanel / phpMyAdmin):
// Sesuaikan DB_USER dan DB_PASS dengan akun database MySQL Anda di aaPanel
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
// false = Dashboard dapat dilihat publik, login admin hanya untuk kontrol sistem/reset
// true  = Seluruh halaman wajib login admin
define('REQUIRE_LOGIN_TO_VIEW', false);

// Nama dan Identitas Sistem
define('APP_NAME', 'PowMr 6.5kW Solar Energy Monitor');
define('APP_DEVICE_MODEL', 'POW-HVM6.5K-48V-E');
define('APP_VERSION', '2.0.0');
