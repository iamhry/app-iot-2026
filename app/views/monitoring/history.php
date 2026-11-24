<?php

declare(strict_types=1);

/** @var array $filters */
/** @var array $errors */
/** @var array $devices */
/** @var array $locations */
/** @var array $result */
$offset = ($result['page'] - 1) * $result['per_page'];

$query = static function (array $overrides = []) use ($filters): string {
    $params = [
        'device' => $filters['device'],
        'location' => $filters['location'],
        'q' => $filters['q'],
        'date_from' => $filters['date_from'],
        'date_to' => $filters['date_to'],
        'page' => $filters['page'],
    ];
    foreach ($overrides as $key => $value) {
        $params[$key] = $value;
    }
    $params = array_filter($params, static fn ($value) => $value !== '' && $value !== null);
    return '/monitoring/history.php' . ($params === [] ? '' : '?' . http_build_query($params));
};
?>
<form class="panel mb-3" method="get" action="/monitoring/history.php">
    <div class="row g-3">
        <div class="col-md-4">
            <label class="form-label" for="device">Perangkat</label>
            <select class="form-select" id="device" name="device">
                <option value="">Semua perangkat</option>
                <?php foreach ($devices as $device): ?>
                    <option value="<?= e((string) $device['device_id']) ?>" <?= $filters['device'] === $device['device_id'] ? 'selected' : '' ?>>
                        <?= e((string) $device['name']) ?> (<?= e((string) $device['device_id']) ?>)
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-4">
            <label class="form-label" for="location">Lokasi</label>
            <select class="form-select" id="location" name="location">
                <option value="">Semua lokasi</option>
                <?php foreach ($locations as $location): ?>
                    <option value="<?= e((string) $location) ?>" <?= $filters['location'] === $location ? 'selected' : '' ?>><?= e((string) $location) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-4">
            <label class="form-label" for="q">Pencarian</label>
            <input class="form-control" id="q" name="q" maxlength="100" value="<?= e((string) $filters['q']) ?>" placeholder="Nama, Device ID, atau lokasi">
        </div>
        <div class="col-md-3">
            <label class="form-label" for="date_from">Tanggal awal</label>
            <input class="form-control<?= isset($errors['date_from']) ? ' is-invalid' : '' ?>" id="date_from" name="date_from" type="date" value="<?= e((string) $filters['date_from']) ?>">
            <?php if (isset($errors['date_from'])): ?><div class="invalid-feedback d-block"><?= e($errors['date_from']) ?></div><?php endif; ?>
        </div>
        <div class="col-md-3">
            <label class="form-label" for="date_to">Tanggal akhir</label>
            <input class="form-control<?= isset($errors['date_to']) ? ' is-invalid' : '' ?>" id="date_to" name="date_to" type="date" value="<?= e((string) $filters['date_to']) ?>">
            <?php if (isset($errors['date_to'])): ?><div class="invalid-feedback d-block"><?= e($errors['date_to']) ?></div><?php endif; ?>
        </div>
        <div class="col-md-6 d-flex align-items-end gap-2">
            <button class="btn btn-teal" type="submit">Terapkan</button>
            <a class="btn btn-outline-secondary" href="/monitoring/history.php">Reset</a>
            <a class="btn btn-outline-secondary" href="<?= e($query(['export' => 'csv', 'page' => null])) ?>">Ekspor CSV</a>
        </div>
    </div>
</form>
<p class="meta-line">Waktu ditampilkan dalam WIB. Ekspor memuat paling banyak <?= e((string) CSV_MAX_ROWS) ?> baris terbaru sesuai filter. <?= e((string) $result['total']) ?> data ditemukan.</p>
<section class="panel">
    <div class="table-responsive">
        <table class="table align-middle">
            <thead>
            <tr>
                <th>No</th>
                <th>Device ID</th>
                <th>Nama</th>
                <th>Lokasi</th>
                <th>Suhu</th>
                <th>Kelembapan</th>
                <th>Waktu pengukuran</th>
                <th>Waktu diterima</th>
            </tr>
            </thead>
            <tbody>
            <?php if ($result['rows'] === []): ?>
                <tr><td colspan="8" class="empty-state">Tidak ada pengukuran untuk filter ini.</td></tr>
            <?php else: ?>
                <?php foreach ($result['rows'] as $index => $row): ?>
                    <tr>
                        <td><?= e((string) ($offset + $index + 1)) ?></td>
                        <td><?= e((string) $row['device_id']) ?></td>
                        <td><?= e((string) $row['name']) ?></td>
                        <td><?= e((string) $row['location']) ?></td>
                        <td><?= e(format_decimal($row['temperature'])) ?> °C</td>
                        <td><?= e(format_decimal($row['humidity'])) ?> %</td>
                        <td><?= e(format_jakarta((string) $row['recorded_at'])) ?></td>
                        <td><?= e(format_jakarta((string) $row['received_at'])) ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
    <?php if ($result['pages'] > 1): ?>
        <nav class="pager" aria-label="Paginasi riwayat">
            <?php if ($result['page'] > 1): ?>
                <a class="btn btn-sm btn-outline-secondary" href="<?= e($query(['page' => $result['page'] - 1])) ?>">Sebelumnya</a>
            <?php endif; ?>
            <span class="meta-line align-self-center">Halaman <?= e((string) $result['page']) ?> dari <?= e((string) $result['pages']) ?></span>
            <?php if ($result['page'] < $result['pages']): ?>
                <a class="btn btn-sm btn-outline-secondary" href="<?= e($query(['page' => $result['page'] + 1])) ?>">Berikutnya</a>
            <?php endif; ?>
        </nav>
    <?php endif; ?>
</section>
