<?php
/**
 * Contact — contact info + a mailto: link only (no POST, no CSRF, no
 * server-side mail handler) per the design verdict.
 */
require __DIR__ . '/../config/config.php';

function contact_setting(string $key, string $default = ''): string {
    try {
        $stmt = db()->prepare("SELECT `value` FROM settings WHERE `key` = ?");
        $stmt->execute([$key]);
        $row = $stmt->fetch();
        return ($row && $row['value'] !== '') ? (string) $row['value'] : $default;
    } catch (PDOException $e) { return $default; }
}
function contact_setting_json(string $key, array $default = []): array {
    $raw = contact_setting($key, '');
    if ($raw === '') return $default;
    $d = json_decode($raw, true);
    return is_array($d) ? $d : $default;
}
function contact_setting_lang(string $key, string $lang, string $default = ''): string {
    $val = contact_setting($key . '_' . $lang, '');
    if ($val !== '') return $val;
    return contact_setting($key, $default);
}

$lang = $_SESSION['lang'] ?? 'en';
$lang = in_array($lang, ['en', 'am']) ? $lang : 'en';

$contactEmails    = contact_setting_json('contact_emails', [contact_setting('contact_email', 'info@simedalis.org')]);
if (empty($contactEmails)) $contactEmails = [contact_setting('contact_email', 'info@simedalis.org')];
$contactEmail     = $contactEmails[0];
$contactPhones    = contact_setting_json('contact_phones', ['+000 000 000 0000']);
// Addresses: try language-specific JSON key, fall back to legacy single string
$_addrRaw = contact_setting('contact_addresses_' . $lang, '');
if ($_addrRaw !== '') {
    $contactAddresses = json_decode($_addrRaw, true);
    if (!is_array($contactAddresses)) $contactAddresses = [];
}
if (empty($contactAddresses)) {
    // fall back to legacy single-value key
    $_legacyAddr = contact_setting('contact_address_' . $lang, contact_setting('contact_address', 'Your Address Here'));
    $contactAddresses = [$_legacyAddr];
}
$contactSocials   = contact_setting_json('contact_socials', []);
$activeSocials    = array_filter($contactSocials, fn($s) => !empty($s['enabled']) && !empty($s['url']));

$pageTitle = t('nav.contact');
require __DIR__ . '/../includes/header.php';
?>

