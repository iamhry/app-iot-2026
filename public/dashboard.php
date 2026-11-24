<?php

declare(strict_types=1);

require dirname(__DIR__) . '/app/bootstrap.php';
send_security_headers();
require_admin();

render_admin('dashboard', [
    'pageTitle' => 'Dashboard',
    'activeNav' => 'dashboard',
    'payload' => Device::dashboardPayload(),
    'extraScripts' => ['/assets/js/dashboard.js'],
]);
