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

            <div class="donate-bank-cards">
            <?php foreach ($donationBanks as $i => $bank): ?>
            <div class="donate-bank-card">
                <!-- Bank header -->
                <div class="donate-bank-card__head">
                    <div class="donate-bank-card__icon">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="7" width="20" height="14" rx="2"/><path d="M16 7V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v2"/><line x1="12" y1="12" x2="12" y2="16"/><line x1="10" y1="14" x2="14" y2="14"/></svg>
                    </div>
                    <div>
                        <div class="donate-bank-card__bank-name"><?= e($bank['bank_name'] ?? '') ?></div>
                        <div class="donate-bank-card__label"><?= t('donations.account_name_label') ?>: <strong><?= e($bank['account_name'] ?? '') ?></strong></div>
                    </div>
                </div>

                <!-- Fields -->
                <div class="donate-bank-card__fields">

                    <div class="donate-bank-card__field">
                        <span class="donate-bank-card__field-label"><?= t('donations.account_number_label') ?></span>
                        <div class="donate-bank-card__field-value-wrap">
                            <span class="donate-bank-card__field-value donate-bank-card__field-value--mono"
                                  id="accnum-<?= $i ?>"><?= e($bank['account_number'] ?? '') ?></span>
                            <button type="button"
                                    class="copy-btn"
                                    data-copy="<?= e($bank['account_number'] ?? '') ?>"
                                    data-target="accnum-<?= $i ?>"
                                    aria-label="Copy account number">
                                <svg class="copy-btn__icon" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="9" y="9" width="13" height="13" rx="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg>
                                <svg class="copy-btn__check" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
                            </button>
                        </div>
                    </div>

                    <?php if (!empty($bank['reference'])): ?>
                    <div class="donate-bank-card__field">
                        <span class="donate-bank-card__field-label"><?= t('donations.reference_label') ?></span>
                        <div class="donate-bank-card__field-value-wrap">
                            <span class="donate-bank-card__field-value"
                                  id="ref-<?= $i ?>"><?= e($bank['reference'] ?? '') ?></span>
                            <button type="button"
                                    class="copy-btn"
                                    data-copy="<?= e($bank['reference'] ?? '') ?>"
                                    data-target="ref-<?= $i ?>"
                                    aria-label="Copy reference">
                                <svg class="copy-btn__icon" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="9" y="9" width="13" height="13" rx="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg>
                                <svg class="copy-btn__check" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
                            </button>
                        </div>
                    </div>
                    <?php endif; ?>

                </div>
            </div>
            <?php endforeach; ?>
            </div>

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

<script>
document.querySelectorAll('.copy-btn').forEach(function(btn) {
    btn.addEventListener('click', function() {
        var text = btn.dataset.copy;
        if (!text) return;
        navigator.clipboard.writeText(text).then(function() {
            btn.classList.add('copy-btn--copied');
            setTimeout(function() { btn.classList.remove('copy-btn--copied'); }, 2000);
        }).catch(function() {
            // Fallback for older browsers
            var el = document.createElement('textarea');
            el.value = text;
            el.style.position = 'fixed';
            el.style.opacity = '0';
            document.body.appendChild(el);
            el.select();
            document.execCommand('copy');
            document.body.removeChild(el);
            btn.classList.add('copy-btn--copied');
            setTimeout(function() { btn.classList.remove('copy-btn--copied'); }, 2000);
        });
    });
});
</script>

<?php require __DIR__ . '/../includes/footer.php'; ?>