<div class="container">
<section class="section contact-layout">

    <!-- Info cards -->
    <div class="contact-cards">

        <div class="contact-card">
            <div class="contact-card__icon-wrap">
                <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
            </div>
            <div class="contact-card__body">
                <h3><?= t('contact.email') ?></h3>
                <p><?= t('contact.email_body') ?></p>
                <?php foreach ($contactEmails as $em): ?>
                <a href="mailto:<?= e($em) ?>" class="contact-card__value"><?= e($em) ?></a>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="contact-card">
            <div class="contact-card__icon-wrap">
                <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07A19.5 19.5 0 0 1 4.69 12 19.79 19.79 0 0 1 1.63 3.3 2 2 0 0 1 3.6 1h3a2 2 0 0 1 2 1.72c.127.96.361 1.903.7 2.81a2 2 0 0 1-.45 2.11L7.91 8.6a16 16 0 0 0 6 6l.96-.96a2 2 0 0 1 2.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0 1 21.54 16z"/></svg>
            </div>
            <div class="contact-card__body">
                <h3><?= t('contact.phone') ?></h3>
                <p><?= t('contact.phone_body') ?></p>
                <?php foreach ($contactPhones as $ph): ?>
                <a href="tel:<?= e(preg_replace('/\s+/', '', $ph)) ?>" class="contact-card__value"><?= e($ph) ?></a>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="contact-card">
            <div class="contact-card__icon-wrap">
                <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 10c0 7-9 13-9 13S3 17 3 10a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
            </div>
            <div class="contact-card__body">
                <h3><?= t('contact.address') ?></h3>
                <p><?= t('contact.address_body') ?></p>
                <?php foreach ($contactAddresses as $addr): ?>
                <span class="contact-card__value"><?= e($addr) ?></span>
                <?php endforeach; ?>
            </div>
        </div>

        <?php if (!empty($activeSocials)): ?>
        <div class="contact-card">
            <div class="contact-card__icon-wrap">
                <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="18" cy="5" r="3"/><circle cx="6" cy="12" r="3"/><circle cx="18" cy="19" r="3"/><line x1="8.59" y1="13.51" x2="15.42" y2="17.49"/><line x1="15.41" y1="6.51" x2="8.59" y2="10.49"/></svg>
            </div>
            <div class="contact-card__body">
                <h3>Social Media</h3>
                <p>Follow us and stay updated with our latest news and events.</p>
                <div class="contact-socials">
                <?php
                $socialIcons = [
                    'telegram' => '<svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor"><path d="M12 0C5.373 0 0 5.373 0 12s5.373 12 12 12 12-5.373 12-12S18.627 0 12 0zm5.894 8.221-1.97 9.28c-.145.658-.537.818-1.084.508l-3-2.21-1.447 1.394c-.16.16-.295.295-.605.295l.213-3.053 5.56-5.023c.242-.213-.054-.333-.373-.12l-6.871 4.326-2.962-.924c-.643-.204-.657-.643.136-.953l11.57-4.461c.537-.194 1.006.131.833.941z"/></svg>',
                    'facebook' => '<svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>',
                    'tiktok'   => '<svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor"><path d="M12.525.02c1.31-.02 2.61-.01 3.91-.02.08 1.53.63 3.09 1.75 4.17 1.12 1.11 2.7 1.62 4.24 1.79v4.03c-1.44-.05-2.89-.35-4.2-.97-.57-.26-1.1-.59-1.62-.93-.01 2.92.01 5.84-.02 8.75-.08 1.4-.54 2.79-1.35 3.94-1.31 1.92-3.58 3.17-5.91 3.21-1.43.08-2.86-.31-4.08-1.03-2.02-1.19-3.44-3.37-3.65-5.71-.02-.5-.03-1-.01-1.49.18-1.9 1.12-3.72 2.58-4.96 1.66-1.44 3.98-2.13 6.15-1.72.02 1.48-.04 2.96-.04 4.44-.99-.32-2.15-.23-3.02.37-.63.41-1.11 1.04-1.36 1.75-.21.51-.15 1.07-.14 1.61.24 1.64 1.82 3.02 3.5 2.87 1.12-.01 2.19-.66 2.77-1.61.19-.33.4-.67.41-1.06.1-1.79.06-3.57.07-5.36.01-4.03-.01-8.05.02-12.07z"/></svg>',
                    'youtube'  => '<svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor"><path d="M23.495 6.205a3.007 3.007 0 0 0-2.088-2.088c-1.87-.501-9.396-.501-9.396-.501s-7.507-.01-9.396.501A3.007 3.007 0 0 0 .527 6.205a31.247 31.247 0 0 0-.522 5.805 31.247 31.247 0 0 0 .522 5.783 3.007 3.007 0 0 0 2.088 2.088c1.868.502 9.396.502 9.396.502s7.506 0 9.396-.502a3.007 3.007 0 0 0 2.088-2.088 31.247 31.247 0 0 0 .5-5.783 31.247 31.247 0 0 0-.5-5.805zM9.609 15.601V8.408l6.264 3.602z"/></svg>',
                ];
                $socialColors = ['telegram'=>'#2CA5E0','facebook'=>'#1877F2','tiktok'=>'#000','youtube'=>'#FF0000'];
                foreach ($activeSocials as $platform => $soc):
                ?>
                <a href="<?= e($soc['url']) ?>" target="_blank" rel="noopener noreferrer"
                   class="contact-social-btn"
                   style="--social-color:<?= $socialColors[$platform] ?? '#333' ?>">
                    <?= $socialIcons[$platform] ?? '' ?>
                    <?= e(ucfirst($platform)) ?>
                </a>
                <?php endforeach; ?>
                </div>
            </div>
        </div>
        <?php endif; ?>

    </div>

    <!-- CTA panel -->
    <div class="contact-cta">
        <div class="contact-cta__inner">
            <p class="contact-cta__label"><?= t('contact.ready') ?></p>
            <?php /* HTML-bearing key — do not wrap in e(); see t() docblock */ ?>
            <h2 class="contact-cta__title"><?= t('contact.send_email') ?></h2>
            <p class="contact-cta__body"><?= t('contact.cta_body') ?></p>
            <a class="btn btn--white contact-cta__btn" href="mailto:<?= e($contactEmail) ?>"><?= t('contact.email_us_now') ?></a>

            <div class="contact-cta__divider"></div>

            <p class="contact-cta__also"><?= t('contact.cta_also') ?></p>
            <a class="contact-cta__ghost" href="<?= e(url('pages/donate.php')) ?>"><?= t('contact.make_donation') ?></a>
        </div>
    </div>

</section>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
