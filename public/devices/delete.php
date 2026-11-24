<?php

declare(strict_types=1);

require dirname(__DIR__, 2) . '/app/bootstrap.php';
send_security_headers();
require_admin();

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
if ($method === 'POST') {
    csrf_verify();
    $id = ctype_digit((string) ($_POST['id'] ?? '')) ? (int) $_POST['id'] : 0;
    $device = require_device_record($id);
    if (SensorReading::countForDevice($id) > 0) {
        Device::deactivate($id);
        flash('success', 'Perangkat memiliki riwayat pengukuran sehingga tidak dihapus. Status diubah menjadi nonaktif.');
    } else {
        Device::delete($id);
        flash('success', 'Perangkat tanpa riwayat berhasil dihapus.');
        redirect('/devices/index.php');
    }
    redirect('/devices/show.php?id=' . $id);
}

if ($method !== 'GET') {
    http_response_code(405);
    header('Allow: GET, POST');
    echo 'Metode tidak diizinkan.';
    exit;
}

$id = ctype_digit((string) ($_GET['id'] ?? '')) ? (int) $_GET['id'] : 0;
$device = require_device_record($id);
render_admin('devices/confirm-delete', [
    'pageTitle' => 'Nonaktifkan perangkat',
    'activeNav' => 'devices',
    'device' => $device,
    'hasHistory' => SensorReading::countForDevice($id) > 0,
]);
