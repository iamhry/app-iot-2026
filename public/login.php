<?php

declare(strict_types=1);

require dirname(__DIR__) . '/app/bootstrap.php';
send_security_headers();

if (current_user() !== null) {
    redirect('/dashboard.php');
}

$error = '';
$username = '';
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    csrf_verify();
    $username = trim((string) ($_POST['username'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');
    if ($username === '' || $password === '') {
        $error = 'Username dan kata sandi wajib diisi.';
    } else {
        $error = attempt_login($username, $password) ?? '';
        if ($error === '') {
            redirect('/dashboard.php');
        }
    }
} elseif (($_SERVER['REQUEST_METHOD'] ?? '') !== 'GET') {
    http_response_code(405);
    header('Allow: GET, POST');
    echo 'Metode tidak diizinkan.';
    exit;
}

render_guest('login', [
    'pageTitle' => 'Masuk',
    'error' => $error,
    'username' => $username,
]);
