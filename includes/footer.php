<?php
/**
 * Shared footer — closes <main>, renders footer with social media, loads main.js.
 * Used by both public and admin pages.
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

// Load social media (public pages only)
$_footer_socials = [];
if (!$_footer_is_admin) {
    try {
        $stmt = db()->prepare("SELECT `value` FROM settings WHERE `key` = 'contact_socials'");
        $stmt->execute();
        $row = $stmt->fetch();
        if ($row && $row['value'] !== '') {
            $decoded = json_decode($row['value'], true);
            if (is_array($decoded)) {
                $_footer_socials = array_filter($decoded, fn($s) => !empty($s['enabled']) && !empty($s['url']));
            }
        }
    } catch (Exception $e) { /* silently ignore */ }
}

$_social_icons = [
    'telegram' => '<svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M12 0C5.373 0 0 5.373 0 12s5.373 12 12 12 12-5.373 12-12S18.627 0 12 0zm5.894 8.221-1.97 9.28c-.145.658-.537.818-1.084.508l-3-2.21-1.447 1.394c-.16.16-.295.295-.605.295l.213-3.053 5.56-5.023c.242-.213-.054-.333-.373-.12l-6.871 4.326-2.962-.924c-.643-.204-.657-.643.136-.953l11.57-4.461c.537-.194 1.006.131.833.941z"/></svg>',
    'facebook' => '<svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>',
    'tiktok'   => '<svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M12.525.02c1.31-.02 2.61-.01 3.91-.02.08 1.53.63 3.09 1.75 4.17 1.12 1.11 2.7 1.62 4.24 1.79v4.03c-1.44-.05-2.89-.35-4.2-.97-.57-.26-1.1-.59-1.62-.93-.01 2.92.01 5.84-.02 8.75-.08 1.4-.54 2.79-1.35 3.94-1.31 1.92-3.58 3.17-5.91 3.21-1.43.08-2.86-.31-4.08-1.03-2.02-1.19-3.44-3.37-3.65-5.71-.02-.5-.03-1-.01-1.49.18-1.9 1.12-3.72 2.58-4.96 1.66-1.44 3.98-2.13 6.15-1.72.02 1.48-.04 2.96-.04 4.44-.99-.32-2.15-.23-3.02.37-.63.41-1.11 1.04-1.36 1.75-.21.51-.15 1.07-.14 1.61.24 1.64 1.82 3.02 3.5 2.87 1.12-.01 2.19-.66 2.77-1.61.19-.33.4-.67.41-1.06.1-1.79.06-3.57.07-5.36.01-4.03-.01-8.05.02-12.07z"/></svg>',
    'youtube'  => '<svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M23.495 6.205a3.007 3.007 0 0 0-2.088-2.088c-1.87-.501-9.396-.501-9.396-.501s-7.507-.01-9.396.501A3.007 3.007 0 0 0 .527 6.205a31.247 31.247 0 0 0-.522 5.805 31.247 31.247 0 0 0 .522 5.783 3.007 3.007 0 0 0 2.088 2.088c1.868.502 9.396.502 9.396.502s7.506 0 9.396-.502a3.007 3.007 0 0 0 2.088-2.088 31.247 31.247 0 0 0 .5-5.783 31.247 31.247 0 0 0-.5-5.805zM9.609 15.601V8.408l6.264 3.602z"/></svg>',
];
?>
</main>

<footer class="site-footer">
    <div class="container">
        <div class="site-footer__inner">
            <p class="site-footer__copy">&copy; <?= date('Y') ?> <?= $_footer_rights ?></p>

            <?php if (!empty($_footer_socials)): ?>
            <div class="footer-socials">
                <?php foreach ($_footer_socials as $platform => $soc): ?>
                <a href="<?= e($soc['url']) ?>"
                   target="_blank"
                   rel="noopener noreferrer"
                   class="footer-social-btn"
                   aria-label="<?= e(ucfirst($platform)) ?>">
                    <?= $_social_icons[$platform] ?? '' ?>
                </a>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

            <p class="site-footer__verse"><?= $_footer_verse ?></p>
        </div>
    </div>
</footer>

<script src="<?= e(url('assets/js/main.js')) ?>"></script>
</body>
</html>
