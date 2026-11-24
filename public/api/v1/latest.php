<?php

declare(strict_types=1);

require dirname(__DIR__, 3) . '/app/bootstrap.php';
send_security_headers();
api_require_method('GET');

$device = authenticate_device();
$latest = SensorReading::latestForDevice((int) $device['id']);
if ($latest === null) {
    json_error('Pengukuran belum tersedia.', 404);
}

json_response([
    'success' => true,
    'message' => 'Pengukuran terakhir',
    'data' => [
        'device_id' => (string) $device['device_id'],
        'temperature' => (float) $latest['temperature'],
        'humidity' => (float) $latest['humidity'],
        'status' => temperature_status($latest['temperature'], $device['min_temperature'], $device['max_temperature']),
        'recorded_at' => iso_utc((string) $latest['recorded_at']),
        'received_at' => iso_utc((string) $latest['received_at']),
    ],
]);
