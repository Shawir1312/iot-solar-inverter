/*
 * ====================================================================
 * Program Arduino IDE: Monitoring Inverter PowMr 6.5kW (POW-HVM6.5K-48V-E)
 * Mikrokontroler : ESP32-C3 Mini / SuperMini
 * Komunikasi      : Hardware UART ke Level Shifter MAX3232 (2400 Baud, 8N1)
 * Pin ESP32-C3    : TX = GPIO6 (ke RXD MAX3232 -> Pin 3 RJ45 Inverter)
 *                   RX = GPIO7 (ke TXD MAX3232 -> Pin 6 RJ45 Inverter)
 * Fitur           : Web Server Onboard (/api/data) + Push Data ke PHP Server
 * ====================================================================
 */

#include <WiFi.h>
#include <WebServer.h>
#include <HTTPClient.h>

// ================= PENGATURAN WI-FI & PHP SERVER =================
const char* WIFI_SSID     = "NAMA_WIFI_ANDA";
const char* WIFI_PASSWORD = "PASSWORD_WIFI_ANDA";

// URL file api.php pada Web Server PHP Anda
// Contoh: "http://192.168.1.10/php-monitoring/api.php"
const char* PHP_SERVER_URL = "http://192.168.1.100/php-monitoring/api.php";
const char* API_KEY        = "powmr_secret_key_123"; // Harus sama dengan config.php

// Interval Pengiriman Data ke PHP Server (dalam milidetik)
const unsigned long PUSH_INTERVAL_MS = 15000; // Kirim tiap 15 detik

// ================= PIN UART ESP32-C3 =================
#define UART_TX_PIN 6 // GPIO 6
#define UART_RX_PIN 7 // GPIO 7
#define INVERTER_BAUD 2400

HardwareSerial InverterSerial(1);
WebServer server(80);

// ================= STRUKTUR DATA TELEMETRI =================
struct InverterData {
  // Mode & Status
  String operatingMode = "Standby";
  String statusCode = "00";
  String batteryType = "PYL";
  String protocol = "HPVINV02";
  String firmware = "40.05";
  
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
  String mainsDirection = "Standby";
  
  // Suhu & Kipas
  float inverterTemp = 0.0;       // °C
  float boostTemp = 0.0;          // °C
  float trafoTemp = 0.0;          // °C
  float pvTemp = 0.0;             // °C
  int   fan1Speed = 0;            // %
  int   fan2Speed = 0;            // %
  
  // Penghitung Energi Asli (HGEN)
  String genDate = "";
  String genTime = "";
  float dailyKwh = 0.0;           // kWh
  float monthlyKwh = 0.0;         // kWh
  float yearlyKwh = 0.0;          // kWh
  float totalKwh = 0.0;           // kWh
  
  unsigned long lastUpdated = 0;
} solarData;

unsigned long lastPollTime = 0;
unsigned long lastPushTime = 0;
int pollStep = 0;

