/*
 * =======================================================================================
 * AUTO-SCANNER & DETEKTOR PROTOKOL INVERTER POWMR 6.5kW (POW-HVM6.5K-48V)
 * Board         : ESP32-C3 Super Mini
 * Komunikasi    : Serial Monitor USB (115200 baud) & Hardware UART1 (GPIO 6 TX, GPIO 7 RX)
 * Level Shifter : Mini MAX3232 (HW-027)
 * 
 * ---------------------------------------------------------------------------------------
 * PANDUAN KABEL RJ45 UNTUK POW-HVM6.5K-48V (STANDAR TERKONFIRMASI):
 * ---------------------------------------------------------------------------------------
 * [Kabel LAN RJ45 Inverter]       [Sisi RS-232 Modul MAX3232]
 *  - Pin 1 (Putih-Orange : TX) ->  Pad '<—' (RXD RS232)
 *  - Pin 2 (Orange       : RX) ->  Pad '—>' (TXD RS232)
 *  - Pin 8 (Cokelat      : GND)->  Pad '－' (GND)
 *  - Pin 4 (Biru : +12V DC)    ->  ⚠️ JANGAN DIHUBUNGKAN! (Potong/Isolasi)
 *  - Pin 3, 5, 6, 7            ->  KOSONG / TIDAK DIGUNAKAN DI INVERTER INI!
 * 
 * [ESP32-C3 Super Mini]          [Sisi TTL Modul MAX3232]
 *  - Pin 5V                    ->  Pad '+' (VCC)  <-- WAJIB 5V agar IC MAX3232 clone bekerja!
 *  - Pin GND                   ->  Pad '－' (GND)
 *  - Pin GPIO 6 (TX)           ->  Pad '<—' (RXD TTL)
 *  - Pin GPIO 7 (RX)           ->  Pad '—>' (TXD TTL)
 * =======================================================================================
 */

#include <Arduino.h>

#define UART_TX_PIN 6 // GPIO 6 (TX ESP32 -> RXD TTL MAX3232)
#define UART_RX_PIN 7 // GPIO 7 (RX ESP32 <- TXD TTL MAX3232)

HardwareSerial InverterSerial(1);

// Frame Request Protokol 8851 (Binary Modbus Frame)
const uint8_t REQ_8851[] = { 0x88, 0x51, 0x00, 0x03, 0x00, 0x00, 0x00, 0x00, 0x4D, 0x08 };

int testMode = 0; // 0=Auto Scan semua, 1=8851 (9600), 2=HSTS (2400), 3=QPIGS (2400)

void clearRxBuffer() {
  while (InverterSerial.available()) InverterSerial.read();
}

bool readResponse(String& asciiResp, String& hexResp, unsigned long timeoutMs = 700) {
  asciiResp = "";
  hexResp = "";
  unsigned long start = millis();
  while (millis() - start < timeoutMs) {
    if (InverterSerial.available()) {
      uint8_t b = InverterSerial.read();
      char hexBuf[5];
      snprintf(hexBuf, sizeof(hexBuf), "%02X ", b);
      hexResp += hexBuf;
      if (b >= 32 && b <= 126) {
        asciiResp += (char)b;
      } else if (b == '\r') {
        asciiResp += "\\r";
      } else if (b == '\n') {
        asciiResp += "\\n";
      } else {
        asciiResp += ".";
      }
    }
    yield();
  }
  return (hexResp.length() > 0);
}

void setup() {
  Serial.begin(115200);
  unsigned long startUsb = millis();
  while (!Serial && (millis() - startUsb < 2500)) delay(10);
  
  delay(500);
  Serial.println("\n");
  Serial.println("========================================================================");
  Serial.println("  AUTO-DETECTOR & SCANNER PROTOKOL POWMR 6.5kW (POW-HVM6.5K-48V)");
  Serial.println("========================================================================");
  Serial.printf("  Pin ESP32-C3: TX = GPIO %d, RX = GPIO %d\n", UART_TX_PIN, UART_RX_PIN);
  Serial.println("  Pastikan VCC Modul MAX3232 dicolok ke pin 5V ESP32.");
  Serial.println("  Kabel LAN Inverter:");
  Serial.println("   - Pin 1 (Putih-Orange) ke Pad RXD RS232 modul MAX3232");
  Serial.println("   - Pin 2 (Orange) ke Pad TXD RS232 modul MAX3232");
  Serial.println("   - Pin 8 (Cokelat) ke Pad GND modul MAX3232");
  Serial.println("========================================================================\n");
}

