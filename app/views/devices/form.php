<?php

declare(strict_types=1);

/** @var array $values */
/** @var array $errors */
/** @var string $formAction */
/** @var string $submitLabel */

$invalid = static function (array $errors, string $key): string {
    return isset($errors[$key]) ? ' is-invalid' : '';
};
$errorText = static function (array $errors, string $key): string {
    if (!isset($errors[$key])) {
        return '';
    }
    return '<div class="invalid-feedback d-block">' . e((string) $errors[$key]) . '</div>';
};
?>
<form class="panel" method="post" action="<?= e($formAction) ?>">
    <?= csrf_field() ?>
    <?php if (!empty($deviceId)): ?>
        <input type="hidden" name="id" value="<?= e((string) $deviceId) ?>">
    <?php endif; ?>
    <div class="row g-3">
        <div class="col-md-6">
            <label class="form-label" for="name">Nama</label>
            <input class="form-control<?= $invalid($errors, 'name') ?>" id="name" name="name" maxlength="100" required value="<?= e((string) $values['name']) ?>">
            <?= $errorText($errors, 'name') ?>
        </div>
        <div class="col-md-6">
            <label class="form-label" for="location">Lokasi</label>
            <input class="form-control<?= $invalid($errors, 'location') ?>" id="location" name="location" maxlength="150" required value="<?= e((string) $values['location']) ?>">
            <?= $errorText($errors, 'location') ?>
        </div>
        <div class="col-md-6">
            <label class="form-label" for="sensor_type">Jenis sensor</label>
            <select class="form-select<?= $invalid($errors, 'sensor_type') ?>" id="sensor_type" name="sensor_type" required>
                <?php foreach (sensor_catalog() as $code => $sensor): ?>
                    <option value="<?= e($code) ?>" <?= $values['sensor_type'] === $code ? 'selected' : '' ?>><?= e($sensor['label']) ?></option>
                <?php endforeach; ?>
            </select>
            <?= $errorText($errors, 'sensor_type') ?>
        </div>
        <div class="col-md-6">
            <label class="form-label" for="reporting_interval">Interval pengiriman (detik)</label>
            <input class="form-control<?= $invalid($errors, 'reporting_interval') ?>" id="reporting_interval" name="reporting_interval" inputmode="numeric" required value="<?= e((string) $values['reporting_interval']) ?>">
            <?= $errorText($errors, 'reporting_interval') ?>
        </div>
        <div class="col-md-6">
            <label class="form-label" for="min_temperature">Batas minimum suhu (°C)</label>
            <input class="form-control<?= $invalid($errors, 'min_temperature') ?>" id="min_temperature" name="min_temperature" inputmode="decimal" required value="<?= e((string) $values['min_temperature']) ?>">
            <?= $errorText($errors, 'min_temperature') ?>
        </div>
        <div class="col-md-6">
            <label class="form-label" for="max_temperature">Batas maksimum suhu (°C)</label>
            <input class="form-control<?= $invalid($errors, 'max_temperature') ?>" id="max_temperature" name="max_temperature" inputmode="decimal" required value="<?= e((string) $values['max_temperature']) ?>">
            <?= $errorText($errors, 'max_temperature') ?>
        </div>
        <div class="col-12">
            <div class="form-check">
                <input class="form-check-input" type="checkbox" id="is_active" name="is_active" value="1" <?= (int) $values['is_active'] === 1 ? 'checked' : '' ?>>
                <label class="form-check-label" for="is_active">Perangkat aktif</label>
            </div>
        </div>
    </div>
    <div class="d-flex gap-2 mt-4">
        <button class="btn btn-teal" type="submit"><?= e($submitLabel) ?></button>
        <a class="btn btn-outline-secondary" href="/devices/index.php">Batal</a>
    </div>
</form>
<?php if (!empty($deviceId)): ?>
    <p class="meta-line mt-3">Mengubah data ini tidak mengganti token perangkat.</p>
<?php else: ?>
    <p class="meta-line mt-3">Device ID dan token dibuat otomatis setelah formulir disimpan.</p>
<?php endif; ?>
