<?php

declare(strict_types=1);

/** @var string $activeNav */
/** @var string $pageTitle */
/** @var array|null $currentUser */

$navItems = [
    ['key' => 'dashboard', 'href' => '/dashboard.php', 'icon' => 'bi-speedometer2', 'label' => 'Dashboard'],
    ['key' => 'devices', 'href' => '/devices/index.php', 'icon' => 'bi-cpu', 'label' => 'Perangkat Sensor'],
    ['key' => 'monitoring', 'href' => '/monitoring/index.php', 'icon' => 'bi-graph-up', 'label' => 'Monitoring'],
    ['key' => 'history', 'href' => '/monitoring/history.php', 'icon' => 'bi-clock-history', 'label' => 'Riwayat Pengukuran'],
    ['key' => 'docs', 'href' => '/documentation/index.php', 'icon' => 'bi-journal-text', 'label' => 'Dokumentasi API'],
];

$renderNav = static function (string $extraClass = '') use ($navItems, $activeNav): void {
    echo '<nav class="nav-stack ' . e($extraClass) . '" aria-label="Menu utama">';
    foreach ($navItems as $item) {
        $class = $item['key'] === $activeNav ? 'nav-link active' : 'nav-link';
        echo '<a class="' . $class . '" href="' . e($item['href']) . '">';
        echo '<i class="bi ' . e($item['icon']) . '" aria-hidden="true"></i>';
        echo '<span>' . e($item['label']) . '</span>';
        echo '</a>';
    }
    echo '</nav>';
};
?>
<aside class="sidebar d-none d-lg-flex">
    <div class="brand">
        <span class="brand-mark" aria-hidden="true"><i class="bi bi-thermometer-half"></i></span>
        <div>
            <strong>IoT Suhu</strong>
            <small>Ruang pembelajaran</small>
        </div>
    </div>
    <?php $renderNav(); ?>
    <div class="sidebar-foot">
        <div class="user-chip">
            <i class="bi bi-person-circle" aria-hidden="true"></i>
            <span><?= e((string) ($currentUser['name'] ?? 'Administrator')) ?></span>
        </div>
        <form method="post" action="/logout.php">
            <?= csrf_field() ?>
            <button type="submit" class="nav-link logout-link">
                <i class="bi bi-box-arrow-right" aria-hidden="true"></i>
                <span>Logout</span>
            </button>
        </form>
    </div>
</aside>
<div class="offcanvas offcanvas-start" tabindex="-1" id="mobileNav" aria-labelledby="mobileNavLabel">
    <div class="offcanvas-header">
        <h2 class="offcanvas-title h5" id="mobileNavLabel">Menu</h2>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Tutup"></button>
    </div>
    <div class="offcanvas-body d-flex flex-column">
        <?php $renderNav('mobile'); ?>
        <form method="post" action="/logout.php" class="mt-auto">
            <?= csrf_field() ?>
            <button type="submit" class="btn btn-outline-secondary w-100">Logout</button>
        </form>
    </div>
</div>
<div class="main-column">
    <header class="topbar">
        <button class="btn btn-outline-secondary d-lg-none" type="button" data-bs-toggle="offcanvas" data-bs-target="#mobileNav" aria-controls="mobileNav">
            <i class="bi bi-list" aria-hidden="true"></i>
            <span class="visually-hidden">Buka menu</span>
        </button>
        <h1><?= e($pageTitle) ?></h1>
    </header>
    <main id="konten" class="content">
        <?php $success = flash('success'); ?>
        <?php $errorMessage = flash('error'); ?>
        <?php if ($success !== null): ?>
            <div class="alert alert-success" role="status"><?= e($success) ?></div>
        <?php endif; ?>
        <?php if ($errorMessage !== null): ?>
            <div class="alert alert-danger" role="alert"><?= e($errorMessage) ?></div>
        <?php endif; ?>