void loop() {
  // Cek jika pengguna mengetik pilihan mode di Serial Monitor
  if (Serial.available()) {
    char c = Serial.read();
    if (c == '1') { testMode = 1; Serial.println(">>> MODE DIKUNCI: 8851 Protocol @ 9600 Baud"); }
    else if (c == '2') { testMode = 2; Serial.println(">>> MODE DIKUNCI: HSTS Protocol @ 2400 Baud"); }
    else if (c == '3') { testMode = 3; Serial.println(">>> MODE DIKUNCI: QPIGS Protocol @ 2400 Baud"); }
    else if (c == '0') { testMode = 0; Serial.println(">>> MODE OTOMATIS: Scan Semua Protokol"); }
  }

  // ================= 1. UJI PROTOKOL 8851 (9600 BAUD) =================
  if (testMode == 0 || testMode == 1) {
    InverterSerial.begin(9600, SERIAL_8N1, UART_RX_PIN, UART_TX_PIN);
    clearRxBuffer();
    
    Serial.print("[TEST 1/3] Mencoba Protokol 8851 (Binary, 9600 Baud)... ");
    InverterSerial.write(REQ_8851, sizeof(REQ_8851));
    InverterSerial.flush();
    
    String ascii, hex;
    if (readResponse(ascii, hex, 600)) {
      Serial.println("\n🎉 >>> INVERTER MERESPON! <<<");
      Serial.printf("    [RAW HEX]   : %s\n", hex.c_str());
      Serial.printf("    [RAW ASCII] : %s\n", ascii.c_str());
      Serial.println("    --> KESIMPULAN: Inverter Anda menggunakan Protokol 8851 @ 9600 Baud!\n");
      delay(2000);
      return;
    } else {
      Serial.println("Tidak ada respon (Timeout)");
    }
    delay(200);
  }

  // ================= 2. UJI PROTOKOL H-SERIES (2400 BAUD) =================
  if (testMode == 0 || testMode == 2) {
    InverterSerial.begin(2400, SERIAL_8N1, UART_RX_PIN, UART_TX_PIN);
    clearRxBuffer();

    Serial.print("[TEST 2/3] Mencoba Protokol HSTS (ASCII, 2400 Baud)... ");
    InverterSerial.print("HSTS\r");
    InverterSerial.flush();

    String ascii, hex;
    if (readResponse(ascii, hex, 600)) {
      Serial.println("\n🎉 >>> INVERTER MERESPON! <<<");
      Serial.printf("    [RAW HEX]   : %s\n", hex.c_str());
      Serial.printf("    [RAW ASCII] : %s\n", ascii.c_str());
      Serial.println("    --> KESIMPULAN: Inverter Anda menggunakan H-Protocol (HSTS) @ 2400 Baud!\n");
      delay(2000);
      return;
    } else {
      Serial.println("Tidak ada respon (Timeout)");
    }
    delay(200);
  }

  // ================= 3. UJI PROTOKOL VOLTRONIC PI30 (2400 BAUD) =================
  if (testMode == 0 || testMode == 3) {
    InverterSerial.begin(2400, SERIAL_8N1, UART_RX_PIN, UART_TX_PIN);
    clearRxBuffer();

    Serial.print("[TEST 3/3] Mencoba Protokol QPIGS (Voltronic, 2400 Baud)... ");
    InverterSerial.print("QPIGS\r");
    InverterSerial.flush();

    String ascii, hex;
    if (readResponse(ascii, hex, 600)) {
      Serial.println("\n🎉 >>> INVERTER MERESPON! <<<");
      Serial.printf("    [RAW HEX]   : %s\n", hex.c_str());
      Serial.printf("    [RAW ASCII] : %s\n", ascii.c_str());
      Serial.println("    --> KESIMPULAN: Inverter Anda menggunakan Protokol Voltronic (QPIGS) @ 2400 Baud!\n");
      delay(2000);
      return;
    } else {
      Serial.println("Tidak ada respon (Timeout)");
    }
  }

  Serial.println("------------------------------------------------------------------------");
  Serial.println("⏳ Mengulang scan dalam 2 detik...");
  Serial.println("   (Jika masih gagal: Pastikan VCC ke 5V & Kabel LAN di Pin 1, 2, 8)");
  Serial.println("------------------------------------------------------------------------\n");
  delay(2000);
}
