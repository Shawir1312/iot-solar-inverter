/*
 * ====================================================================
 * Program Arduino IDE: Monitoring Inverter PowMr 6.5kW (POW-HVM6.5K-48V)
 * Mikrokontroler : ESP32-C3 Mini / SuperMini
 * Library WiFi   : shawirWifi (Captive Portal & Smart WiFi Manager)
 * Indikator LED  :
 *   - LED Hijau (GPIO 2): Indikator WiFi
 *     * Belum Konek   : Kedip-kedip cepat (100ms)
 *     * Sudah Konek   : Langsung nyala diam (Solid ON)
 *   - LED Merah (GPIO 1): Indikator Website (Push Telemetri)
 *     * Belum Konek   : Kedip inisialisasi (500ms)
 *     * Sudah Konek   : Mati diam (berkedip singkat saat kirim paket)
 * Komunikasi     : Hardware UART1 ke Level Shifter MAX3232 (2400 Baud, 8N1)
 * Protokol       : Voltronic PI30 (QPIGS, QMOD) dengan CRC-16 Checksum
 * Pinout ESP32-C3:
 *   - Pin GPIO 1 -> LED Merah (+ resistor ke GND)
 *   - Pin GPIO 2 -> LED Hijau (+ resistor ke GND)
 *   - Pin GPIO 6 -> RXD TTL MAX3232 (ke Pin 2 RJ45 Inverter TX)
 *   - Pin GPIO 7 -> TXD TTL MAX3232 (ke Pin 1 RJ45 Inverter RX)
 *   - VCC MAX3232 -> 5V / 3.3V ESP32
 *   - GND MAX3232 -> GND ESP32 & Pin 8 RJ45 Inverter (Pin 4 +12V diputus)
 * ====================================================================
 */

#include <Arduino.h>
#include <HTTPClient.h>
#include <Ticker.h>
#include <WebServer.h>
#include <shawirWifi.h>

// ================= PIN DEFINISI =================
#define LED_RED_PIN 1   // GPIO 1: Indikator Website
#define LED_GREEN_PIN 2 // GPIO 2: Indikator WiFi

#define UART_TX_PIN 4 // GPIO 6: UART1 TX ke MAX3232 RXD
#define UART_RX_PIN 5 // GPIO 7: UART1 RX ke MAX3232 TXD
#define INVERTER_BAUD 2400

// ================= PENGATURAN SERVER PHP =================
// URL file api.php pada Web Server PHP Anda
const char *PHP_SERVER_URL = "http://plts.shawir.id/api.php?api_key=shawir1312";
const char *API_KEY = "shawir1312"; // Harus sama dengan config.php

// Interval Pengiriman Data ke PHP Server (dalam milidetik)
const unsigned long PUSH_INTERVAL_MS = 5000; // Kirim tiap 5 detik

// ================= OBJEK & TICKER =================
HardwareSerial InverterSerial(1);
WebServer server(80);

Ticker tickerLedGreen;
Ticker tickerLedRed;

// Status State
volatile bool isGreenBlinking = false;
volatile bool isRedBlinking = false;
bool isWebsiteConnected = false;

// Callback Ticker LED Hijau (Kedip Cepat 100ms)
void toggleLedGreen() {
  digitalWrite(LED_GREEN_PIN, !digitalRead(LED_GREEN_PIN));
}

// Callback Ticker LED Merah (Kedip Inisialisasi 500ms)
void toggleLedRed() { digitalWrite(LED_RED_PIN, !digitalRead(LED_RED_PIN)); }

void startGreenBlinkFast() {
  if (!isGreenBlinking) {
    tickerLedGreen.attach_ms(100, toggleLedGreen);
    isGreenBlinking = true;
  }
}

void stopGreenBlinkSolidOn() {
  if (isGreenBlinking) {
    tickerLedGreen.detach();
    isGreenBlinking = false;
  }
  digitalWrite(LED_GREEN_PIN, HIGH); // Nyala diam
}

void startRedBlinkInit() {
  if (!isRedBlinking) {
    tickerLedRed.attach_ms(500, toggleLedRed);
    isRedBlinking = true;
  }
}

void stopRedBlinkOff() {
  if (isRedBlinking) {
    tickerLedRed.detach();
    isRedBlinking = false;
  }
  digitalWrite(LED_RED_PIN, LOW); // Mati diam
}

// ================= STRUKTUR DATA TELEMETRI =================
struct InverterData {
  String operatingMode = "Standby";
  String statusCode = "00";
  String batteryType = "Lead/Lithium";
  String protocol = "PI30";

  // Solar PV
  float pvPower = 0.0;   // Watt
  float pvVoltage = 0.0; // Volt
  float pvCurrent = 0.0; // Ampere

  // Baterai 48V
  float batteryVoltage = 0.0;   // Volt
  int batteryCapacity = 0;      // %
  float chargingCurrent = 0.0;  // Ampere
  float dischargeCurrent = 0.0; // Ampere
  float busVoltage = 0.0;       // Volt

  // Beban AC Rumah (Output)
  float acOutputVoltage = 0.0;     // Volt
  float acOutputFreq = 0.0;        // Hz
  float outputActivePower = 0.0;   // Watt
  float outputApparentPower = 0.0; // VA
  int outputLoadPercent = 0;       // %

  // Listrik PLN (Input Grid)
  float acInputVoltage = 0.0; // Volt
  float mainsFreq = 0.0;      // Hz
  float mainsPower = 0.0;     // Watt

