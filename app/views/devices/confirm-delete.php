<?php

declare(strict_types=1);

/** @var array $device */
/** @var bool $hasHistory */
?>
<section class="panel">
    <h2 class="h5">Konfirmasi penonaktifan</h2>
    <?php if ($hasHistory): ?>
        <p><?= e((string) $device['name']) ?> memiliki riwayat pengukuran, jadi perangkat tidak dihapus permanen. Status akan diubah menjadi nonaktif dan token tidak lagi dapat mengirim data.</p>
    <?php else: ?>
        <p><?= e((string) $device['name']) ?> belum memiliki riwayat. Perangkat akan dihapus permanen.</p>
    <?php endif; ?>
    <form method="post" action="/devices/delete.php" class="d-flex gap-2">
        <?= csrf_field() ?>
        <input type="hidden" name="id" value="<?= e((string) $device['id']) ?>">
        <button class="btn btn-danger" type="submit"><?= $hasHistory ? 'Nonaktifkan' : 'Hapus permanen' ?></button>
        <a class="btn btn-outline-secondary" href="/devices/show.php?id=<?= e((string) $device['id']) ?>">Batal</a>
    </form>
</section>
