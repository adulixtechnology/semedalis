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

$contactEmail   = contact_setting('contact_email',   'info@simedalis.org');
$contactPhones  = contact_setting_json('contact_phones', ['+000 000 000 0000']);
$contactAddress = contact_setting('contact_address', 'Your Address Here');

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
                <a href="mailto:<?= e($contactEmail) ?>" class="contact-card__value"><?= e($contactEmail) ?></a>
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
                <span class="contact-card__value"><?= e($contactAddress) ?></span>
            </div>
        </div>

    </div>

    <!-- CTA panel -->
    <div class="contact-cta">
        <div class="contact-cta__inner">
            <p class="contact-cta__label"><?= t('contact.ready') ?></p>
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
