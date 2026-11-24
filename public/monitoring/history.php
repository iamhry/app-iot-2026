<?php

declare(strict_types=1);

require dirname(__DIR__, 2) . '/app/bootstrap.php';
send_security_headers();
require_admin();

$deviceCode = trim((string) ($_GET['device'] ?? ''));
$location = trim((string) ($_GET['location'] ?? ''));
$q = trim((string) ($_GET['q'] ?? ''));
$dateFrom = trim((string) ($_GET['date_from'] ?? ''));
$dateTo = trim((string) ($_GET['date_to'] ?? ''));
$page = filter_input(INPUT_GET, 'page', FILTER_VALIDATE_INT);
$page = is_int($page) && $page > 0 ? $page : 1;
$errors = [];

if (strlen($deviceCode) > 50) {
    $errors['device'] = 'Device ID terlalu panjang.';
}
if (text_length($location) > 150) {
    $errors['location'] = 'Lokasi terlalu panjang.';
}
if (text_length($q) > 100) {
    $errors['q'] = 'Pencarian paling panjang 100 karakter.';
}

$fromUtc = '';
$toUtc = '';
if ($dateFrom !== '') {
    $fromUtc = jakarta_day_to_utc_start($dateFrom) ?? '';
    if ($fromUtc === '') {
        $errors['date_from'] = 'Tanggal awal tidak valid.';
    }
}
if ($dateTo !== '') {
    $toUtc = jakarta_day_to_utc_end($dateTo) ?? '';
    if ($toUtc === '') {
        $errors['date_to'] = 'Tanggal akhir tidak valid.';
    }
}
if ($fromUtc !== '' && $toUtc !== '' && $fromUtc > $toUtc) {
    $errors['date_to'] = 'Tanggal akhir harus sama atau setelah tanggal awal.';
}

$devicePk = 0;
if ($deviceCode !== '' && !isset($errors['device'])) {
    $device = Device::findByDeviceId($deviceCode);
    if ($device === null) {
        $errors['device'] = 'Perangkat tidak ditemukan.';
    } else {
        $devicePk = (int) $device['id'];
    }
}

$filters = [
    'device' => $deviceCode,
    'location' => $location,
    'q' => $q,
    'date_from' => $dateFrom,
    'date_to' => $dateTo,
    'page' => $page,
    'device_pk' => $devicePk,
    'from_utc' => $fromUtc,
    'to_utc' => $toUtc,
];

$empty = [
    'rows' => [],
    'total' => 0,
    'page' => 1,
    'pages' => 1,
    'per_page' => HISTORY_PAGE_SIZE,
];

if ($errors === [] && (($_GET['export'] ?? '') === 'csv')) {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="riwayat-pengukuran.csv"');
    header('Cache-Control: no-store');
    $out = fopen('php://output', 'w');
    if ($out === false) {
        http_response_code(500);
        echo 'Ekspor gagal.';
        exit;
    }
    fwrite($out, "\xEF\xBB\xBF");
    fputcsv($out, ['Nomor', 'Device ID', 'Nama', 'Lokasi', 'Suhu (C)', 'Kelembapan (%)', 'Waktu pengukuran (WIB)', 'Waktu diterima (WIB)'], ',', '"', '\\');
    $stmt = SensorReading::exportStatement($filters);
    $number = 0;
    while ($row = $stmt->fetch()) {
        $number++;
        fputcsv($out, [
            (string) $number,
            csv_safe((string) $row['device_id']),
            csv_safe((string) $row['name']),
            csv_safe((string) $row['location']),
            csv_safe(format_decimal($row['temperature'])),
            csv_safe(format_decimal($row['humidity'])),
            csv_safe(format_jakarta((string) $row['recorded_at'])),
            csv_safe(format_jakarta((string) $row['received_at'])),
        ], ',', '"', '\\');
    }
    $stmt->closeCursor();
    fclose($out);
    exit;
}

$result = $errors === [] ? SensorReading::search($filters, $page, HISTORY_PAGE_SIZE) : $empty;
$filters['page'] = $result['page'];

render_admin('monitoring/history', [
    'pageTitle' => 'Riwayat Pengukuran',
    'activeNav' => 'history',
    'filters' => $filters,
    'errors' => $errors,
    'devices' => Device::all(),
    'locations' => Device::locations(),
    'result' => $result,
]);
