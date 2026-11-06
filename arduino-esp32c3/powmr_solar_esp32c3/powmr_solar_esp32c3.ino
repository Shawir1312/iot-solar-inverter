/*
 * ====================================================================
 * Program Arduino IDE: Monitoring Inverter PowMr 6.5kW (POW-HVM6.5K-48V)
 * Mikrokontroler : ESP32-C3 Mini / SuperMini
 * Komunikasi      : Hardware UART1 ke Level Shifter MAX3232 (2400 Baud, 8N1)
 * Protokol        : Voltronic PI30 (QPIGS, QMOD) dengan CRC-16 Checksum
 * Pin ESP32-C3    : TX = GPIO6 (ke RXD TTL MAX3232 -> Pin 2 RJ45 Inverter)
 *                   RX = GPIO7 (ke TXD TTL MAX3232 -> Pin 1 RJ45 Inverter)
 *                   VCC Modul MAX3232 -> Pin 5V ESP32
 *                   GND Modul MAX3232 -> GND ESP32 & Pin 8 RJ45 Inverter
 * Fitur           : Web Server Onboard (/api/data) + Push Data ke PHP Server
 * ====================================================================
 */

#include <WiFi.h>
#include <WebServer.h>
#include <HTTPClient.h>

// ================= PENGATURAN WI-FI & PHP SERVER =================
const char* WIFI_SSID     = "NAMA_WIFI_ANDA";
const char* WIFI_PASSWORD = "PASSWORD_WIFI_ANDA";

// URL file api.php pada Web Server PHP Anda (Ganti dengan domain / IP server Anda)
// Contoh: "http://plts.shawir.id/api.php"
const char* PHP_SERVER_URL = "http://plts.shawir.id/api.php";
const char* API_KEY        = "powmr_secret_key_123"; // Harus sama dengan config.php

// Interval Pengiriman Data ke PHP Server (dalam milidetik)
const unsigned long PUSH_INTERVAL_MS = 10000; // Kirim tiap 10 detik

// ================= PIN UART ESP32-C3 =================
#define UART_TX_PIN   6 // GPIO 6
#define UART_RX_PIN   7 // GPIO 7
#define INVERTER_BAUD 2400

HardwareSerial InverterSerial(1);
WebServer server(80);

// ================= STRUKTUR DATA TELEMETRI =================
struct InverterData {
  String operatingMode = "Standby";
  String statusCode = "00";
  String batteryType = "Lead/Lithium";
  String protocol = "PI30";
  
  // Solar PV
  float pvPower = 0.0;     // Watt
  float pvVoltage = 0.0;   // Volt
  float pvCurrent = 0.0;   // Ampere
  
  // Baterai 48V
  float batteryVoltage = 0.0;     // Volt
  int   batteryCapacity = 0;      // %
  float chargingCurrent = 0.0;    // Ampere
  float dischargeCurrent = 0.0;   // Ampere
  float busVoltage = 0.0;         // Volt
  
  // Beban AC Rumah (Output)
  float acOutputVoltage = 0.0;    // Volt
  float acOutputFreq = 0.0;       // Hz
  float outputActivePower = 0.0;  // Watt
  float outputApparentPower = 0.0;// VA
  int   outputLoadPercent = 0;    // %
  
  // Listrik PLN (Input Grid)
  float acInputVoltage = 0.0;     // Volt
  float mainsFreq = 0.0;          // Hz
  float mainsPower = 0.0;         // Watt
  
  // Suhu
  float inverterTemp = 0.0;       // °C
  float boostTemp = 0.0;
  float trafoTemp = 0.0;
  float pvTemp = 0.0;
  int   fan1Speed = 0;
  int   fan2Speed = 0;
  
