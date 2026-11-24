<?php

declare(strict_types=1);

/** @var array $devices */
?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <p class="meta-line mb-0">Device ID dan token dibuat otomatis saat perangkat disimpan.</p>
    <a class="btn btn-teal" href="/devices/create.php">Tambah perangkat</a>
</div>
<div class="panel">
    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead>
            <tr>
                <th>Nama</th>
                <th>Device ID</th>
                <th>Lokasi</th>
                <th>Sensor</th>
                <th>Ambang suhu</th>
                <th>Status</th>
                <th></th>
            </tr>
            </thead>
            <tbody>
            <?php if ($devices === []): ?>
                <tr>
                    <td colspan="7" class="empty-state">Belum ada perangkat sensor.</td>
                </tr>
            <?php else: ?>
                <?php foreach ($devices as $device): ?>
                    <tr>
                        <td><?= e((string) $device['name']) ?></td>
                        <td><?= e((string) $device['device_id']) ?></td>
                        <td><?= e((string) $device['location']) ?></td>
                        <td><?= e((string) $device['sensor_type']) ?></td>
                        <td><?= e(format_decimal($device['min_temperature'])) ?> – <?= e(format_decimal($device['max_temperature'])) ?> °C</td>
                        <td>
                            <?php if ((int) $device['is_active'] === 1): ?>
                                <span class="badge-online">Aktif</span>
                            <?php else: ?>
                                <span class="badge-muted">Nonaktif</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-end">
                            <a class="btn btn-sm btn-outline-secondary" href="/devices/show.php?id=<?= e((string) $device['id']) ?>">Detail</a>
                            <a class="btn btn-sm btn-outline-secondary" href="/devices/edit.php?id=<?= e((string) $device['id']) ?>">Ubah</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
