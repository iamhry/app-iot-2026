<?php

declare(strict_types=1);

function sensor_catalog(): array
{
    return [
        'DHT22' => [
            'label' => 'DHT22',
            'temp_min' => -40.0,
            'temp_max' => 80.0,
            'hum_min' => 0.0,
            'hum_max' => 100.0,
        ],
        'DHT11' => [
            'label' => 'DHT11',
            'temp_min' => 0.0,
            'temp_max' => 50.0,
            'hum_min' => 20.0,
            'hum_max' => 90.0,
        ],
    ];
}

function sensor_ranges(string $type): ?array
{
    $catalog = sensor_catalog();
    return $catalog[strtoupper($type)] ?? null;
}

function validate_device_input(array $input): array
{
    $errors = [];
    $name = trim((string) ($input['name'] ?? ''));
    $location = trim((string) ($input['location'] ?? ''));
    $sensor = strtoupper(trim((string) ($input['sensor_type'] ?? '')));
    $minRaw = $input['min_temperature'] ?? null;
    $maxRaw = $input['max_temperature'] ?? null;
    $intervalRaw = trim((string) ($input['reporting_interval'] ?? ''));
    $active = isset($input['is_active']) ? 1 : 0;

    if ($name === '' || text_length($name) > 100) {
        $errors['name'] = 'Nama wajib diisi dan paling panjang 100 karakter.';
    }
    if ($location === '' || text_length($location) > 150) {
        $errors['location'] = 'Lokasi wajib diisi dan paling panjang 150 karakter.';
    }
    if (sensor_ranges($sensor) === null) {
        $errors['sensor_type'] = 'Jenis sensor harus DHT22 atau DHT11.';
    }

    $min = null;
    $max = null;
    if (!is_numeric($minRaw)) {
        $errors['min_temperature'] = 'Batas minimum suhu harus berupa angka.';
    } else {
        $min = round((float) $minRaw, 2);
        if ($min < -99.99 || $min > 999.99) {
            $errors['min_temperature'] = 'Batas minimum suhu di luar rentang yang didukung.';
        }
    }
    if (!is_numeric($maxRaw)) {
        $errors['max_temperature'] = 'Batas maksimum suhu harus berupa angka.';
    } else {
        $max = round((float) $maxRaw, 2);
        if ($max < -99.99 || $max > 999.99) {
            $errors['max_temperature'] = 'Batas maksimum suhu di luar rentang yang didukung.';
        }
    }
    if ($min !== null && $max !== null && $min >= $max && !isset($errors['max_temperature'])) {
        $errors['max_temperature'] = 'Batas maksimum harus lebih besar dari batas minimum.';
    }

    $interval = null;
    if (!preg_match('/^\d+$/', $intervalRaw)) {
        $errors['reporting_interval'] = 'Interval pengiriman harus bilangan bulat antara 5 dan 86400 detik.';
    } else {
        $interval = (int) $intervalRaw;
        if ($interval < 5 || $interval > 86400) {
            $errors['reporting_interval'] = 'Interval pengiriman harus bilangan bulat antara 5 dan 86400 detik.';
        }
    }

    return [
        'errors' => $errors,
        'data' => [
            'name' => $name,
            'location' => $location,
            'sensor_type' => $sensor,
            'min_temperature' => $min === null ? '' : number_format($min, 2, '.', ''),
            'max_temperature' => $max === null ? '' : number_format($max, 2, '.', ''),
            'reporting_interval' => $interval ?? '',
            'is_active' => $active,
        ],
    ];
}

function json_finite_number(mixed $value): ?float
{
    if (is_int($value)) {
        return (float) $value;
    }
    if (is_float($value) && is_finite($value)) {
        return $value;
    }
    return null;
}
