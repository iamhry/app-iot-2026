/*
 * Contoh pengiriman DHT22 dari ESP32 ke REST API monitoring ruangan.
 *
 * Library (Arduino Library Manager):
 * - DHT sensor library (Adafruit)
 * - Adafruit Unified Sensor
 * - WiFi dan HTTPClient sudah termasuk di board ESP32.
 *
 * Wiring modul DHT22:
 * - VCC  -> 3V3 ESP32
 * - GND  -> GND ESP32
 * - DATA -> GPIO 4
 * Sensor polos tanpa modul perlu resistor pull-up 10k ohm antara DATA dan 3V3.
 * Jangan memberi DHT22 tegangan 5V pada pin data ESP32.
 *
 * Untuk produksi gunakan HTTPS dan sertifikat CA (WiFiClientSecure + setCACert).
 * Jangan memakai setInsecure().
 *
 * Token tidak dicetak ke Serial Monitor.
 */

#include <WiFi.h>
#include <HTTPClient.h>
#include <DHT.h>

const char* WIFI_SSID = "YOUR_WIFI";
const char* WIFI_PASSWORD = "YOUR_PASSWORD";
const char* API_URL = "YOUR_API_ENDPOINT";
const char* DEVICE_ID = "YOUR_DEVICE_ID";
const char* DEVICE_TOKEN = "YOUR_DEVICE_TOKEN";

const uint8_t DHT_PIN = 4;
const unsigned long REPORT_INTERVAL_MS = 30000;
const unsigned long HTTP_TIMEOUT_MS = 10000;

DHT dht(DHT_PIN, DHT22);
unsigned long lastReportMs = 0;

void connectWifi() {
  if (WiFi.status() == WL_CONNECTED) {
    return;
  }
  WiFi.mode(WIFI_STA);
  WiFi.begin(WIFI_SSID, WIFI_PASSWORD);
  Serial.print("Menghubungkan WiFi");
  const unsigned long started = millis();
  while (WiFi.status() != WL_CONNECTED && millis() - started < 15000) {
    delay(500);
    Serial.print(".");
  }
  Serial.println();
  if (WiFi.status() == WL_CONNECTED) {
    Serial.print("WiFi tersambung, IP ");
    Serial.println(WiFi.localIP());
  } else {
    Serial.println("WiFi gagal. Mencoba lagi pada siklus berikutnya.");
  }
}

bool sensorReady(float temperature, float humidity) {
  if (isnan(temperature) || isnan(humidity)) {
    Serial.println("Pembacaan DHT22 gagal.");
    return false;
  }
  if (temperature < -40.0f || temperature > 80.0f || humidity < 0.0f || humidity > 100.0f) {
    Serial.println("Nilai DHT22 di luar rentang sensor.");
    return false;
  }
  return true;
}

void sendReading(float temperature, float humidity) {
  if (WiFi.status() != WL_CONNECTED) {
    connectWifi();
  }
  if (WiFi.status() != WL_CONNECTED) {
    return;
  }

  char payload[160];
  snprintf(
    payload,
    sizeof(payload),
    "{\"device_id\":\"%s\",\"temperature\":%.2f,\"humidity\":%.2f}",
    DEVICE_ID,
    temperature,
    humidity
  );

  HTTPClient http;
  http.setTimeout(HTTP_TIMEOUT_MS);
  if (!http.begin(API_URL)) {
    Serial.println("URL API tidak valid.");
    return;
  }
  http.addHeader("Content-Type", "application/json");
  http.addHeader("Authorization", String("Bearer ") + DEVICE_TOKEN);

  const int status = http.POST(payload);
  Serial.print("HTTP ");
  Serial.println(status);
  http.end();
}

void setup() {
  Serial.begin(115200);
  delay(300);
  dht.begin();
  connectWifi();
}

void loop() {
  const unsigned long now = millis();
  if (lastReportMs != 0 && now - lastReportMs < REPORT_INTERVAL_MS) {
    return;
  }
  lastReportMs = now;

  const float temperature = dht.readTemperature();
  const float humidity = dht.readHumidity();
  if (!sensorReady(temperature, humidity)) {
    return;
  }
  Serial.print("Suhu ");
  Serial.print(temperature, 1);
  Serial.print(" C, kelembapan ");
  Serial.println(humidity, 1);
  sendReading(temperature, humidity);
}
