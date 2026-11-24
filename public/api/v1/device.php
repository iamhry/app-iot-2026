<?php

declare(strict_types=1);

require dirname(__DIR__, 3) . '/app/bootstrap.php';
send_security_headers();
api_require_method('GET');

$device = authenticate_device();
json_response([
    'success' => true,
    'message' => 'Identitas perangkat',
    'data' => [
        'device_id' => (string) $device['device_id'],
        'name' => (string) $device['name'],
        'location' => (string) $device['location'],
        'sensor_type' => (string) $device['sensor_type'],
        'min_temperature' => (float) $device['min_temperature'],
        'max_temperature' => (float) $device['max_temperature'],
        'reporting_interval' => (int) $device['reporting_interval'],
        'is_active' => (bool) (int) $device['is_active'],
    ],
]);
