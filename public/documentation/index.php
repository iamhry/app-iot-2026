<?php

declare(strict_types=1);

require dirname(__DIR__, 2) . '/app/bootstrap.php';
send_security_headers();
require_admin();

render_admin('documentation/index', [
    'pageTitle' => 'Dokumentasi API',
    'activeNav' => 'docs',
]);
