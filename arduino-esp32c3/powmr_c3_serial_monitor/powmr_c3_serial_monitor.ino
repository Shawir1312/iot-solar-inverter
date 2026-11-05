/*
 * =======================================================================================
 * PROGRAM ARDUINO IDE: PEMBACA PARAMETER INVERTER POWMR 6.5kW VIA SERIAL MONITOR
 * Board         : ESP32-C3 Super Mini
 * Komunikasi    : Serial Monitor USB (115200 baud) & Hardware UART1 RS232 (2400 baud, 8N1)
 * Modul Level   : MAX3232 Level Shifter (TTL 3.3V <-> RS-232 Inverter)
 * Port Inverter : Port RJ45 (Port 14 - RS232 / WiFi)
 * 
 * ---------------------------------------------------------------------------------------
 * PANDUAN PENGKABELAN (WIRING):
 * ---------------------------------------------------------------------------------------
 * [ESP32-C3 Super Mini]  <---->  [Modul MAX3232 TTL]  <---->  [Kabel RJ45 Inverter PowMr]
 *      3.3V                       VCC (atau 5V jika modul butuh 5V)
 *      GND                        GND                         Pin 4 & Pin 5 (GND)
 *      GPIO 6 (TX)                RXD (TTL) ---> TX RS232 ---> Pin 3 (RX Inverter)
 *      GPIO 7 (RX)                TXD (TTL) <--- RX RS232 <--- Pin 6 (TX Inverter)
 * 
 *   PERHATIAN KERAS:
 *   - Pin 2 RJ45 adalah +12V DC dari inverter. JANGAN DIHUBUNGKAN KE ESP32!
 * 
 * ---------------------------------------------------------------------------------------
 * PENGATURAN ARDUINO IDE UNTUK ESP32-C3 SUPER MINI:
 * ---------------------------------------------------------------------------------------
 * 1. Board            : "ESP32C3 Dev Module"
 * 2. USB CDC On Boot  : "Enabled"  <-- PENTING agar Serial.print muncul di USB Super Mini!
 * 3. CPU Frequency    : "160MHz"
 * 4. Flash Size       : "4MB"
 * 5. Serial Monitor   : 115200 Baud, Line ending: Both NL & CR
 * =======================================================================================
 */

#include <Arduino.h>

// ================= KONFIGURASI PIN =================
#define UART_TX_PIN   6    // GPIO 6 (TX ESP32 -> RXD MAX3232)
#define UART_RX_PIN   7    // GPIO 7 (RX ESP32 <- TXD MAX3232)
#define INVERTER_BAUD 2400 // Baud rate wajib PowMr H-Protocol

// Buat object HardwareSerial untuk UART1
HardwareSerial InverterSerial(1);

// Variabel Waktu Polling
unsigned long lastPollTime = 0;
const unsigned long POLL_INTERVAL_MS = 3000; // Baca data setiap 3 detik
int queryStep = 0;

// Variabel Penampung Data Inverter
struct InverterParams {
  // Status & Mode
  String statusCode = "-";
  String operatingMode = "Mencari data...";
  
  // Solar Panel (PV)
  float pvVoltage = 0.0;
  float pvCurrent = 0.0;
  float pvPower = 0.0;
  
  // Baterai 48V
  String batteryType = "-";
  float batteryVoltage = 0.0;
  int batterySoc = 0;
  float chargingCurrent = 0.0;
  float dischargeCurrent = 0.0;
  float busVoltage = 0.0;
  
  // Beban Rumah (AC Output)
  float acOutVoltage = 0.0;
  float acOutFreq = 0.0;
  float loadApparentVA = 0.0;
  float loadActiveWatt = 0.0;
  int loadPercent = 0;
  
  // Jaringan PLN (Grid Input)
  float acInVoltage = 0.0;
  float acInFreq = 0.0;
  float mainsPower = 0.0;
  
  // Suhu & Kipas
  float tempInverter = 0.0;
  float tempBoost = 0.0;
  float tempTrafo = 0.0;
  float tempPv = 0.0;
  int fan1Speed = 0;
  int fan2Speed = 0;
  
  // Akumulasi Energi (HGEN)
  String genDate = "-";
  String genTime = "-";
  float dailyKwh = 0.0;
  float monthlyKwh = 0.0;
  float yearlyKwh = 0.0;
  float totalKwh = 0.0;
  
  // Diagnostik Komunikasi
  unsigned long totalSent = 0;
  unsigned long totalSuccess = 0;
  unsigned long totalTimeout = 0;
} inv;

