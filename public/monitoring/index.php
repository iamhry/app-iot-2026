<?php

declare(strict_types=1);

require dirname(__DIR__, 2) . '/app/bootstrap.php';
send_security_headers();
require_admin();

render_admin('monitoring/index', [
    'pageTitle' => 'Monitoring',
    'activeNav' => 'monitoring',
    'payload' => Device::dashboardPayload(),
    'extraScripts' => ['/assets/js/dashboard.js'],
]);
