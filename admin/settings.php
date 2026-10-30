<?php
/**
 * Admin — Site Settings.
 * Multiple phones stored as JSON in contact_phones.
 * Multiple bank accounts stored as JSON in donation_banks.
 */
require __DIR__ . '/../config/config.php';
$adminTitle = 'Settings';

// --- Ensure the settings table exists --------------------------------------
try {
    db()->exec("CREATE TABLE IF NOT EXISTS settings (
        `key`   VARCHAR(100) NOT NULL PRIMARY KEY,
        `value` TEXT         NOT NULL DEFAULT ''
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
} catch (PDOException $e) {
    error_log('Settings table init: ' . $e->getMessage());
}

// --- Helpers ---------------------------------------------------------------
function get_setting(string $key, string $default = ''): string {
    try {
        $stmt = db()->prepare("SELECT `value` FROM settings WHERE `key` = ?");
        $stmt->execute([$key]);
        $row = $stmt->fetch();
        return ($row !== false && $row['value'] !== '') ? (string) $row['value'] : $default;
    } catch (PDOException $e) { return $default; }
}
function save_setting(string $key, string $value): void {
    $stmt = db()->prepare(
        "INSERT INTO settings (`key`, `value`) VALUES (?, ?)
         ON DUPLICATE KEY UPDATE `value` = VALUES(`value`)"
    );
    $stmt->execute([$key, $value]);
}

/** Decode a JSON list, always returns array. */
function setting_json(string $key, array $default = []): array {
    $raw = get_setting($key, '');
    if ($raw === '') return $default;
    $decoded = json_decode($raw, true);
    return is_array($decoded) ? $decoded : $default;
}

// --- Data migration: copy base key into _en key if _en is empty ------------
$langVariantKeys = [
    'about_intro', 'about_mission', 'about_vision',
    'about_work_title', 'about_work_desc', 'about_work_items',
    'contact_address',
];
foreach ($langVariantKeys as $baseKey) {
    $enKey  = $baseKey . '_en';
    $enVal  = get_setting($enKey, '');
    if ($enVal === '') {
        $baseVal = get_setting($baseKey, '');
        if ($baseVal !== '') {
            save_setting($enKey, $baseVal);
        }
    }
}

$errors = [];

// --- Active slang ----------------------------------------------------------
$activeSlang = $_GET['slang'] ?? 'en';
if (!in_array($activeSlang, ['en', 'am'], true)) $activeSlang = 'en';

// --- POST handler ----------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $tab = $_POST['tab'] ?? 'contact';
    $postSlang = $_POST['slang'] ?? 'en';
    if (!in_array($postSlang, ['en', 'am'], true)) $postSlang = 'en';
    $activeSlang = $postSlang;

    // Run migration again before saving (in case this is the very first POST)
    foreach ($langVariantKeys as $baseKey) {
        $enKey = $baseKey . '_en';
        $enVal = get_setting($enKey, '');
        if ($enVal === '') {
            $baseVal = get_setting($baseKey, '');
            if ($baseVal !== '') {
                save_setting($enKey, $baseVal);
            }
        }
    }

    // ---- Contact -----------------------------------------------------------
    if ($tab === 'contact') {
        $email   = trim($_POST['contact_email']   ?? '');
        $address = trim($_POST['contact_address'] ?? '');

        // Multiple phones — filter out blank entries
        $rawPhones = $_POST['contact_phones'] ?? [];
        $phones = [];
        foreach ((array) $rawPhones as $p) {
            $p = trim($p);
            if ($p !== '') $phones[] = $p;
        }

        if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors['contact_email'] = 'Please enter a valid email address.';
        }

        if (empty($errors)) {
            try {
                save_setting('contact_email',  $email);
                save_setting('contact_phones', json_encode(array_values($phones)));
                save_setting('contact_address_' . $postSlang, $address);
                flash('settings_success', 'Contact info saved.');
                header('Location: ' . url('admin/settings.php') . '?tab=contact&slang=' . $postSlang);
                exit;
            } catch (PDOException $e) {
                error_log('Settings save contact: ' . $e->getMessage());
                $errors['form'] = 'Could not save settings. Please try again.';
            }
        }

    // ---- Donation ----------------------------------------------------------
    } elseif ($tab === 'donation') {
        // Multiple bank accounts
        $names    = $_POST['bank_account_name']   ?? [];
        $bankNms  = $_POST['bank_name']           ?? [];
        $numbers  = $_POST['bank_account_number'] ?? [];
        $refs     = $_POST['bank_reference']      ?? [];

        $banks = [];
        $count = max(count($names), count($bankNms), count($numbers), count($refs));
        for ($i = 0; $i < $count; $i++) {
            $name   = trim($names[$i]   ?? '');
            $bname  = trim($bankNms[$i] ?? '');
            $number = trim($numbers[$i] ?? '');
            $ref    = trim($refs[$i]    ?? '');
            // Only save rows that have at least a bank name or account number
            if ($bname !== '' || $number !== '') {
                $banks[] = [
                    'account_name'   => $name,
                    'bank_name'      => $bname,
                    'account_number' => $number,
                    'reference'      => $ref,
                ];
            }
        }

        if (empty($errors)) {
            try {
                save_setting('donation_banks', json_encode(array_values($banks)));
                flash('settings_success', 'Bank details saved.');
                header('Location: ' . url('admin/settings.php') . '?tab=donation&slang=en');
                exit;
            } catch (PDOException $e) {
                error_log('Settings save donation: ' . $e->getMessage());
                $errors['form'] = 'Could not save settings. Please try again.';
            }
        }

    // ---- About -------------------------------------------------------------
    } elseif ($tab === 'about') {
        $about_intro   = trim($_POST['about_intro']   ?? '');
        $about_mission = trim($_POST['about_mission'] ?? '');
        $about_vision  = trim($_POST['about_vision']  ?? '');
        $about_work_title = trim($_POST['about_work_title'] ?? '');
        $about_work_desc  = trim($_POST['about_work_desc']  ?? '');

        // Multiple work items
        $workTitles = $_POST['work_item_title'] ?? [];
        $workDescs  = $_POST['work_item_desc']  ?? [];

        $workItems = [];
        $count = max(count($workTitles), count($workDescs));
        for ($i = 0; $i < $count; $i++) {
            $title = trim($workTitles[$i] ?? '');
            $desc  = trim($workDescs[$i]  ?? '');
            if ($title !== '' || $desc !== '') {
                $workItems[] = [
                    'title' => $title,
                    'description' => $desc,
                ];
            }
        }

        if (empty($errors)) {
            try {
                save_setting('about_intro_'       . $postSlang, $about_intro);
                save_setting('about_mission_'     . $postSlang, $about_mission);
                save_setting('about_vision_'      . $postSlang, $about_vision);
                save_setting('about_work_title_'  . $postSlang, $about_work_title);
                save_setting('about_work_desc_'   . $postSlang, $about_work_desc);
                save_setting('about_work_items_'  . $postSlang, json_encode(array_values($workItems)));
                flash('settings_success', 'About info saved.');
                header('Location: ' . url('admin/settings.php') . '?tab=about&slang=' . $postSlang);
                exit;
            } catch (PDOException $e) {
                error_log('Settings save about: ' . $e->getMessage());
                $errors['form'] = 'Could not save settings. Please try again.';
            }
        }
    }
}

