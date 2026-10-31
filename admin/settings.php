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

// --- Data migration: copy base key into _en key if _en is empty -----------
// Guard: only run the migration loop if at least one _en key is genuinely
// absent. Once all _en keys exist this check short-circuits immediately.
$langVariantKeys = [
    'about_intro', 'about_mission', 'about_vision',
    'about_secondary_note',
    'about_work_title', 'about_work_desc', 'about_work_items',
    'contact_addresses',
    'home_label', 'home_tagline', 'home_body',
];
$needsMigration = false;
foreach ($langVariantKeys as $baseKey) {
    if (get_setting($baseKey . '_en', '') === '' && get_setting($baseKey, '') !== '') {
        $needsMigration = true;
        break;
    }
}
if ($needsMigration) {
    foreach ($langVariantKeys as $baseKey) {
        $enKey = $baseKey . '_en';
        if (get_setting($enKey, '') === '') {
            $baseVal = get_setting($baseKey, '');
            if ($baseVal !== '') {
                save_setting($enKey, $baseVal);
            }
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
        // Multiple addresses
        $rawAddresses = $_POST['contact_addresses'] ?? [];
        $addresses = [];
        foreach ((array) $rawAddresses as $a) {
            $a = trim($a);
            if ($a !== '') $addresses[] = $a;
        }

        // Multiple emails
        $rawEmails = $_POST['contact_emails'] ?? [];
        $emails = [];
        foreach ((array) $rawEmails as $em) {
            $em = trim($em);
            if ($em !== '' && filter_var($em, FILTER_VALIDATE_EMAIL)) $emails[] = $em;
            elseif ($em !== '') $errors['contact_emails'] = 'One or more email addresses are invalid.';
        }

        // Multiple phones
        $rawPhones = $_POST['contact_phones'] ?? [];
        $phones = [];
        foreach ((array) $rawPhones as $p) {
            $p = trim($p);
            if ($p !== '') $phones[] = $p;
        }

        // Social media — each is url + enabled flag
        $socialPlatforms = ['telegram', 'facebook', 'tiktok', 'youtube'];
        $socials = [];
        foreach ($socialPlatforms as $platform) {
            $url     = trim($_POST['social_' . $platform . '_url'] ?? '');
            $enabled = isset($_POST['social_' . $platform . '_enabled']) ? 1 : 0;
            $socials[$platform] = ['url' => $url, 'enabled' => $enabled];
        }

        if (empty($errors)) {
            try {
                save_setting('contact_emails',  json_encode(array_values($emails)));
                save_setting('contact_phones',  json_encode(array_values($phones)));
                save_setting('contact_addresses_' . $postSlang, json_encode(array_values($addresses)));
                save_setting('contact_socials', json_encode($socials));
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
        $about_intro          = trim($_POST['about_intro']          ?? '');
        $about_secondary_note = trim($_POST['about_secondary_note'] ?? '');
        $about_mission        = trim($_POST['about_mission']        ?? '');
        $about_vision         = trim($_POST['about_vision']         ?? '');
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
                save_setting('about_intro_'          . $postSlang, $about_intro);
                save_setting('about_secondary_note_' . $postSlang, $about_secondary_note);
                save_setting('about_mission_'        . $postSlang, $about_mission);
                save_setting('about_vision_'         . $postSlang, $about_vision);
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

    // ---- Home --------------------------------------------------------------
    } elseif ($tab === 'home') {
        $home_label   = trim($_POST['home_label']   ?? '');
        $home_tagline = trim($_POST['home_tagline'] ?? '');
        $home_body    = trim($_POST['home_body']    ?? '');
        if (empty($errors)) {
            try {
                save_setting('home_label_'   . $postSlang, $home_label);
                save_setting('home_tagline_' . $postSlang, $home_tagline);
                save_setting('home_body_'    . $postSlang, $home_body);
                flash('settings_success', 'Home section saved.');
                header('Location: ' . url('admin/settings.php') . '?tab=home&slang=' . $postSlang);
                exit;
            } catch (PDOException $e) {
                error_log('Settings save home: ' . $e->getMessage());
                $errors['form'] = 'Could not save settings. Please try again.';
            }
        }
    }
}

$activeTab = $_GET['tab'] ?? 'contact';
if (!in_array($activeTab, ['contact', 'donation', 'about', 'home'], true)) {
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

$defaultSocials = [
    'telegram' => ['url' => '', 'enabled' => 0],
    'facebook' => ['url' => '', 'enabled' => 0],
    'tiktok'   => ['url' => '', 'enabled' => 0],
    'youtube'  => ['url' => '', 'enabled' => 0],
];
$savedSocials = setting_json('contact_socials', []);
$socialsMerged = array_merge($defaultSocials, $savedSocials);

$defaultEmails = ['info@simedalis.org'];

$cur = [
    'contact_emails'    => setting_json('contact_emails', $defaultEmails),
    'contact_email'     => get_setting('contact_email', 'info@simedalis.org'),
    'contact_phones'    => setting_json('contact_phones', $defaultPhones),
    'contact_addresses' => setting_json_lang('contact_addresses', $activeSlang, ['Your Address Here']),
    'contact_socials'   => $socialsMerged,
    'donation_banks'  => setting_json('donation_banks',  $defaultBanks),
    'about_intro'          => get_setting_lang('about_intro',          $activeSlang, 'Semidalis is a gospel and charity team dedicated to proclaiming a message of hope and serving our community through practical acts of love.'),
    'about_secondary_note' => get_setting_lang('about_secondary_note', $activeSlang, 'We are grateful for every volunteer, partner, and donor who makes this work possible. Together, we continue to be a source of hope and help.'),
    'about_mission'        => get_setting_lang('about_mission',        $activeSlang, "To proclaim the gospel and demonstrate God's love through practical acts of charity, music, and community service."),
    'about_vision'         => get_setting_lang('about_vision',         $activeSlang, 'A community transformed by hope, where everyone experiences the love and grace we are called to share.'),
    'about_work_title' => get_setting_lang('about_work_title', $activeSlang, 'What We Do'),
    'about_work_desc'  => get_setting_lang('about_work_desc',  $activeSlang, 'Our work spans worship, outreach, and practical support for families in need.'),
    'about_work_items' => setting_json_lang('about_work_items', $activeSlang, $defaultWorkItems),
    // Home section
    'home_label'   => get_setting_lang('home_label',   $activeSlang, 'Who We Are'),
    'home_tagline' => get_setting_lang('home_tagline', $activeSlang, 'Serving Community Through Faith & Love'),
    'home_body'    => get_setting_lang('home_body',    $activeSlang, 'Semidalis is a gospel and volunteer ministry dedicated to spreading hope and serving those in need. Rooted in James 1:27 — we believe every act of compassion makes a lasting difference.'),
];

// Ensure arrays are not empty for form rendering
if (empty($cur['contact_phones'])) $cur['contact_phones'] = [''];
if (empty($cur['contact_addresses'])) $cur['contact_addresses'] = [''];
if (empty($cur['donation_banks']))  $cur['donation_banks']  = [['account_name'=>'','bank_name'=>'','account_number'=>'','reference'=>'']];
if (empty($cur['about_work_items'])) $cur['about_work_items'] = [['title'=>'','description'=>'']];

require __DIR__ . '/../includes/admin_header.php';
$flashSuccess = flash('settings_success');
?>
<style>
.settings-lang-tabs { display:flex; gap:6px; margin-bottom:18px; }
.settings-lang-tab { display:inline-block; padding:4px 14px; border-radius:20px; font-size:.8rem; font-weight:600; border:1.5px solid var(--border); color:var(--text-muted); text-decoration:none; transition:background .15s,color .15s; }
.settings-lang-tab:hover { background:var(--bg); color:var(--primary); }
.settings-lang-tab--active { background:var(--primary); color:#fff; border-color:var(--primary); }
</style>
<div class="admin-content">
    <div class="admin-page-head">
        <h1><?= e(t('admin.settings.title')) ?></h1>
        <span class="text-muted">Manage public-facing content for Home, Contact, Donations, and About pages.</span>
    </div>

    <?php if ($flashSuccess): ?><div class="alert alert--success"><?= e($flashSuccess) ?></div><?php endif; ?>
    <?php if (!empty($errors['form'])): ?><div class="alert alert--danger"><?= e($errors['form']) ?></div><?php endif; ?>

    <!-- Main tabs -->
    <div class="settings-tabs">
        <a href="?tab=home&slang=<?= e($activeSlang) ?>"     class="settings-tab<?= $activeTab==='home'     ?' settings-tab--active':'' ?>">Home</a>
        <a href="?tab=contact&slang=<?= e($activeSlang) ?>"  class="settings-tab<?= $activeTab==='contact'  ?' settings-tab--active':'' ?>"><?= e(t('admin.settings.contact_tab')) ?></a>
        <a href="?tab=donation&slang=<?= e($activeSlang) ?>" class="settings-tab<?= $activeTab==='donation' ?' settings-tab--active':'' ?>"><?= e(t('admin.settings.donation_tab')) ?></a>
        <a href="?tab=about&slang=<?= e($activeSlang) ?>"    class="settings-tab<?= $activeTab==='about'    ?' settings-tab--active':'' ?>"><?= e(t('admin.settings.about_tab')) ?></a>
    </div>

    <div class="settings-layout">

    <?php if ($activeTab === 'home'): ?>
    <!-- ================================================================ HOME ================================================================ -->
    <div class="admin-form settings-form-col">
        <div class="settings-form-col__head">
            <h2>Edit Home "Who We Are" Section</h2>
            <p>Appears on the <a href="<?= e(url('index.php')) ?>" target="_blank">Home page</a> below the slider.</p>
        </div>
        <div class="settings-lang-tabs">
            <a href="?tab=home&slang=en" class="settings-lang-tab<?= $activeSlang==='en'?' settings-lang-tab--active':'' ?>">English</a>
            <a href="?tab=home&slang=am" class="settings-lang-tab<?= $activeSlang==='am'?' settings-lang-tab--active':'' ?>">አማርኛ</a>
        </div>
        <form method="post" action="<?= e(url('admin/settings.php')) ?>?tab=home">
            <?= csrf_field() ?>
            <input type="hidden" name="tab" value="home">
            <input type="hidden" name="slang" value="<?= e($activeSlang) ?>">
            <div class="form-group">
                <label>Section Label (<?= $activeSlang==='am'?'አማርኛ':'English' ?>)</label>
                <input type="text" name="home_label" value="<?= e($cur['home_label']) ?>" maxlength="100" placeholder="Who We Are">
                <div class="form-hint">Small label above the heading</div>
            </div>
            <div class="form-group">
                <label>Heading / Tagline (<?= $activeSlang==='am'?'አማርኛ':'English' ?>)</label>
                <input type="text" name="home_tagline" value="<?= e($cur['home_tagline']) ?>" maxlength="150" placeholder="Serving Community Through Faith & Love">
            </div>
            <div class="form-group">
                <label>Body Text (<?= $activeSlang==='am'?'አማርኛ':'English' ?>)</label>
                <textarea name="home_body" rows="5" placeholder="Describe who Semidalis is..."><?= e($cur['home_body']) ?></textarea>
            </div>
            <div class="form-actions mt-2">
                <button type="submit" class="btn btn--primary">Save Home Section</button>
            </div>
        </form>
    </div>
    <div class="settings-preview">
        <div class="settings-preview__head">
            <span class="settings-preview__label">Preview</span>
            <a href="<?= e(url('index.php')) ?>" target="_blank" class="settings-preview__link">View page</a>
        </div>
        <div class="settings-preview__body" style="padding:20px">
            <p style="font-size:.75rem;font-weight:700;text-transform:uppercase;letter-spacing:.08em;color:var(--accent);margin:0 0 8px"><?= e($cur['home_label']) ?></p>
            <h3 style="font-size:1.1rem;font-weight:800;color:var(--primary);margin:0 0 10px;line-height:1.3"><?= e($cur['home_tagline']) ?></h3>
            <p style="font-size:.88rem;color:var(--text-muted);line-height:1.6;margin:0"><?= e($cur['home_body']) ?></p>
        </div>
    </div>

    <?php elseif ($activeTab === 'contact'): ?>
    <!-- ================================================================ CONTACT ================================================================ -->
    <div class="admin-form settings-form-col">
        <div class="settings-form-col__head">
            <h2>Edit Contact Info</h2>
            <p>Appears on the <a href="<?= e(url('pages/contact.php')) ?>" target="_blank">Contact page</a>.</p>
        </div>
        <div class="settings-lang-tabs">
            <a href="?tab=contact&slang=en" class="settings-lang-tab<?= $activeSlang==='en'?' settings-lang-tab--active':'' ?>">English</a>
            <a href="?tab=contact&slang=am" class="settings-lang-tab<?= $activeSlang==='am'?' settings-lang-tab--active':'' ?>">አማርኛ</a>
        </div>
        <form method="post" action="<?= e(url('admin/settings.php')) ?>?tab=contact">
            <?= csrf_field() ?>
            <input type="hidden" name="tab" value="contact">
            <input type="hidden" name="slang" value="<?= e($activeSlang) ?>">

            <!-- Emails -->
            <div class="form-group">
                <label>Email Addresses</label>
                <div class="multi-list" id="emails-list">
                    <?php foreach ($cur['contact_emails'] as $em): ?>
                    <div class="multi-list__row">
                        <input type="email" name="contact_emails[]" value="<?= e($em) ?>" placeholder="email@example.com" maxlength="150">
                        <button type="button" class="multi-list__remove" aria-label="Remove">&times;</button>
                    </div>
                    <?php endforeach; ?>
                </div>
                <button type="button" class="btn btn--sm multi-list__add" data-target="emails-list"
                        data-template='<div class="multi-list__row"><input type="email" name="contact_emails[]" placeholder="email@example.com" maxlength="150"><button type="button" class="multi-list__remove">&times;</button></div>'>
                    + Add Email
                </button>
                <?php if (!empty($errors['contact_emails'])): ?><div class="field-error"><?= e($errors['contact_emails']) ?></div><?php endif; ?>
            </div>

            <!-- Phones -->
            <div class="form-group">
                <label><?= e(t('admin.settings.contact_phones')) ?></label>
                <div class="multi-list" id="phones-list">
                    <?php foreach ($cur['contact_phones'] as $ph): ?>
                    <div class="multi-list__row">
                        <input type="text" name="contact_phones[]" value="<?= e($ph) ?>" placeholder="+000 000 000 0000" maxlength="50">
                        <button type="button" class="multi-list__remove" aria-label="Remove">&times;</button>
                    </div>
                    <?php endforeach; ?>
                </div>
                <button type="button" class="btn btn--sm multi-list__add" data-target="phones-list"
                        data-template='<div class="multi-list__row"><input type="text" name="contact_phones[]" placeholder="+000 000 000 0000" maxlength="50"><button type="button" class="multi-list__remove">&times;</button></div>'>
                    <?= e(t('admin.settings.add_phone')) ?>
                </button>
            </div>

            <!-- Address -->
            <div class="form-group">
                <label><?= e(t('admin.settings.contact_address')) ?> (<?= $activeSlang==='am'?'አማርኛ':'English' ?>)</label>
                <div class="multi-list" id="addresses-list">
                    <?php foreach ($cur['contact_addresses'] as $addr): ?>
                    <div class="multi-list__row">
                        <input type="text" name="contact_addresses[]" value="<?= e($addr) ?>" placeholder="Street, City, Region" maxlength="255">
                        <button type="button" class="multi-list__remove" aria-label="Remove">&times;</button>
                    </div>
                    <?php endforeach; ?>
                </div>
                <button type="button" class="btn btn--sm multi-list__add" data-target="addresses-list"
                        data-template='<div class="multi-list__row"><input type="text" name="contact_addresses[]" placeholder="Street, City, Region" maxlength="255"><button type="button" class="multi-list__remove">&times;</button></div>'>
                    + Add Address
                </button>
            </div>

            <!-- Social media -->
            <hr style="margin:20px 0;border:none;border-top:1px solid var(--border)">
            <h3 style="font-size:.95rem;font-weight:700;color:var(--primary);margin:0 0 14px">Social Media</h3>
            <?php
            $socialMeta = [
                'telegram' => ['label'=>'Telegram', 'placeholder'=>'https://t.me/yourchannel',            'color'=>'#2CA5E0'],
                'facebook' => ['label'=>'Facebook',  'placeholder'=>'https://facebook.com/yourpage',      'color'=>'#1877F2'],
                'tiktok'   => ['label'=>'TikTok',    'placeholder'=>'https://tiktok.com/@yourhandle',     'color'=>'#000000'],
                'youtube'  => ['label'=>'YouTube',   'placeholder'=>'https://youtube.com/@yourchannel',   'color'=>'#FF0000'],
            ];
            foreach ($socialMeta as $key => $meta):
                $soc = $cur['contact_socials'][$key] ?? ['url'=>'','enabled'=>0];
            ?>
            <div class="social-setting-row">
                <div class="social-setting-row__dot" style="background:<?= $meta['color'] ?>"></div>
                <div class="social-setting-row__body">
                    <label class="social-setting-row__label"><?= $meta['label'] ?></label>
                    <input type="url" name="social_<?= $key ?>_url" value="<?= e($soc['url']) ?>" placeholder="<?= $meta['placeholder'] ?>" maxlength="300">
                </div>
                <label class="social-setting-row__toggle">
                    <input type="checkbox" name="social_<?= $key ?>_enabled" value="1" <?= !empty($soc['enabled'])?'checked':'' ?>>
                    <span class="social-toggle-label">Show</span>
                </label>
            </div>
            <?php endforeach; ?>

            <div class="form-actions mt-2">
                <button type="submit" class="btn btn--primary"><?= e(t('admin.settings.save_btn')) ?></button>
            </div>
        </form>
    </div>
    <!-- Contact preview -->
    <div class="settings-preview">
        <div class="settings-preview__head">
            <span class="settings-preview__label">Preview</span>
            <a href="<?= e(url('pages/contact.php')) ?>" target="_blank" class="settings-preview__link">View page</a>
        </div>
        <div class="settings-preview__body">
            <div class="sp-contact">
                <div class="sp-contact__row">
                    <span class="sp-contact__icon">✉</span>
                    <div><div class="sp-contact__key">Email(s)</div>
                    <?php foreach ($cur['contact_emails'] as $em): ?><div class="sp-contact__val"><?= e($em) ?></div><?php endforeach; ?></div>
                </div>
                <div class="sp-contact__row">
                    <span class="sp-contact__icon">📞</span>
                    <div><div class="sp-contact__key">Phone(s)</div>
                    <?php foreach ($cur['contact_phones'] as $ph): ?><div class="sp-contact__val"><?= e($ph) ?></div><?php endforeach; ?></div>
                </div>
                <div class="sp-contact__row">
                    <span class="sp-contact__icon">📍</span>
                    <div><div class="sp-contact__key">Address(es)</div>
                    <?php foreach ($cur['contact_addresses'] as $addr): ?><div class="sp-contact__val"><?= e($addr) ?></div><?php endforeach; ?></div>
                </div>
                <?php $activeSocials = array_filter($cur['contact_socials'], fn($s)=>!empty($s['enabled'])&&!empty($s['url'])); ?>
                <?php if (!empty($activeSocials)): ?>
                <div class="sp-contact__row">
                    <span class="sp-contact__icon">🔗</span>
                    <div><div class="sp-contact__key">Social</div>
                    <?php foreach ($activeSocials as $p=>$s): ?><div class="sp-contact__val"><?= e(ucfirst($p)) ?>: <?= e($s['url']) ?></div><?php endforeach; ?></div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <?php elseif ($activeTab === 'donation'): ?>
    <!-- ================================================================ DONATION ================================================================ -->
    <div class="admin-form settings-form-col">
        <div class="settings-form-col__head">
            <h2>Edit Bank Details</h2>
            <p>Appears on the <a href="<?= e(url('pages/donations.php')) ?>" target="_blank">Donations page</a>.</p>
        </div>
        <form method="post" action="<?= e(url('admin/settings.php')) ?>?tab=donation">
            <?= csrf_field() ?>
            <input type="hidden" name="tab" value="donation">
            <input type="hidden" name="slang" value="en">

            <div id="banks-list">
                <?php foreach ($cur['donation_banks'] as $i => $bank): ?>
                <div class="multi-bank" data-index="<?= $i ?>">
                    <div class="multi-bank__head">
                        <span class="multi-bank__num">Bank Account <?= $i+1 ?></span>
                        <button type="button" class="multi-bank__remove btn btn--danger btn--sm">Remove</button>
                    </div>
                    <div class="multi-bank__grid">
                        <div class="form-group"><label><?= e(t('admin.settings.account_name')) ?></label>
                            <input type="text" name="bank_account_name[]" value="<?= e($bank['account_name']??'') ?>" maxlength="150" placeholder="Account holder name"></div>
                        <div class="form-group"><label><?= e(t('admin.settings.bank_name')) ?></label>
                            <input type="text" name="bank_name[]" value="<?= e($bank['bank_name']??'') ?>" maxlength="150" placeholder="e.g. CBE, Awash Bank"></div>
                        <div class="form-group"><label><?= e(t('admin.settings.account_number')) ?></label>
                            <input type="text" name="bank_account_number[]" value="<?= e($bank['account_number']??'') ?>" maxlength="50" placeholder="0000 0000 0000 0000"></div>
                        <div class="form-group"><label><?= e(t('admin.settings.reference')) ?></label>
                            <input type="text" name="bank_reference[]" value="<?= e($bank['reference']??'') ?>" maxlength="255" placeholder="Donation + Your Name"></div>
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
    <!-- Donation preview -->
    <div class="settings-preview">
        <div class="settings-preview__head">
            <span class="settings-preview__label">Preview</span>
            <a href="<?= e(url('pages/donations.php')) ?>" target="_blank" class="settings-preview__link">View page</a>
        </div>
        <div class="settings-preview__body">
            <?php foreach ($cur['donation_banks'] as $i => $bank): ?>
            <?php if ($i>0): ?><div style="height:10px"></div><?php endif; ?>
            <p class="sp-section-label"><?= count($cur['donation_banks'])>1 ? 'Account '.($i+1).' — '.e($bank['bank_name']??'') : 'Bank Details' ?></p>
            <div class="sp-bank">
                <div class="sp-bank__row"><span class="sp-bank__key">Account Name</span><span class="sp-bank__val"><?= e($bank['account_name']??'') ?></span></div>
                <div class="sp-bank__row"><span class="sp-bank__key">Bank</span><span class="sp-bank__val"><?= e($bank['bank_name']??'') ?></span></div>
                <div class="sp-bank__row"><span class="sp-bank__key">Account No.</span><span class="sp-bank__val"><?= e($bank['account_number']??'') ?></span></div>
                <div class="sp-bank__row"><span class="sp-bank__key">Reference</span><span class="sp-bank__val"><?= e($bank['reference']??'') ?></span></div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <?php elseif ($activeTab === 'about'): ?>
    <!-- ================================================================ ABOUT ================================================================ -->
    <div class="admin-form settings-form-col">
        <div class="settings-form-col__head">
            <h2>Edit About Page</h2>
            <p>Appears on the <a href="<?= e(url('pages/about.php')) ?>" target="_blank">About page</a>.</p>
        </div>
        <div class="settings-lang-tabs">
            <a href="?tab=about&slang=en" class="settings-lang-tab<?= $activeSlang==='en'?' settings-lang-tab--active':'' ?>">English</a>
            <a href="?tab=about&slang=am" class="settings-lang-tab<?= $activeSlang==='am'?' settings-lang-tab--active':'' ?>">አማርኛ</a>
        </div>
        <form method="post" action="<?= e(url('admin/settings.php')) ?>?tab=about">
            <?= csrf_field() ?>
            <input type="hidden" name="tab" value="about">
            <input type="hidden" name="slang" value="<?= e($activeSlang) ?>">

            <div class="form-group">
                <label><?= e(t('admin.settings.about_intro')) ?> (<?= $activeSlang==='am'?'አማርኛ':'English' ?>)</label>
                <textarea name="about_intro" rows="4" placeholder="Intro text..."><?= e($cur['about_intro']) ?></textarea>
            </div>
            <div class="form-group">
                <label>Secondary Note (<?= $activeSlang==='am'?'አማርኛ':'English' ?>)</label>
                <textarea name="about_secondary_note" rows="3" placeholder="We are grateful for every volunteer..."><?= e($cur['about_secondary_note']) ?></textarea>
                <div class="form-hint">Shown below the intro paragraph on the About page.</div>
            </div>
            <div class="form-group">
                <label><?= e(t('admin.settings.mission')) ?> (<?= $activeSlang==='am'?'አማርኛ':'English' ?>)</label>
                <textarea name="about_mission" rows="3" placeholder="Our mission is..."><?= e($cur['about_mission']) ?></textarea>
            </div>
            <div class="form-group">
                <label><?= e(t('admin.settings.vision')) ?> (<?= $activeSlang==='am'?'አማርኛ':'English' ?>)</label>
                <textarea name="about_vision" rows="3" placeholder="Our vision is..."><?= e($cur['about_vision']) ?></textarea>
            </div>

            <hr style="margin:24px 0;border:none;border-top:1px solid var(--border)">

            <div class="form-group">
                <label><?= e(t('admin.settings.work_title')) ?> (<?= $activeSlang==='am'?'አማርኛ':'English' ?>)</label>
                <input type="text" name="about_work_title" value="<?= e($cur['about_work_title']) ?>" maxlength="100" placeholder="What We Do">
            </div>
            <div class="form-group">
                <label><?= e(t('admin.settings.work_desc')) ?> (<?= $activeSlang==='am'?'አማርኛ':'English' ?>)</label>
                <input type="text" name="about_work_desc" value="<?= e($cur['about_work_desc']) ?>" maxlength="255" placeholder="Our work spans...">
            </div>

            <div class="form-group">
                <label><?= e(t('admin.settings.work_items')) ?> (<?= $activeSlang==='am'?'አማርኛ':'English' ?>)</label>
                <div id="work-items-list">
                    <?php foreach ($cur['about_work_items'] as $i => $item): ?>
                    <div class="multi-work-item" data-index="<?= $i ?>">
                        <div class="multi-work-item__head">
                            <span class="multi-work-item__num">Item <?= $i+1 ?></span>
                            <button type="button" class="multi-work-item__remove btn btn--danger btn--sm">Remove</button>
                        </div>
                        <div class="multi-work-item__grid">
                            <div class="form-group"><label>Title</label>
                                <input type="text" name="work_item_title[]" value="<?= e($item['title']??'') ?>" maxlength="100" placeholder="e.g. Gospel Music"></div>
                            <div class="form-group"><label>Description</label>
                                <textarea name="work_item_desc[]" rows="2"><?= e($item['description']??'') ?></textarea></div>
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
    <!-- About preview -->
    <div class="settings-preview">
        <div class="settings-preview__head">
            <span class="settings-preview__label">Preview</span>
            <a href="<?= e(url('pages/about.php')) ?>" target="_blank" class="settings-preview__link">View page</a>
        </div>
        <div class="settings-preview__body">
            <p class="sp-section-label">Intro</p>
            <p class="sp-text"><?= e($cur['about_intro']) ?></p>
            <p class="sp-text" style="font-style:italic;margin-top:6px"><?= e($cur['about_secondary_note']) ?></p>
            <p class="sp-section-label">Mission</p>
            <p class="sp-text"><?= e($cur['about_mission']) ?></p>
            <p class="sp-section-label">Vision</p>
            <p class="sp-text"><?= e($cur['about_vision']) ?></p>
            <hr style="margin:12px 0;border:none;border-top:1px solid rgba(0,0,0,.1)">
            <p class="sp-section-label"><?= e($cur['about_work_title']) ?></p>
            <p class="sp-text"><?= e($cur['about_work_desc']) ?></p>
            <?php foreach ($cur['about_work_items'] as $item): ?>
            <div style="margin-top:10px;padding-top:10px;border-top:1px dotted rgba(0,0,0,.12)">
                <div style="font-weight:700;font-size:.85rem;color:var(--primary);margin-bottom:2px"><?= e($item['title']??'') ?></div>
                <div style="font-size:.8rem;color:var(--text-muted);line-height:1.5"><?= e($item['description']??'') ?></div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <?php endif; ?>
    </div><!-- /.settings-layout -->
</div>

<script>
(function () {
    // Generic multi-list add/remove (emails, phones)
    document.querySelectorAll('.multi-list__add').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var list = document.getElementById(btn.dataset.target);
            var tmp = document.createElement('div');
            tmp.innerHTML = btn.dataset.template;
            var row = tmp.firstChild;
            list.appendChild(row);
            bindRemove(row.querySelector('.multi-list__remove'));
            var inp = row.querySelector('input');
            if (inp) inp.focus();
        });
    });
    document.querySelectorAll('.multi-list__remove').forEach(bindRemove);
    function bindRemove(btn) {
        if (!btn) return;
        btn.addEventListener('click', function () {
            var list = btn.closest('.multi-list');
            if (list && list.querySelectorAll('.multi-list__row').length > 1)
                btn.closest('.multi-list__row').remove();
        });
    }

    // Bank accounts
    var bankList = document.getElementById('banks-list');
    var addBankBtn = document.getElementById('add-bank-btn');
    if (bankList && addBankBtn) {
        function bankCount() { return bankList.querySelectorAll('.multi-bank').length; }
        function updateBankLabels() {
            bankList.querySelectorAll('.multi-bank__num').forEach(function (el, i) { el.textContent = 'Bank Account ' + (i+1); });
        }
        function bindBankRemove(btn) {
            btn.addEventListener('click', function () {
                if (bankCount() > 1) { btn.closest('.multi-bank').remove(); updateBankLabels(); }
            });
        }
        bankList.querySelectorAll('.multi-bank__remove').forEach(bindBankRemove);
        addBankBtn.addEventListener('click', function () {
            var idx = bankCount();
            var div = document.createElement('div');
            div.className = 'multi-bank'; div.dataset.index = idx;
            div.innerHTML = '<div class="multi-bank__head"><span class="multi-bank__num">Bank Account '+(idx+1)+'</span><button type="button" class="multi-bank__remove btn btn--danger btn--sm">Remove</button></div>' +
                '<div class="multi-bank__grid">' +
                '<div class="form-group"><label>Account Name</label><input type="text" name="bank_account_name[]" maxlength="150" placeholder="Account holder name"></div>' +
                '<div class="form-group"><label>Bank Name</label><input type="text" name="bank_name[]" maxlength="150" placeholder="e.g. CBE, Awash Bank"></div>' +
                '<div class="form-group"><label>Account Number</label><input type="text" name="bank_account_number[]" maxlength="50" placeholder="0000 0000 0000 0000"></div>' +
                '<div class="form-group"><label>Reference</label><input type="text" name="bank_reference[]" maxlength="255" placeholder="Donation + Your Name"></div>' +
                '</div>';
            bindBankRemove(div.querySelector('.multi-bank__remove'));
            bankList.appendChild(div);
            div.querySelector('input').focus();
        });
    }

    // Work items
    var workList = document.getElementById('work-items-list');
    var addWorkBtn = document.getElementById('add-work-item-btn');
    if (workList && addWorkBtn) {
        function workCount() { return workList.querySelectorAll('.multi-work-item').length; }
        function updateWorkLabels() {
            workList.querySelectorAll('.multi-work-item__num').forEach(function (el, i) { el.textContent = 'Item ' + (i+1); });
        }
        function bindWorkRemove(btn) {
            btn.addEventListener('click', function () {
                if (workCount() > 1) { btn.closest('.multi-work-item').remove(); updateWorkLabels(); }
            });
        }
        workList.querySelectorAll('.multi-work-item__remove').forEach(bindWorkRemove);
        addWorkBtn.addEventListener('click', function () {
            var idx = workCount();
            var div = document.createElement('div');
            div.className = 'multi-work-item'; div.dataset.index = idx;
            div.innerHTML = '<div class="multi-work-item__head"><span class="multi-work-item__num">Item '+(idx+1)+'</span><button type="button" class="multi-work-item__remove btn btn--danger btn--sm">Remove</button></div>' +
                '<div class="multi-work-item__grid">' +
                '<div class="form-group"><label>Title</label><input type="text" name="work_item_title[]" maxlength="100" placeholder="e.g. Gospel Music"></div>' +
                '<div class="form-group"><label>Description</label><textarea name="work_item_desc[]" rows="2"></textarea></div>' +
                '</div>';
            bindWorkRemove(div.querySelector('.multi-work-item__remove'));
            workList.appendChild(div);
            div.querySelector('input').focus();
        });
    }
}());
</script>
<?php require __DIR__ . '/../includes/footer.php'; ?>
