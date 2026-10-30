<?php
/**
 * Donations info page — how to donate + link to the submission form.
 * Donations are NEVER listed publicly (the list is admin-only).
 */
require __DIR__ . '/../config/config.php';

function donations_setting(string $key, string $default = ''): string {
    try {
        $stmt = db()->prepare("SELECT `value` FROM settings WHERE `key` = ?");
        $stmt->execute([$key]);
        $row = $stmt->fetch();
        return ($row && $row['value'] !== '') ? (string) $row['value'] : $default;
    } catch (PDOException $e) { return $default; }
}
function donations_setting_json(string $key, array $default = []): array {
    $raw = donations_setting($key, '');
    if ($raw === '') return $default;
    $d = json_decode($raw, true);
    return is_array($d) ? $d : $default;
}

$donationBanks = donations_setting_json('donation_banks', [[
    'account_name'   => 'Semidalis Gospel Team',
    'bank_name'      => 'Your Bank Name Here',
    'account_number' => '0000 0000 0000 0000',
    'reference'      => 'Donation + Your Name',
]]);

$pageTitle = t('nav.donations');
require __DIR__ . '/../includes/header.php';
?>

<div class="container">

    <!-- Steps -->
    <section class="section donate-steps-section">
        <div class="donate-steps-head">
            <h2><?= t('donations.how_to_donate') ?></h2>
            <p><?= t('donations.three_steps') ?></p>
        </div>
        <div class="donate-steps">

            <div class="donate-step">
                <div class="donate-step__num">1</div>
                <div class="donate-step__body">
                    <h3><?= t('donations.transfer_gift') ?></h3>
                    <p><?= t('donations.transfer_body') ?></p>
                </div>
            </div>

            <div class="donate-step__connector"></div>

            <div class="donate-step">
                <div class="donate-step__num">2</div>
                <div class="donate-step__body">
                    <h3><?= t('donations.capture_screenshot') ?></h3>
                    <p><?= t('donations.capture_body') ?></p>
                </div>
            </div>

            <div class="donate-step__connector"></div>

            <div class="donate-step">
                <div class="donate-step__num">3</div>
                <div class="donate-step__body">
                    <h3><?= t('donations.submit_form') ?></h3>
                    <p><?= t('donations.submit_body') ?></p>
                </div>
            </div>

        </div>
    </section>

    <!-- Bank Details + CTA side by side -->
    <section class="section donate-split">

        <div class="donate-bank">
            <div class="donate-bank__header">
                <h2><?= t('donations.bank_details') ?></h2>
                <p><?= t('donations.transfer_to') ?></p>
            </div>
            <?php foreach ($donationBanks as $i => $bank): ?>
            <?php if ($i > 0): ?><div class="donate-bank__divider"></div><?php endif; ?>
            <?php if (count($donationBanks) > 1): ?>
            <p class="donate-bank__sub-label"><?= e($bank['bank_name'] ?? '') ?></p>
            <?php endif; ?>
            <dl class="donate-bank__dl">
                <div class="donate-bank__row">
                    <dt><?= t('donations.account_name_label') ?></dt>
                    <dd><?= e($bank['account_name'] ?? '') ?></dd>
                </div>
                <div class="donate-bank__row">
                    <dt><?= t('donations.bank_label') ?></dt>
                    <dd><?= e($bank['bank_name'] ?? '') ?></dd>
                </div>
                <div class="donate-bank__row">
                    <dt><?= t('donations.account_number_label') ?></dt>
                    <dd><?= e($bank['account_number'] ?? '') ?></dd>
                </div>
                <div class="donate-bank__row">
                    <dt><?= t('donations.reference_label') ?></dt>
                    <dd><?= e($bank['reference'] ?? '') ?></dd>
                </div>
            </dl>
            <?php endforeach; ?>
            <p class="donate-bank__note"><?= t('donations.bank_note') ?></p>
        </div>

        <div class="donate-cta-card">
            <div class="donate-cta-card__inner">
                <p class="donate-cta-card__label"><?= t('donations.ready') ?></p>
                <?php /* HTML-bearing key — do not wrap in e(); see t() docblock */ ?>
                <h2 class="donate-cta-card__title"><?= t('donations.submit_your') ?></h2>
                <p class="donate-cta-card__body"><?= t('donations.submit_desc') ?></p>
                <a class="btn btn--white donate-cta-card__btn" href="<?= e(url('pages/donate.php')) ?>"><?= t('donations.submit_donation') ?></a>
            </div>
        </div>

    </section>

    <!-- Scripture pull-quote -->
    <section class="section">
        <blockquote class="donate-quote">
            <p><?= t('donations.verse_body') ?></p>
            <cite><?= t('donations.verse_cite') ?></cite>
        </blockquote>
    </section>

</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
