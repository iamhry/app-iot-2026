<?php

declare(strict_types=1);
?>
<p class="meta-line">Pilih perangkat untuk melihat grafik suhu dan kelembapan. Tabel di bawah diperbarui setiap 10 detik.</p>
<section class="panel">
    <div class="panel-head">
        <h2 class="h5 mb-0">Perangkat</h2>
        <p id="poll-status" role="status"></p>
    </div>
    <p class="meta-line" id="updated-at"></p>
    <?php require BASE_PATH . '/app/views/partials/device-table.php'; ?>
</section>
