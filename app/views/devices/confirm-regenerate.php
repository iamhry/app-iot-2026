<?php

declare(strict_types=1);

/** @var array $device */
?>
<section class="panel">
    <h2 class="h5">Regenerasi token</h2>
    <p>Token <?= e((string) $device['name']) ?> (<?= e((string) $device['device_id']) ?>) akan diganti. Token lama langsung tidak berlaku. Device ID tetap sama.</p>
    <form method="post" action="/devices/regenerate-token.php" class="d-flex gap-2">
        <?= csrf_field() ?>
        <input type="hidden" name="id" value="<?= e((string) $device['id']) ?>">
        <button class="btn btn-teal" type="submit">Ganti token</button>
        <a class="btn btn-outline-secondary" href="/devices/show.php?id=<?= e((string) $device['id']) ?>">Batal</a>
    </form>
</section>
