<?php
/**
 * Admin — Gallery Albums CRUD.
 * Supports images (JPG/PNG/GIF/WebP) and videos (MP4/MOV/WebM/AVI).
 */
require __DIR__ . '/../config/config.php';
$adminTitle = 'Gallery';

$unlinkUpload = function (string $type, $file): void {
    if ($file !== '' && $file !== null && strpos((string) $file, '/') === false) {
        @unlink(dirname(__DIR__) . '/backend/uploads/' . $type . '/' . $file);
    }
};

// Detect whether a filename is a video
function is_video_file(string $filename): bool {
    $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    return in_array($ext, ['mp4', 'mov', 'webm', 'avi'], true);
}

// Process a multi-file upload field supporting both images and videos
function process_media_uploads(array $filesArr, callable $unlinkUpload): array {
    $results = []; // ['file' => ..., 'media_type' => 'image'|'video']
    $count = count($filesArr['name']);
    for ($i = 0; $i < $count; $i++) {
        if (($filesArr['error'][$i] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) continue;
        $ext = strtolower(pathinfo($filesArr['name'][$i] ?? '', PATHINFO_EXTENSION));
        $isVideo = in_array($ext, ['mp4', 'mov', 'webm', 'avi'], true);

        // Route to the correct validator
        $_FILES['_media_upload'] = [
            'name'     => $filesArr['name'][$i],
            'type'     => $filesArr['type'][$i],
            'tmp_name' => $filesArr['tmp_name'][$i],
            'error'    => $filesArr['error'][$i],
            'size'     => $filesArr['size'][$i],
        ];

        if ($isVideo) {
            $result = validate_upload_video('_media_upload');
        } else {
            $result = validate_upload('_media_upload', 'gallery');
        }

        if ($result === '') continue;
        if (!ctype_digit($result[0] ?? '')) {
            // Error — clean up what we already saved
            foreach ($results as $r) $unlinkUpload('gallery', $r['file']);
            return ['error' => $result];
        }
        $results[] = ['file' => $result, 'media_type' => $isVideo ? 'video' : 'image'];
    }
    return $results;
}

$errors    = [];
$editingId = 0;
$editRow   = null;

// ── POST handlers ──────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action = $_POST['action'] ?? '';

    if ($action === 'create_album') {
        $title       = trim($_POST['title'] ?? '');
        $category    = trim($_POST['category'] ?? '') ?: 'general';
        $description = trim($_POST['description'] ?? '');
        set_old('title', $title); set_old('category', $category); set_old('description', $description);

        if ($title === '') $errors['title'] = 'Album title is required.';
        elseif (mb_strlen($title) > 255) $errors['title'] = 'Max 255 characters.';

        $uploadedMedia = [];
        if (empty($errors)) {
            if (empty($_FILES['images']['name'][0])) {
                $errors['images'] = 'Please select at least one photo or video.';
            } else {
                $uploadedMedia = process_media_uploads($_FILES['images'], $unlinkUpload);
                if (isset($uploadedMedia['error'])) {
                    $errors['images'] = $uploadedMedia['error'];
                    $uploadedMedia = [];
                }
            }
        }

        if (empty($errors) && !empty($uploadedMedia)) {
            try {
                db()->beginTransaction();
                // Use first image as cover; if first is video use null for cover
                $coverImage = $uploadedMedia[0]['media_type'] === 'image' ? $uploadedMedia[0]['file'] : null;
                // Find first image for cover
                foreach ($uploadedMedia as $m) {
                    if ($m['media_type'] === 'image') { $coverImage = $m['file']; break; }
                }
                $stmt = db()->prepare("INSERT INTO gallery_albums (title, category, description, cover_image) VALUES (?,?,?,?)");
                $stmt->execute([$title, $category, $description ?: null, $coverImage]);
                $albumId = (int) db()->lastInsertId();

                $ins = db()->prepare("INSERT INTO gallery (album_id, title, image_path, media_type, category) VALUES (?,?,?,?,?)");
                foreach ($uploadedMedia as $m) {
                    $ins->execute([$albumId, $title, $m['file'], $m['media_type'], $category]);
                }
                db()->commit();
                $photos = count(array_filter($uploadedMedia, fn($m) => $m['media_type'] === 'image'));
                $videos = count(array_filter($uploadedMedia, fn($m) => $m['media_type'] === 'video'));
                $summary = [];
                if ($photos) $summary[] = "$photos photo(s)";
                if ($videos) $summary[] = "$videos video(s)";
                flash('success', 'Album created with ' . implode(' and ', $summary) . '.');
                header('Location: ' . url('admin/gallery.php'));
                exit;
            } catch (PDOException $e) {
                if (db()->inTransaction()) db()->rollBack();
                foreach ($uploadedMedia as $m) $unlinkUpload('gallery', $m['file']);
                error_log('Gallery create_album: ' . $e->getMessage());
                $errors['form'] = 'Could not save the album. Please try again.';
            }
        }

    } elseif ($action === 'add_photos') {
        $albumId = (int) ($_POST['album_id'] ?? 0);
        if ($albumId <= 0) { flash('error', 'Invalid album.'); header('Location: ' . url('admin/gallery.php')); exit; }

        if (empty($_FILES['images']['name'][0])) {
            flash('error', 'Please select at least one file.');
            header('Location: ' . url('admin/gallery.php?id=' . $albumId)); exit;
        }

        $addedMedia = process_media_uploads($_FILES['images'], $unlinkUpload);
        if (isset($addedMedia['error'])) {
            flash('error', $addedMedia['error']);
            header('Location: ' . url('admin/gallery.php?id=' . $albumId)); exit;
        }

        if (!empty($addedMedia)) {
            try {
                $albumRow = db()->prepare("SELECT * FROM gallery_albums WHERE id=?");
                $albumRow->execute([$albumId]);
                $albumData = $albumRow->fetch();
                db()->beginTransaction();
                $ins = db()->prepare("INSERT INTO gallery (album_id, title, image_path, media_type, category) VALUES (?,?,?,?,?)");
                foreach ($addedMedia as $m) {
                    $ins->execute([$albumId, $albumData['title'] ?? '', $m['file'], $m['media_type'], $albumData['category'] ?? 'general']);
                }
                // Update cover with first image if album had none
                if (empty($albumData['cover_image'])) {
                    foreach ($addedMedia as $m) {
                        if ($m['media_type'] === 'image') {
                            db()->prepare("UPDATE gallery_albums SET cover_image=? WHERE id=?")->execute([$m['file'], $albumId]);
                            break;
                        }
                    }
                }
                db()->commit();
                $photos = count(array_filter($addedMedia, fn($m) => $m['media_type'] === 'image'));
                $videos = count(array_filter($addedMedia, fn($m) => $m['media_type'] === 'video'));
                $summary = [];
                if ($photos) $summary[] = "$photos photo(s)";
                if ($videos) $summary[] = "$videos video(s)";
                flash('success', implode(' and ', $summary) . ' added.');
            } catch (PDOException $e) {
                if (db()->inTransaction()) db()->rollBack();
                foreach ($addedMedia as $m) $unlinkUpload('gallery', $m['file']);
                error_log('Gallery add_photos: ' . $e->getMessage());
                flash('error', 'Could not add media.');
            }
        }
        header('Location: ' . url('admin/gallery.php?id=' . $albumId)); exit;

    } elseif ($action === 'update_album') {
        $id = (int) ($_POST['id'] ?? 0);
        if ($id <= 0) { flash('error', 'Invalid record.'); header('Location: ' . url('admin/gallery.php')); exit; }
        $title       = trim($_POST['title'] ?? '');
        $category    = trim($_POST['category'] ?? '') ?: 'general';
        $description = trim($_POST['description'] ?? '');
        if ($title === '') $errors['title'] = 'Album title is required.';
        if (empty($errors)) {
            try {
                db()->prepare("UPDATE gallery_albums SET title=?, category=?, description=? WHERE id=?")->execute([$title, $category, $description ?: null, $id]);
                db()->prepare("UPDATE gallery SET title=?, category=? WHERE album_id=?")->execute([$title, $category, $id]);
                flash('success', 'Album updated.');
                header('Location: ' . url('admin/gallery.php?id=' . $id)); exit;
            } catch (PDOException $e) {
                error_log('Gallery update_album: ' . $e->getMessage());
                $errors['form'] = 'Could not update album.';
            }
        }
        $editingId = $id;

    } elseif ($action === 'delete_photo') {
        $photoId = (int) ($_POST['photo_id'] ?? 0);
        $albumId = (int) ($_POST['album_id'] ?? 0);
        if ($photoId > 0) {
            try {
                $row = db()->prepare("SELECT image_path FROM gallery WHERE id=?");
                $row->execute([$photoId]);
                $photo = $row->fetch();
                db()->prepare("DELETE FROM gallery WHERE id=?")->execute([$photoId]);
                if ($photo) $unlinkUpload('gallery', $photo['image_path']);
                if ($albumId > 0) {
                    $fp = db()->prepare("SELECT image_path, media_type FROM gallery WHERE album_id=? ORDER BY id LIMIT 1");
                    $fp->execute([$albumId]); $fpRow = $fp->fetch();
                    $newCover = ($fpRow && $fpRow['media_type'] === 'image') ? $fpRow['image_path'] : null;
                    db()->prepare("UPDATE gallery_albums SET cover_image=? WHERE id=?")->execute([$newCover, $albumId]);
                }
                flash('success', 'Item deleted.');
            } catch (PDOException $e) {
                error_log('Gallery delete_photo: ' . $e->getMessage());
                flash('error', 'Could not delete item.');
            }
        }
        header('Location: ' . url('admin/gallery.php?id=' . $albumId)); exit;

    } elseif ($action === 'delete_album') {
        $id = (int) ($_POST['id'] ?? 0);
        if ($id <= 0) { flash('error', 'Invalid record.'); header('Location: ' . url('admin/gallery.php')); exit; }
        try {
            $photos = db()->prepare("SELECT image_path FROM gallery WHERE album_id=?");
            $photos->execute([$id]);
            $photoFiles = $photos->fetchAll();
            db()->prepare("DELETE FROM gallery_albums WHERE id=?")->execute([$id]);
            foreach ($photoFiles as $pf) $unlinkUpload('gallery', $pf['image_path']);
            flash('success', 'Album deleted.');
        } catch (PDOException $e) {
            error_log('Gallery delete_album: ' . $e->getMessage());
            flash('error', 'Could not delete album.');
        }
        header('Location: ' . url('admin/gallery.php')); exit;
    }
}

