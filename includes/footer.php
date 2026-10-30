<?php
/**
 * Shared footer — closes <main>, renders footer, loads main.js, closes body/html.
 * Used by both public and admin pages.
 */
?>
</main>

<footer class="site-footer">
    <div class="container">
        <div class="site-footer__inner">
            <p class="site-footer__copy">&copy; <?= date('Y') ?> <?= t('footer.rights') ?></p>
            <p class="site-footer__verse"><?= t('footer.verse') ?></p>
        </div>
    </div>
</footer>

<script src="<?= e(url('assets/js/main.js')) ?>"></script>
</body>
</html>
