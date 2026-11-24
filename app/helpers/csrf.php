<?php

declare(strict_types=1);

function csrf_token(): string
{
    start_admin_session();
    $token = $_SESSION['_csrf'] ?? '';
    if (!is_string($token) || $token === '') {
        $token = bin2hex(random_bytes(32));
        $_SESSION['_csrf'] = $token;
    }
    return $token;
}

function csrf_field(): string
{
    return '<input type="hidden" name="_csrf" value="' . e(csrf_token()) . '">';
}

function csrf_verify(): void
{
    start_admin_session();
    $sent = $_POST['_csrf'] ?? '';
    $known = $_SESSION['_csrf'] ?? '';
    if (!is_string($sent) || !is_string($known) || $known === '' || !hash_equals($known, $sent)) {
        http_response_code(419);
        header('Content-Type: text/html; charset=utf-8');
        echo 'Permintaan ditolak karena token CSRF tidak valid. Muat ulang halaman, lalu coba lagi.';
        exit;
    }
}