$activeTab = $_GET['tab'] ?? 'contact';
if (!in_array($activeTab, ['contact', 'donation', 'about'], true)) {
    $activeTab = 'contact';
}

// --- Load current values (language-aware) ----------------------------------
$defaultPhones = ['+000 000 000 0000'];
$defaultBanks  = [[
    'account_name'   => 'Semidalis Gospel Team',
    'bank_name'      => 'Your Bank Name Here',
    'account_number' => '0000 0000 0000 0000',
    'reference'      => 'Donation + Your Name',
]];
$defaultWorkItems = [
    ['title' => 'Gospel Music and Worship', 'description' => 'We organize gospel music events and worship gatherings that bring the community together in faith and praise.'],
    ['title' => 'Community Outreach', 'description' => 'Our charity programs reach families and individuals facing hardship, providing practical support and encouragement.'],
    ['title' => 'Support for Families in Need', 'description' => 'We stand alongside orphans, widows, and vulnerable families — walking with them through their difficulties.'],
    ['title' => 'Fellowship and Inspiration', 'description' => 'We host gatherings that build community, nurture faith, and inspire people to live out the gospel in everyday life.'],
];

// Helper: load language-suffixed key, fall back to base key
function get_setting_lang(string $key, string $lang, string $default = ''): string {
    $val = get_setting($key . '_' . $lang, '');
    if ($val !== '') return $val;
    return get_setting($key, $default);
}
function setting_json_lang(string $key, string $lang, array $default = []): array {
    $raw = get_setting($key . '_' . $lang, '');
    if ($raw === '') $raw = get_setting($key, '');
    if ($raw === '') return $default;
    $decoded = json_decode($raw, true);
    return is_array($decoded) ? $decoded : $default;
}

