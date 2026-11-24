<?php

declare(strict_types=1);

require dirname(__DIR__, 2) . '/app/bootstrap.php';
send_security_headers();
require_admin();

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    redirect('/devices/index.php');
}

csrf_verify();
$id = ctype_digit((string) ($_POST['id'] ?? '')) ? (int) $_POST['id'] : 0;
$device = require_device_record($id);
$result = validate_device_input($_POST);
if ($result['errors'] !== []) {
    http_response_code(422);
    render_admin('devices/form', [
        'pageTitle' => 'Ubah perangkat',
        'activeNav' => 'devices',
        'formAction' => '/devices/update.php',
        'submitLabel' => 'Simpan perubahan',
        'deviceId' => $id,
        'errors' => $result['errors'],
        'values' => $result['data'],
    ]);
    exit;
}

Device::update($id, $result['data']);
flash('success', 'Metadata perangkat diperbarui. Token tidak berubah.');
redirect('/devices/show.php?id=' . $id);
