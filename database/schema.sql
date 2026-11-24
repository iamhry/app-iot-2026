-- Skema monitoring suhu dan kelembapan ruangan.
-- Impor ke database yang dipakai aplikasi (nilai $db di config.php).
-- Contoh: mysql --skip-ssl -h 127.0.0.1 -u app-iot-2026 -p app-iot-2026 < database/schema.sql
-- Waktu disimpan sebagai DATETIME UTC. Tampilan aplikasi memakai Asia/Jakarta.

SET NAMES utf8mb4;
SET time_zone = '+00:00';

CREATE TABLE IF NOT EXISTS users (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    username VARCHAR(100) NOT NULL,
    password VARCHAR(255) NOT NULL,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_users_username (username)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS devices (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    device_id VARCHAR(50) NOT NULL,
    name VARCHAR(100) NOT NULL,
    location VARCHAR(150) NOT NULL,
    sensor_type VARCHAR(50) NOT NULL,
    token_hash CHAR(64) NOT NULL,
    token_prefix VARCHAR(20) NOT NULL,
    min_temperature DECIMAL(5,2) NOT NULL,
    max_temperature DECIMAL(5,2) NOT NULL,
    reporting_interval INT UNSIGNED NOT NULL DEFAULT 30,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    last_seen_at DATETIME NULL,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_devices_device_id (device_id),
    UNIQUE KEY uq_devices_token_hash (token_hash)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS sensor_readings (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    device_id BIGINT UNSIGNED NOT NULL,
    temperature DECIMAL(5,2) NOT NULL,
    humidity DECIMAL(5,2) NOT NULL,
    recorded_at DATETIME NOT NULL,
    received_at DATETIME NOT NULL,
    PRIMARY KEY (id),
    KEY idx_device_recorded (device_id, recorded_at, id),
    KEY idx_recorded_at (recorded_at),
    CONSTRAINT fk_readings_device FOREIGN KEY (device_id) REFERENCES devices (id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS rate_limits (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    bucket_key VARCHAR(128) NOT NULL,
    window_start DATETIME NOT NULL,
    hit_count INT UNSIGNED NOT NULL DEFAULT 1,
    PRIMARY KEY (id),
    UNIQUE KEY uq_bucket_window (bucket_key, window_start),
    KEY idx_rate_window (window_start)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