$cur = [
    'contact_email'   => get_setting('contact_email',   'info@simedalis.org'),
    'contact_phones'  => setting_json('contact_phones',  $defaultPhones),
    'contact_address' => get_setting_lang('contact_address', $activeSlang, 'Your Address Here'),
    'donation_banks'  => setting_json('donation_banks',  $defaultBanks),
    'about_intro'      => get_setting_lang('about_intro',      $activeSlang, 'Semidalis is a gospel and charity team dedicated to proclaiming a message of hope and serving our community through practical acts of love.'),
    'about_mission'    => get_setting_lang('about_mission',    $activeSlang, "To proclaim the gospel and demonstrate God's love through practical acts of charity, music, and community service."),
    'about_vision'     => get_setting_lang('about_vision',     $activeSlang, 'A community transformed by hope, where everyone experiences the love and grace we are called to share.'),
    'about_work_title' => get_setting_lang('about_work_title', $activeSlang, 'What We Do'),
    'about_work_desc'  => get_setting_lang('about_work_desc',  $activeSlang, 'Our work spans worship, outreach, and practical support for families in need.'),
    'about_work_items' => setting_json_lang('about_work_items', $activeSlang, $defaultWorkItems),
];

// Ensure arrays are not empty for form rendering
if (empty($cur['contact_phones'])) $cur['contact_phones'] = [''];
if (empty($cur['donation_banks']))  $cur['donation_banks']  = [['account_name'=>'','bank_name'=>'','account_number'=>'','reference'=>'']];
if (empty($cur['about_work_items'])) $cur['about_work_items'] = [['title'=>'','description'=>'']];

