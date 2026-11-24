<?php

declare(strict_types=1);

require dirname(__DIR__, 2) . '/app/bootstrap.php';
send_security_headers();
require_admin();

$id = ctype_digit((string) ($_GET['id'] ?? '')) ? (int) $_GET['id'] : 0;
$device = require_device_record($id);
$period = (string) ($_GET['period'] ?? '1h');
if (!in_array($period, ['1h', '6h', '24h', '7d'], true)) {
    $period = '1h';
}

render_admin('monitoring/detail', [
    'pageTitle' => 'Grafik ' . $device['name'],
    'activeNav' => 'monitoring',
    'device' => $device,
    'period' => $period,
    'extraScripts' => [
        '/assets/vendor/chart.umd.min.js',
        '/assets/js/monitoring.js',
    ],
]);