// ── Load edit target ───────────────────────────────────────────────────────
$idParam = isset($_GET['id']) ? (int) $_GET['id'] : 0;
if ($idParam > 0 && $editingId === 0) $editingId = $idParam;
$albumPhotos = [];
if ($editingId > 0) {
    try {
        $estmt = db()->prepare("SELECT * FROM gallery_albums WHERE id=?");
        $estmt->execute([$editingId]);
        $editRow = $estmt->fetch();
        if (!$editRow) {
            $editingId = 0;
        } else {
            $pstmt = db()->prepare("SELECT * FROM gallery WHERE album_id=? ORDER BY id ASC");
            $pstmt->execute([$editingId]);
            $albumPhotos = $pstmt->fetchAll();
        }
    } catch (PDOException $e) { error_log('Gallery edit load: ' . $e->getMessage()); $editingId = 0; }
}

// ── Load album list ────────────────────────────────────────────────────────
$albums = [];
try {
    $albums = db()->query(
        "SELECT a.*, COUNT(g.id) AS photo_count
         FROM gallery_albums a
         LEFT JOIN gallery g ON g.album_id = a.id
         GROUP BY a.id ORDER BY a.created_at DESC"
    )->fetchAll();
} catch (PDOException $e) { error_log('Gallery albums list: ' . $e->getMessage()); }

