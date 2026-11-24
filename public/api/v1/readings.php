<?php

declare(strict_types=1);

require dirname(__DIR__, 3) . '/app/bootstrap.php';
send_security_headers();

api_require_method('POST');
if (content_type() !== 'application/json') {
    json_error('Content-Type harus application/json.', 415);
}

$length = (int) ($_SERVER['CONTENT_LENGTH'] ?? 0);
if ($length > MAX_REQUEST_BYTES) {
    json_error('Ukuran permintaan terlalu besar.', 400);
}

$device = authenticate_device();
$data = read_json_body();
$errors = [];

$payloadDeviceId = null;
if (!array_key_exists('device_id', $data) || !is_string($data['device_id']) || trim($data['device_id']) === '' || strlen($data['device_id']) > 50) {
    $errors['device_id'] = 'Device ID wajib berupa string yang valid.';
} else {
    $payloadDeviceId = $data['device_id'];
    if ($payloadDeviceId !== (string) $device['device_id']) {
        json_error('Device ID tidak sesuai dengan perangkat token.', 403);
    }
}

$temperature = null;
if (!array_key_exists('temperature', $data)) {
    $errors['temperature'] = 'Temperature wajib berupa angka.';
} else {
    $temperature = json_finite_number($data['temperature']);
    if ($temperature === null) {
        $errors['temperature'] = 'Temperature wajib berupa angka JSON, bukan string atau null.';
    }
}

$humidity = null;
if (!array_key_exists('humidity', $data)) {
    $errors['humidity'] = 'Humidity wajib berupa angka.';
} else {
    $humidity = json_finite_number($data['humidity']);
    if ($humidity === null) {
        $errors['humidity'] = 'Humidity wajib berupa angka JSON, bukan string atau null.';
    }
}

$recordedAt = now_utc();
if (array_key_exists('recorded_at', $data)) {
    $parsed = parse_iso_utc($data['recorded_at']);
    if ($parsed === null) {
        $errors['recorded_at'] = 'recorded_at harus berupa ISO 8601 dengan zona waktu.';
    } else {
        $recordedAt = $parsed;
    }
}

$ranges = sensor_ranges((string) $device['sensor_type']);
if ($ranges === null) {
    json_error('Jenis sensor perangkat tidak didukung.', 422);
}
if ($temperature !== null && ($temperature < $ranges['temp_min'] || $temperature > $ranges['temp_max'])) {
    $errors['temperature'] = 'Suhu di luar rentang ' . $device['sensor_type'] . '.';
}
if ($humidity !== null && ($humidity < $ranges['hum_min'] || $humidity > $ranges['hum_max'])) {
    $errors['humidity'] = 'Kelembapan di luar rentang ' . $device['sensor_type'] . '.';
}

if ($errors !== []) {
    json_error('Data tidak lolos validasi.', 422, $errors);
}

$temperatureSql = number_format(round((float) $temperature, 2), 2, '.', '');
$humiditySql = number_format(round((float) $humidity, 2), 2, '.', '');

try {
    SensorReading::store(
        (int) $device['id'],
        $temperatureSql,
        $humiditySql,
        $recordedAt,
        (string) $device['token_hash']
    );
} catch (ReadingRejected $rejected) {
    if ($rejected->status === 403) {
        json_error('Perangkat nonaktif.', 403);
    }
    json_error('Token tidak valid.', 401, [], ['WWW-Authenticate' => 'Bearer realm="iot-api"']);
}

$status = temperature_status($temperatureSql, $device['min_temperature'], $device['max_temperature']);
json_response([
    'success' => true,
    'message' => 'Data sensor berhasil disimpan',
    'data' => [
        'device_id' => (string) $device['device_id'],
        'temperature' => (float) $temperatureSql,
        'humidity' => (float) $humiditySql,
        'status' => $status,
    ],
], 201);
