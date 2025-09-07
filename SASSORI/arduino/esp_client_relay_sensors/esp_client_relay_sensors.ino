// File: esp_client_relay_sensors.ino
// ESP8266 client: controls relays, reads sensors, posts telemetry, polls commands.
#include <ESP8266WiFi.h>
#include <ESP8266HTTPClient.h>
#include <DHT.h>

#define WIFI_SSID "YOUR_WIFI"
#define WIFI_PASS "YOUR_PASS"

#define DHTPIN D4
#define DHTTYPE DHT11
DHT dht(DHTPIN, DHTTYPE);

#define PIR_PIN D5
#define RELAY_LIGHT D1
#define RELAY_AIRCOND D2
#define RELAY_CURTAIN_UP D6
#define RELAY_CURTAIN_DOWN D7

const char* API_BASE = "http://your-server/api";

unsigned long lastPost = 0;
const unsigned long postIntervalMs = 10000; // 10s

float readCurrentA() {
  int raw = analogRead(A0);
  float voltage = (raw / 1023.0) * 3.3;
  // Adjust offset & sensitivity for your ACS712 model (e.g., 185mV/A for 5A module)
  float current = (voltage - 1.65) / 0.066; 
  return current;
}

void safePulse(int pin, int ms=500){
  digitalWrite(pin, LOW);  // ACTIVE LOW relays (check your module)
  delay(ms);
  digitalWrite(pin, HIGH);
}

void setup() {
  pinMode(PIR_PIN, INPUT);
  for (int p : {RELAY_LIGHT, RELAY_AIRCOND, RELAY_CURTAIN_UP, RELAY_CURTAIN_DOWN}) {
    pinMode(p, OUTPUT);
    digitalWrite(p, HIGH); // idle HIGH for active-low relay
  }

  Serial.begin(115200);
  dht.begin();

  WiFi.begin(WIFI_SSID, WIFI_PASS);
  Serial.print("WiFi...");
  while (WiFi.status() != WL_CONNECTED) { delay(500); Serial.print("."); }
  Serial.println("OK");
}

void loop() {
  // Fetch desired state from API
  if (WiFi.status() == WL_CONNECTED) {
    HTTPClient http;
    String url = String(API_BASE) + "/commands?device_id=DB01";
    http.begin(url);
    int code = http.GET();
    if (code == 200) {
      String body = http.getString();
      if (body.indexOf("\"light\":\"on\"") >= 0)  safePulse(RELAY_LIGHT);
      if (body.indexOf("\"aircond\":\"on\"") >= 0) safePulse(RELAY_AIRCOND);
      if (body.indexOf("\"curtain\":\"up\"") >= 0) safePulse(RELAY_CURTAIN_UP);
      if (body.indexOf("\"curtain\":\"down\"") >= 0) safePulse(RELAY_CURTAIN_DOWN);
    }
    http.end();
  }

  // Periodic telemetry
  if (millis() - lastPost > postIntervalMs && WiFi.status() == WL_CONNECTED) {
    lastPost = millis();
    float t = dht.readTemperature();
    float h = dht.readHumidity();
    int occupancy = digitalRead(PIR_PIN);
    float currentA = readCurrentA();

    HTTPClient http;
    http.begin(String(API_BASE) + "/telemetry");
    http.addHeader("Content-Type", "application/json");
    String payload = String("{\"device_id\":\"DB01\",\"temp\":") + t +
                     ",\"hum\":" + h + ",\"occupancy\":" + occupancy +
                     ",\"current\":" + currentA + "}";
    int code = http.POST(payload);
    http.end();
  }

  delay(1000);
}

// SAFETY: Work inside distribution boards / aircond units requires certified electrician.