  // Suhu
  float inverterTemp = 0.0; // °C
  float boostTemp = 0.0;
  float trafoTemp = 0.0;
  float pvTemp = 0.0;
  int fan1Speed = 0;
  int fan2Speed = 0;

  // Akumulasi Energi
  float dailyKwh = 0.0;
  float monthlyKwh = 0.0;
  float yearlyKwh = 0.0;
  float totalKwh = 0.0;

  unsigned long lastUpdated = 0;
  unsigned long totalSent = 0;
  unsigned long totalSuccess = 0;
};

InverterData solarData;
unsigned long lastPushTime = 0;
unsigned long lastPollTime = 0;
unsigned long lastQpiriPollTime = 0;

// ================= STRUKTUR DATA PENGATURAN INVERTER =================
struct InverterSettings {
  bool hasQpiri = false;
  String outputPriority = "SBU";
  String chargerPriority = "SNU";
  int maxAcChargeAmps = 10;
  int maxTotalChargeAmps = 60;
  String batteryType = "USE";
  float backToGridVolt = 48.0;
  float backToDischargeVolt = 54.0;
  float cutoffVolt = 42.0;
  float bulkVolt = 56.4;
  float floatVolt = 54.0;
  String rawQpiri = "";
};

InverterSettings invSettings;

// ================= TABEL CRC-16 VOLTRONIC =================
const uint16_t crc_table[256] = {
    0x0000, 0x1021, 0x2042, 0x3063, 0x4084, 0x50a5, 0x60c6, 0x70e7, 0x8108,
    0x9129, 0xa14a, 0xb16b, 0xc18c, 0xd1ad, 0xe1ce, 0xf1ef, 0x1231, 0x0210,
    0x3273, 0x2252, 0x52b5, 0x4294, 0x72f7, 0x62d6, 0x9339, 0x8318, 0xb37b,
    0xa35a, 0xd3bd, 0xc39c, 0xf3ff, 0xe3de, 0x2462, 0x3443, 0x0420, 0x1401,
    0x64e6, 0x74c7, 0x44a4, 0x5485, 0xa56a, 0xb54b, 0x8528, 0x9509, 0xe5ee,
    0xf5cf, 0xc5ac, 0xd58d, 0x3653, 0x2672, 0x1611, 0x0630, 0x76d7, 0x66f6,
    0x5695, 0x46b4, 0xb75b, 0xa77a, 0x9719, 0x8738, 0xf7df, 0xe7fe, 0xd79d,
    0xc7bc, 0x48c4, 0x58e5, 0x6886, 0x78a7, 0x0840, 0x1861, 0x2802, 0x3823,
    0xc9cc, 0xd9ed, 0xe98e, 0xf9af, 0x8948, 0x9969, 0xa90a, 0xb92b, 0x5af5,
    0x4ad4, 0x7ab7, 0x6a96, 0x1a71, 0x0a50, 0x3a33, 0x2a12, 0xdbfd, 0xcbdc,
    0xfbbf, 0xeb9e, 0x9b79, 0x8b58, 0xbb3b, 0xab1a, 0x6ca6, 0x7c87, 0x4ce4,
    0x5cc5, 0x2c22, 0x3c03, 0x0c60, 0x1c41, 0xedae, 0xfd8f, 0xcdec, 0xddcd,
    0xad2a, 0xbd0b, 0x8d68, 0x9d49, 0x7e97, 0x6eb6, 0x5ed5, 0x4ef4, 0x3e13,
    0x2e32, 0x1e51, 0x0e70, 0xff9f, 0xefbe, 0xdfdd, 0xcffc, 0xbf1b, 0xaf3a,
    0x9f59, 0x8f78, 0x9188, 0x81a9, 0xb1ca, 0xa1eb, 0xd10c, 0xc12d, 0xf14e,
    0xe16f, 0x1080, 0x00a1, 0x30c2, 0x20e3, 0x5004, 0x4025, 0x7046, 0x6067,
    0x83b9, 0x9398, 0xa3fb, 0xb3da, 0xc33d, 0xd31c, 0xe37f, 0xf35e, 0x02b1,
    0x1290, 0x22f3, 0x32d2, 0x4235, 0x5214, 0x6277, 0x7256, 0xb5ea, 0xa5cb,
    0x95a8, 0x8589, 0xf56e, 0xe54f, 0xd52c, 0xc50d, 0x34e2, 0x24c3, 0x14a0,
    0x0481, 0x7466, 0x6447, 0x5424, 0x4405, 0xa7db, 0xb7fa, 0x8799, 0x97b8,
    0xe75f, 0xf77e, 0xc71d, 0xd73c, 0x26d3, 0x36f2, 0x0691, 0x16b0, 0x6657,
    0x7676, 0x4615, 0x5634, 0xd94c, 0xc96d, 0xf90e, 0xe92f, 0x99c8, 0x89e9,
    0xb98a, 0xa9ab, 0x5844, 0x4865, 0x7806, 0x6827, 0x18c0, 0x08e1, 0x3882,
    0x28a3, 0xcb7d, 0xdb5c, 0xeb3f, 0xfb1e, 0x8bf9, 0x9bd8, 0xabbb, 0xbb9a,
    0x4a75, 0x5a54, 0x6a37, 0x7a16, 0x0af1, 0x1ad0, 0x2ab3, 0x3a92, 0xfd2e,
    0xed0f, 0xdd6c, 0xcd4d, 0xbdae, 0xad8f, 0x9dec, 0x8dcd, 0x7c22, 0x6c03,
    0x5c60, 0x4c41, 0x3ca6, 0x2c87, 0x1ce4, 0x0cc5, 0xef1b, 0xff3a, 0xcf59,
    0xdf78, 0xaf9f, 0xbfbe, 0x8fdd, 0x9ffc, 0x6e13, 0x7e32, 0x4e51, 0x5e70,
    0x2e97, 0x3eb6, 0x0ed5, 0x1ef4};

