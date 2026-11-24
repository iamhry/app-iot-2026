<?php

declare(strict_types=1);

require dirname(__DIR__, 2) . '/app/bootstrap.php';
send_security_headers();
require_admin();

$id = (int) ($_GET['id'] ?? 0);
if ((string) $id !== (string) ($_GET['id'] ?? '')) {
    $id = ctype_digit((string) ($_GET['id'] ?? '')) ? (int) $_GET['id'] : 0;
}
$device = require_device_record($id);

render_admin('devices/form', [
    'pageTitle' => 'Ubah perangkat',
    'activeNav' => 'devices',
    'formAction' => '/devices/update.php',
    'submitLabel' => 'Simpan perubahan',
    'deviceId' => (int) $device['id'],
    'errors' => [],
    'values' => [
        'name' => (string) $device['name'],
        'location' => (string) $device['location'],
        'sensor_type' => (string) $device['sensor_type'],
        'min_temperature' => format_decimal($device['min_temperature']),
        'max_temperature' => format_decimal($device['max_temperature']),
        'reporting_interval' => (string) $device['reporting_interval'],
        'is_active' => (int) $device['is_active'],
    ],
]);
