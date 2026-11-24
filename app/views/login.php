<?php

declare(strict_types=1);

/** @var string $error */
?>
<div class="brand mb-3">
    <span class="brand-mark" aria-hidden="true"><i class="bi bi-thermometer-half"></i></span>
    <div>
        <strong>IoT Suhu</strong>
        <small class="d-block text-secondary">Monitoring ruangan</small>
    </div>
</div>
<h1>Masuk administrator</h1>
<?php if ($error !== ''): ?>
    <div class="alert alert-danger" role="alert"><?= e($error) ?></div>
<?php endif; ?>
<form method="post" action="/login.php" autocomplete="on">
    <?= csrf_field() ?>
    <div class="mb-3">
        <label class="form-label" for="username">Username</label>
        <input class="form-control" id="username" name="username" required maxlength="100" autocomplete="username" value="<?= e($username ?? '') ?>">
    </div>
    <div class="mb-3">
        <label class="form-label" for="password">Kata sandi</label>
        <input class="form-control" id="password" name="password" type="password" required maxlength="128" autocomplete="current-password">
    </div>
    <button class="btn btn-teal w-100" type="submit">Masuk</button>
</form>
