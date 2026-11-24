<?php

declare(strict_types=1);

function is_https(): bool
{
    if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
        return true;
    }
    return strtolower((string) ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '')) === 'https';
}

function start_admin_session(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }
    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');
    ini_set('session.cookie_httponly', '1');
    ini_set('session.cookie_samesite', 'Lax');
    session_name('IOTSESSID');
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'secure' => is_https(),
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

function current_user(): ?array
{
    start_admin_session();
    $id = $_SESSION['user_id'] ?? null;
    if (!is_int($id) && !(is_string($id) && ctype_digit($id))) {
        return null;
    }
    return User::findById((int) $id);
}

function require_admin(): array
{
    $user = current_user();
    if ($user !== null) {
        return $user;
    }
    $script = (string) ($_SERVER['SCRIPT_NAME'] ?? '');
    $accept = (string) ($_SERVER['HTTP_ACCEPT'] ?? '');
    if (str_contains($script, '/ajax/') || str_contains($accept, 'application/json')) {
        json_error('Sesi berakhir. Silakan masuk kembali.', 401);
    }
    redirect('/login.php');
}

function client_ip(): string
{
    return substr((string) ($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0'), 0, 45);
}

function attempt_login(string $username, string $password): ?string
{
    $bucket = 'login:' . hash('sha256', client_ip() . '|' . strtolower($username));
    if (RateLimit::count($bucket, RATE_LIMIT_LOGIN_WINDOW) >= RATE_LIMIT_LOGIN_MAX) {
        return 'Terlalu banyak percobaan masuk. Coba lagi beberapa menit lagi.';
    }

    $dummy = '$2y$10$N9qo8uLOickgx2ZMRZoMyeIjZAgcfl7p92ldGxad68LJZdL17lhWy';
    $user = User::findByUsername($username);
    $hash = is_array($user) ? (string) $user['password'] : $dummy;
    $valid = password_verify($password, $hash);
    if (!is_array($user) || !$valid) {
        RateLimit::hit($bucket, RATE_LIMIT_LOGIN_WINDOW);
        return 'Username atau kata sandi salah.';
    }
    if (password_needs_rehash($hash, PASSWORD_DEFAULT)) {
        User::updatePasswordHash((int) $user['id'], $password);
    }

    start_admin_session();
    session_regenerate_id(true);
    $_SESSION['user_id'] = (int) $user['id'];
    $_SESSION['_csrf'] = bin2hex(random_bytes(32));
    return null;
}

function logout_user(): void
{
    start_admin_session();
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', [
            'expires' => time() - 42000,
            'path' => $params['path'],
            'domain' => $params['domain'],
            'secure' => (bool) $params['secure'],
            'httponly' => (bool) $params['httponly'],
            'samesite' => 'Lax',
        ]);
    }
    session_destroy();
}

function flash(string $key, ?string $value = null): ?string
{
    start_admin_session();
    if ($value !== null) {
        $_SESSION['_flash'][$key] = $value;
        return null;
    }
    if (!isset($_SESSION['_flash'][$key]) || !is_string($_SESSION['_flash'][$key])) {
        return null;
    }
    $message = $_SESSION['_flash'][$key];
    unset($_SESSION['_flash'][$key]);
    return $message;
}

function store_credential(int $devicePk, string $deviceId, string $token): void
{
    start_admin_session();
    $_SESSION['device_credential'] = [
        'device_pk' => $devicePk,
        'device_id' => $deviceId,
        'token' => $token,
        'expires_at' => time() + 300,
    ];
}

function pull_credential(int $devicePk): ?array
{
    start_admin_session();
    $credential = $_SESSION['device_credential'] ?? null;
    if (!is_array($credential) || (int) ($credential['device_pk'] ?? 0) !== $devicePk) {
        return null;
    }
    unset($_SESSION['device_credential']);
    if ((int) ($credential['expires_at'] ?? 0) < time()) {
        return null;
    }
    return $credential;
}

function require_device_record(int $id): array
{
    $device = Device::findById($id);
    if ($device !== null) {
        return $device;
    }
    http_response_code(404);
    render_admin('message', [
        'pageTitle' => 'Tidak ditemukan',
        'activeNav' => 'devices',
        'message' => 'Perangkat tidak ditemukan.',
    ]);
    exit;
}