// Prototipe Fungsi
String sendInverterCommand(const char* cmd, unsigned long timeoutMs = 800);
void parseTokens(const String& raw, String tokens[], int maxTokens, int& tokenCount);
void decodeResponse(const String& cmd, const String& raw);
void printDashboardToSerial();

void setup() {
  // Inisialisasi Serial USB (Serial Monitor PC)
  Serial.begin(115200);
  
  // Tunggu koneksi USB CDC (maksimal 2 detik)
  unsigned long startUsb = millis();
  while (!Serial && (millis() - startUsb < 2000)) {
    delay(10);
  }
  
  delay(500);
  Serial.println("\n");
  Serial.println("===============================================================");
  Serial.println("     ESP32-C3 SUPER MINI - PEMBACA PARAMETER INVERTER POWMR   ");
  Serial.println("===============================================================");
  Serial.printf("  [INIT] UART Inverter: TX = GPIO %d, RX = GPIO %d @ %d Baud\n", UART_TX_PIN, UART_RX_PIN, INVERTER_BAUD);
  Serial.println("  [TIPS] Anda juga bisa mengetik perintah manual (contoh: HPV, HBAT, HSTS)");
  Serial.println("         di kolom Serial Monitor untuk uji coba langsung.");
  Serial.println("===============================================================\n");

  // Inisialisasi UART1 ke MAX3232
  InverterSerial.begin(INVERTER_BAUD, SERIAL_8N1, UART_RX_PIN, UART_TX_PIN);
  
  // Bersihkan buffer serial
  while (InverterSerial.available()) InverterSerial.read();
}

void loop() {
  unsigned long currentMillis = millis();

  // 1. POLLING OTOMATIS SETIAP INTERVAL
  if (currentMillis - lastPollTime >= POLL_INTERVAL_MS) {
    lastPollTime = currentMillis;

    // Ambil semua grup data secara berurutan
    String resHSTS  = sendInverterCommand("HSTS");
    decodeResponse("HSTS", resHSTS);
    delay(100);

    String resHPV   = sendInverterCommand("HPV");
    decodeResponse("HPV", resHPV);
    delay(100);

    String resHBAT  = sendInverterCommand("HBAT");
    decodeResponse("HBAT", resHBAT);
    delay(100);

    String resHOP   = sendInverterCommand("HOP");
    decodeResponse("HOP", resHOP);
    delay(100);

    String resHGRID = sendInverterCommand("HGRID");
    decodeResponse("HGRID", resHGRID);
    delay(100);

    String resHTEMP = sendInverterCommand("HTEMP");
    decodeResponse("HTEMP", resHTEMP);
    delay(100);

    String resHGEN  = sendInverterCommand("HGEN");
    decodeResponse("HGEN", resHGEN);

    // Tampilkan parameter rapi ke Serial Monitor
    printDashboardToSerial();
  }

  // 2. FITUR INPUT SERIAL MANUAL DARI PENGGUNA
  // Pengguna bisa mengetik perintah manual di Serial Monitor (misal: "HPV" lalu Enter)
  if (Serial.available()) {
    String manualCmd = Serial.readStringUntil('\n');
    manualCmd.trim();
    if (manualCmd.length() > 0) {
      Serial.println("\n---------------------------------------------------------------");
      Serial.printf(">>> [MANUAL COMMAND] Mengirim: '%s'\n", manualCmd.c_str());
      String resp = sendInverterCommand(manualCmd.c_str(), 1200);
      Serial.printf("<<< [RAW RESPON]     : '%s'\n", resp.c_str());
      decodeResponse(manualCmd, resp);
      Serial.println("---------------------------------------------------------------");
    }
  }
}

// ================= PENGIRIMAN PERINTAH VIA RS-232 =================
String sendInverterCommand(const char* cmd, unsigned long timeoutMs) {
  inv.totalSent++;

  // Buang data sampah di buffer RX sebelum mengirim perintah baru
  while (InverterSerial.available()) {
    InverterSerial.read();
  }

  // Kirim string perintah dengan akhiran Carriage Return (\r / 0x0D)
  InverterSerial.print(cmd);
  InverterSerial.write(0x0D);
  InverterSerial.flush();

  // Tunggu dan baca balasan frame dari inverter sampai karakter '\r'
  String response = "";
  unsigned long startWait = millis();
  bool received = false;

  while (millis() - startWait < timeoutMs) {
    if (InverterSerial.available()) {
      char c = (char)InverterSerial.read();
      if (c == '\r') {
        received = true;
        break; // Akhir frame balasan
      }
      if (c != '\n') {
        response += c;
      }
    }
    yield();
  }

  response.trim();

  if (response.length() > 0 && response.charAt(0) == '(') {
    inv.totalSuccess++;
  } else {
    inv.totalTimeout++;
  }

  return response;
}

