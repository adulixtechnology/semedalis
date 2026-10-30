<?php
/**
 * Single announcement — 404 if the id is invalid, missing, or inactive.
 */
require __DIR__ . '/../config/config.php';

$id = (int)($_GET['id'] ?? 0);
$announcement = null;

if ($id > 0) {
    try {
        $stmt = db()->prepare("SELECT * FROM announcements WHERE id = ? AND status = 'active'");
        $stmt->execute([$id]);
        $announcement = $stmt->fetch();
    } catch (PDOException $e) {
        error_log('Announcement fetch: ' . $e->getMessage());
    }
}

if (!$announcement) {
    http_response_code(404);
}

$pageTitle = $announcement ? $announcement['title'] : t('announcements.not_found');
require __DIR__ . '/../includes/header.php';
?>
<div class="container">
    <?php /* HTML-bearing key — do not wrap in e(); see t() docblock */ ?>
    <a class="back-link" href="<?= e(url('pages/announcements.php')) ?>"><?= t('announcements.back') ?></a>

    <?php if ($announcement): ?>
    <article class="announcement">
        <h1><?= e($announcement['title']) ?></h1>
        <p class="date"><?= e(format_date($announcement['created_at'])) ?></p>
        <?php $img = image_url('announcements', $announcement['image_path'] ?? ''); ?>
        <?php if ($img !== ''): ?>
        <img src="<?= e($img) ?>" alt="<?= e($announcement['title']) ?>">
        <?php endif; ?>
        <div class="article-content">
            <?= nl2br(e($announcement['content'] ?? '')) ?>
        </div>
    </article>
    <?php else: ?>
    <div class="empty-state">
        <h2><?= t('announcements.not_found') ?></h2>
        <p><?= t('announcements.not_found_body') ?></p>
    </div>
    <?php endif; ?>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