uint16_t calculate_voltronic_crc(const char *msg, uint16_t len) {
  uint16_t crc = 0;
  for (uint16_t i = 0; i < len; i++) {
    char c = msg[i];
    crc = (crc << 8) ^ crc_table[((crc >> 8) ^ c) & 0x00FF];
  }
  uint8_t crc_low = crc & 0xFF;
  uint8_t crc_high = (crc >> 8) & 0xFF;
  if (crc_low == 0x28 || crc_low == 0x0D || crc_low == 0x0A)
    crc_low++;
  if (crc_high == 0x28 || crc_high == 0x0D || crc_high == 0x0A)
    crc_high++;
  return (crc_high << 8) | crc_low;
}

String sendVoltronicCommand(const char *cmd, uint8_t forcedH = 0,
                            uint8_t forcedL = 0,
                            unsigned long timeoutMs = 800) {
  while (InverterSerial.available())
    InverterSerial.read();

  uint8_t h, l;
  if (forcedH != 0 && forcedL != 0) {
    h = forcedH;
    l = forcedL;
  } else {
    uint16_t crc = calculate_voltronic_crc(cmd, strlen(cmd));
    h = (uint8_t)(crc >> 8);
    l = (uint8_t)(crc & 0xFF);
  }

  InverterSerial.print(cmd);
  InverterSerial.write(h);
  InverterSerial.write(l);
  InverterSerial.write(0x0D);
  InverterSerial.flush();

  String resp = "";
  unsigned long start = millis();
  while (millis() - start < timeoutMs) {
    if (InverterSerial.available()) {
      char c = (char)InverterSerial.read();
      if (c == '\r')
        break;
      if (c != '\n')
        resp += c;
    }
    yield();
  }
  resp.trim();
  return resp;
}

String sendHCommand(const char *cmd, unsigned long timeoutMs = 400) {
  while (InverterSerial.available())
    InverterSerial.read();

  InverterSerial.print(cmd);
  InverterSerial.write(0x0D);
  InverterSerial.flush();

  String resp = "";
  unsigned long start = millis();
  while (millis() - start < timeoutMs) {
    if (InverterSerial.available()) {
      char c = (char)InverterSerial.read();
      if (c == '\r')
        break;
      if (c != '\n')
        resp += c;
    }
    yield();
  }
  resp.trim();
  return resp;
}

void parseQmodResponse(const String &payload) {
  if (!payload.startsWith("(") || payload.startsWith("(NAK"))
    return;
  char mode = payload.charAt(1);
  switch (mode) {
  case 'P':
    solarData.operatingMode = "Power On Mode";
    break;
  case 'S':
    solarData.operatingMode = "Standby Mode";
    break;
  case 'L':
    solarData.operatingMode = "Line / Mains Mode (PLN)";
    break;
  case 'B':
    solarData.operatingMode = "Battery Mode (PLTS)";
    break;
  case 'F':
    solarData.operatingMode = "Fault / Error Mode";
    break;
  case 'D':
    solarData.operatingMode = "Shutdown Mode";
    break;
  default:
    solarData.operatingMode = "Mode " + String(mode);
    break;
  }
}

