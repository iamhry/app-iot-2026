<?php

declare(strict_types=1);

/** @var array $device */
/** @var string $period */
$periods = [
    '1h' => '1 jam',
    '6h' => '6 jam',
    '24h' => '24 jam',
    '7d' => '7 hari',
];
?>
<section class="panel" id="chart-root" data-device-id="<?= e((string) $device['id']) ?>" data-period="<?= e($period) ?>">
    <div class="panel-head">
        <div>
            <h2 class="h5 mb-1"><?= e((string) $device['name']) ?></h2>
            <p><?= e((string) $device['device_id']) ?> · <?= e((string) $device['location']) ?> · ambang <?= e(format_decimal($device['min_temperature'])) ?>–<?= e(format_decimal($device['max_temperature'])) ?> °C</p>
        </div>
        <div class="btn-group" role="group" aria-label="Periode grafik">
            <?php foreach ($periods as $key => $label): ?>
                <button class="btn btn-outline-secondary<?= $period === $key ? ' active' : '' ?>" type="button" data-period="<?= e($key) ?>"><?= e($label) ?></button>
            <?php endforeach; ?>
        </div>
    </div>
    <p class="meta-line" id="chart-updated"></p>
    <p id="chart-status" class="text-danger" role="status"></p>
    <p id="chart-empty" class="empty-state" hidden>Belum ada pengukuran pada periode ini.</p>
    <h3 class="h6">Suhu</h3>
    <div class="chart-box"><canvas id="chart-temperature" aria-label="Grafik suhu"></canvas></div>
    <h3 class="h6">Kelembapan</h3>
    <div class="chart-box"><canvas id="chart-humidity" aria-label="Grafik kelembapan"></canvas></div>
</section>
