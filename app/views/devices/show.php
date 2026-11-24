<?php

declare(strict_types=1);

/** @var array $device */
/** @var array|null $credential */
/** @var array|null $latest */
$connection = device_connection_status($device);
$base = app_base_url();
?>
<?php if (is_array($credential)): ?>
    <div class="alert alert-warning" role="status">
        Token asli hanya ditampilkan sekali. Simpan di tempat yang aman. Token tidak disimpan di database dan tidak dapat dilihat kembali.
    </div>
    <section class="panel mb-3">
        <h2 class="h5">Kredensial perangkat</h2>
        <p class="meta-line mb-2">Device ID</p>
        <div class="d-flex gap-2 mb-3">
            <input class="form-control token-box" readonly value="<?= e((string) $credential['device_id']) ?>">
            <button class="btn btn-outline-secondary" type="button" data-copy="<?= e((string) $credential['device_id']) ?>">Salin</button>
        </div>
        <p class="meta-line mb-2">Token</p>
        <div class="d-flex gap-2 mb-3">
            <input class="form-control token-box" readonly value="<?= e((string) $credential['token']) ?>">
            <button class="btn btn-outline-secondary" type="button" data-copy="<?= e((string) $credential['token']) ?>">Salin</button>
        </div>
        <p class="meta-line mb-2">Endpoint</p>
        <input class="form-control token-box mb-3" readonly value="<?= e($base . '/api/v1/readings.php') ?>">
        <p class="meta-line mb-2">Contoh header</p>
        <pre class="token-box p-3">Authorization: Bearer &lt;token&gt;
Content-Type: application/json</pre>
        <p class="meta-line mb-2">Contoh JSON</p>
        <pre class="token-box p-3">{
  "device_id": "<?= e((string) $credential['device_id']) ?>",
  "temperature": 26.5,
  "humidity": 65.2
}</pre>
    </section>
<?php endif; ?>

<section class="panel mb-3">
    <div class="panel-head">
        <div>
            <h2 class="h5 mb-1"><?= e((string) $device['name']) ?></h2>
            <p><?= e((string) $device['device_id']) ?> · <?= e((string) $device['location']) ?></p>
        </div>
        <span class="<?= $connection === 'online' ? 'badge-online' : 'badge-muted' ?>"><?= e(connection_status_label($connection)) ?></span>
    </div>
    <dl class="row mb-0">
        <dt class="col-sm-4">Jenis sensor</dt>
        <dd class="col-sm-8"><?= e((string) $device['sensor_type']) ?></dd>
        <dt class="col-sm-4">Ambang suhu</dt>
        <dd class="col-sm-8"><?= e(format_decimal($device['min_temperature'])) ?> – <?= e(format_decimal($device['max_temperature'])) ?> °C</dd>
        <dt class="col-sm-4">Interval</dt>
        <dd class="col-sm-8"><?= e((string) $device['reporting_interval']) ?> detik</dd>
        <dt class="col-sm-4">Status perangkat</dt>
        <dd class="col-sm-8"><?= (int) $device['is_active'] === 1 ? 'Aktif' : 'Nonaktif' ?></dd>
        <dt class="col-sm-4">Awalan token</dt>
        <dd class="col-sm-8"><?= e((string) $device['token_prefix']) ?>…</dd>
        <dt class="col-sm-4">Terakhir terlihat</dt>
        <dd class="col-sm-8"><?= e(format_jakarta($device['last_seen_at'] === null ? null : (string) $device['last_seen_at'])) ?> WIB</dd>
        <dt class="col-sm-4">Pengukuran terakhir</dt>
        <dd class="col-sm-8">
            <?php if ($latest === null): ?>
                Belum ada data.
            <?php else: ?>
                <?= e(format_decimal($latest['temperature'])) ?> °C, <?= e(format_decimal($latest['humidity'])) ?> %
                pada <?= e(format_jakarta((string) $latest['recorded_at'])) ?> WIB
            <?php endif; ?>
        </dd>
    </dl>
</section>
<div class="d-flex flex-wrap gap-2">
    <a class="btn btn-outline-secondary" href="/devices/edit.php?id=<?= e((string) $device['id']) ?>">Ubah metadata</a>
    <a class="btn btn-outline-secondary" href="/devices/regenerate-token.php?id=<?= e((string) $device['id']) ?>">Regenerasi token</a>
    <a class="btn btn-teal" href="/monitoring/detail.php?id=<?= e((string) $device['id']) ?>">Lihat grafik</a>
    <a class="btn btn-outline-danger" href="/devices/delete.php?id=<?= e((string) $device['id']) ?>">Nonaktifkan atau hapus</a>
</div>