require __DIR__ . '/../includes/admin_header.php';
$flashSuccess = flash('settings_success');
?>
<style>
.settings-lang-tabs {
    display: flex;
    gap: 6px;
    margin-bottom: 18px;
}
.settings-lang-tab {
    display: inline-block;
    padding: 4px 14px;
    border-radius: 20px;
    font-size: .8rem;
    font-weight: 600;
    border: 1.5px solid var(--border, #ddd);
    color: var(--text-muted, #666);
    text-decoration: none;
    transition: background .15s, color .15s;
}
.settings-lang-tab:hover {
    background: var(--primary-light, #eef2ff);
    color: var(--primary, #4f46e5);
}
.settings-lang-tab--active {
    background: var(--primary, #4f46e5);
    color: #fff;
    border-color: var(--primary, #4f46e5);
}
</style>
<div class="admin-content">
    <div class="admin-page-head">
        <h1><?= e(t('admin.settings.title')) ?></h1>
        <span class="text-muted">Manage public-facing info displayed on the About, Donations, and Contact pages.</span>
    </div>

    <?php if ($flashSuccess): ?>
    <div class="alert alert--success"><?= e($flashSuccess) ?></div>
    <?php endif; ?>
    <?php if (!empty($errors['form'])): ?>
    <div class="alert alert--danger"><?= e($errors['form']) ?></div>
    <?php endif; ?>

    <!-- Tabs -->
    <div class="settings-tabs">
        <a href="<?= e(url('admin/settings.php')) ?>?tab=contact&slang=<?= e($activeSlang) ?>"
           class="settings-tab<?= $activeTab === 'contact'  ? ' settings-tab--active' : '' ?>"><?= e(t('admin.settings.contact_tab')) ?></a>
        <a href="<?= e(url('admin/settings.php')) ?>?tab=donation&slang=<?= e($activeSlang) ?>"
           class="settings-tab<?= $activeTab === 'donation' ? ' settings-tab--active' : '' ?>"><?= e(t('admin.settings.donation_tab')) ?></a>
        <a href="<?= e(url('admin/settings.php')) ?>?tab=about&slang=<?= e($activeSlang) ?>"
           class="settings-tab<?= $activeTab === 'about'    ? ' settings-tab--active' : '' ?>"><?= e(t('admin.settings.about_tab')) ?></a>
    </div>

    <div class="settings-layout">

    <?php if ($activeTab === 'contact'): ?>
    <!-- ================================================================
         CONTACT TAB
         ================================================================ -->
        <div class="admin-form settings-form-col">
            <div class="settings-form-col__head">
                <h2>Edit Contact Info</h2>
                <p>Appears on the <a href="<?= e(url('pages/contact.php')) ?>" target="_blank">Contact page</a>.</p>
            </div>

            <!-- Language sub-tabs -->
            <div class="settings-lang-tabs">
                <a href="?tab=contact&slang=en"
                   class="settings-lang-tab<?= $activeSlang === 'en' ? ' settings-lang-tab--active' : '' ?>">English</a>
                <a href="?tab=contact&slang=am"
                   class="settings-lang-tab<?= $activeSlang === 'am' ? ' settings-lang-tab--active' : '' ?>">አማርኛ</a>
            </div>

            <form method="post" action="<?= e(url('admin/settings.php')) ?>?tab=contact">
                <?= csrf_field() ?>
                <input type="hidden" name="tab" value="contact">
                <input type="hidden" name="slang" value="<?= e($activeSlang) ?>">

                <div class="form-group">
                    <label for="contact_email"><?= e(t('admin.settings.contact_email')) ?></label>
                    <input type="email" id="contact_email" name="contact_email"
                           value="<?= e(old('contact_email', $cur['contact_email'])) ?>"
                           maxlength="150" placeholder="info@simedalis.org">
                    <?php if (!empty($errors['contact_email'])): ?>
                    <div class="field-error"><?= e($errors['contact_email']) ?></div>
                    <?php endif; ?>
                </div>

                <!-- Multiple phones -->
                <div class="form-group">
                    <label><?= e(t('admin.settings.contact_phones')) ?></label>
                    <div class="multi-list" id="phones-list">
                        <?php foreach ($cur['contact_phones'] as $i => $ph): ?>
                        <div class="multi-list__row">
                            <input type="text" name="contact_phones[]"
                                   value="<?= e($ph) ?>"
                                   placeholder="+000 000 000 0000" maxlength="50">
                            <button type="button" class="multi-list__remove" aria-label="Remove">&times;</button>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <button type="button" class="btn btn--sm multi-list__add" data-target="phones-list"
                            data-template='<div class="multi-list__row"><input type="text" name="contact_phones[]" placeholder="+000 000 000 0000" maxlength="50"><button type="button" class="multi-list__remove" aria-label="Remove">&times;</button></div>'>
                        <?= e(t('admin.settings.add_phone')) ?>
                    </button>
                </div>

                <div class="form-group">
                    <label for="contact_address"><?= e(t('admin.settings.contact_address')) ?>
                        (<?= $activeSlang === 'am' ? 'አማርኛ' : 'English' ?>)</label>
                    <input type="text" id="contact_address" name="contact_address"
                           value="<?= e(old('contact_address', $cur['contact_address'])) ?>"
                           maxlength="255" placeholder="Street, City, Province">
                </div>

                <div class="form-actions mt-2">
                    <button type="submit" class="btn btn--primary"><?= e(t('admin.settings.save_btn')) ?></button>
                </div>
            </form>
        </div>

        <!-- Preview -->
        <div class="settings-preview">
            <div class="settings-preview__head">
                <span class="settings-preview__label">Live Preview</span>
                <a href="<?= e(url('pages/contact.php')) ?>" target="_blank" class="settings-preview__link">View page</a>
            </div>
            <div class="settings-preview__body">
                <div class="sp-contact">
                    <div class="sp-contact__row">
                        <span class="sp-contact__icon">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
                        </span>
                        <div>
                            <div class="sp-contact__key">Email</div>
                            <div class="sp-contact__val"><?= e($cur['contact_email']) ?></div>
                        </div>
                    </div>
                    <div class="sp-contact__row">
                        <span class="sp-contact__icon">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07A19.5 19.5 0 0 1 4.69 12 19.79 19.79 0 0 1 1.63 3.3 2 2 0 0 1 3.6 1h3a2 2 0 0 1 2 1.72c.127.96.361 1.903.7 2.81a2 2 0 0 1-.45 2.11L7.91 8.6a16 16 0 0 0 6 6l.96-.96a2 2 0 0 1 2.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0 1 21.54 16z"/></svg>
                        </span>
                        <div>
                            <div class="sp-contact__key">Phone<?= count($cur['contact_phones']) > 1 ? 's' : '' ?></div>
                            <?php foreach ($cur['contact_phones'] as $ph): ?>
                            <div class="sp-contact__val"><?= e($ph) ?></div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <div class="sp-contact__row">
                        <span class="sp-contact__icon">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 10c0 7-9 13-9 13S3 17 3 10a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
                        </span>
                        <div>
                            <div class="sp-contact__key">Address</div>
                            <div class="sp-contact__val"><?= e($cur['contact_address']) ?></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    <?php elseif ($activeTab === 'donation'): ?>
    <!-- ================================================================
         DONATION TAB
         ================================================================ -->
        <div class="admin-form settings-form-col">
            <div class="settings-form-col__head">
                <h2>Edit Bank Details</h2>
                <p>Appears on the <a href="<?= e(url('pages/donations.php')) ?>" target="_blank">Donations page</a>. Add one entry per bank or payment method.</p>
            </div>
            <form method="post" action="<?= e(url('admin/settings.php')) ?>?tab=donation">
                <?= csrf_field() ?>
                <input type="hidden" name="tab" value="donation">
                <input type="hidden" name="slang" value="en">

                <div id="banks-list">
                    <?php foreach ($cur['donation_banks'] as $i => $bank): ?>
                    <div class="multi-bank" data-index="<?= $i ?>">
                        <div class="multi-bank__head">
                            <span class="multi-bank__num">Bank Account <?= $i + 1 ?></span>
                            <button type="button" class="multi-bank__remove btn btn--danger btn--sm">Remove</button>
                        </div>
                        <div class="multi-bank__grid">
                            <div class="form-group">
                                <label><?= e(t('admin.settings.account_name')) ?></label>
                                <input type="text" name="bank_account_name[]"
                                       value="<?= e($bank['account_name'] ?? '') ?>"
                                       maxlength="150" placeholder="Account holder name">
                            </div>
                            <div class="form-group">
                                <label><?= e(t('admin.settings.bank_name')) ?></label>
                                <input type="text" name="bank_name[]"
                                       value="<?= e($bank['bank_name'] ?? '') ?>"
                                       maxlength="150" placeholder="e.g. BDO, BPI, GCash">
                            </div>
                            <div class="form-group">
                                <label><?= e(t('admin.settings.account_number')) ?></label>
                                <input type="text" name="bank_account_number[]"
                                       value="<?= e($bank['account_number'] ?? '') ?>"
                                       maxlength="50" placeholder="0000 0000 0000 0000">
                            </div>
                            <div class="form-group">
                                <label><?= e(t('admin.settings.reference')) ?></label>
                                <input type="text" name="bank_reference[]"
                                       value="<?= e($bank['reference'] ?? '') ?>"
                                       maxlength="255" placeholder="e.g. Donation + Your Name">
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>

                <button type="button" class="btn btn--sm" id="add-bank-btn" style="margin-bottom:20px">
                    <?= e(t('admin.settings.add_bank')) ?>
                </button>

                <div class="form-actions mt-2">
                    <button type="submit" class="btn btn--primary"><?= e(t('admin.settings.save_btn')) ?></button>
                </div>
            </form>
        </div>

        <!-- Preview -->
        <div class="settings-preview">
            <div class="settings-preview__head">
                <span class="settings-preview__label">Live Preview</span>
                <a href="<?= e(url('pages/donations.php')) ?>" target="_blank" class="settings-preview__link">View page</a>
            </div>
            <div class="settings-preview__body">
                <?php foreach ($cur['donation_banks'] as $i => $bank): ?>
                <?php if ($i > 0): ?><div style="height:12px"></div><?php endif; ?>
                <p class="sp-section-label"><?= count($cur['donation_banks']) > 1 ? 'Account ' . ($i + 1) . ' — ' . e($bank['bank_name'] ?? '') : 'Bank Details' ?></p>
                <div class="sp-bank">
                    <div class="sp-bank__row"><span class="sp-bank__key">Account Name</span><span class="sp-bank__val"><?= e($bank['account_name'] ?? '') ?></span></div>
                    <div class="sp-bank__row"><span class="sp-bank__key">Bank</span><span class="sp-bank__val"><?= e($bank['bank_name'] ?? '') ?></span></div>
                    <div class="sp-bank__row"><span class="sp-bank__key">Account No.</span><span class="sp-bank__val"><?= e($bank['account_number'] ?? '') ?></span></div>
                    <div class="sp-bank__row"><span class="sp-bank__key">Reference</span><span class="sp-bank__val"><?= e($bank['reference'] ?? '') ?></span></div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

    <?php elseif ($activeTab === 'about'): ?>
    <!-- ================================================================
         ABOUT TAB
         ================================================================ -->
        <div class="admin-form settings-form-col">
            <div class="settings-form-col__head">
                <h2>Edit About Info</h2>
                <p>Appears on the <a href="<?= e(url('pages/about.php')) ?>" target="_blank">About page</a>.</p>
            </div>

            <!-- Language sub-tabs -->
            <div class="settings-lang-tabs">
                <a href="?tab=about&slang=en"
                   class="settings-lang-tab<?= $activeSlang === 'en' ? ' settings-lang-tab--active' : '' ?>">English</a>
                <a href="?tab=about&slang=am"
                   class="settings-lang-tab<?= $activeSlang === 'am' ? ' settings-lang-tab--active' : '' ?>">አማርኛ</a>
            </div>

            <form method="post" action="<?= e(url('admin/settings.php')) ?>?tab=about">
                <?= csrf_field() ?>
                <input type="hidden" name="tab" value="about">
                <input type="hidden" name="slang" value="<?= e($activeSlang) ?>">

                <div class="form-group">
                    <label for="about_intro"><?= e(t('admin.settings.about_intro')) ?>
                        (<?= $activeSlang === 'am' ? 'አማርኛ' : 'English' ?>)</label>
                    <textarea id="about_intro" name="about_intro" rows="4"
                              placeholder="Short intro describing who Semidalis is..."><?= e(old('about_intro', $cur['about_intro'])) ?></textarea>
                </div>
                <div class="form-group">
                    <label for="about_mission"><?= e(t('admin.settings.mission')) ?>
                        (<?= $activeSlang === 'am' ? 'አማርኛ' : 'English' ?>)</label>
                    <textarea id="about_mission" name="about_mission" rows="3"
                              placeholder="Our mission is..."><?= e(old('about_mission', $cur['about_mission'])) ?></textarea>
                </div>
                <div class="form-group">
                    <label for="about_vision"><?= e(t('admin.settings.vision')) ?>
                        (<?= $activeSlang === 'am' ? 'አማርኛ' : 'English' ?>)</label>
                    <textarea id="about_vision" name="about_vision" rows="3"
                              placeholder="Our vision is..."><?= e(old('about_vision', $cur['about_vision'])) ?></textarea>
                </div>

                <!-- What We Do section -->
                <hr style="margin: 24px 0; border: none; border-top: 1px solid var(--border);">

                <div class="form-group">
                    <label for="about_work_title"><?= e(t('admin.settings.work_title')) ?>
                        (<?= $activeSlang === 'am' ? 'አማርኛ' : 'English' ?>)</label>
                    <input type="text" id="about_work_title" name="about_work_title"
                           value="<?= e(old('about_work_title', $cur['about_work_title'])) ?>"
                           maxlength="100" placeholder="What We Do">
                </div>

                <div class="form-group">
                    <label for="about_work_desc"><?= e(t('admin.settings.work_desc')) ?>
                        (<?= $activeSlang === 'am' ? 'አማርኛ' : 'English' ?>)</label>
                    <input type="text" id="about_work_desc" name="about_work_desc"
                           value="<?= e(old('about_work_desc', $cur['about_work_desc'])) ?>"
                           maxlength="255" placeholder="Our work spans worship, outreach, and practical support for families in need.">
                </div>

                <!-- Multiple work items -->
                <div class="form-group">
                    <label><?= e(t('admin.settings.work_items')) ?>
                        (<?= $activeSlang === 'am' ? 'አማርኛ' : 'English' ?>)</label>
                    <div id="work-items-list">
                        <?php foreach ($cur['about_work_items'] as $i => $item): ?>
                        <div class="multi-work-item" data-index="<?= $i ?>">
                            <div class="multi-work-item__head">
                                <span class="multi-work-item__num">Item <?= $i + 1 ?></span>
                                <button type="button" class="multi-work-item__remove btn btn--danger btn--sm">Remove</button>
                            </div>
                            <div class="multi-work-item__grid">
                                <div class="form-group">
                                    <label>Title</label>
                                    <input type="text" name="work_item_title[]"
                                           value="<?= e($item['title'] ?? '') ?>"
                                           maxlength="100" placeholder="e.g. Gospel Music and Worship">
                                </div>
                                <div class="form-group">
                                    <label>Description</label>
                                    <textarea name="work_item_desc[]" rows="2"
                                              placeholder="Describe this work..."><?= e($item['description'] ?? '') ?></textarea>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>

                    <button type="button" class="btn btn--sm" id="add-work-item-btn" style="margin-top:10px">
                        <?= e(t('admin.settings.add_item')) ?>
                    </button>
                </div>

                <div class="form-actions mt-2">
                    <button type="submit" class="btn btn--primary"><?= e(t('admin.settings.save_btn')) ?></button>
                </div>
            </form>
        </div>

        <!-- Preview -->
        <div class="settings-preview">
            <div class="settings-preview__head">
                <span class="settings-preview__label">Live Preview</span>
                <a href="<?= e(url('pages/about.php')) ?>" target="_blank" class="settings-preview__link">View page</a>
            </div>
            <div class="settings-preview__body">
                <p class="sp-section-label">Intro</p>
                <p class="sp-text"><?= e($cur['about_intro']) ?></p>
                <p class="sp-section-label">Our Mission</p>
                <p class="sp-text"><?= e($cur['about_mission']) ?></p>
                <p class="sp-section-label">Our Vision</p>
                <p class="sp-text"><?= e($cur['about_vision']) ?></p>
                
                <hr style="margin: 16px 0; border: none; border-top: 1px solid rgba(0,0,0,.1);">

                <p class="sp-section-label"><?= e($cur['about_work_title']) ?></p>
                <p class="sp-text"><?= e($cur['about_work_desc']) ?></p>
                <?php foreach ($cur['about_work_items'] as $item): ?>
                <div style="margin-top: 12px; padding-top: 12px; border-top: 1px dotted rgba(0,0,0,.15);">
                    <div style="font-weight: 700; font-size: .88rem; color: var(--primary); margin-bottom: 2px;"><?= e($item['title'] ?? '') ?></div>
                    <div style="font-size: .82rem; color: var(--text-muted); line-height: 1.5;"><?= e($item['description'] ?? '') ?></div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

    <?php endif; ?>
    </div><!-- .settings-layout -->
</div>

<script>
(function () {
    // ---- Generic "add row" for simple lists (phones) ----------------------
    document.querySelectorAll('.multi-list__add').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var list = document.getElementById(btn.dataset.target);
            var tmp  = document.createElement('div');
            tmp.innerHTML = btn.dataset.template;
            var row = tmp.firstChild;
            list.appendChild(row);
            bindRemove(row.querySelector('.multi-list__remove'));
            row.querySelector('input').focus();
        });
    });
    document.querySelectorAll('.multi-list__remove').forEach(function (btn) {
        bindRemove(btn);
    });
    function bindRemove(btn) {
        if (!btn) return;
        btn.addEventListener('click', function () {
            var list = btn.closest('.multi-list');
            if (list && list.querySelectorAll('.multi-list__row').length > 1) {
                btn.closest('.multi-list__row').remove();
            }
        });
    }

    // ---- Bank accounts ----------------------------------------------------
    var bankList  = document.getElementById('banks-list');
    var addBankBtn = document.getElementById('add-bank-btn');
    if (!bankList || !addBankBtn) return;

    function bankCount() { return bankList.querySelectorAll('.multi-bank').length; }
    function updateLabels() {
        bankList.querySelectorAll('.multi-bank__num').forEach(function (el, i) {
            el.textContent = 'Bank Account ' + (i + 1);
        });
    }
    function makeBankBlock() {
        var idx = bankCount();
        var div = document.createElement('div');
        div.className = 'multi-bank';
        div.dataset.index = idx;
        div.innerHTML =
            '<div class="multi-bank__head">' +
                '<span class="multi-bank__num">Bank Account ' + (idx + 1) + '</span>' +
                '<button type="button" class="multi-bank__remove btn btn--danger btn--sm">Remove</button>' +
            '</div>' +
            '<div class="multi-bank__grid">' +
                '<div class="form-group"><label>Account Name</label>' +
                    '<input type="text" name="bank_account_name[]" maxlength="150" placeholder="Account holder name"></div>' +
                '<div class="form-group"><label>Bank / Payment Method</label>' +
                    '<input type="text" name="bank_name[]" maxlength="150" placeholder="e.g. BDO, BPI, GCash"></div>' +
                '<div class="form-group"><label>Account / Mobile Number</label>' +
                    '<input type="text" name="bank_account_number[]" maxlength="50" placeholder="0000 0000 0000 0000"></div>' +
                '<div class="form-group"><label>Reference Instruction</label>' +
                    '<input type="text" name="bank_reference[]" maxlength="255" placeholder="e.g. Donation + Your Name"></div>' +
            '</div>';
        bindBankRemove(div.querySelector('.multi-bank__remove'));
        return div;
    }
    function bindBankRemove(btn) {
        btn.addEventListener('click', function () {
            if (bankCount() > 1) {
                btn.closest('.multi-bank').remove();
                updateLabels();
            }
        });
    }
    // Bind existing remove buttons
    bankList.querySelectorAll('.multi-bank__remove').forEach(bindBankRemove);

    addBankBtn.addEventListener('click', function () {
        var block = makeBankBlock();
        bankList.appendChild(block);
        block.querySelector('input').focus();
    });

    // ---- Work items -------------------------------------------------------
    var workList    = document.getElementById('work-items-list');
    var addWorkBtn  = document.getElementById('add-work-item-btn');
    if (!workList || !addWorkBtn) return;

    function workCount() { return workList.querySelectorAll('.multi-work-item').length; }
    function updateWorkLabels() {
        workList.querySelectorAll('.multi-work-item__num').forEach(function (el, i) {
            el.textContent = 'Item ' + (i + 1);
        });
    }
    function makeWorkBlock() {
        var idx = workCount();
        var div = document.createElement('div');
        div.className = 'multi-work-item';
        div.dataset.index = idx;
        div.innerHTML =
            '<div class="multi-work-item__head">' +
                '<span class="multi-work-item__num">Item ' + (idx + 1) + '</span>' +
                '<button type="button" class="multi-work-item__remove btn btn--danger btn--sm">Remove</button>' +
            '</div>' +
            '<div class="multi-work-item__grid">' +
                '<div class="form-group"><label>Title</label>' +
                    '<input type="text" name="work_item_title[]" maxlength="100" placeholder="e.g. Gospel Music and Worship"></div>' +
                '<div class="form-group"><label>Description</label>' +
                    '<textarea name="work_item_desc[]" rows="2" placeholder="Describe this work..."></textarea></div>' +
            '</div>';
        bindWorkRemove(div.querySelector('.multi-work-item__remove'));
        return div;
    }
    function bindWorkRemove(btn) {
        btn.addEventListener('click', function () {
            if (workCount() > 1) {
                btn.closest('.multi-work-item').remove();
                updateWorkLabels();
            }
        });
    }
    // Bind existing remove buttons
    workList.querySelectorAll('.multi-work-item__remove').forEach(bindWorkRemove);

    addWorkBtn.addEventListener('click', function () {
        var block = makeWorkBlock();
        workList.appendChild(block);
        block.querySelector('input').focus();
    });
}());
</script>
<?php require __DIR__ . '/../includes/footer.php'; ?>
