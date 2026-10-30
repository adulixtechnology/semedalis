<?php
/**
 * About — static page about the gospel/charity team.
 */
require __DIR__ . '/../config/config.php';

function about_setting(string $key, string $default = ''): string {
    try {
        $stmt = db()->prepare("SELECT `value` FROM settings WHERE `key` = ?");
        $stmt->execute([$key]);
        $row = $stmt->fetch();
        return ($row && $row['value'] !== '') ? (string) $row['value'] : $default;
    } catch (PDOException $e) { return $default; }
}
function about_setting_json(string $key, array $default = []): array {
    $raw = about_setting($key, '');
    if ($raw === '') return $default;
    $d = json_decode($raw, true);
    return is_array($d) ? $d : $default;
}
function about_setting_lang(string $key, string $lang, string $default = ''): string {
    $val = about_setting($key . '_' . $lang, '');
    if ($val !== '') return $val;
    return about_setting($key, $default);
}

$lang = $_SESSION['lang'] ?? 'en';
$lang = in_array($lang, ['en', 'am']) ? $lang : 'en';

$aboutIntro = about_setting_lang('about_intro', $lang, 'Semidalis is a gospel and charity team dedicated to proclaiming a message of hope and serving our community through practical acts of love. Rooted in James 1:27, we believe that true faith is shown in how we care for those around us.');
$aboutMission = about_setting_lang('about_mission', $lang, "To proclaim the gospel and demonstrate God's love through practical acts of charity, music, and community service — reaching every corner of our community with grace and compassion.");
$aboutVision  = about_setting_lang('about_vision',  $lang, 'A community transformed by hope, where everyone — regardless of circumstance — experiences the love and grace we are called to share through the gospel of Christ.');
$aboutWorkTitle = about_setting_lang('about_work_title', $lang, 'What We Do');
$aboutWorkDesc  = about_setting_lang('about_work_desc',  $lang, 'Our work spans worship, outreach, and practical support for families in need.');

// about_work_items: try language-suffixed key first, fall back to base key
$aboutWorkItemsRaw = about_setting('about_work_items_' . $lang, '');
if ($aboutWorkItemsRaw !== '') {
    $aboutWorkItemsDecoded = json_decode($aboutWorkItemsRaw, true);
    $aboutWorkItems = is_array($aboutWorkItemsDecoded) ? $aboutWorkItemsDecoded : [];
}
if (empty($aboutWorkItems)) {
    $aboutWorkItems = about_setting_json('about_work_items', [
        ['title' => 'Gospel Music and Worship', 'description' => 'We organize gospel music events and worship gatherings that bring the community together in faith and praise.'],
        ['title' => 'Community Outreach', 'description' => 'Our charity programs reach families and individuals facing hardship, providing practical support and encouragement.'],
        ['title' => 'Support for Families in Need', 'description' => 'We stand alongside orphans, widows, and vulnerable families — walking with them through their difficulties.'],
        ['title' => 'Fellowship and Inspiration', 'description' => 'We host gatherings that build community, nurture faith, and inspire people to live out the gospel in everyday life.'],
    ]);
}

$pageTitle = t('nav.about');
require __DIR__ . '/../includes/header.php';
?>

<div class="container">

    <!-- Intro -->
    <section class="section about-intro">
        <div class="about-intro__text">
            <p><?= e($aboutIntro) ?></p>
            <p><?= t('about.secondary_note') ?></p>
        </div>
        <blockquote class="about-intro__verse">
            <p><?= t('about.verse_body') ?></p>
            <cite><?= t('about.verse_cite') ?></cite>
        </blockquote>
    </section>

    <!-- Mission & Vision -->
    <section class="section about-mv">
        <div class="about-mv__card about-mv__card--mission">
            <div class="about-mv__icon-wrap">
                <div class="about-mv__icon">M</div>
            </div>
            <h2><?= t('about.our_mission') ?></h2>
            <p><?= e($aboutMission) ?></p>
        </div>
        <div class="about-mv__card about-mv__card--vision">
            <div class="about-mv__icon-wrap">
                <div class="about-mv__icon">V</div>
            </div>
            <h2><?= t('about.our_vision') ?></h2>
            <p><?= e($aboutVision) ?></p>
        </div>
    </section>

    <!-- What We Do -->
    <section class="section about-work">
        <div class="about-work__head">
            <h2><?= e($aboutWorkTitle) ?></h2>
            <p><?= e($aboutWorkDesc) ?></p>
        </div>
        <div class="about-work__grid">
            <?php foreach ($aboutWorkItems as $item): ?>
            <div class="about-work__item">
                <div class="about-work__marker"></div>
                <div>
                    <h3><?= e($item['title'] ?? '') ?></h3>
                    <p><?= e($item['description'] ?? '') ?></p>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </section>

    <!-- Get Involved CTA -->
    <section class="section">
        <div class="about-cta">
            <div class="about-cta__text">
                <h2><?= t('about.be_part') ?></h2>
                <p><?= t('about.be_part_body') ?></p>
            </div>
            <div class="about-cta__actions">
                <a class="btn btn--white" href="<?= e(url('pages/donate.php')) ?>"><?= t('about.donate_now') ?></a>
                <a class="btn about-cta__ghost" href="<?= e(url('pages/contact.php')) ?>"><?= t('about.contact_us') ?></a>
            </div>
        </div>
    </section>

</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
