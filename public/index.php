<?php

declare(strict_types=1);

require dirname(__DIR__) . '/app/bootstrap.php';
send_security_headers();

if (current_user() !== null) {
    redirect('/dashboard.php');
}
redirect('/login.php');
