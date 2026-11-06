# PowMr 6.5kW Solar Energy Monitor (IoT PLTS)

Sistem Monitoring IoT untuk Inverter Surya **PowMr 6.5kW (POW-HVM6.5K-48V-E)** berbasis mikrokontroler **ESP32-C3 Super Mini** via serial RS232 (Protokol Voltronic PI30 / `QPIGS` @ 2400 Baud).

Dilengkapi dengan:
1. **Firmware Arduino IDE (`.ino`)**:
   - `arduino-esp32c3/powmr_solar_esp32c3/powmr_solar_esp32c3.ino`: Membaca parameter inverter via UART Hardware (GPIO6 & GPIO7 via MAX3232), WebServer onboard (`/api/data`), dan auto-push HTTP POST ke server PHP (`http://plts.shawir.id/api.php`).
   - `arduino-esp32c3/powmr_c3_serial_monitor/powmr_c3_serial_monitor.ino`: Reader standalone via Serial Monitor USB tanpa WiFi.
2. **Aplikasi Web Full PHP (Langsung di Root Directory)**:
   - ⚡ **Realtime Monitor (`index.php`)**: Animasi dinamis aliran daya (*Power Flow*) dua arah (Solar PV, PLN, Inverter, Baterai 48V masuk/keluar, dan Beban Rumah) dengan AJAX live polling setiap 2 detik.
   - 🔑 **Login Admin Modern (`login.php`)**: Kartu glassmorphism dengan toggle pemilih mode Terang / Gelap (*Dark & Light mode*), responsive untuk mobile & desktop.
   - 📅 **Laporan Harian (`daily.php`)**: Analisis kurva daya 24 jam (Solar PV vs Beban Rumah), puncak Watt, kWh harian, dan estimasi hemat tagihan PLN (Rp).
   - 📊 **Laporan Bulanan (`monthly.php`)**: Diagram batang akumulasi kWh tgl 1 s/d 31, total bulanan, rata-rata harian, dan tabel rincian per tanggal.
   - 📋 **Riwayat Log Telemetri (`history.php`)**: Tabel lengkap parameter + Export CSV.
   - 🛠️ **1-Click Database Installer (`install.php`)**: Sekali klik otomatis membuat database dan tabel di **phpMyAdmin** (MySQL/MariaDB).
   - 🌓 **Dukungan Dark Mode & Light Mode** dengan tombol switcher instan.

---

## Struktur File Proyek

Semua file web sengaja diletakkan langsung di **Root Directory** repositori agar pada VPS (`/www/wwwroot/plts.shawir.id`), Anda cukup menjalankan `git pull` tanpa perlu memindahkan file antar folder.

```text
├── README.md
├── .gitignore
├── config.php                        # Konfigurasi Database, Admin, dan Tarif PLN
├── auth.php                          # Helper Autentikasi Admin
├── login.php                         # Halaman Login Admin (Dark/Light mode selector)
├── logout.php                        # Logout Admin
├── navbar.php                        # Navigasi Responsive + Switcher Tema
├── index.php                         # Dashboard Realtime + Animasi Aliran Daya
├── daily.php                         # Laporan Harian & Kurva 24 Jam
├── monthly.php                       # Laporan Bulanan & Penghematan Listrik (Rp)
├── history.php                       # Riwayat Log & Export CSV
├── install.php                       # 1-Klik Setup phpMyAdmin (MySQL)
├── api.php                           # REST API Penerima Data ESP32 & JSON Endpoint
├── database.php                      # Koneksi PDO SQLite / MySQL
├── arduino-esp32c3/
│   ├── powmr_solar_esp32c3/          # Firmware WiFi + HTTP POST ke VPS
│   └── powmr_c3_serial_monitor/      # Firmware Serial Monitor Standalone
└── web-dashboard/
    └── index.html                    # Dashboard Web Interaktif Standalone
```

---

## Pengkabelan Hardware Teruji (Wiring Diagram)

> [!CAUTION]
> Inverter mengeluarkan sinyal level tegangan RS232 ($\pm12\text{V}$) dan daya 12V di Pin 4. Wajib menggunakan modul level shifter **MAX3232** (beri daya VCC 5V dari ESP32) dan **dilarang menyambungkan Pin 4 (+12V)**!

### 1. Sambungan Kabel LAN (RJ45 Port 14 Inverter ➔ MAX3232)
* **Pin 1 (Putih - Orange)** : **TX Inverter** ➔ Sambung ke **RXD RS232** pada MAX3232
* **Pin 2 (Orange)** : **RX Inverter** ➔ Sambung ke **TXD RS232** pada MAX3232
* **Pin 8 (Cokelat)** : **GND Inverter** ➔ Sambung ke **GND** pada MAX3232
* **Pin 4 (Biru)** : **+12V DC** ➔ **POTONG / ISOLASI (JANGAN DISAMBUNG)**
* *Catatan: Pin 3, 5, 6, 7 pada Inverter PowMr 6.5kW adalah Dummy / Tidak Terhubung.*

### 2. Sambungan MAX3232 (Sisi TTL) ➔ ESP32-C3 Super Mini
* **VCC** MAX3232 ➔ **5V** ESP32-C3 *(Gunakan 5V agar pompa muatan MAX3232 clone bekerja optimal)*
* **GND** MAX3232 ➔ **GND** ESP32-C3
* **TXD TTL** MAX3232 ➔ **GPIO7 (RX)** ESP32-C3
* **RXD TTL** MAX3232 ➔ **GPIO6 (TX)** ESP32-C3

---

## Cara Update & Deploy di VPS (`plts.shawir.id`)

Cukup login SSH ke server VPS Anda dan jalankan perintah:

```bash
cd /www/wwwroot/plts.shawir.id
git pull origin main

# Atur izin file agar SQLite dapat ditulis oleh webserver Nginx/PHP
chown -R www:www .
chmod 666 solar_data.sqlite 2>/dev/null || true
```

Akses website: **[http://plts.shawir.id](http://plts.shawir.id)**  
Login Admin: **[http://plts.shawir.id/login.php](http://plts.shawir.id/login.php)**  
* Akun default: `admin` / `admin123` (bisa diubah di `config.php`)

---

## Lisensi
MIT License