  // Akumulasi Energi
  String genDate = "";
  String genTime = "";
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

String sendVoltronicCommand(const char* cmd, uint8_t forcedH = 0, uint8_t forcedL = 0, unsigned long timeoutMs = 800) {
  while (InverterSerial.available()) InverterSerial.read();

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
      if (c == '\r') break;
      if (c != '\n') resp += c;
    }
    yield();
  }
  resp.trim();
  return resp;
}

void parseQmodResponse(const String& payload) {
  if (!payload.startsWith("(") || payload.startsWith("(NAK")) return;
  char mode = payload.charAt(1);
  switch (mode) {
    case 'P': solarData.operatingMode = "Power On Mode"; break;
    case 'S': solarData.operatingMode = "Standby Mode"; break;
    case 'L': solarData.operatingMode = "Line / Mains Mode (PLN)"; break;
    case 'B': solarData.operatingMode = "Battery Mode (PLTS)"; break;
    case 'F': solarData.operatingMode = "Fault / Error Mode"; break;
    case 'D': solarData.operatingMode = "Shutdown Mode"; break;
    default:  solarData.operatingMode = "Mode " + String(mode); break;
  }
}

void parseQpigsResponse(const String& raw) {
  if (!raw.startsWith("(") || raw.startsWith("(NAK")) return;
  String payload = raw.substring(1);
  payload.trim();

  String tokens[25];
  int count = 0;
  int start = 0;
  while (start < payload.length() && count < 25) {
    int nextSpace = payload.indexOf(' ', start);
    if (nextSpace == -1) {
      tokens[count++] = payload.substring(start);
      break;
    } else {
      tokens[count++] = payload.substring(start, nextSpace);
      start = nextSpace + 1;
    }
  }

  if (count >= 16) {
    solarData.lastUpdated           = millis();
    solarData.acInputVoltage        = tokens[0].toFloat();
    solarData.mainsFreq             = tokens[1].toFloat();
    solarData.acOutputVoltage       = tokens[2].toFloat();
    solarData.acOutputFreq          = tokens[3].toFloat();
    solarData.outputApparentPower   = tokens[4].toFloat();
    solarData.outputActivePower     = tokens[5].toFloat();
    solarData.outputLoadPercent     = tokens[6].toInt();
    solarData.busVoltage            = tokens[7].toFloat();
    solarData.batteryVoltage        = tokens[8].toFloat();
    solarData.chargingCurrent       = tokens[9].toFloat();
    solarData.batteryCapacity       = tokens[10].toInt();
    solarData.inverterTemp          = tokens[11].toFloat();
    solarData.pvCurrent             = tokens[12].toFloat();
    solarData.pvVoltage             = tokens[13].toFloat();
    solarData.dischargeCurrent      = tokens[15].toFloat();
    
    if (count >= 20 && tokens[19].toFloat() > 0) {
      solarData.pvPower = tokens[19].toFloat();
    } else {
      solarData.pvPower = solarData.pvVoltage * solarData.pvCurrent;
    }
    
    // Mains power kalkulasi
    if (solarData.operatingMode.indexOf("Line") >= 0) {
      solarData.mainsPower = solarData.outputActivePower;
    } else {
      solarData.mainsPower = 0.0;
    }
  }
}

void pollInverter() {
  // Query 1: QMOD
  String rMod = sendVoltronicCommand("QMOD", 0x4D, 0xC5);
  if (rMod.startsWith("(NAK")) rMod = sendVoltronicCommand("QMOD", 0x49, 0xC1);
  parseQmodResponse(rMod);
  delay(80);

  // Query 2: QPIGS
  String rPigs = sendVoltronicCommand("QPIGS", 0xB7, 0xA9);
  if (rPigs.startsWith("(NAK") || rPigs.length() < 10) {
    rPigs = sendVoltronicCommand("QPIGS", 0xB3, 0xA9);
  }
  parseQpigsResponse(rPigs);
}