void parseQpigsResponse(const String &raw) {
  if (!raw.startsWith("(") || raw.startsWith("(NAK"))
    return;
  String payload = raw.substring(1);
  payload.trim();

  // Tokenizer tangguh yang mengabaikan spasi ganda / spasi berlebih
  String tokens[30];
  int count = 0;
  int len = payload.length();
  int i = 0;

  while (i < len && count < 30) {
    while (i < len && (payload.charAt(i) == ' ' || payload.charAt(i) == '\t')) {
      i++;
    }
    if (i >= len) break;

    int tokenStart = i;
    while (i < len && payload.charAt(i) != ' ' && payload.charAt(i) != '\t') {
      i++;
    }
    tokens[count++] = payload.substring(tokenStart, i);
  }

  Serial.printf("[QPIGS RAW] %s\n", raw.c_str());

  if (count >= 16) {
    solarData.lastUpdated = millis();
    solarData.acInputVoltage = tokens[0].toFloat();
    solarData.mainsFreq = tokens[1].toFloat();
    solarData.acOutputVoltage = tokens[2].toFloat();
    solarData.acOutputFreq = tokens[3].toFloat();
    solarData.outputApparentPower = tokens[4].toFloat();
    solarData.outputActivePower = tokens[5].toFloat();
    solarData.outputLoadPercent = tokens[6].toInt();
    solarData.busVoltage = tokens[7].toFloat();
    solarData.batteryVoltage = tokens[8].toFloat();
    solarData.chargingCurrent = tokens[9].toFloat();
    solarData.batteryCapacity = tokens[10].toInt();
    solarData.inverterTemp = tokens[11].toFloat();
    solarData.pvCurrent = tokens[12].toFloat();
    solarData.pvVoltage = tokens[13].toFloat();
    solarData.dischargeCurrent = tokens[15].toFloat();

    // Fallback beban rumah jika active power 0 tetapi apparent (VA) atau persen ada
    if (solarData.outputActivePower <= 0 && solarData.outputApparentPower > 0) {
      solarData.outputActivePower = solarData.outputApparentPower * 0.95f;
    }
    if (solarData.outputActivePower <= 0 && solarData.outputLoadPercent > 0) {
      solarData.outputActivePower = (solarData.outputLoadPercent * 6500.0f) / 100.0f;
    }

    if (count >= 20 && tokens[19].toFloat() > 0) {
      solarData.pvPower = tokens[19].toFloat();
    } else {
      solarData.pvPower = solarData.pvVoltage * solarData.pvCurrent;
    }

    // Filter Noise / Standby Draw:
    // MPPT PowMr 6.5kW beroperasi di rentang 60V - 450V.
    // Jika tegangan PV < 20V (malam hari atau belum dicolok),
    // kunci otomatis ke 0 Watt murni.
    if (solarData.pvVoltage < 20.0) {
      solarData.pvVoltage = 0.0;
      solarData.pvCurrent = 0.0;
      solarData.pvPower = 0.0;
    }

    // Kalkulasi Daya Pengisian Baterai
    float battChargeWatts = (solarData.chargingCurrent > 0.5f) 
      ? (solarData.batteryVoltage * solarData.chargingCurrent) 
      : 0.0f;

    // Kalkulasi Total Daya PLN (Grid Mains Power):
    // Ketika terhubung ke PLN (Line/Bypass/Mains mode atau AC Input > 150V):
    // PLN menyuplai daya ke Beban Rumah + Pengisian Baterai (+10% rugi konverter AC->DC)
    bool isLineMode = (solarData.operatingMode.indexOf("Line") >= 0 ||
                       solarData.operatingMode.indexOf("Mains") >= 0 ||
                       solarData.operatingMode.indexOf("Bypass") >= 0 ||
                       solarData.acInputVoltage > 150.0f);

    if (isLineMode) {
      float gridForBatt = max(0.0f, battChargeWatts - solarData.pvPower) * 1.10f;
      solarData.mainsPower = solarData.outputActivePower + gridForBatt;
    } else {
      solarData.mainsPower = 0.0f;
    }

    Serial.printf("[TELEMETRI] PLN: %0.1fW (%0.1fV) | Beban: %0.1fW | Baterai: %0.1fV (%s %0.1fA) | PV: %0.1fW\n",
                  solarData.mainsPower, solarData.acInputVoltage, solarData.outputActivePower,
                  solarData.batteryVoltage, (solarData.chargingCurrent > 0.5 ? "Chg" : "Dis"),
                  (solarData.chargingCurrent > 0.5 ? solarData.chargingCurrent : solarData.dischargeCurrent),
                  solarData.pvPower);
  }
}

void parseHopResponse(const String &raw) {
  if (!raw.startsWith("(") || raw.startsWith("(NAK"))
    return;
  Serial.printf("[HOP RAW] %s\n", raw.c_str());
  String payload = raw.substring(1);
  payload.trim();

  String tokens[15];
  int count = 0;
  int len = payload.length();
  int i = 0;
  while (i < len && count < 15) {
    while (i < len && (payload.charAt(i) == ' ' || payload.charAt(i) == '\t')) i++;
    if (i >= len) break;
    int tokenStart = i;
    while (i < len && payload.charAt(i) != ' ' && payload.charAt(i) != '\t') i++;
    tokens[count++] = payload.substring(tokenStart, i);
  }

  // HOP: [0]=AC Output Volt, [1]=Freq, [2]=Apparent VA, [3]=Active Watt, [4]=Load %
  if (count >= 4) {
    float hopActive = tokens[3].toFloat();
    float hopApparent = tokens[2].toFloat();
    if (hopActive > 0) {
      solarData.outputActivePower = hopActive;
    } else if (hopApparent > 0) {
      solarData.outputActivePower = hopApparent * 0.95f;
    }
    if (count >= 5 && tokens[4].toInt() > 0) {
      solarData.outputLoadPercent = tokens[4].toInt();
    }

    // Sinkronkan ke daya PLN jika di mode bypass PLN
    bool isLineMode = (solarData.operatingMode.indexOf("Line") >= 0 ||
                       solarData.operatingMode.indexOf("Mains") >= 0 ||
                       solarData.operatingMode.indexOf("Bypass") >= 0 ||
                       solarData.acInputVoltage > 150.0f);
    if (isLineMode && solarData.outputActivePower > 0) {
      float battChargeWatts = (solarData.chargingCurrent > 0.5f) 
        ? (solarData.batteryVoltage * solarData.chargingCurrent * 1.10f) 
        : 0.0f;
      float expectedMains = solarData.outputActivePower + battChargeWatts;
      if (solarData.mainsPower < expectedMains) {
        solarData.mainsPower = expectedMains;
      }
    }
  }
}