require __DIR__ . '/../includes/admin_header.php';
$success = flash('success');
$error   = flash('error');
$isEdit  = $editingId > 0 && $editRow;
?>
<div class="admin-content">
    <div class="admin-page-head">
        <h1><?= e(t('admin.gallery.title')) ?></h1>
    </div>

    <?php if ($success): ?><div class="alert alert--success"><?= e($success) ?></div><?php endif; ?>
    <?php if ($error): ?><div class="alert alert--error"><?= e($error) ?></div><?php endif; ?>
    <?php if (!empty($errors['form'])): ?><div class="alert alert--error"><?= e($errors['form']) ?></div><?php endif; ?>

    <div class="gallery-layout">

        <!-- ── Left: Form ── -->
        <div class="gallery-layout__form">

            <?php if ($isEdit): ?>
            <div class="form-card">
                <h2 class="mt-0"><?= e(t('admin.gallery.edit_album')) ?></h2>
                <p class="text-muted" style="margin-bottom:18px"><a href="<?= e(url('admin/gallery.php')) ?>"><?= e(t('admin.gallery.back')) ?></a></p>
                <form action="<?= e(url('admin/gallery.php')) ?>" method="post">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="update_album">
                    <input type="hidden" name="id" value="<?= (int) $editingId ?>">
                    <div class="form-group">
                        <label><?= e(t('admin.gallery.album_title')) ?> <span class="field-req">*</span></label>
                        <input type="text" name="title" value="<?= e($editRow['title'] ?? '') ?>" maxlength="255" placeholder="Album name" required>
                        <?php if (!empty($errors['title'])): ?><div class="field-error"><?= e($errors['title']) ?></div><?php endif; ?>
                    </div>
                    <div class="form-group">
                        <label><?= e(t('admin.gallery.category')) ?></label>
                        <input type="text" name="category" value="<?= e($editRow['category'] ?? 'general') ?>" maxlength="100" placeholder="e.g. events, outreach">
                    </div>
                    <div class="form-group">
                        <label><?= e(t('admin.gallery.description')) ?></label>
                        <textarea name="description" rows="3" placeholder="Optional description..."><?= e($editRow['description'] ?? '') ?></textarea>
                    </div>
                    <div class="form-actions">
                        <button type="submit" class="btn btn--primary"><?= e(t('admin.gallery.save_changes')) ?></button>
                    </div>
                </form>
            </div>

            <div class="form-card" style="margin-top:16px">
                <h3 style="margin:0 0 14px;font-size:1rem;font-weight:700;color:var(--primary)"><?= e(t('admin.gallery.add_more')) ?></h3>
                <form action="<?= e(url('admin/gallery.php')) ?>" method="post" enctype="multipart/form-data">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="add_photos">
                    <input type="hidden" name="album_id" value="<?= (int) $editingId ?>">
                    <div class="form-group">
                        <label><?= e(t('admin.gallery.photos_videos')) ?></label>
                        <input type="file" name="images[]" multiple
                               accept="image/jpeg,image/png,image/gif,image/webp,video/mp4,video/quicktime,video/webm,video/x-msvideo,.mp4,.mov,.webm,.avi"
                               id="morePhotos">
                        <div class="form-hint">Images: JPG PNG GIF WebP · Videos: MP4 MOV WebM AVI · Max 100 MB per video / 5 MB per image</div>
                        <div id="morePhotosPreview" class="upload-preview"></div>
                    </div>
                    <div class="form-actions">
                        <button type="submit" class="btn btn--primary">Upload</button>
                    </div>
                </form>
            </div>

            <?php else: ?>
            <div class="form-card">
                <h2 class="mt-0"><?= e(t('admin.gallery.create_album')) ?></h2>
                <p class="text-muted" style="margin-bottom:18px">Group photos and videos under one album.</p>
                <form action="<?= e(url('admin/gallery.php')) ?>" method="post" enctype="multipart/form-data">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="create_album">
                    <div class="form-group">
                        <label><?= e(t('admin.gallery.album_title')) ?> <span class="field-req">*</span></label>
                        <input type="text" name="title" value="<?= e(old('title')) ?>" maxlength="255" placeholder="e.g. Community Outreach 2024" required>
                        <?php if (!empty($errors['title'])): ?><div class="field-error"><?= e($errors['title']) ?></div><?php endif; ?>
                    </div>
                    <div class="form-group">
                        <label><?= e(t('admin.gallery.category')) ?></label>
                        <input type="text" name="category" value="<?= e(old('category', 'general')) ?>" maxlength="100" placeholder="e.g. events, outreach, worship">
                        <div class="form-hint">Defaults to "general" if left blank.</div>
                    </div>
                    <div class="form-group">
                        <label><?= e(t('admin.gallery.description')) ?></label>
                        <textarea name="description" rows="3" placeholder="Optional description..."><?= e(old('description')) ?></textarea>
                    </div>
                    <div class="form-group">
                        <label><?= e(t('admin.gallery.photos_videos')) ?> <span class="field-req">*</span></label>
                        <input type="file" name="images[]" id="photoInput" multiple required
                               accept="image/jpeg,image/png,image/gif,image/webp,video/mp4,video/quicktime,video/webm,video/x-msvideo,.mp4,.mov,.webm,.avi">
                        <div class="form-hint">Images: JPG PNG GIF WebP · Videos: MP4 MOV WebM AVI · Max 100 MB per video / 5 MB per image · Select multiple</div>
                        <?php if (!empty($errors['images'])): ?><div class="field-error"><?= e($errors['images']) ?></div><?php endif; ?>
                        <div id="photoPreview" class="upload-preview"></div>
                    </div>
                    <div class="form-actions">
                        <button type="submit" class="btn btn--primary">Create Album</button>
                    </div>
                </form>
            </div>
            <?php endif; ?>

        </div>

        <!-- ── Right: Albums / Media ── -->
        <div class="gallery-layout__list">

            <?php if ($isEdit): ?>
            <h2 style="font-size:1.1rem;font-weight:700;margin-bottom:16px;color:var(--primary)">
                <?= e($editRow['title']) ?> — <?= count($albumPhotos) ?> item(s)
            </h2>
            <?php if (empty($albumPhotos)): ?>
            <div class="empty-state"><?= e(t('admin.gallery.no_media')) ?></div>
            <?php else: ?>
            <div class="gallery-admin-grid">
                <?php foreach ($albumPhotos as $item):
                    $isVid = ($item['media_type'] ?? 'image') === 'video';
                ?>
                <div class="gallery-admin-card<?= $isVid ? ' gallery-admin-card--video' : '' ?>">
                    <div class="gallery-admin-card__img" <?= $isVid ? '' : "onclick=\"openImageModal('" . e(image_url('gallery', $item['image_path'])) . "')\"" ?>>
                        <?php if ($isVid): ?>
                        <video controls playsinline preload="auto"
                               style="width:100%;height:auto;max-height:160px;display:block;background:#111">
                            <source src="<?= e(image_url('gallery', $item['image_path'])) ?>" type="video/<?= e(strtolower(pathinfo($item['image_path'], PATHINFO_EXTENSION))) ?>">
                        </video>
                        <div class="gallery-video-badge">▶ Video</div>
                        <?php else: ?>
                        <img src="<?= e(image_url('gallery', $item['image_path'])) ?>" alt="">
                        <div class="gallery-admin-card__overlay">🔍</div>
                        <?php endif; ?>
                    </div>
                    <div class="gallery-admin-card__body">
                        <div class="gallery-admin-card__meta">
                            <span class="pill pill--<?= $isVid ? 'info' : 'active' ?>"><?= $isVid ? 'Video' : 'Photo' ?></span>
                        </div>
                        <div class="gallery-admin-card__actions">
                            <form method="post" action="<?= e(url('admin/gallery.php')) ?>" style="width:100%">
                                <?= csrf_field() ?>
                                <input type="hidden" name="action" value="delete_photo">
                                <input type="hidden" name="photo_id" value="<?= (int) $item['id'] ?>">
                                <input type="hidden" name="album_id" value="<?= (int) $editingId ?>">
                                <button type="submit" class="btn btn--danger btn--sm" style="width:100%" data-confirm="Delete this item?"><?= e(t('admin.delete')) ?></button>
                            </form>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

            <?php else: ?>
            <h2 style="font-size:1.1rem;font-weight:700;margin-bottom:16px;color:var(--primary)">
                <?= e(t('admin.gallery.all_albums')) ?> (<?= count($albums) ?>)
            </h2>
            <?php if (empty($albums)): ?>
            <div class="empty-state"><?= e(t('admin.gallery.no_albums')) ?></div>
            <?php else: ?>
            <div class="gallery-admin-grid">
                <?php foreach ($albums as $album): ?>
                <div class="gallery-admin-card">
                    <div class="gallery-admin-card__img" onclick="location.href='<?= e(url('admin/gallery.php?id=' . (int) $album['id'])) ?>'">
                        <?php if (!empty($album['cover_image'])): ?>
                        <img src="<?= e(image_url('gallery', $album['cover_image'])) ?>" alt="<?= e($album['title']) ?>">
                        <?php else: ?>
                        <div style="width:100%;height:100%;background:linear-gradient(135deg,var(--primary),var(--accent));display:flex;align-items:center;justify-content:center;font-size:2rem;color:rgba(255,255,255,.3)">📷</div>
                        <?php endif; ?>
                        <div class="gallery-admin-card__overlay">Open Album</div>
                    </div>
                    <div class="gallery-admin-card__body">
                        <div class="gallery-admin-card__title"><?= e($album['title']) ?></div>
                        <div class="gallery-admin-card__meta">
                            <span class="pill pill--info"><?= e($album['category']) ?></span>
                            <span class="text-muted" style="font-size:0.78rem"><?= (int)$album['photo_count'] ?> <?= e(t('admin.gallery.items')) ?></span>
                        </div>
                        <div class="gallery-admin-card__actions">
                            <a class="btn btn--ghost btn--sm" href="<?= e(url('admin/gallery.php?id=' . (int) $album['id'])) ?>"><?= e(t('admin.edit')) ?></a>
                            <form method="post" action="<?= e(url('admin/gallery.php')) ?>" style="display:inline">
                                <?= csrf_field() ?>
                                <input type="hidden" name="action" value="delete_album">
                                <input type="hidden" name="id" value="<?= (int) $album['id'] ?>">
                                <button type="submit" class="btn btn--danger btn--sm" data-confirm="Delete album '<?= e($album['title']) ?>' and all its media?"><?= e(t('admin.delete')) ?></button>
                            </form>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
            <?php endif; ?>

        </div>
    </div>
