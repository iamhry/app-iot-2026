<?php

declare(strict_types=1);

require dirname(__DIR__, 2) . '/app/bootstrap.php';
send_security_headers();
require_admin();

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'GET') {
    json_error('Metode tidak diizinkan.', 405, [], ['Allow' => 'GET']);
}

$id = ctype_digit((string) ($_GET['id'] ?? '')) ? (int) $_GET['id'] : 0;
$device = Device::findById($id);
if ($device === null) {
    json_error('Perangkat tidak ditemukan.', 404);
}
$period = (string) ($_GET['period'] ?? '1h');
json_response(SensorReading::chart($id, $period));
