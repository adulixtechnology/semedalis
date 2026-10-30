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
                <p class="about-preview__label"><?= t('home.who_we_are') ?></p>
                <h2 class="about-preview__title"><?= t('home.tagline') ?></h2>
                <p class="about-preview__body"><?= t('home.about_body') ?></p>
                <a class="btn btn--primary" href="<?= e(url('pages/about.php')) ?>#about"><?= t('home.learn_more') ?></a>
            </div>
            <div class="about-preview__image">
                <div class="about-preview__icon">✝</div>
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