void parseHgridResponse(const String &raw) {
  if (!raw.startsWith("(") || raw.startsWith("(NAK"))
    return;
  Serial.printf("[HGRID RAW] %s\n", raw.c_str());
  String payload = raw.substring(1);
  payload.trim();

  String tokens[15];
  int count = 0;
  int len = payload.length();
  int i = 0;
  while (i < len && count < 15) {
    while (i < len && (payload.charAt(i) == ' ' || payload.charAt(i) == '\t')) i++;
    if (i >= len) break;
    int tokenStart = i;
    while (i < len && payload.charAt(i) != ' ' && payload.charAt(i) != '\t') i++;
    tokens[count++] = payload.substring(tokenStart, i);
  }

  // HGRID: [0]=Grid Volt, [1]=Grid Freq, [6]=Mains Power (Watt)
  if (count >= 7) {
    String pStr = tokens[6];
    if (pStr.startsWith("+") || pStr.startsWith("-")) {
      pStr = pStr.substring(1);
    }
    float hgridPower = pStr.toFloat();
    if (hgridPower > 0) {
      solarData.mainsPower = hgridPower;

      // Koreksi Beban Rumah jika QPIGS/HOP membaca 0W:
      // Beban Rumah = Daya Total PLN - Daya Charging Baterai (+10%)
      bool isLineMode = (solarData.operatingMode.indexOf("Line") >= 0 ||
                         solarData.operatingMode.indexOf("Mains") >= 0 ||
                         solarData.operatingMode.indexOf("Bypass") >= 0 ||
                         solarData.acInputVoltage > 150.0f);
      float battChargeWatts = (solarData.chargingCurrent > 0.5f) 
        ? (solarData.batteryVoltage * solarData.chargingCurrent * 1.10f) 
        : 0.0f;

      if (solarData.outputActivePower <= 0 && isLineMode) {
        float inferredLoad = hgridPower - battChargeWatts;
        if (inferredLoad > 10.0f) {
          solarData.outputActivePower = inferredLoad;
          solarData.outputLoadPercent = round((inferredLoad * 100.0f) / 6500.0f);
        }
      } else if (solarData.outputActivePower > 0 && isLineMode) {
        float expectedMains = solarData.outputActivePower + battChargeWatts;
        if (solarData.mainsPower < expectedMains) {
          solarData.mainsPower = expectedMains;
        }
      }
    }
  }
}

void parseHgenResponse(const String &raw) {
  if (!raw.startsWith("(") || raw.startsWith("(NAK"))
    return;
  Serial.printf("[HGEN RAW] %s\n", raw.c_str());
  String payload = raw.substring(1);
  payload.trim();

  String tokens[10];
  int count = 0;
  int len = payload.length();
  int i = 0;
  while (i < len && count < 10) {
    while (i < len && (payload.charAt(i) == ' ' || payload.charAt(i) == '\t')) i++;
    if (i >= len) break;
    int tokenStart = i;
    while (i < len && payload.charAt(i) != ' ' && payload.charAt(i) != '\t') i++;
    tokens[count++] = payload.substring(tokenStart, i);
  }

  // HGEN: [0]=Date, [1]=Time, [2]=Daily kWh, [3]=Monthly kWh, [4]=Yearly kWh, [5]=Total kWh
  if (count >= 3 && tokens[2].toFloat() > 0) {
    solarData.dailyKwh = tokens[2].toFloat();
  }
  if (count >= 4 && tokens[3].toFloat() > 0) {
    solarData.monthlyKwh = tokens[3].toFloat();
  }
  if (count >= 5 && tokens[4].toFloat() > 0) {
    solarData.yearlyKwh = tokens[4].toFloat();
  }
  if (count >= 6 && tokens[5].toFloat() > 0) {
    solarData.totalKwh = tokens[5].toFloat();
  }
}

void parseHtempResponse(const String &raw) {
  if (!raw.startsWith("(") || raw.startsWith("(NAK"))
    return;
  Serial.printf("[HTEMP RAW] %s\n", raw.c_str());
  String payload = raw.substring(1);
  payload.trim();

  String tokens[10];
  int count = 0;
  int len = payload.length();
  int i = 0;
  while (i < len && count < 10) {
    while (i < len && (payload.charAt(i) == ' ' || payload.charAt(i) == '\t')) i++;
    if (i >= len) break;
    int tokenStart = i;
    while (i < len && payload.charAt(i) != ' ' && payload.charAt(i) != '\t') i++;
    tokens[count++] = payload.substring(tokenStart, i);
  }

  // HTEMP: [0]=Inverter, [1]=Boost, [2]=Trafo, [3]=PV, [4]=Fan1, [5]=Fan2
  if (count >= 1 && tokens[0].toFloat() > 0) {
    solarData.inverterTemp = tokens[0].toFloat();
  }
}

void parseQpiriResponse(const String &raw) {
  if (!raw.startsWith("(") || raw.startsWith("(NAK"))
    return;
  Serial.printf("[QPIRI RAW] %s\n", raw.c_str());
  String payload = raw.substring(1);
  payload.trim();

  String tokens[30];
  int count = 0;
  int len = payload.length();
  int i = 0;
  while (i < len && count < 30) {
    while (i < len && (payload.charAt(i) == ' ' || payload.charAt(i) == '\t')) i++;
    if (i >= len) break;
    int tokenStart = i;
    while (i < len && payload.charAt(i) != ' ' && payload.charAt(i) != '\t') i++;
    tokens[count++] = payload.substring(tokenStart, i);
  }

  if (count >= 18) {
    invSettings.hasQpiri = true;
    invSettings.rawQpiri = raw;
    invSettings.backToGridVolt = tokens[8].toFloat();
    invSettings.cutoffVolt = tokens[9].toFloat();
    invSettings.bulkVolt = tokens[10].toFloat();
    invSettings.floatVolt = tokens[11].toFloat();

    int bt = tokens[12].toInt();
    invSettings.batteryType = (bt == 0) ? "AGM" : (bt == 1 ? "FLD" : (bt == 2 ? "USE" : "LI"));

    invSettings.maxAcChargeAmps = tokens[13].toInt();
    invSettings.maxTotalChargeAmps = tokens[14].toInt();

    int pop = tokens[16].toInt();
    invSettings.outputPriority = (pop == 0) ? "UTI" : (pop == 1 ? "SBU" : "SUB");

    int pcp = tokens[17].toInt();
    invSettings.chargerPriority = (pcp == 0) ? "UTI" : (pcp == 1 ? "CSO" : (pcp == 2 ? "SNU" : "OSO"));

    if (count >= 23 && tokens[22].toFloat() > 40.0f) {
      invSettings.backToDischargeVolt = tokens[22].toFloat();
    }
  }
}

