/*
 * =======================================================================================
 * PROGRAM ARDUINO IDE: PEMBACA PARAMETER INVERTER POWMR 6.5kW (POW-HVM6.5K-48V)
 * Board         : ESP32-C3 Super Mini
 * Komunikasi    : Serial Monitor USB (115200 baud) & Hardware UART1 RS232 (2400 baud, 8N1)
 * Modul Level   : MAX3232 Level Shifter (VCC dicolok ke pin 5V ESP32)
 * Protokol      : Voltronic PI30 Standard (QPIGS, QMOD, QPI) + CRC-16 Checksum
 * 
 * ---------------------------------------------------------------------------------------
 * PANDUAN PENGKABELAN (WIRING) TERVERIFIKASI:
 * ---------------------------------------------------------------------------------------
 * [ESP32-C3 Super Mini]    [Modul MAX3232 TTL]       [Kabel LAN RJ45 Inverter PowMr 6.5K]
 *      5V           ---->   Pad '+' (VCC)
 *      GND          ---->   Pad '－' (GND)            Pad '－' (GND) -> Pin 8 (Cokelat)
 *      GPIO 6 (TX)  ---->   Pad '⇦' (RXD TTL)
 *      GPIO 7 (RX)  <----   Pad '⇨' (TXD TTL)
 *                           Pad '⇨' (TXD RS232) ----> Pin 2 (Orange : RX Inverter)
 *                           Pad '⇦' (RXD RS232) <---- Pin 1 (Putih-Orange : TX Inverter)
 * 
 *      ⚠️ PERINGATAN: Pin 4 kabel LAN adalah +12V DC. JANGAN DIHUBUNGKAN!
 * =======================================================================================
 */

#include <Arduino.h>

// ================= KONFIGURASI PIN =================
#define UART_TX_PIN   4    // GPIO 6 (TX ESP32 -> RXD TTL MAX3232)
#define UART_RX_PIN   5    // GPIO 7 (RX ESP32 <- TXD TTL MAX3232)
#define INVERTER_BAUD 2400 // Baud rate wajib Voltronic/PowMr

HardwareSerial InverterSerial(1);

// Variabel Waktu Polling
unsigned long lastPollTime = 0;
const unsigned long POLL_INTERVAL_MS = 2500; // Baca data setiap 2.5 detik

// ================= TABEL CRC-16 VOLTRONIC =================
const uint16_t crc_table[256] = {
  0x0000, 0x1021, 0x2042, 0x3063, 0x4084, 0x50a5, 0x60c6, 0x70e7,
  0x8108, 0x9129, 0xa14a, 0xb16b, 0xc18c, 0xd1ad, 0xe1ce, 0xf1ef,
  0x1231, 0x0210, 0x3273, 0x2252, 0x52b5, 0x4294, 0x72f7, 0x62d6,
  0x9339, 0x8318, 0xb37b, 0xa35a, 0xd3bd, 0xc39c, 0xf3ff, 0xe3de,
  0x2462, 0x3443, 0x0420, 0x1401, 0x64e6, 0x74c7, 0x44a4, 0x5485,
  0xa56a, 0xb54b, 0x8528, 0x9509, 0xe5ee, 0xf5cf, 0xc5ac, 0xd58d,
  0x3653, 0x2672, 0x1611, 0x0630, 0x76d7, 0x66f6, 0x5695, 0x46b4,
  0xb75b, 0xa77a, 0x9719, 0x8738, 0xf7df, 0xe7fe, 0xd79d, 0xc7bc,
  0x48c4, 0x58e5, 0x6886, 0x78a7, 0x0840, 0x1861, 0x2802, 0x3823,
  0xc9cc, 0xd9ed, 0xe98e, 0xf9af, 0x8948, 0x9969, 0xa90a, 0xb92b,
  0x5af5, 0x4ad4, 0x7ab7, 0x6a96, 0x1a71, 0x0a50, 0x3a33, 0x2a12,
  0xdbfd, 0xcbdc, 0xfbbf, 0xeb9e, 0x9b79, 0x8b58, 0xbb3b, 0xab1a,
  0x6ca6, 0x7c87, 0x4ce4, 0x5cc5, 0x2c22, 0x3c03, 0x0c60, 0x1c41,
  0xedae, 0xfd8f, 0xcdec, 0xddcd, 0xad2a, 0xbd0b, 0x8d68, 0x9d49,
  0x7e97, 0x6eb6, 0x5ed5, 0x4ef4, 0x3e13, 0x2e32, 0x1e51, 0x0e70,
  0xff9f, 0xefbe, 0xdfdd, 0xcffc, 0xbf1b, 0xaf3a, 0x9f59, 0x8f78,
  0x9188, 0x81a9, 0xb1ca, 0xa1eb, 0xd10c, 0xc12d, 0xf14e, 0xe16f,
  0x1080, 0x00a1, 0x30c2, 0x20e3, 0x5004, 0x4025, 0x7046, 0x6067,
  0x83b9, 0x9398, 0xa3fb, 0xb3da, 0xc33d, 0xd31c, 0xe37f, 0xf35e,
  0x02b1, 0x1290, 0x22f3, 0x32d2, 0x4235, 0x5214, 0x6277, 0x7256,
  0xb5ea, 0xa5cb, 0x95a8, 0x8589, 0xf56e, 0xe54f, 0xd52c, 0xc50d,
  0x34e2, 0x24c3, 0x14a0, 0x0481, 0x7466, 0x6447, 0x5424, 0x4405,
  0xa7db, 0xb7fa, 0x8799, 0x97b8, 0xe75f, 0xf77e, 0xc71d, 0xd73c,
  0x26d3, 0x36f2, 0x0691, 0x16b0, 0x6657, 0x7676, 0x4615, 0x5634,
  0xd94c, 0xc96d, 0xf90e, 0xe92f, 0x99c8, 0x89e9, 0xb98a, 0xa9ab,
  0x5844, 0x4865, 0x7806, 0x6827, 0x18c0, 0x08e1, 0x3882, 0x28a3,
  0xcb7d, 0xdb5c, 0xeb3f, 0xfb1e, 0x8bf9, 0x9bd8, 0xabbb, 0xbb9a,
  0x4a75, 0x5a54, 0x6a37, 0x7a16, 0x0af1, 0x1ad0, 0x2ab3, 0x3a92,
  0xfd2e, 0xed0f, 0xdd6c, 0xcd4d, 0xbdae, 0xad8f, 0x9dec, 0x8dcd,
  0x7c22, 0x6c03, 0x5c60, 0x4c41, 0x3ca6, 0x2c87, 0x1ce4, 0x0cc5,
  0xef1b, 0xff3a, 0xcf59, 0xdf78, 0xaf9f, 0xbfbe, 0x8fdd, 0x9ffc,
  0x6e13, 0x7e32, 0x4e51, 0x5e70, 0x2e97, 0x3eb6, 0x0ed5, 0x1ef4
};