// ================= HELPER PARSER PROTOKOL H =================
// Memisahkan token yang dipisahkan spasi pada respons berawalan '('
void parseTokens(const String& payload, String tokens[], int maxTokens, int& tokenCount) {
  tokenCount = 0;
  int start = 0;
  int len = payload.length();
  
  // Abaikan karakter '(' pembuka jika ada
  if (len > 0 && payload.charAt(0) == '(') {
    start = 1;
  }
  
  while (start < len && tokenCount < maxTokens) {
    while (start < len && (payload.charAt(start) == ' ' || payload.charAt(start) == '\r' || payload.charAt(start) == '\n')) {
      start++;
    }
    if (start >= len) break;
    
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

// Kirim perintah ASCII dengan akhiran \r (0x0D) dan baca respons
String sendHCommand(const char* cmd, unsigned long timeoutMs = 800) {
  // Bersihkan buffer RX lama
  while (InverterSerial.available()) {
    InverterSerial.read();
  }
  
  // Kirim perintah
  InverterSerial.print(cmd);
  InverterSerial.write(0x0D);
  InverterSerial.flush();
  
  // Baca balasan sampai '\r'
  String response = "";
  unsigned long start = millis();
  while (millis() - start < timeoutMs) {
    if (InverterSerial.available()) {
      char c = (char)InverterSerial.read();
      if (c == '\r') {
        break; // Karakter terminator frame
      }
      if (c != '\n') {
        response += c;
      }
    }
    yield();
  }
  response.trim();
  return response;
}

// ================= PARSING PERINTAH TELEMETRI =================
void decodeResponse(const String& cmd, const String& raw) {
  if (raw.length() == 0 || raw.charAt(0) != '(') return;
  
  String tokens[30];
  int count = 0;
  parseTokens(raw, tokens, 30, count);
  if (count == 0) return;
  
  solarData.lastUpdated = millis();
  
  if (cmd == "HSTS" && count >= 2) {
    solarData.statusCode = tokens[0];
    char modeChar = tokens[1].charAt(0);
    if (modeChar == 'L') solarData.operatingMode = "Line / Mains Mode (PLN)";
    else if (modeChar == 'B') solarData.operatingMode = "Battery Mode (PLTS)";
    else if (modeChar == 'P') solarData.operatingMode = "Power On Mode";
    else if (modeChar == 'S') solarData.operatingMode = "Standby Mode";
    else if (modeChar == 'F') solarData.operatingMode = "Fault / Error Mode";
    else solarData.operatingMode = "Mode " + String(modeChar);
  }
  else if (cmd == "HGRID" && count >= 7) {
    solarData.acInputVoltage = tokens[0].toFloat();
    solarData.mainsFreq = tokens[1].toFloat();
    solarData.mainsPower = tokens[6].toFloat();
  }
  else if (cmd == "HOP" && count >= 5) {
    solarData.acOutputVoltage = tokens[0].toFloat();
    solarData.acOutputFreq = tokens[1].toFloat();
    solarData.outputApparentPower = tokens[2].toFloat();
    solarData.outputActivePower = tokens[3].toFloat();
    solarData.outputLoadPercent = tokens[4].toInt();
  }
  else if (cmd == "HBAT" && count >= 6) {
    solarData.batteryVoltage = tokens[1].toFloat();
    solarData.batteryCapacity = tokens[2].toInt();
    solarData.chargingCurrent = tokens[3].toFloat();
    solarData.dischargeCurrent = tokens[4].toFloat();
    solarData.busVoltage = tokens[5].toFloat();
  }
  else if (cmd == "HPV" && count >= 3) {
    solarData.pvVoltage = tokens[0].toFloat();
    solarData.pvCurrent = tokens[1].toFloat();
    solarData.pvPower = tokens[2].toFloat();
  }
  else if (cmd == "HTEMP" && count >= 6) {
    solarData.inverterTemp = tokens[0].toFloat();
    solarData.boostTemp = tokens[1].toFloat();
    solarData.trafoTemp = tokens[2].toFloat();
    solarData.pvTemp = tokens[3].toFloat();
    solarData.fan1Speed = tokens[4].toInt();
    solarData.fan2Speed = tokens[5].toInt();
  }
  else if (cmd == "HGEN" && count >= 6) {
    solarData.genDate = tokens[0];
    solarData.genTime = tokens[1];
    solarData.dailyKwh = tokens[2].toFloat();
    solarData.monthlyKwh = tokens[3].toFloat();
    solarData.yearlyKwh = tokens[4].toFloat();
    solarData.totalKwh = tokens[5].toFloat();
  }
}

// ================= STATE MACHINE POLLING INVERTER =================
void pollInverter() {
  switch (pollStep) {
    case 0: decodeResponse("HSTS",  sendHCommand("HSTS")); break;
    case 1: decodeResponse("HGRID", sendHCommand("HGRID")); break;
    case 2: decodeResponse("HOP",   sendHCommand("HOP")); break;
    case 3: decodeResponse("HBAT",  sendHCommand("HBAT")); break;
    case 4: decodeResponse("HPV",   sendHCommand("HPV")); break;
    case 5: decodeResponse("HTEMP", sendHCommand("HTEMP")); break;
    case 6: decodeResponse("HGEN",  sendHCommand("HGEN")); break;
  }
  pollStep = (pollStep + 1) % 7;
}

// ================= MEMBENTUK JSON TELEMETRI =================
String getTelemetryJson() {
  String json = "{";
  json += "\"operating_mode\":\"" + solarData.operatingMode + "\",";
  json += "\"status_code\":\"" + solarData.statusCode + "\",";
  
  // Solar PV
  json += "\"pv_power\":" + String(solarData.pvPower, 1) + ",";
  json += "\"pv_voltage\":" + String(solarData.pvVoltage, 1) + ",";
  json += "\"pv_current\":" + String(solarData.pvCurrent, 2) + ",";
  
  // Baterai 48V
  json += "\"battery_voltage\":" + String(solarData.batteryVoltage, 1) + ",";
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
  
  // Suhu & Kipas
  json += "\"inverter_temp\":" + String(solarData.inverterTemp, 1) + ",";
  json += "\"boost_temp\":" + String(solarData.boostTemp, 1) + ",";
  json += "\"trafo_temp\":" + String(solarData.trafoTemp, 1) + ",";
  json += "\"pv_temp\":" + String(solarData.pvTemp, 1) + ",";
  json += "\"fan1_speed\":" + String(solarData.fan1Speed) + ",";
  json += "\"fan2_speed\":" + String(solarData.fan2Speed) + ",";
  
  // Akumulasi Energi (HGEN)
  json += "\"gen_date\":\"" + solarData.genDate + "\",";
  json += "\"gen_time\":\"" + solarData.genTime + "\",";
  json += "\"daily_kwh\":" + String(solarData.dailyKwh, 3) + ",";
  json += "\"monthly_kwh\":" + String(solarData.monthlyKwh, 1) + ",";
  json += "\"yearly_kwh\":" + String(solarData.yearlyKwh, 1) + ",";
  json += "\"total_kwh\":" + String(solarData.totalKwh, 1) + ",";
  
  json += "\"uptime_sec\":" + String(millis() / 1000);
  json += "}";
  return json;
}

// ================= PUSH DATA KE PHP SERVER VIA HTTP POST =================
void pushDataToPhpServer() {
  if (WiFi.status() != WL_CONNECTED) return;
  
  HTTPClient http;
  http.begin(PHP_SERVER_URL);
  http.addHeader("Content-Type", "application/json");
  http.addHeader("X-API-KEY", API_KEY);
  
  String payload = getTelemetryJson();
  int httpCode = http.POST(payload);
  
  if (httpCode > 0) {
    Serial.printf("[HTTP POST] Sukses kirim data ke PHP: Code %d\n", httpCode);
  } else {
    Serial.printf("[HTTP POST] Gagal kirim: %s\n", http.errorToString(httpCode).c_str());
  }
  http.end();
}

// ================= SETUP UTAMA =================
void setup() {
  Serial.begin(115200);
  delay(1000);
  Serial.println("\n--- PowMr 6.5kW Solar Monitor ESP32-C3 ---");
  
  // Inisialisasi UART1 ke inverter via MAX3232 (RX=7, TX=6)
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
    Serial.print("Alamat IP ESP32: http://");
    Serial.println(WiFi.localIP());
  } else {
    Serial.println("\nWi-Fi Gagal! Mengaktifkan Access Point Darurat...");
    WiFi.softAP("PowMr-Solar-AP", "powmr12345");
    Serial.print("AP IP: ");
    Serial.println(WiFi.softAPIP());
  }
  
  // Endpoint Web Server Onboard
  server.on("/api/data", HTTP_GET, []() {
    server.sendHeader("Access-Control-Allow-Origin", "*");
    server.send(200, "application/json", getTelemetryJson());
  });
  
  server.on("/", HTTP_GET, []() {
    String html = "<!DOCTYPE html><html><head><title>PowMr ESP32-C3</title>";
    html += "<meta name='viewport' content='width=device-width, initial-scale=1'>";
    html += "<style>body{font-family:sans-serif;background:#0f172a;color:#f8fafc;padding:20px;text-align:center;}";
    html += ".card{background:#1e293b;padding:20px;border-radius:12px;display:inline-block;max-width:400px;text-align:left;}";
    html += "b{color:#38bdf8;}</style></head><body>";
    html += "<h2>PowMr 6.5kW Inverter Node</h2>";
    html += "<div class='card'>";
    html += "<p>Mode: <b>" + solarData.operatingMode + "</b></p>";
    html += "<p>Solar PV: <b>" + String(solarData.pvPower, 1) + " W</b> (" + String(solarData.pvVoltage, 1) + " V)</p>";
    html += "<p>Baterai: <b>" + String(solarData.batteryCapacity) + " %</b> (" + String(solarData.batteryVoltage, 1) + " V)</p>";
    html += "<p>Beban Rumah: <b>" + String(solarData.outputActivePower, 1) + " W</b> (" + String(solarData.outputLoadPercent) + " %)</p>";
    html += "<p>Produksi Hari Ini: <b>" + String(solarData.dailyKwh, 3) + " kWh</b></p>";
    html += "<hr><p><a style='color:#38bdf8' href='/api/data'>Buka Raw JSON /api/data</a></p>";
    html += "</div></body></html>";
    server.send(200, "text/html", html);
  });
  
  server.begin();
  Serial.println("Web Server Onboard ESP32 siap!");
}

// ================= LOOP UTAMA =================
void loop() {
  server.handleClient();
  
  // Polling Inverter setiap 500ms bergantian perintah
  unsigned long now = millis();
  if (now - lastPollTime >= 500) {
    lastPollTime = now;
    pollInverter();
  }
  
  // Kirim data ke PHP Server secara periodik
  if (now - lastPushTime >= PUSH_INTERVAL_MS) {
    lastPushTime = now;
    pushDataToPhpServer();
  }
}

