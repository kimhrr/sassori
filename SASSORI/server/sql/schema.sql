-- File: schema.sql
CREATE TABLE IF NOT EXISTS devices (
  id INT AUTO_INCREMENT PRIMARY KEY,
  device_id VARCHAR(50) UNIQUE,
  label VARCHAR(100),
  location VARCHAR(100),
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS telemetry (
  id BIGINT AUTO_INCREMENT PRIMARY KEY,
  device_id VARCHAR(50),
  temp FLOAT,
  hum FLOAT,
  occupancy TINYINT(1),
  current FLOAT,
  ts TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX (device_id, ts)
);

CREATE TABLE IF NOT EXISTS commands (
  id INT AUTO_INCREMENT PRIMARY KEY,
  device_id VARCHAR(50) UNIQUE,
  light ENUM('on','off') DEFAULT 'off',
  aircond ENUM('on','off') DEFAULT 'off',
  curtain ENUM('stop','up','down') DEFAULT 'stop',
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