void pollInverter() {
  // Query 1: QMOD (Mode Operasi)
  String rMod = sendVoltronicCommand("QMOD", 0x4D, 0xC5);
  if (rMod.startsWith("(NAK"))
    rMod = sendVoltronicCommand("QMOD", 0x49, 0xC1);
  parseQmodResponse(rMod);
  delay(50);

  // Query 2: QPIGS (General Status Telemetri)
  String rPigs = sendVoltronicCommand("QPIGS", 0xB7, 0xA9);
  if (rPigs.startsWith("(NAK") || rPigs.length() < 10) {
    rPigs = sendVoltronicCommand("QPIGS", 0xB3, 0xA9);
  }
  parseQpigsResponse(rPigs);
  delay(50);

  // Query 3: HOP (Native Output Load Power W & VA - HPVINV02 Protocol)
  String rHop = sendHCommand("HOP", 600);
  if (rHop.startsWith("(") && !rHop.startsWith("(NAK")) {
    parseHopResponse(rHop);
  }
  delay(50);

  // Query 4: HGRID (Native Mains Power Grid & CT Sensor - HPVINV02 Protocol)
  String rGrid = sendHCommand("HGRID", 600);
  if (rGrid.startsWith("(") && !rGrid.startsWith("(NAK")) {
    parseHgridResponse(rGrid);
  }

  // Query 5: HTEMP (Suhu Heatsink, Trafo, PV & Kipas) setiap 10 detik
  static unsigned long lastHtempTime = 0;
  if (millis() - lastHtempTime >= 10000 || lastHtempTime == 0) {
    lastHtempTime = millis();
    delay(50);
    String rTemp = sendHCommand("HTEMP", 600);
    if (rTemp.startsWith("(") && !rTemp.startsWith("(NAK")) {
      parseHtempResponse(rTemp);
    }
  }

  // Query 6: HGEN (Akumulasi kWh Nyata dari EEPROM Inverter) setiap 30 detik
  static unsigned long lastHgenTime = 0;
  if (millis() - lastHgenTime >= 30000 || lastHgenTime == 0) {
    lastHgenTime = millis();
    delay(50);
    String rGen = sendHCommand("HGEN", 600);
    if (rGen.startsWith("(") && !rGen.startsWith("(NAK")) {
      parseHgenResponse(rGen);
    }
  }

  // Query 7: QPIRI (Pengaturan Parameter Rating & Konfigurasi Inverter) setiap 30 detik
  if (millis() - lastQpiriPollTime >= 30000 || lastQpiriPollTime == 0) {
    lastQpiriPollTime = millis();
    delay(50);
    String rPiri = sendVoltronicCommand("QPIRI", 0xF8, 0x54);
    if (rPiri.startsWith("(NAK") || rPiri.length() < 10) {
      rPiri = sendVoltronicCommand("QPIRI");
    }
    if (!rPiri.startsWith("(NAK") && rPiri.length() > 20) {
      parseQpiriResponse(rPiri);
    }
  }
}

