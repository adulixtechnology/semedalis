<?php
/**
 * Announcements — list of active announcements.
 */
require __DIR__ . '/../config/config.php';

$announcements = [];
try {
    $stmt = db()->prepare("SELECT * FROM announcements WHERE status = 'active' ORDER BY created_at DESC");
    $stmt->execute();
    $announcements = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log('Announcements list query: ' . $e->getMessage());
}

$pageTitle = t('nav.announcements');
require __DIR__ . '/../includes/header.php';
?>
<div class="container">
    <section class="section">
        <div class="section-head">
            <h1><?= t('announcements.announcements') ?></h1>
        </div>
        <?php if (!empty($announcements)): ?>
        <div class="news-grid">
            <?php foreach ($announcements as $ann): ?>
            <article class="news-card">
                <?php if (!empty($ann['image_path'])): ?>
                <div class="news-card__image">
                    <img src="<?= e(image_url('announcements', $ann['image_path'])) ?>" alt="<?= e($ann['title'] ?? '') ?>">
                </div>
                <?php else: ?>
                <div class="news-card__image news-card__image--placeholder">
                    <div class="news-card__placeholder-icon">&#128240;</div>
                </div>
                <?php endif; ?>

                <div class="news-card__content">
                    <h3 class="news-card__title"><?= e($ann['title'] ?? '') ?></h3>
                    <?php if (!empty($ann['content'])): ?>
                    <p class="news-card__excerpt"><?= e(mb_strimwidth((string) $ann['content'], 0, 120, '...')) ?></p>
                    <?php endif; ?>
                    <div class="news-card__meta">
                        <span class="news-card__date"><?= e(format_date($ann['created_at'] ?? '')) ?></span>
                        <span class="news-card__lang"><?= e(strtoupper((string) ($ann['language'] ?? 'en'))) ?></span>
                    </div>
                    <a href="<?= e(url('pages/announcement.php?id=' . (int) $ann['id'])) ?>" class="news-card__link"><?= t('announcements.read_more') ?></a>
                </div>
            </article>
            <?php endforeach; ?>
        </div>
        <?php else: ?>
        <div class="empty-state"><?= t('announcements.none') ?></div>
        <?php endif; ?>
    </section>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