// ================= PEMISAH TOKEN SPASI =================
void parseTokens(const String& raw, String tokens[], int maxTokens, int& tokenCount) {
  tokenCount = 0;
  if (raw.length() <= 1) return;

  // Lewati tanda pembuka '('
  String payload = raw.substring(1);
  payload.trim();

  int start = 0;
  while (start < payload.length() && tokenCount < maxTokens) {
    int nextSpace = payload.indexOf(' ', start);
    if (nextSpace == -1) {
      tokens[tokenCount++] = payload.substring(start);
      break;
    } else {
      tokens[tokenCount++] = payload.substring(start, nextSpace);
      start = nextSpace + 1;
    }
  }
}

// ================= DEKODE RESPON KE VARIABEL =================
void decodeResponse(const String& cmd, const String& raw) {
  if (raw.length() == 0 || raw.charAt(0) != '(') return;

  String tokens[30];
  int count = 0;
  parseTokens(raw, tokens, 30, count);
  if (count == 0) return;

  // 1. Status & Mode Operasi (HSTS) -> contoh: (00 B
  if (cmd == "HSTS" && count >= 2) {
    inv.statusCode = tokens[0];
    char mode = tokens[1].charAt(0);
    switch (mode) {
      case 'L': inv.operatingMode = "Line / Mains Mode (PLN)"; break;
      case 'B': inv.operatingMode = "Battery Mode (PLTS)"; break;
      case 'P': inv.operatingMode = "Power On Mode"; break;
      case 'S': inv.operatingMode = "Standby Mode"; break;
      case 'F': inv.operatingMode = "Fault / Error Mode"; break;
      default:  inv.operatingMode = "Mode " + String(mode); break;
    }
  }
  // 2. Panel Surya (HPV) -> contoh: (285.4 7.8 2226.1
  else if (cmd == "HPV" && count >= 3) {
    inv.pvVoltage = tokens[0].toFloat();
    inv.pvCurrent = tokens[1].toFloat();
    inv.pvPower   = tokens[2].toFloat();
  }
  // 3. Baterai 48V (HBAT) -> contoh: (PYL 53.2 85 15.0 0.0 380.0
  else if (cmd == "HBAT" && count >= 6) {
    inv.batteryType      = tokens[0];
    inv.batteryVoltage   = tokens[1].toFloat();
    inv.batterySoc       = tokens[2].toInt();
    inv.chargingCurrent  = tokens[3].toFloat();
    inv.dischargeCurrent = tokens[4].toFloat();
    inv.busVoltage       = tokens[5].toFloat();
  }
  // 4. Beban AC Rumah (HOP) -> contoh: (230.2 50.0 950.0 920.0 14
  else if (cmd == "HOP" && count >= 5) {
    inv.acOutVoltage   = tokens[0].toFloat();
    inv.acOutFreq      = tokens[1].toFloat();
    inv.loadApparentVA = tokens[2].toFloat();
    inv.loadActiveWatt = tokens[3].toFloat();
    inv.loadPercent    = tokens[4].toInt();
  }
  // 5. Grid Listrik PLN (HGRID) -> contoh: (224.5 50.0 0.0 0.0 0.0 0.0 0.0
  else if (cmd == "HGRID" && count >= 7) {
    inv.acInVoltage = tokens[0].toFloat();
    inv.acInFreq    = tokens[1].toFloat();
    inv.mainsPower  = tokens[6].toFloat();
  }
  // 6. Suhu & Kipas (HTEMP) -> contoh: (36.5 40.2 38.0 42.1 50 50
  else if (cmd == "HTEMP" && count >= 6) {
    inv.tempInverter = tokens[0].toFloat();
    inv.tempBoost    = tokens[1].toFloat();
    inv.tempTrafo    = tokens[2].toFloat();
    inv.tempPv       = tokens[3].toFloat();
    inv.fan1Speed    = tokens[4].toInt();
    inv.fan2Speed    = tokens[5].toInt();
  }
  // 7. Akumulasi Energi (HGEN) -> contoh: (20261105 143000 12.450 185.0 1650.0 3250.0
  else if (cmd == "HGEN" && count >= 6) {
    inv.genDate    = tokens[0];
    inv.genTime    = tokens[1];
    inv.dailyKwh   = tokens[2].toFloat();
    inv.monthlyKwh = tokens[3].toFloat();
    inv.yearlyKwh  = tokens[4].toFloat();
    inv.totalKwh   = tokens[5].toFloat();
  }
}

