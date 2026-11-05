# Aplikasi Web PHP Monitoring Inverter PowMr 6.5kW

Aplikasi web berbasis **PHP murni (tanpa framework berat)** untuk monitoring telemetri lengkap, analisis pemakaian energi harian & bulanan, serta estimasi penghematan biaya listrik PLN dari Inverter Solar **PowMr 6.5kW (POW-HVM6.5K-48V-E)** menggunakan **ESP32-C3 Mini**.

---

## Instalasi Cepat Database phpMyAdmin (1-Klik)

Aplikasi ini sudah dilengkapi file **[`install.php`](file:///Users/mushawirodegoa/Documents/iot%20solar/php-monitoring/install.php)**:
1. Buka browser: **`http://localhost/solar/install.php`** (jika di XAMPP) atau **`http://localhost:8000/install.php`**.
2. Masukkan username (default: `root`) dan nama database (default: `powmr_monitoring`).
3. Centang opsi *"Isi Data Simulasi 7 Hari Terakhir"* agar grafik langsung terisi.
4. Klik tombol **⚡ Mulai Install Database Sekarang (1-Klik)**.
5. Selesai! Database dan tabel `solar_telemetry` otomatis dibuat di **phpMyAdmin**, dan file `config.php` langsung otomatis terkonfigurasi ke MySQL.

---

## Fitur Utama

1. **⚡ Realtime Monitor (`index.php`)**:
   - Diagram aliran daya animasi (*Power Flow*): Solar PV ➔ Inverter ➔ Beban Rumah, Baterai, dan PLN.
   - Status baterai 48V (SOC %, Voltase, Arus Charging/Discharge).
   - Sensor suhu termal lengkap (Suhu Inti, Boost DC-DC, Trafo, Heatsink PV) & kecepatan kipas pendingin 1 & 2.
   - Auto-refresh data setiap 3 detik via AJAX REST API.

2. **📅 Laporan Pemakaian Harian (`daily.php`)**:
   - Pemilih tanggal (*date picker*).
   - Grafik kurva 24 jam (Jam 00:00 s/d 23:00) membandingkan **Produksi Solar PV (Watt)** vs **Beban Pemakaian Rumah (Watt)**.
   - Puncak daya solar (Peak Watt), beban rata-rata, dan total kWh hari tersebut.
   - Kalkulasi otomatis penghematan tagihan PLN dalam Rupiah (`Rp`).

3. **📊 Laporan Pemakaian Bulanan (`monthly.php`)**:
   - Pemilih bulan (*month picker*).
   - Diagram batang (*Bar Chart*) akumulasi produksi harian sepanjang tanggal 1 s/d 31.
   - Total kWh bulanan, rata-rata harian (kWh/hari), dan hari terbaik (*Peak Day*).
   - Rincian tabel per tanggal lengkap dengan estimasi rupiah yang dihemat.

4. **📋 Riwayat Log Telemetri (`history.php`)**:
   - Tabel riwayat parameter lengkap.
   - Tombol **Download CSV** untuk keperluan backup dan analisis di Microsoft Excel.

5. **🌓 Dukungan Light Mode & Dark Mode**:
   - Tombol toggle instan di navigasi atas (tersimpan otomatis di browser).

6. **⚡ Generator Data Contoh (`demo_data.php`)**:
   - Fitur satu klik untuk mengisi data simulasi 7 hari ke belakang agar grafik langsung tampil indah saat pertama kali dibuka.

---

## Cara Menjalankan Aplikasi Web

### Cara 1: Menggunakan PHP Built-in Server (Paling Cepat)
Buka Terminal di Mac/Linux atau Command Prompt di Windows, masuk ke folder ini dan jalankan:

```bash
cd "/Users/mushawirodegoa/Documents/iot solar/php-monitoring"
php -S 0.0.0.0:8000
```

Lalu buka browser:
- Dari Laptop: `http://localhost:8000`
- Dari HP (yang satu Wi-Fi): `http://IP_LAPTOP_ANDA:8000` (contoh: `http://192.168.1.100:8000`)

---

### Cara 2: Menggunakan XAMPP
1. Salin folder `php-monitoring` ke dalam direktori `htdocs` XAMPP:
   - Mac: `/Applications/XAMPP/xamppfiles/htdocs/solar/`
   - Windows: `C:\xampp\htdocs\solar\`
2. Jalankan Apache dari XAMPP Control Panel.
3. Buka browser: `http://localhost/solar/`

---

## Konfigurasi Database (`config.php`)

Secara default, aplikasi menggunakan **SQLite** (`solar_data.sqlite`) yang **otomatis dibuat dan siap pakai tanpa perlu instalasi database server**.

Jika Anda ingin menggunakan **MySQL** di XAMPP:
1. Buka `config.php`.
2. Ubah `define('DB_DRIVER', 'mysql');`.
3. Sesuaikan nama database, user, dan password MySQL Anda.
Tabel `solar_telemetry` akan otomatis dibuat saat aplikasi pertama kali dibuka.

---

## Menghubungkan ke ESP32-C3 Mini (Arduino IDE)

1. Buka file sketch Arduino:  
   `arduino-esp32c3/powmr_solar_esp32c3/powmr_solar_esp32c3.ino`
2. Sesuaikan konstanta:
   ```cpp
   const char* WIFI_SSID     = "Nama_WiFi_Anda";
   const char* WIFI_PASSWORD = "Password_WiFi_Anda";
   const char* PHP_SERVER_URL = "http://IP_KOMPUTER_PHP:8000/api.php";
   ```
3. Upload sketch ke ESP32-C3 Mini Anda. ESP32 akan membaca parameter inverter PowMr via pin UART (GPIO6 & GPIO7 melalui MAX3232) lalu secara otomatis mengirim data telemetri ke `api.php`.

