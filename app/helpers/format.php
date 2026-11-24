<?php

declare(strict_types=1);

function now_utc(): string
{
    return gmdate('Y-m-d H:i:s');
}

function format_jakarta(?string $utc, string $format = 'd M Y H:i:s'): string
{
    if ($utc === null || $utc === '') {
        return '—';
    }
    $dt = new DateTimeImmutable($utc, new DateTimeZone('UTC'));
    return $dt->setTimezone(new DateTimeZone(APP_TIMEZONE_DISPLAY))->format($format);
}

function format_decimal(mixed $value): string
{
    if ($value === null || $value === '') {
        return '—';
    }
    return number_format((float) $value, 2, '.', '');
}

function iso_utc(?string $utc): ?string
{
    if ($utc === null || $utc === '') {
        return null;
    }
    $dt = new DateTimeImmutable($utc, new DateTimeZone('UTC'));
    return $dt->format('Y-m-d\TH:i:s\Z');
}

function parse_iso_utc(mixed $value): ?string
{
    if (!is_string($value)) {
        return null;
    }
    if (!preg_match('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}(?:\.\d+)?(Z|[+\-]\d{2}:\d{2})$/', $value)) {
        return null;
    }
    try {
        $dt = new DateTimeImmutable($value);
    } catch (Exception) {
        return null;
    }
    return $dt->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d H:i:s');
}

function jakarta_day_to_utc_start(string $date): ?string
{
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
        return null;
    }
    $dt = DateTimeImmutable::createFromFormat('!Y-m-d', $date, new DateTimeZone(APP_TIMEZONE_DISPLAY));
    $dateErrors = DateTimeImmutable::getLastErrors();
    if ($dt === false || (is_array($dateErrors) && (($dateErrors['warning_count'] ?? 0) > 0 || ($dateErrors['error_count'] ?? 0) > 0))) {
        return null;
    }
    if ($dt->format('Y-m-d') !== $date) {
        return null;
    }
    return $dt->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d H:i:s');
}

function jakarta_day_to_utc_end(string $date): ?string
{
    $start = jakarta_day_to_utc_start($date);
    if ($start === null) {
        return null;
    }
    $dt = new DateTimeImmutable($start, new DateTimeZone('UTC'));
    return $dt->modify('+1 day')->modify('-1 second')->format('Y-m-d H:i:s');
}

function temperature_status(mixed $temp, mixed $min, mixed $max): string
{
    $value = (float) $temp;
    if ($value < (float) $min) {
        return 'below_min';
    }
    if ($value > (float) $max) {
        return 'above_max';
    }
    return 'normal';
}

function temperature_status_label(string $status): string
{
    return match ($status) {
        'below_min' => 'Rendah',
        'above_max' => 'Tinggi',
        default => 'Normal',
    };
}

function device_connection_status(array $device, ?int $nowTs = null): string
{
    if (!(int) $device['is_active']) {
        return 'inactive';
    }
    if (empty($device['last_seen_at'])) {
        return 'never';
    }
    $nowTs ??= time();
    $seen = new DateTimeImmutable((string) $device['last_seen_at'], new DateTimeZone('UTC'));
    $interval = max(1, (int) $device['reporting_interval']);
    $age = $nowTs - $seen->getTimestamp();
    if ($age <= (3 * $interval)) {
        return 'online';
    }
    return 'offline';
}

function connection_status_label(string $status): string
{
    return match ($status) {
        'online' => 'Online',
        'offline' => 'Offline',
        'inactive' => 'Nonaktif',
        default => 'Belum terhubung',
    };
}

function csv_safe(string $value): string
{
    if (preg_match('/^[=+\-@\t\r]/', $value) === 1) {
        return "'" . $value;
    }
    return $value;
}

function text_length(string $value): int
{
    if (function_exists('mb_strlen')) {
        return mb_strlen($value);
    }
    return strlen($value);
}
