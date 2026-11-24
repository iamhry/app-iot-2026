<?php

declare(strict_types=1);

/** @var array $payload */
$summary = $payload['summary'];
?>
<div class="stat-grid">
    <article class="stat-card">
        <span>Total perangkat</span>
        <strong id="stat-total"><?= e((string) $summary['total']) ?></strong>
    </article>
    <article class="stat-card">
        <span>Online</span>
        <strong id="stat-online"><?= e((string) $summary['online']) ?></strong>
    </article>
    <article class="stat-card">
        <span>Offline</span>
        <strong id="stat-offline"><?= e((string) $summary['offline']) ?></strong>
    </article>
    <article class="stat-card">
        <span>Peringatan suhu</span>
        <strong id="stat-warnings"><?= e((string) $summary['warnings']) ?></strong>
    </article>
</div>
<section class="panel">
    <div class="panel-head">
        <div>
            <h2 class="h5 mb-1">Perangkat</h2>
            <p id="updated-at">Diperbarui <?= e((string) $payload['updated_at']) ?></p>
        </div>
        <p id="poll-status" role="status"></p>
    </div>
    <?php require BASE_PATH . '/app/views/partials/device-table.php'; ?>
</section>
