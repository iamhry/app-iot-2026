<?php

declare(strict_types=1);

require dirname(__DIR__, 2) . '/app/bootstrap.php';
send_security_headers();
require_admin();

render_admin('devices/form', [
    'pageTitle' => 'Tambah perangkat',
    'activeNav' => 'devices',
    'formAction' => '/devices/store.php',
    'submitLabel' => 'Simpan perangkat',
    'deviceId' => 0,
    'errors' => [],
    'values' => [
        'name' => '',
        'location' => '',
        'sensor_type' => 'DHT22',
        'min_temperature' => '18.00',
        'max_temperature' => '30.00',
        'reporting_interval' => '30',
        'is_active' => 1,
    ],
]);
