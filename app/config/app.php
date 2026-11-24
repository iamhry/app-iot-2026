<?php

declare(strict_types=1);

const APP_NAME = 'Monitoring Suhu Ruangan';
const APP_TIMEZONE_DISPLAY = 'Asia/Jakarta';

const RATE_LIMIT_DEVICE_MAX = 30;
const RATE_LIMIT_DEVICE_WINDOW = 60;
const RATE_LIMIT_AUTH_FAIL_MAX = 20;
const RATE_LIMIT_AUTH_FAIL_WINDOW = 60;
const RATE_LIMIT_LOGIN_MAX = 5;
const RATE_LIMIT_LOGIN_WINDOW = 900;

const MAX_REQUEST_BYTES = 65536;
const HISTORY_PAGE_SIZE = 20;
const CSV_MAX_ROWS = 5000;

function load_env_file(string $path): void
{
    if (!is_file($path)) {
        return;
    }
    $lines = file($path, FILE_IGNORE_NEW_LINES);
    if ($lines === false) {
        return;
    }
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#')) {
            continue;
        }
        $pos = strpos($line, '=');
        if ($pos === false) {
            continue;
        }
        $key = trim(substr($line, 0, $pos));
        $value = trim(substr($line, $pos + 1));
        if ($value !== '' && ($value[0] === '"' || $value[0] === "'")) {
            $value = trim($value, "\"'");
        }
        if ($key === '' || getenv($key) !== false) {
            continue;
        }
        putenv($key . '=' . $value);
        $_ENV[$key] = $value;
    }
}

load_env_file(BASE_PATH . '/.env');

function env_string(string $key, string $default = ''): string
{
    $value = getenv($key);
    if ($value === false || $value === '') {
        return $default;
    }
    return $value;
}