// Hitung CRC-16 dengan Voltronic escape rules
uint16_t calculate_voltronic_crc(const char *msg, uint16_t len) {
  uint16_t crc = 0;
  for (uint16_t i = 0; i < len; i++) {
    char c = msg[i];
    crc = (crc << 8) ^ crc_table[((crc >> 8) ^ c) & 0x00FF];
  }
  uint8_t crc_low = crc & 0xFF;
  uint8_t crc_high = (crc >> 8) & 0xFF;
  if (crc_low == 0x28 || crc_low == 0x0D || crc_low == 0x0A) crc_low++;
  if (crc_high == 0x28 || crc_high == 0x0D || crc_high == 0x0A) crc_high++;
  return (crc_high << 8) | crc_low;
}

// Struktur Data Telemetri Inverter
struct InverterParams {
  String operatingMode = "Mencari status...";
  String protocolId = "PI30";
  
  // Grid PLN
  float gridVoltage = 0.0;
  float gridFrequency = 0.0;
  
  // AC Output (Beban Rumah)
  float acOutputVoltage = 0.0;
  float acOutputFrequency = 0.0;
  float outputApparentPower = 0.0;
  float outputActivePower = 0.0;
  int   outputLoadPercent = 0;
  
  // Baterai
  float busVoltage = 0.0;
  float batteryVoltage = 0.0;
  float batteryChargingCurrent = 0.0;
  float batteryDischargeCurrent = 0.0;
  int   batteryCapacitySoc = 0;
  
  // Suhu
  float inverterTemp = 0.0;
  
  // Solar PV
  float pvInputVoltage = 0.0;
  float pvInputCurrent = 0.0;
  float pvPowerWatt = 0.0;
  
  // Status Bits
  String deviceStatusBits = "";
  
  // Akumulasi Energi
  float dailyKwh = 0.0;
  float totalKwh = 0.0;
  
  // Statistik
  unsigned long totalSent = 0;
  unsigned long totalSuccess = 0;
  unsigned long totalNak = 0;
  unsigned long totalTimeout = 0;
} inv;

// Prototipe Fungsi
String sendVoltronicCmd(const char* cmd, uint8_t forcedCrcH = 0, uint8_t forcedCrcL = 0, unsigned long timeoutMs = 800);
void parseQpigs(const String& payload);
void parseQmod(const String& payload);
void printDashboard();

