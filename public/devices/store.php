<?php

declare(strict_types=1);

require dirname(__DIR__, 2) . '/app/bootstrap.php';
send_security_headers();
require_admin();

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    redirect('/devices/create.php');
}

csrf_verify();
$result = validate_device_input($_POST);
if ($result['errors'] !== []) {
    http_response_code(422);
    render_admin('devices/form', [
        'pageTitle' => 'Tambah perangkat',
        'activeNav' => 'devices',
        'formAction' => '/devices/store.php',
        'submitLabel' => 'Simpan perangkat',
        'deviceId' => 0,
        'errors' => $result['errors'],
        'values' => $result['data'],
    ]);
    exit;
}

$created = Device::create($result['data']);
store_credential($created['id'], $created['device_id'], $created['token']);
flash('success', 'Perangkat berhasil didaftarkan. Simpan token sebelum meninggalkan halaman ini.');
redirect('/devices/show.php?id=' . $created['id']);
