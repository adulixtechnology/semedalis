<?php
/**
 * Home page — slider, about preview, donate CTA.
 */
require __DIR__ . '/config/config.php';

$slides = [];
try {
    $stmt = db()->prepare("SELECT * FROM slider_images WHERE status = 'active' ORDER BY order_index ASC, id ASC");
    $stmt->execute();
    $slides = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log('Home slider query: ' . $e->getMessage());
}

// Load home section content from settings (language-aware)
$_homeLang = $_SESSION['lang'] ?? 'en';
$_homeLang = in_array($_homeLang, ['en', 'am']) ? $_homeLang : 'en';

function home_setting(string $key, string $lang, string $default): string {
    try {
        // Try language-specific key first
        $stmt = db()->prepare("SELECT `value` FROM settings WHERE `key` = ?");
        $stmt->execute([$key . '_' . $lang]);
        $row = $stmt->fetch();
        if ($row && $row['value'] !== '') return (string) $row['value'];
        // Fall back to base key
        $stmt2 = db()->prepare("SELECT `value` FROM settings WHERE `key` = ?");
        $stmt2->execute([$key]);
        $row2 = $stmt2->fetch();
        return ($row2 && $row2['value'] !== '') ? (string) $row2['value'] : $default;
    } catch (PDOException $e) { return $default; }
}

$homeLabel   = home_setting('home_label',   $_homeLang, t('home.who_we_are'));
$homeTagline = home_setting('home_tagline', $_homeLang, t('home.tagline'));
$homeBody    = home_setting('home_body',    $_homeLang, t('home.about_body'));

$pageTitle = t('nav.home');
require __DIR__ . '/includes/header.php';
?>

<?php if (!empty($slides)): ?>
<section aria-label="Featured slider">
    <div class="hero">
        <div class="hero__track">
            <?php foreach ($slides as $slide): ?>
            <div class="hero__slide">
                <img src="<?= e(image_url('slider', $slide['image_path'])) ?>" alt="Semidalis">
            </div>
            <?php endforeach; ?>
        </div>
        <?php if (count($slides) > 1): ?>
        <button class="hero__btn hero__btn--prev" type="button" aria-label="Previous slide">&#8249;</button>
        <button class="hero__btn hero__btn--next" type="button" aria-label="Next slide">&#8250;</button>
        <div class="hero__dots"></div>
        <?php endif; ?>

        <div class="hero__mission">
            <div class="hero__mission-title"><?= t('hero.title') ?></div>
            <div class="hero__mission-verse"><?= t('hero.verse') ?></div>
        </div>
    </div>
</section>
<?php else: ?>
<div class="hero hero--static">
    <div class="hero__mission">
        <div class="hero__mission-title"><?= t('hero.title') ?></div>
        <div class="hero__mission-verse"><?= t('hero.verse') ?></div>
    </div>
</div>
<?php endif; ?>

<div class="container">

    <!-- About preview -->
    <section class="section" id="about-preview">
        <div class="about-preview">
            <div class="about-preview__text">
                <p class="about-preview__label"><?= e($homeLabel) ?></p>
                <h2 class="about-preview__title"><?= e($homeTagline) ?></h2>
                <p class="about-preview__body"><?= e($homeBody) ?></p>
                <a class="btn btn--primary" href="<?= e(url('pages/about.php')) ?>#about"><?= t('home.learn_more') ?></a>
            </div>
            <div class="about-preview__image">
                <img src="<?= e(url('assets/logo.PNG')) ?>" alt="Semidalis" class="about-preview__logo">
            </div>
        </div>
    </section>

    <!-- Donate CTA -->
    <section class="section">
        <div class="cta-banner">
            <h2><?= t('home.support_mission') ?></h2>
            <p><?= t('home.support_body') ?></p>
            <a class="btn btn--white" href="<?= e(url('pages/donate.php')) ?>"><?= t('home.donate_now') ?></a>
        </div>
    </section>

</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