// ================= CETAK TAMPILAN DASHBOARD KE SERIAL MONITOR =================
void printDashboardToSerial() {
  Serial.println("\n===============================================================");
  Serial.printf("        MONITORING INVERTER POWMR 6.5kW (Waktu: %lu detik)\n", millis() / 1000);
  Serial.println("===============================================================");
  
  // Status Operasi
  Serial.printf("  [STATUS] Mode Operasi : %s\n", inv.operatingMode.c_str());
  Serial.printf("  [STATUS] Kode Status  : %s\n", inv.statusCode.c_str());
  Serial.println("---------------------------------------------------------------");

  // Panel Surya (Solar PV)
  Serial.printf("  ☀️  SOLAR PV        : %7.1f Watt\n", inv.pvPower);
  Serial.printf("      Tegangan PV     : %7.1f Volt\n", inv.pvVoltage);
  Serial.printf("      Arus PV         : %7.2f Ampere\n", inv.pvCurrent);
  Serial.println("---------------------------------------------------------------");

  // Baterai 48V
  Serial.printf("  🔋 BATERAI 48V      : %7.1f Volt   | SOC: %3d %%\n", inv.batteryVoltage, inv.batterySoc);
  Serial.printf("      Arus Pengisian  : %7.2f A (Charge)\n", inv.chargingCurrent);
  Serial.printf("      Arus Pengosongan: %7.2f A (Discharge)\n", inv.dischargeCurrent);
  Serial.printf("      Tegangan Bus DC : %7.1f Volt   | Tipe: %s\n", inv.busVoltage, inv.batteryType.c_str());
  Serial.println("---------------------------------------------------------------");

  // Beban Rumah (AC Output)
  Serial.printf("  🏠 BEBAN RUMAH (LOAD): %6.1f Watt   | Beban: %2d %%\n", inv.loadActiveWatt, inv.loadPercent);
  Serial.printf("      Daya Semu (VA)  : %7.1f VA\n", inv.loadApparentVA);
  Serial.printf("      Tegangan Output : %7.1f Volt AC\n", inv.acOutVoltage);
  Serial.printf("      Frekuensi Output: %7.2f Hz\n", inv.acOutFreq);
  Serial.println("---------------------------------------------------------------");

  // Listrik PLN (Grid Input)
  Serial.printf("  ⚡ LISTRIK PLN      : %7.1f Volt AC | Daya PLN: %.1f W\n", inv.acInVoltage, inv.mainsPower);
  Serial.printf("      Frekuensi PLN   : %7.2f Hz\n", inv.acInFreq);
  Serial.println("---------------------------------------------------------------");

  // Suhu & Kipas
  Serial.printf("  🌡️  SUHU            : Inv: %.1f°C | Boost: %.1f°C | Trafo: %.1f°C | PV: %.1f°C\n",
                inv.tempInverter, inv.tempBoost, inv.tempTrafo, inv.tempPv);
  Serial.printf("      Kecepatan Kipas : Kipas 1: %d %% | Kipas 2: %d %%\n", inv.fan1Speed, inv.fan2Speed);
  Serial.println("---------------------------------------------------------------");

  // Akumulasi Energi kWh
  Serial.printf("  📊 ENERGI (KWH)     : Hari Ini : %7.3f kWh\n", inv.dailyKwh);
  Serial.printf("                        Bulan Ini: %7.1f kWh\n", inv.monthlyKwh);
  Serial.printf("                        Total    : %7.1f kWh\n", inv.totalKwh);
  Serial.println("---------------------------------------------------------------");

  // Status Diagnostik Koneksi
  Serial.printf("  📡 DIAGNOSTIK KONEKSI: Kirim: %lu | Sukses: %lu | Gagal/Timeout: %lu\n",
                inv.totalSent, inv.totalSuccess, inv.totalTimeout);
  
  if (inv.totalSuccess == 0 && inv.totalSent >= 7) {
    Serial.println("  ⚠️  [PERINGATAN]: Belum ada data diterima dari inverter!");
    Serial.println("      - Periksa kabel MAX3232 TX (GPIO6) & RX (GPIO7) apakah terbalik.");
    Serial.println("      - Pastikan modul MAX3232 mendapat daya VCC 3.3V dan GND terpasang.");
    Serial.println("      - Pastikan pin RJ45: Pin 3 (RX Inverter) & Pin 6 (TX Inverter).");
  }
  Serial.println("===============================================================");
}
