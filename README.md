# iot-solar-inverter

Sistem Monitoring IoT untuk Inverter Surya **PowMr 6.5kW (POW-HVM6.5K-48V-E)** berbasis mikrokontroler **ESP32-C3 Mini** via protokol serial RS232 (H-Protocol).

Dilengkapi dengan:
1. **Firmware Arduino IDE (`.ino`)**: Membaca parameter inverter via UART Hardware (GPIO6 & GPIO7 via MAX3232) dan melakukan auto-push HTTP POST ke server PHP.
2. **Firmware ESPHome (`.yaml`)**: Pilihan alternatif untuk integrasi langsung ke Home Assistant atau Web Server bawaan ESP32.
3. **Aplikasi Web Full PHP**:
   - ⚡ **Realtime Monitor (`index.php`)**: Diagram animasi aliran daya (*Power Flow*), status baterai 48V, beban rumah, solar PV, dan suhu.
   - 📅 **Laporan Harian (`daily.php`)**: Analisis kurva daya 24 jam (Solar PV vs Beban Rumah), puncak Watt, kWh harian, dan estimasi hemat tagihan PLN (Rp).
   - 📊 **Laporan Bulanan (`monthly.php`)**: Diagram batang akumulasi kWh tgl 1 s/d 31, total bulanan, rata-rata harian, dan tabel rincian per tanggal.
   - 📋 **Riwayat Log Telemetri (`history.php`)**: Tabel lengkap parameter + Export CSV.
   - 🛠️ **1-Click Database Installer (`install.php`)**: Sekali klik otomatis membuat database dan tabel di **phpMyAdmin** (MySQL/MariaDB).
   - 🌓 **Dukungan Light Mode & Dark Mode**.
4. **Standalone Web Dashboard (`web-dashboard/index.html`)**: Dashboard HTML interaktif dengan mode simulasi (*demo mode*).

---

## Struktur Folder

```text
├── README.md
├── .gitignore
├── solarplug-c3.yaml                 # Konfigurasi ESPHome ESP32-C3
├── arduino-esp32c3/
│   └── powmr_solar_esp32c3/
│       └── powmr_solar_esp32c3.ino   # Program Arduino IDE ESP32-C3 Mini
├── php-monitoring/                   # Aplikasi Web PHP & API Database
│   ├── config.php
│   ├── database.php
│   ├── api.php
│   ├── navbar.php
│   ├── index.php                     # Dashboard Realtime
│   ├── daily.php                     # Laporan Harian
│   ├── monthly.php                   # Laporan Bulanan
│   ├── history.php                   # Riwayat & Export CSV
│   ├── demo_data.php                 # Generator Data Contoh
│   ├── install.php                   # 1-Klik Setup phpMyAdmin
│   └── README.md
└── web-dashboard/
    └── index.html                    # Dashboard Web Interaktif Standalone
```

---

## Pengkabelan Hardware (Wiring)

> [!CAUTION]
> Inverter menggunakan sinyal level RS232 ($\pm12\text{V}$) dan mengeluarkan daya 12V di Pin 2. Wajib menggunakan modul level shifter **MAX3232** dan **dilarang menyambung Pin 2**.

* **ESP32-C3 TX (GPIO6)** ➔ MAX3232 TTL RXD ➔ **Pin 3 RJ45 Inverter (RX)**
* **ESP32-C3 RX (GPIO7)** ➔ MAX3232 TTL TXD ➔ **Pin 6 RJ45 Inverter (TX)**
* **ESP32-C3 GND** ➔ MAX3232 GND ➔ **Pin 4 & 5 RJ45 Inverter (GND)**
* **Pin 2 RJ45 (+12V)** ➔ *KOSONGKAN / JANGAN DIHUBUNGKAN*
* **Port Inverter**: Port No. 14 (WIFI / RS-232) di bagian bawah inverter.
* **Baud Rate**: 2400 bps, 8N1.

---

## Lisensi
MIT License
