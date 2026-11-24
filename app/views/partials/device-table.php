<?php

declare(strict_types=1);

/** @var array $payload */
$devices = $payload['devices'];
?>
<div class="table-responsive">
    <table class="table align-middle">
        <thead>
        <tr>
            <th>Nama</th>
            <th>Device ID</th>
            <th>Lokasi</th>
            <th>Suhu terakhir</th>
            <th>Kelembapan terakhir</th>
            <th>Status suhu</th>
            <th>Status koneksi</th>
            <th>Waktu pengiriman terakhir</th>
            <th>Aksi</th>
        </tr>
        </thead>
        <tbody id="device-rows" data-actions="1">
        <?php if ($devices === []): ?>
            <tr>
                <td colspan="9" class="empty-state">Belum ada perangkat. Daftarkan sensor dari menu Perangkat Sensor.</td>
            </tr>
        <?php else: ?>
            <?php foreach ($devices as $device): ?>
                <?php
                $tempClass = match ($device['temperature_status']) {
                    'below_min' => 'badge-low',
                    'above_max' => 'badge-high',
                    'normal' => 'badge-normal',
                    default => '',
                };
                $connClass = $device['connection_status'] === 'online' ? 'badge-online' : 'badge-muted';
                ?>
                <tr>
                    <td><?= e($device['name']) ?></td>
                    <td><?= e($device['device_id']) ?></td>
                    <td><?= e($device['location']) ?></td>
                    <td>
                        <?= $device['temperature'] === null ? '—' : e($device['temperature'] . ' °C') ?>
                        <?php if ($device['stale'] && $device['temperature'] !== null): ?>
                            <span class="stale-note">Data terakhir, bukan kondisi saat ini</span>
                        <?php endif; ?>
                    </td>
                    <td><?= $device['humidity'] === null ? '—' : e($device['humidity'] . ' %') ?></td>
                    <td>
                        <?php if ($device['temperature_status'] === null): ?>
                            —
                        <?php else: ?>
                            <span class="<?= e($tempClass) ?>"><?= e($device['temperature_label']) ?></span>
                        <?php endif; ?>
                    </td>
                    <td><span class="<?= e($connClass) ?>"><?= e($device['connection_label']) ?></span></td>
                    <td><?= e($device['last_seen_label']) ?></td>
                    <td>
                        <div class="d-flex gap-2">
                            <a class="btn btn-sm btn-outline-secondary" href="/devices/show.php?id=<?= e((string) $device['id']) ?>">Detail</a>
                            <a class="btn btn-sm btn-teal" href="/monitoring/detail.php?id=<?= e((string) $device['id']) ?>">Grafik</a>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
        <?php endif; ?>
        </tbody>
    </table>
</div>