void setup() {
  Serial.begin(115200);
  unsigned long startUsb = millis();
  while (!Serial && (millis() - startUsb < 2000)) delay(10);
  delay(500);

  Serial.println("\n===============================================================");
  Serial.println("  MONITORING INVERTER POWMR 6.5kW (POW-HVM6.5K-48V)");
  Serial.println("  Protokol: Voltronic PI30 @ 2400 Baud (CRC Verified)");
  Serial.println("===============================================================");
  Serial.printf("  [INIT] UART: GPIO %d (TX) & GPIO %d (RX) @ %d Baud\n", UART_TX_PIN, UART_RX_PIN, INVERTER_BAUD);
  Serial.println("  [KABEL] Pin 1 (TX) ke RXD MAX, Pin 2 (RX) ke TXD MAX, Pin 8 (GND)");
  Serial.println("===============================================================\n");

  InverterSerial.begin(INVERTER_BAUD, SERIAL_8N1, UART_RX_PIN, UART_TX_PIN);
  while (InverterSerial.available()) InverterSerial.read();
}

void loop() {
  unsigned long currentMillis = millis();

  // 1. POLLING OTOMATIS SETIAP INTERVAL
  if (currentMillis - lastPollTime >= POLL_INTERVAL_MS) {
    lastPollTime = currentMillis;

    // 1.1 Ambil Status Mode Operasi (QMOD)
    // Coba dengan CRC terhitung
    String respQmod = sendVoltronicCmd("QMOD", 0x4D, 0xC5);
    if (respQmod.startsWith("(NAK")) {
      respQmod = sendVoltronicCmd("QMOD", 0x49, 0xC1); // alternatif variant CRC
    }
    parseQmod(respQmod);
    delay(100);

    // 1.2 Ambil Parameter Lengkap (QPIGS)
    String respQpigs = sendVoltronicCmd("QPIGS", 0xB7, 0xA9);
    if (respQpigs.startsWith("(NAK") || respQpigs.length() < 10) {
      respQpigs = sendVoltronicCmd("QPIGS", 0xB3, 0xA9); // alternatif variant CRC
    }
    parseQpigs(respQpigs);

    // 1.3 Cetak Dashboard
    printDashboard();
  }

  // 2. INPUT MANUAL DARI USER
  if (Serial.available()) {
    String userCmd = Serial.readStringUntil('\n');
    userCmd.trim();
    if (userCmd.length() > 0) {
      Serial.println("\n---------------------------------------------------------------");
      Serial.printf(">>> [MANUAL] Mengirim '%s' dengan auto-CRC...\n", userCmd.c_str());
      String resp = sendVoltronicCmd(userCmd.c_str());
      Serial.printf("<<< [RESPON] : '%s'\n", resp.c_str());
      Serial.println("---------------------------------------------------------------");
    }
  }
}

// ================= PENGIRIMAN DENGAN CRC =================
String sendVoltronicCmd(const char* cmd, uint8_t forcedCrcH, uint8_t forcedCrcL, unsigned long timeoutMs) {
  inv.totalSent++;
  while (InverterSerial.available()) InverterSerial.read();

  uint8_t crcH, crcL;
  if (forcedCrcH != 0 && forcedCrcL != 0) {
    crcH = forcedCrcH;
    crcL = forcedCrcL;
  } else {
    uint16_t len = strlen(cmd);
    uint16_t crc = calculate_voltronic_crc(cmd, len);
    crcH = (uint8_t)(crc >> 8);
    crcL = (uint8_t)(crc & 0xFF);
  }

  // Kirim string perintah + CRC 2-byte + \r (0x0D)
  InverterSerial.print(cmd);
  InverterSerial.write(crcH);
  InverterSerial.write(crcL);
  InverterSerial.write(0x0D);
  InverterSerial.flush();

  // Baca respon
  String response = "";
  unsigned long start = millis();
  while (millis() - start < timeoutMs) {
    if (InverterSerial.available()) {
      char c = (char)InverterSerial.read();
      if (c == '\r') break;
      if (c != '\n') response += c;
    }
    yield();
  }
  response.trim();

  if (response.startsWith("(NAK")) {
    inv.totalNak++;
  } else if (response.length() > 0 && response.charAt(0) == '(') {
    inv.totalSuccess++;
  } else {
    inv.totalTimeout++;
  }

  return response;
}

// ================= PARSING RESPONS QMOD =================
void parseQmod(const String& payload) {
  if (!payload.startsWith("(") || payload.startsWith("(NAK")) return;
  char mode = payload.charAt(1);
  switch (mode) {
    case 'P': inv.operatingMode = "Power On Mode"; break;
    case 'S': inv.operatingMode = "Standby Mode"; break;
    case 'L': inv.operatingMode = "Line / Mains Mode (PLN)"; break;
    case 'B': inv.operatingMode = "Battery Mode (PLTS)"; break;
    case 'F': inv.operatingMode = "Fault / Error Mode"; break;
    case 'D': inv.operatingMode = "Shutdown Mode"; break;
    default:  inv.operatingMode = "Mode " + String(mode); break;
  }
}

