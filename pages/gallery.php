<?php
/**
 * Gallery — shows albums as cards; clicking an album opens it to view all photos.
 */
require __DIR__ . '/../config/config.php';

// Which album is open?
$openAlbumId = isset($_GET['album']) ? (int) $_GET['album'] : 0;
$openAlbum   = null;
$albumPhotos = [];

if ($openAlbumId > 0) {
    try {
        $astmt = db()->prepare("SELECT * FROM gallery_albums WHERE id=?");
        $astmt->execute([$openAlbumId]);
        $openAlbum = $astmt->fetch();

        if ($openAlbum) {
            $pstmt = db()->prepare("SELECT * FROM gallery WHERE album_id=? ORDER BY id ASC");
            $pstmt->execute([$openAlbumId]);
            $albumPhotos = $pstmt->fetchAll();
        }
    } catch (PDOException $e) {
        error_log('Gallery album open: ' . $e->getMessage());
    }
}

// All albums list
$albums = [];
try {
    $albums = db()->query(
        "SELECT a.*, COUNT(g.id) AS photo_count
         FROM gallery_albums a
         LEFT JOIN gallery g ON g.album_id = a.id
         GROUP BY a.id ORDER BY a.created_at DESC"
    )->fetchAll();
} catch (PDOException $e) {
    error_log('Gallery albums query: ' . $e->getMessage());
}

$pageTitle = $openAlbum ? e($openAlbum['title']) . ' — ' . t('gallery.gallery') : t('gallery.gallery');
require __DIR__ . '/../includes/header.php';
?>

<div class="container">
    <section class="section">

        <?php if ($openAlbum): ?>
        <!-- ── Album view ── -->
        <div class="gallery-album-head">
            <a href="<?= e(url('pages/gallery.php')) ?>" class="gallery-back"><?= t('gallery.back') ?></a>
            <h1><?= e($openAlbum['title']) ?></h1>
            <?php if (!empty($openAlbum['description'])): ?>
            <p class="gallery-album-desc"><?= e($openAlbum['description']) ?></p>
            <?php endif; ?>
            <span class="gallery-album-count"><?= count($albumPhotos) ?> <?= t('gallery.photos') ?></span>
        </div>

        <?php if (empty($albumPhotos)): ?>
        <div class="empty-state"><?= t('gallery.no_media') ?></div>
        <?php else: ?>
        <div class="gallery-grid">
            <?php foreach ($albumPhotos as $row):
                $isVid = ($row['media_type'] ?? 'image') === 'video';
            ?>
            <?php if ($isVid): ?>
            <div class="gallery-video-item">
                <video controls playsinline preload="auto"
                       style="width:100%;height:auto;display:block;border-radius:var(--radius);background:#111;max-height:400px">
                    <source src="<?= e(image_url('gallery', $row['image_path'])) ?>" type="video/<?= e(strtolower(pathinfo($row['image_path'], PATHINFO_EXTENSION))) ?>">
                </video>
                <div class="gallery-video-item__label">&#9654; Video</div>
            </div>
            <?php else: ?>
            <figure class="gallery-item"
                    onclick="openLightbox('<?= e(image_url('gallery', $row['image_path'])) ?>', '<?= e(addslashes($row['title'] ?? '')) ?>')">
                <img src="<?= e(image_url('gallery', $row['image_path'])) ?>" alt="<?= e($row['title'] ?? '') ?>">
            </figure>
            <?php endif; ?>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <?php else: ?>
        <!-- ── Albums list ── -->
        <div class="section-head">
            <h1><?= t('gallery.gallery') ?></h1>
        </div>

        <?php if (empty($albums)): ?>
        <div class="empty-state"><?= t('gallery.no_photos') ?></div>
        <?php else: ?>
        <div class="gallery-grid">
            <?php foreach ($albums as $album): ?>
            <figure class="gallery-item gallery-album-card" onclick="location.href='<?= e(url('pages/gallery.php?album=' . (int) $album['id'])) ?>'">
                <?php if (!empty($album['cover_image'])): ?>
                <img src="<?= e(image_url('gallery', $album['cover_image'])) ?>" alt="<?= e($album['title']) ?>">
                <?php else: ?>
                <div class="gallery-item__no-cover">&#128247;</div>
                <?php endif; ?>
                <figcaption>
                    <span class="gallery-album-card__title"><?= e($album['title']) ?></span>
                    <span class="gallery-album-card__count"><?= (int) $album['photo_count'] ?> <?= t('gallery.photos') ?></span>
                </figcaption>
            </figure>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
        <?php endif; ?>

    </section>
</div>

<!-- Lightbox (album photo view) -->
<div id="lightbox" class="image-modal" role="dialog" aria-modal="true">
    <div class="image-modal__overlay" onclick="closeLightbox()"></div>
    <div class="image-modal__content">
        <button class="image-modal__close" onclick="closeLightbox()">&#10005;</button>
        <img id="lightboxImg" src="" alt="">
    </div>
</div>

<script>
function openLightbox(src, alt) {
    document.getElementById('lightboxImg').src = src;
    document.getElementById('lightboxImg').alt = alt || '';
    document.getElementById('lightbox').classList.add('active');
    document.body.style.overflow = 'hidden';
}
function closeLightbox() {
    document.getElementById('lightbox').classList.remove('active');
    document.body.style.overflow = '';
}
document.addEventListener('keydown', function (e) { if (e.key === 'Escape') closeLightbox(); });
</script>

<?php require __DIR__ . '/../includes/footer.php'; ?>
