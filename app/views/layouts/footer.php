    </main>
    <footer class="app-footer">
        Prototipe pembelajaran D4 Teknologi Rekayasa Elektromedis. Bukan sistem klinis tervalidasi dan bukan pengendali alat medis.
    </footer>
</div>
</div>
<script src="/assets/vendor/bootstrap.bundle.min.js"></script>
<script src="/assets/js/app.js"></script>
<?php if (!empty($extraScripts) && is_array($extraScripts)): ?>
    <?php foreach ($extraScripts as $script): ?>
        <script src="<?= e((string) $script) ?>"></script>
    <?php endforeach; ?>
<?php endif; ?>
</body>
</html>