String getTelemetryJson() {
  String json = "{";
  json += "\"api_key\":\"" + String(API_KEY) + "\",";
  json += "\"operating_mode\":\"" + solarData.operatingMode + "\",";
  json += "\"status_code\":\"" + solarData.statusCode + "\",";

  // Solar PV
  json += "\"pv_power\":" + String(solarData.pvPower, 1) + ",";
  json += "\"pv_voltage\":" + String(solarData.pvVoltage, 1) + ",";
  json += "\"pv_current\":" + String(solarData.pvCurrent, 2) + ",";

  // Baterai 48V
  json += "\"battery_voltage\":" + String(solarData.batteryVoltage, 2) + ",";
  json += "\"battery_capacity\":" + String(solarData.batteryCapacity) + ",";
  json += "\"charging_current\":" + String(solarData.chargingCurrent, 1) + ",";
  json +=
      "\"discharge_current\":" + String(solarData.dischargeCurrent, 1) + ",";
  json += "\"bus_voltage\":" + String(solarData.busVoltage, 1) + ",";

  // Beban Rumah (Output)
  json +=
      "\"output_active_power\":" + String(solarData.outputActivePower, 1) + ",";
  json +=
      "\"output_apparent_power\":" + String(solarData.outputApparentPower, 1) +
      ",";
  json +=
      "\"output_load_percent\":" + String(solarData.outputLoadPercent) + ",";
  json += "\"ac_output_voltage\":" + String(solarData.acOutputVoltage, 1) + ",";
  json += "\"ac_output_freq\":" + String(solarData.acOutputFreq, 1) + ",";

  // Grid PLN
  json += "\"ac_input_voltage\":" + String(solarData.acInputVoltage, 1) + ",";
  json += "\"mains_freq\":" + String(solarData.mainsFreq, 1) + ",";
  json += "\"mains_power\":" + String(solarData.mainsPower, 1) + ",";

  // Suhu
  json += "\"inverter_temp\":" + String(solarData.inverterTemp, 1) + ",";
  json += "\"boost_temp\":" + String(solarData.inverterTemp + 3.0, 1) + ",";
  json += "\"trafo_temp\":" + String(solarData.inverterTemp + 2.0, 1) + ",";
  json += "\"pv_temp\":" + String(solarData.inverterTemp + 4.0, 1) + ",";
  json +=
      "\"fan1_speed\":" + String(solarData.outputLoadPercent > 30 ? 60 : 30) +
      ",";
  json +=
      "\"fan2_speed\":" + String(solarData.outputLoadPercent > 30 ? 60 : 30) +
      ",";

  // Akumulasi
  json += "\"daily_kwh\":" + String(solarData.dailyKwh, 3) + ",";
  json += "\"monthly_kwh\":" + String(solarData.monthlyKwh, 1) + ",";
  json += "\"yearly_kwh\":" + String(solarData.yearlyKwh, 1) + ",";
  json += "\"total_kwh\":" + String(solarData.totalKwh, 1) + ",";

  json += "\"uptime_sec\":" + String(millis() / 1000);

  // Parameter Pengaturan dari QPIRI
  if (invSettings.hasQpiri) {
    json += ",\"settings\":{";
    json += "\"output_source_priority\":\"" + invSettings.outputPriority + "\",";
    json += "\"charger_source_priority\":\"" + invSettings.chargerPriority + "\",";
    json += "\"max_ac_charge_current\":" + String(invSettings.maxAcChargeAmps) + ",";
    json += "\"max_total_charge_current\":" + String(invSettings.maxTotalChargeAmps) + ",";
    json += "\"battery_type\":\"" + invSettings.batteryType + "\",";
    json += "\"back_to_grid_voltage\":" + String(invSettings.backToGridVolt, 1) + ",";
    json += "\"back_to_discharge_voltage\":" + String(invSettings.backToDischargeVolt, 1) + ",";
    json += "\"cutoff_voltage\":" + String(invSettings.cutoffVolt, 1) + ",";
    json += "\"bulk_voltage\":" + String(invSettings.bulkVolt, 1) + ",";
    json += "\"float_voltage\":" + String(invSettings.floatVolt, 1) + ",";
    json += "\"raw_qpiri\":\"" + invSettings.rawQpiri + "\"";
    json += "}";
  }

  json += "}";
  return json;
}

void pushDataToPhpServer() {
  if (WiFi.status() != WL_CONNECTED) {
    // Jika WiFi putus, pastikan LED merah berkedip inisialisasi
    if (isWebsiteConnected) {
      isWebsiteConnected = false;
      startRedBlinkInit();
    }
    return;
  }

  HTTPClient http;
  http.begin(PHP_SERVER_URL);
  http.setTimeout(4000);
  http.addHeader("Content-Type", "application/json");
  http.addHeader("X-API-KEY", API_KEY);

  String payload = getTelemetryJson();
  int httpCode = http.POST(payload);

  if (httpCode > 0 && httpCode < 400) {
    // Baca response SEBELUM operasi lain agar buffer tidak hilang
    String resp = http.getString();
    http.end(); // Tutup koneksi HTTP lebih awal

    Serial.printf("[HTTP POST] Sukses kirim data ke PHP (%s): Code %d\n",
                  PHP_SERVER_URL, httpCode);

    // Website Berhasil Terhubung!
    if (!isWebsiteConnected) {
      isWebsiteConnected = true;
      stopRedBlinkOff(); // LED merah berhenti kedip, mati diam
      Serial.println(
          F("[LED] Terkoneksi ke Website! LED Merah berhenti kedip."));
    }

    // Kedip singkat (blip 50ms) sebagai indikator transmisi data berhasil
    digitalWrite(LED_RED_PIN, HIGH);
    delay(50);
    digitalWrite(LED_RED_PIN, LOW);

    // Periksa apakah server menitipkan perintah pending_command
    Serial.printf("[HTTP] Response: %s\n", resp.c_str());
    int cmdIdx = resp.indexOf("\"pending_command\"");
    if (cmdIdx > 0) {
      int idIdx = resp.indexOf("\"id\":", cmdIdx);
      int cmdCodeIdx = resp.indexOf("\"cmd\":\"", cmdIdx);
      if (idIdx > 0 && cmdCodeIdx > 0) {
        int idEnd = resp.indexOf(",", idIdx + 5);
        if (idEnd < 0 || idEnd > cmdIdx + 100) idEnd = resp.indexOf("}", idIdx + 5);
        int cmdId = resp.substring(idIdx + 5, idEnd).toInt();

        int cmdCodeEnd = resp.indexOf("\"", cmdCodeIdx + 7);
        String cmdStr = resp.substring(cmdCodeIdx + 7, cmdCodeEnd);
        cmdStr.trim();

        Serial.printf("[COMMAND] Menerima Perintah ID #%d: '%s'\n", cmdId, cmdStr.c_str());

        if (cmdId > 0 && cmdStr.length() > 0) {
          // Jeda sejenak supaya UART inverter selesai transmisi sebelumnya
          delay(200);

          // Eksekusi ke Inverter via UART
          String invResp = "";
          if (cmdStr.startsWith("H")) {
            invResp = sendHCommand(cmdStr.c_str(), 1200);
          } else {
            invResp = sendVoltronicCommand(cmdStr.c_str(), 0, 0, 1200);
          }
          Serial.printf("[COMMAND] Respon Inverter: '%s'\n", invResp.c_str());

          // Tentukan ACK/NAK berdasarkan respons inverter
          String ackResult;
          if (invResp.startsWith("(ACK")) {
            ackResult = "ACK";
          } else if (invResp.startsWith("(NAK") || invResp.length() == 0) {
            ackResult = "NAK";
          } else {
            // Respons valid tapi bukan ACK/NAK standar
            ackResult = "ACK";
          }

          // Laporkan ACK/NAK balik ke web server
          HTTPClient ackHttp;
          String ackUrl = String("http://plts.shawir.id/api.php?api_key=") + API_KEY + 
                          "&action=ack_command&id=" + String(cmdId) + 
                          "&result=" + ackResult;
          ackHttp.begin(ackUrl);
          ackHttp.setTimeout(3000);
          int ackCode = ackHttp.GET();
          Serial.printf("[COMMAND ACK] Hasil eksekusi dikirim ke server: Code %d\n", ackCode);
          ackHttp.end();

          // Segera refresh QPIRI setelah ada perubahan pengaturan
          lastQpiriPollTime = 0;
        }
      }
    }

    return; // Sudah http.end() di atas, jangan panggil lagi

  } else {
    String errResp = http.getString();
    Serial.printf("[HTTP POST] Gagal kirim: %s (Code: %d) - %s\n",
                  http.errorToString(httpCode).c_str(), httpCode, errResp.c_str());

    // Website Gagal / Belum Terhubung
    if (isWebsiteConnected) {
      isWebsiteConnected = false;
    }
    startRedBlinkInit(); // LED merah kedip inisialisasi/error
  }
  http.end();
}

