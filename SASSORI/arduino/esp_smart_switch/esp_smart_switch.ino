// File: esp_smart_switch.ino
// Add smart control to an existing wall switch.
#include <ESP8266WiFi.h>
#include <ESP8266HTTPClient.h>

#define WIFI_SSID "YOUR_WIFI"
#define WIFI_PASS "YOUR_PASS"

#define SWITCH_IN D3     // physical switch input (active LOW if wired to GND)
#define RELAY_OUT D1     // relay output (active LOW typical)

bool lastSwitch = false;
bool relayOn = false;
unsigned long lastDebounce = 0;

void applyRelay(bool on){
  relayOn = on;
  digitalWrite(RELAY_OUT, on ? LOW : HIGH);
}

void setup(){
  pinMode(SWITCH_IN, INPUT_PULLUP);
  pinMode(RELAY_OUT, OUTPUT);
  digitalWrite(RELAY_OUT, HIGH);

  WiFi.begin(WIFI_SSID, WIFI_PASS);
  while (WiFi.status() != WL_CONNECTED) { delay(300); }
}

void loop(){
  bool sv = digitalRead(SWITCH_IN) == LOW; // active LOW
  if (sv != lastSwitch && (millis()-lastDebounce)>50) {
    lastDebounce = millis();
    lastSwitch = sv;
    if (sv) applyRelay(!relayOn); // toggle on press
  }
  delay(10);
}
