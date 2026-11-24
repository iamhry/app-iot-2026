<?php

declare(strict_types=1);

function request_header(string $name): string
{
    $key = 'HTTP_' . strtoupper(str_replace('-', '_', $name));
    if (!empty($_SERVER[$key]) && is_string($_SERVER[$key])) {
        return $_SERVER[$key];
    }
    $redirectKey = 'REDIRECT_' . $key;
    if (!empty($_SERVER[$redirectKey]) && is_string($_SERVER[$redirectKey])) {
        return $_SERVER[$redirectKey];
    }
    if (function_exists('getallheaders')) {
        $headers = getallheaders();
        if (is_array($headers)) {
            foreach ($headers as $headerName => $value) {
                if (strcasecmp((string) $headerName, $name) === 0) {
                    return (string) $value;
                }
            }
        }
    }
    return '';
}

function content_type(): string
{
    $raw = (string) ($_SERVER['CONTENT_TYPE'] ?? $_SERVER['HTTP_CONTENT_TYPE'] ?? '');
    return strtolower(trim(explode(';', $raw)[0]));
}

function api_require_method(string $method): void
{
    if (strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? '')) !== strtoupper($method)) {
        json_error('Metode tidak diizinkan.', 405, [], ['Allow' => strtoupper($method)]);
    }
}

function bearer_token(): ?string
{
    $header = trim(request_header('Authorization'));
    if (!preg_match('/^Bearer\s+(\S+)$/i', $header, $matches)) {
        return null;
    }
    return $matches[1];
}

function authenticate_device(): array
{
    $token = bearer_token();
    if ($token === null || $token === '') {
        json_error('Token autentikasi diperlukan.', 401, [], ['WWW-Authenticate' => 'Bearer realm="iot-api"']);
    }

    $failBucket = 'authfail:' . hash('sha256', client_ip());
    if (RateLimit::count($failBucket, RATE_LIMIT_AUTH_FAIL_WINDOW) >= RATE_LIMIT_AUTH_FAIL_MAX) {
        json_error('Terlalu banyak percobaan autentikasi. Coba lagi nanti.', 429, [], [
            'Retry-After' => (string) RATE_LIMIT_AUTH_FAIL_WINDOW,
        ]);
    }

    $device = Device::findByTokenHash(hash('sha256', $token));
    if ($device === null) {
        RateLimit::hit($failBucket, RATE_LIMIT_AUTH_FAIL_WINDOW);
        json_error('Token tidak valid.', 401, [], ['WWW-Authenticate' => 'Bearer realm="iot-api"']);
    }

    $hits = RateLimit::hit('device:' . $device['id'], RATE_LIMIT_DEVICE_WINDOW);
    if ($hits > RATE_LIMIT_DEVICE_MAX) {
        json_error('Terlalu banyak permintaan. Coba lagi nanti.', 429, [], [
            'Retry-After' => (string) RATE_LIMIT_DEVICE_WINDOW,
        ]);
    }
    if (!(int) $device['is_active']) {
        json_error('Perangkat nonaktif.', 403);
    }

    return $device;
}

function read_json_body(): array
{
    $length = (int) ($_SERVER['CONTENT_LENGTH'] ?? 0);
    if ($length > MAX_REQUEST_BYTES) {
        json_error('Ukuran permintaan terlalu besar.', 400);
    }
    $raw = file_get_contents('php://input');
    if ($raw === false) {
        json_error('Permintaan tidak dapat dibaca.', 400);
    }
    if (strlen($raw) > MAX_REQUEST_BYTES) {
        json_error('Ukuran permintaan terlalu besar.', 400);
    }
    $trimmed = ltrim($raw);
    if ($trimmed === '' || $trimmed[0] !== '{') {
        json_error('JSON tidak valid.', 400);
    }
    try {
        $data = json_decode($raw, true, 16, JSON_THROW_ON_ERROR);
    } catch (JsonException) {
        json_error('JSON tidak valid.', 400);
    }
    if (!is_array($data)) {
        json_error('JSON tidak valid.', 400);
    }
    return $data;
}