</div>

<!-- Image Modal -->
<div id="imageModal" class="image-modal">
    <div class="image-modal__overlay"></div>
    <div class="image-modal__content">
        <button class="image-modal__close" onclick="closeImageModal()">✕</button>
        <img id="modalImage" src="" alt="full size">
    </div>
</div>

<script>
function openImageModal(src) {
    document.getElementById('modalImage').src = src;
    document.getElementById('imageModal').classList.add('active');
    document.body.style.overflow = 'hidden';
}
function closeImageModal() {
    document.getElementById('imageModal').classList.remove('active');
    document.body.style.overflow = '';
}
document.addEventListener('DOMContentLoaded', function () {
    document.querySelector('.image-modal__overlay').addEventListener('click', closeImageModal);
});
document.addEventListener('keydown', function (e) { if (e.key === 'Escape') closeImageModal(); });

// File preview with video support
function setupPreview(inputId, previewId) {
    const input = document.getElementById(inputId);
    const preview = document.getElementById(previewId);
    if (!input || !preview) return;
    input.addEventListener('change', function () {
        preview.innerHTML = '';
        const files = Array.from(this.files);
        if (!files.length) return;
        files.forEach(function (file) {
            const isVideo = file.type.startsWith('video/');
            if (isVideo) {
                const wrap = document.createElement('div');
                wrap.className = 'upload-preview__thumb upload-preview__thumb--video';
                wrap.innerHTML = '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="5 3 19 12 5 21 5 3"/></svg><span>' + file.name.substring(0, 16) + '</span>';
                preview.appendChild(wrap);
            } else {
                const reader = new FileReader();
                reader.onload = function (e) {
                    const img = document.createElement('img');
                    img.src = e.target.result;
                    img.className = 'upload-preview__thumb';
                    preview.appendChild(img);
                };
                reader.readAsDataURL(file);
            }
        });
        const count = document.createElement('div');
        count.className = 'upload-preview__count';
        count.textContent = files.length + ' file(s) selected';
        preview.appendChild(count);
    });
}
setupPreview('photoInput', 'photoPreview');
setupPreview('morePhotos', 'morePhotosPreview');
</script>

<?php require __DIR__ . '/../includes/footer.php'; ?>
