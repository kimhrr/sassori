# Smart Campus Starter (ESP8266 + PHP API + Web Dashboard + iOS)

A minimal end-to-end scaffold so you can demo quickly and extend safely.

## Structure
```
arduino/
  esp_client_relay_sensors/esp_client_relay_sensors.ino
  esp_gateway_server/esp_gateway_server.ino
  esp_smart_switch/esp_smart_switch.ino
server/
  api/index.php
  api/estimate.php
  api/health.php
  sql/schema.sql
web/
  dashboard.html
  floor.json
ios/
  SmartCampusApp.swift
  ControlsView.swift
README.md
```

## Quick Start

### 1) Server (LAMP/XAMPP)
1. Create database `smartcampus` (phpMyAdmin).
2. Import `server/sql/schema.sql`.
3. Copy `server/api/*` to your webroot as `/api` (e.g., `htdocs/api`).
4. Edit DB credentials in each PHP file (`dbuser/dbpass`).  
5. Ensure your server URL is reachable by ESP and iOS (e.g., `http://192.168.1.10` or domain).

### 2) Web Dashboard
- Place `web/dashboard.html` under webroot (e.g., `htdocs/dashboard.html`).
- Open it in a browser; it calls `/api/floor` every 5s.

### 3) Arduino (ESP8266)
- Open each `.ino` in Arduino IDE.
- Set **Board**: *NodeMCU 1.0 (ESP-12E Module)* or your ESP8266 variant.
- Update `WIFI_SSID`, `WIFI_PASS`, and `API_BASE` (client) to your server URL.
- Flash:
  - `esp_client_relay_sensors.ino` to your device at DB/aircond/sensors.
  - Optional: `esp_gateway_server.ino` to one ESP if you want a local gateway.
  - `esp_smart_switch.ino` for converting a wall switch to smart.

**Pins used (example):**
- DHT11 → D4, PIR → D5, ACS712 → A0
- Relays: D1 (Light), D2 (Aircond), D6 (Curtain Up), D7 (Curtain Down)

> ⚠️ **Safety:** AC mains work must be done by a certified electrician. Use proper enclosures, fuses, and isolation.

### 4) iOS App (SwiftUI)
- Create a new SwiftUI project in Xcode (iOS 16+).
- Replace `App` and `ContentView` files with `ios/SmartCampusApp.swift` and `ios/ControlsView.swift`.
- Update `base` URL to your server (e.g., `http://192.168.1.10/api`).
- Build & run on iPhone/iPad on same network.

## API Endpoints (Minimal)
- `POST /api/telemetry` → `{device_id,temp,hum,occupancy,current}`
- `GET  /api/commands?device_id=DB01` → `{"light":"on","aircond":"off","curtain":"stop"}`
- `POST /api/commands` → set desired state
- `GET  /api/floor` → latest telemetry per device
- `GET  /api/estimate?device_id=DB01` → rough RM/month estimate
- `GET  /api/health` → device last-seen & status

## Hardening / Next Steps
- Use **HTTPS** + token auth (e.g., Bearer tokens or signed HMAC).
- Switch to **MQTT** (Mosquitto) if you prefer pub/sub.
- Robust JSON: ArduinoJson on ESP, proper validation on PHP.
- OTA updates for ESP8266 (Arduino OTA).
- Add rules: auto-off when `occupancy=0` for X minutes.
- Replace DHT11 with better sensor (SHT30/BME280) for accuracy.

Good luck & have fun building the Smart Campus! 🚀
