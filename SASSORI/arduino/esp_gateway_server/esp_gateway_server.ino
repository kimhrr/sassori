// File: esp_gateway_server.ino
// ESP8266 gateway: receives local posts and forwards to Cloud.
#include <ESP8266WiFi.h>
#include <ESP8266WebServer.h>
#include <ESP8266HTTPClient.h>

#define WIFI_SSID "YOUR_WIFI"
#define WIFI_PASS "YOUR_PASS"

ESP8266WebServer server(8080);
const char* CLOUD = "http://your-server/api/gateway";

void handleLocalPost() {
  if (!server.hasArg("plain")) { server.send(400, "text/plain", "Bad Request"); return; }
  String data = server.arg("plain");

  HTTPClient http;
  http.begin(String(CLOUD));
  http.addHeader("Content-Type","application/json");
  int code = http.POST(data);
  http.end();

  server.send(200, "application/json", "{\"status\":\"ok\"}");
}

void setup() {
  Serial.begin(115200);
  WiFi.begin(WIFI_SSID, WIFI_PASS);
  while (WiFi.status() != WL_CONNECTED) { delay(300); }
  server.on("/local", HTTP_POST, handleLocalPost);
  server.begin();
}

void loop() { server.handleClient(); }
