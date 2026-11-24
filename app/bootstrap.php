<?php

declare(strict_types=1);

define('BASE_PATH', dirname(__DIR__));

ini_set('display_errors', '0');
ini_set('display_startup_errors', '0');
ini_set('log_errors', '1');
error_reporting(E_ALL);

function e(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

require BASE_PATH . '/app/config/app.php';
require BASE_PATH . '/app/config/database.php';
require BASE_PATH . '/app/helpers/response.php';
require BASE_PATH . '/app/helpers/format.php';
require BASE_PATH . '/app/helpers/csrf.php';
require BASE_PATH . '/app/helpers/validation.php';
require BASE_PATH . '/app/models/User.php';
require BASE_PATH . '/app/models/Device.php';
require BASE_PATH . '/app/models/SensorReading.php';
require BASE_PATH . '/app/models/RateLimit.php';
require BASE_PATH . '/app/helpers/auth.php';
require BASE_PATH . '/app/helpers/device-auth.php';

date_default_timezone_set('UTC');

$scriptName = (string) ($_SERVER['SCRIPT_NAME'] ?? '');
$isApiRequest = str_contains($scriptName, '/api/') || str_contains($scriptName, '/ajax/');

set_exception_handler(static function (Throwable $e) use ($isApiRequest): void {
    error_log('[iot] ' . $e::class . ' ' . $e->getFile() . ':' . $e->getLine() . ' ' . $e->getMessage());
    if ($isApiRequest) {
        if (!headers_sent()) {
            json_error('Terjadi kesalahan internal.', 500);
        }
        exit;
    }
    if (!headers_sent()) {
        http_response_code(500);
        header('Content-Type: text/html; charset=utf-8');
    }
    echo 'Terjadi kesalahan internal. Silakan coba lagi.';
    exit;
});

function render_admin(string $view, array $data = []): void
{
    extract($data, EXTR_SKIP);
    $currentUser = $currentUser ?? current_user();
    $pageTitle = $pageTitle ?? 'Monitoring';
    $activeNav = $activeNav ?? '';
    require BASE_PATH . '/app/views/layouts/header.php';
    require BASE_PATH . '/app/views/layouts/sidebar.php';
    require BASE_PATH . '/app/views/' . $view . '.php';
    require BASE_PATH . '/app/views/layouts/footer.php';
}

function render_guest(string $view, array $data = []): void
{
    extract($data, EXTR_SKIP);
    $pageTitle = $pageTitle ?? 'Masuk';
    require BASE_PATH . '/app/views/layouts/guest-header.php';
    require BASE_PATH . '/app/views/' . $view . '.php';
    require BASE_PATH . '/app/views/layouts/guest-footer.php';
}

function redirect(string $path): never
{
    header('Location: ' . $path);
    exit;
}

function send_security_headers(): void
{
    header('X-Frame-Options: DENY');
    header('X-Content-Type-Options: nosniff');
    header('Referrer-Policy: same-origin');
    header("Content-Security-Policy: default-src 'self'; img-src 'self' data:; style-src 'self'; script-src 'self'; font-src 'self'; connect-src 'self'; frame-ancestors 'none'; base-uri 'self'; form-action 'self'");
}

function app_base_url(): string
{
    $configured = env_string('APP_URL', '');
    if ($configured !== '') {
        return rtrim($configured, '/');
    }
    $scheme = is_https() ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? '127.0.0.1:8000';
    return $scheme . '://' . $host;
}