String getTelemetryJson() {
  String json = "{";
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
  json += "\"discharge_current\":" + String(solarData.dischargeCurrent, 1) + ",";
  json += "\"bus_voltage\":" + String(solarData.busVoltage, 1) + ",";
  
  // Beban Rumah (Output)
  json += "\"output_active_power\":" + String(solarData.outputActivePower, 1) + ",";
  json += "\"output_apparent_power\":" + String(solarData.outputApparentPower, 1) + ",";
  json += "\"output_load_percent\":" + String(solarData.outputLoadPercent) + ",";
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
  json += "\"fan1_speed\":" + String(solarData.outputLoadPercent > 30 ? 60 : 30) + ",";
  json += "\"fan2_speed\":" + String(solarData.outputLoadPercent > 30 ? 60 : 30) + ",";
  
  // Akumulasi
  json += "\"daily_kwh\":" + String(solarData.dailyKwh, 3) + ",";
  json += "\"monthly_kwh\":" + String(solarData.monthlyKwh, 1) + ",";
  json += "\"yearly_kwh\":" + String(solarData.yearlyKwh, 1) + ",";
  json += "\"total_kwh\":" + String(solarData.totalKwh, 1) + ",";
  
  json += "\"uptime_sec\":" + String(millis() / 1000);
  json += "}";
  return json;
}

void pushDataToPhpServer() {
  if (WiFi.status() != WL_CONNECTED) return;
  
  HTTPClient http;
  http.begin(PHP_SERVER_URL);
  http.addHeader("Content-Type", "application/json");
  http.addHeader("X-API-KEY", API_KEY);
  
  String payload = getTelemetryJson();
  int httpCode = http.POST(payload);
  
  if (httpCode > 0) {
    Serial.printf("[HTTP POST] Sukses kirim data ke PHP (%s): Code %d\n", PHP_SERVER_URL, httpCode);
  } else {
    Serial.printf("[HTTP POST] Gagal kirim: %s\n", http.errorToString(httpCode).c_str());
  }
  http.end();
}

void setup() {
  Serial.begin(115200);
  delay(1000);
  Serial.println("\n--- PowMr 6.5kW Solar Monitor ESP32-C3 ---");
  
  InverterSerial.begin(INVERTER_BAUD, SERIAL_8N1, UART_RX_PIN, UART_TX_PIN);
  Serial.printf("UART Inverter aktif pada GPIO%d (TX) & GPIO%d (RX) @ %d Baud\n", UART_TX_PIN, UART_RX_PIN, INVERTER_BAUD);
  
  // Koneksi Wi-Fi
  WiFi.mode(WIFI_STA);
  WiFi.begin(WIFI_SSID, WIFI_PASSWORD);
  Serial.print("Menghubungkan ke Wi-Fi: ");
  Serial.println(WIFI_SSID);
  
  int attempts = 0;
  while (WiFi.status() != WL_CONNECTED && attempts < 25) {
    delay(500);
    Serial.print(".");
    attempts++;
  }
  
  if (WiFi.status() == WL_CONNECTED) {
    Serial.println("\nWi-Fi Terhubung!");
    Serial.print("Alamat IP ESP32: ");
    Serial.println(WiFi.localIP());
  } else {
    Serial.println("\nWi-Fi belum tersambung, berjalan dalam mode offline.");
  }
  
  // Endpoint Web Server Onboard
  server.on("/api/data", HTTP_GET, []() {
    server.sendHeader("Access-Control-Allow-Origin", "*");
    server.send(200, "application/json", getTelemetryJson());
  });
  
  server.begin();
  Serial.println("Web Server ESP32 Onboard aktif di Port 80");
}

void loop() {
  server.handleClient();
  unsigned long currentMillis = millis();

  // Polling Inverter tiap 2.5 detik
  if (currentMillis - lastPollTime >= 2500) {
    lastPollTime = currentMillis;
    pollInverter();
  }

  // Push Data ke Server PHP tiap interval
  if (currentMillis - lastPushTime >= PUSH_INTERVAL_MS) {
    lastPushTime = currentMillis;
    pushDataToPhpServer();
  }
}