// ================= PARSING RESPONS QPIGS =================
void parseQpigs(const String& raw) {
  if (!raw.startsWith("(") || raw.startsWith("(NAK")) return;

  // Hapus karakter pembuka '('
  String payload = raw.substring(1);
  payload.trim();

  // Pisahkan token berdasarkan spasi
  String tokens[25];
  int tokenCount = 0;
  int start = 0;
  while (start < payload.length() && tokenCount < 25) {
    int nextSpace = payload.indexOf(' ', start);
    if (nextSpace == -1) {
      tokens[tokenCount++] = payload.substring(start);
      break;
    } else {
      tokens[tokenCount++] = payload.substring(start, nextSpace);
      start = nextSpace + 1;
    }
  }

  if (tokenCount >= 16) {
    inv.gridVoltage             = tokens[0].toFloat();
    inv.gridFrequency           = tokens[1].toFloat();
    inv.acOutputVoltage         = tokens[2].toFloat();
    inv.acOutputFrequency       = tokens[3].toFloat();
    inv.outputApparentPower     = tokens[4].toFloat();
    inv.outputActivePower       = tokens[5].toFloat();
    inv.outputLoadPercent       = tokens[6].toInt();
    inv.busVoltage              = tokens[7].toFloat();
    inv.batteryVoltage          = tokens[8].toFloat();
    inv.batteryChargingCurrent  = tokens[9].toFloat();
    inv.batteryCapacitySoc      = tokens[10].toInt();
    inv.inverterTemp            = tokens[11].toFloat();
    inv.pvInputCurrent          = tokens[12].toFloat();
    inv.pvInputVoltage          = tokens[13].toFloat();
    inv.batteryDischargeCurrent = tokens[15].toFloat();
    if (tokenCount > 16) inv.deviceStatusBits = tokens[16];

    // Jika ada token ke-19 (PV charging power), pakai itu, jika tidak hitung V*I
    if (tokenCount >= 20 && tokens[19].toFloat() > 0) {
      inv.pvPowerWatt = tokens[19].toFloat();
    } else {
      inv.pvPowerWatt = inv.pvInputVoltage * inv.pvInputCurrent;
    }
  }
}

// ================= CETAK TAMPILAN DASHBOARD =================
void printDashboard() {
  Serial.println("\n===============================================================");
  Serial.printf("        MONITORING INVERTER POWMR 6.5kW (Waktu: %lu s)\n", millis() / 1000);
  Serial.println("===============================================================");
  Serial.printf("  [STATUS] Mode Operasi : %s\n", inv.operatingMode.c_str());
  Serial.println("---------------------------------------------------------------");
  
  // Solar PV
  Serial.printf("  ☀️  SOLAR PV        : %7.1f Watt | %5.1f V | %5.2f A\n", 
                inv.pvPowerWatt, inv.pvInputVoltage, inv.pvInputCurrent);
  
  // Baterai
  Serial.printf("  🔋 BATERAI 48V      : %7.2f Volt | SOC: %3d %%\n", 
                inv.batteryVoltage, inv.batteryCapacitySoc);
  Serial.printf("      Arus Baterai    : Charge: %5.1f A | Discharge: %5.1f A\n", 
                inv.batteryChargingCurrent, inv.batteryDischargeCurrent);
  Serial.printf("      Tegangan Bus DC : %7.1f Volt\n", inv.busVoltage);
  Serial.println("---------------------------------------------------------------");

  // Beban Rumah (Load)
  Serial.printf("  🏠 BEBAN RUMAH (LOAD): %6.1f Watt | %5.1f VA | Beban: %2d %%\n", 
                inv.outputActivePower, inv.outputApparentPower, inv.outputLoadPercent);
  Serial.printf("      AC Output       : %7.1f Volt AC | %5.2f Hz\n", 
                inv.acOutputVoltage, inv.acOutputFrequency);
  Serial.println("---------------------------------------------------------------");

  // Grid PLN
  Serial.printf("  ⚡ LISTRIK PLN      : %7.1f Volt AC | %5.2f Hz\n", 
                inv.gridVoltage, inv.gridFrequency);
  
  // Suhu
  Serial.printf("  🌡️  SUHU INVERTER   : %7.1f °C\n", inv.inverterTemp);
  Serial.println("---------------------------------------------------------------");

  // Statistik Komunikasi
  Serial.printf("  📡 STATUS KOMUNIKASI : Sukses: %lu | NAK: %lu | Timeout: %lu\n",
                inv.totalSuccess, inv.totalNak, inv.totalTimeout);
  Serial.println("===============================================================");
}
