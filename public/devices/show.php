<?php

declare(strict_types=1);

require dirname(__DIR__, 2) . '/app/bootstrap.php';
send_security_headers();
require_admin();

$id = ctype_digit((string) ($_GET['id'] ?? '')) ? (int) $_GET['id'] : 0;
$device = require_device_record($id);
$credential = pull_credential($id);
if ($credential !== null) {
    header('Cache-Control: no-store, no-cache, must-revalidate');
    header('Pragma: no-cache');
}

render_admin('devices/show', [
    'pageTitle' => (string) $device['name'],
    'activeNav' => 'devices',
    'device' => $device,
    'credential' => $credential,
    'latest' => SensorReading::latestForDevice($id),
]);
