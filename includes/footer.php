<?php
/**
 * Shared footer — closes <main>, renders footer, loads main.js, closes body/html.
 * Used by both public and admin pages.
 *
 * Admin context detection: if this footer is rendered from within the admin/
 * directory, suppress the language-aware t() calls and use hardcoded English
 * to prevent public-site language session bleed into the admin UI.
 */
$_footer_is_admin = str_contains(
    str_replace('\\', '/', realpath($_SERVER['SCRIPT_FILENAME'] ?? '')),
    '/admin/'
);
$_footer_rights = $_footer_is_admin
    ? 'Semidalis Mission and Volunteer Ministry. All rights reserved.'
    : t('footer.rights');
$_footer_verse = $_footer_is_admin
    ? 'James 1:27'
    : t('footer.verse');
?>
</main>

<footer class="site-footer">
    <div class="container">
        <div class="site-footer__inner">
            <p class="site-footer__copy">&copy; <?= date('Y') ?> <?= $_footer_rights ?></p>
            <p class="site-footer__verse"><?= $_footer_verse ?></p>
        </div>
    </div>
</footer>

<script src="<?= e(url('assets/js/main.js')) ?>"></script>
</body>
</html>