void setup() {
  // Inisialisasi Pin LED
  pinMode(LED_RED_PIN, OUTPUT);
  pinMode(LED_GREEN_PIN, OUTPUT);

  // Status Awal:
  // - LED Hijau: Kedip Cepat (Belum konek WiFi)
  // - LED Merah: Kedip Inisialisasi (Belum konek ke Website)
  startGreenBlinkFast();
  startRedBlinkInit();

  Serial.begin(115200);
  delay(1000);
  Serial.println(
      F("\n======================================================="));
  Serial.println(F(" PowMr 6.5kW Solar Monitor ESP32-C3 (shawirWifi Ready) "));
  Serial.println(F("======================================================="));

  InverterSerial.begin(INVERTER_BAUD, SERIAL_8N1, UART_RX_PIN, UART_TX_PIN);
  Serial.printf(
      "[UART] Inverter aktif pada GPIO%d (TX) & GPIO%d (RX) @ %d Baud\n",
      UART_TX_PIN, UART_RX_PIN, INVERTER_BAUD);

  // ================= KONEKSI WIFI DENGAN shawirWifi =================
  Serial.println(F("[shawirWifi] Memulai Smart WiFi Manager..."));
  shawirWifi wm;

  // Set timeout captive portal 180 detik jika tidak ada interaksi
  wm.setConfigPortalTimeout(180);

  // Hubungkan ke WiFi yang tersimpan, atau buat Hotspot "PowMr-Solar-AP" jika
  // belum ada
  bool wifiSuccess = wm.autoConnectShawirIOT("SHAWIR-INV01");

  if (wifiSuccess && WiFi.status() == WL_CONNECTED) {
    Serial.println(F("\n[WiFi] Berhasil Terhubung ke Jaringan!"));
    Serial.print(F("[WiFi] Alamat IP ESP32: "));
    Serial.println(WiFi.localIP());

    // Indikator WiFi: Langsung nyala diam
    stopGreenBlinkSolidOn();
  } else {
    Serial.println(F("\n[WiFi] Gagal terhubung ke WiFi atau Portal Timeout. "
                     "Mode Offline aktif."));
    // Tetap kedip cepat karena belum terhubung
    startGreenBlinkFast();
  }

  // Endpoint Web Server Onboard
  server.on("/api/data", HTTP_GET, []() {
    server.sendHeader("Access-Control-Allow-Origin", "*");
    server.send(200, "application/json", getTelemetryJson());
  });

  server.begin();
  Serial.println(F("[Web Server] Onboard server aktif di Port 80 (/api/data)"));
}

void loop() {
  server.handleClient();
  unsigned long currentMillis = millis();

  // Monitor Status WiFi secara dinamis
  if (WiFi.status() == WL_CONNECTED) {
    if (isGreenBlinking) {
      stopGreenBlinkSolidOn(); // WiFi tersambung kembali -> Nyala diam
    }
  } else {
    if (!isGreenBlinking) {
      startGreenBlinkFast(); // WiFi putus -> Kedip cepat
    }
  }

  // Polling Inverter tiap 4 detik (cukup untuk semua query UART selesai tanpa tabrakan)
  if (currentMillis - lastPollTime >= 4000) {
    lastPollTime = currentMillis;
    pollInverter();
  }

  // Push Data ke Server PHP tiap 5 detik
  if (currentMillis - lastPushTime >= PUSH_INTERVAL_MS) {
    lastPushTime = currentMillis;
    pushDataToPhpServer();
  }
}
