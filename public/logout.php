<?php

declare(strict_types=1);

require dirname(__DIR__) . '/app/bootstrap.php';
send_security_headers();

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    http_response_code(405);
    header('Allow: POST');
    echo 'Logout hanya dapat dilakukan melalui formulir.';
    exit;
}

require_admin();
csrf_verify();
logout_user();
redirect('/login.php');
