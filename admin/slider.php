<?php
/**
 * Admin — Slider images (image upload only, no title/subtitle/order/status fields).
 */
require __DIR__ . '/../config/config.php';
$adminTitle = 'Slider Images';

$unlinkUpload = function (string $type, $file): void {
    if ($file !== '' && $file !== null && strpos((string)$file, '/') === false) {
        @unlink(dirname(__DIR__) . '/backend/uploads/' . $type . '/' . $file);
    }
};

$errors = [];

/* ── POST handlers ──────────────────────────────────────────────────── */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action = $_POST['action'] ?? '';

    if ($action === 'create') {
        $image = null;
        $upload = validate_upload('image', 'slider');
        if ($upload === '') {
            $errors['image'] = 'Please select an image to upload.';
        } elseif ($upload[0] >= '0' && $upload[0] <= '9') {
            $image = $upload;
        } else {
            $errors['image'] = $upload;
        }

        if (empty($errors) && $image !== null) {
            try {
                // get max order_index so new image goes last
                $maxOi = (int)(db()->query("SELECT COALESCE(MAX(order_index),0) FROM slider_images")->fetchColumn());
                db()->prepare(
                    "INSERT INTO slider_images (image_path, order_index, status) VALUES (?, ?, 'active')"
                )->execute([$image, $maxOi + 1]);
                flash('success', 'Slider image added.');
                header('Location: ' . url('admin/slider.php')); exit;
            } catch (PDOException $e) {
                $unlinkUpload('slider', $image);
                error_log('Slider create: ' . $e->getMessage());
                $errors['form'] = 'Could not save the image. Please try again.';
            }
        }

    } elseif ($action === 'toggle') {
        $id     = (int)($_POST['id'] ?? 0);
        $status = ($_POST['status'] ?? 'active') === 'inactive' ? 'inactive' : 'active';
        if ($id > 0) {
            try {
                db()->prepare("UPDATE slider_images SET status=? WHERE id=?")->execute([$status, $id]);
                flash('success', 'Status updated.');
            } catch (PDOException $e) { error_log($e->getMessage()); flash('error', 'Could not update status.'); }
        }
        header('Location: ' . url('admin/slider.php')); exit;

    } elseif ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id > 0) {
            try {
                $r = db()->prepare("SELECT image_path FROM slider_images WHERE id=?");
                $r->execute([$id]); $r = $r->fetch();
                db()->prepare("DELETE FROM slider_images WHERE id=?")->execute([$id]);
                if ($r) $unlinkUpload('slider', $r['image_path']);
                flash('success', 'Slider image deleted.');
            } catch (PDOException $e) { error_log($e->getMessage()); flash('error', 'Could not delete.'); }
        }
        header('Location: ' . url('admin/slider.php')); exit;
    }
}

/* ── Load list ──────────────────────────────────────────────────────── */
$slides = [];
try {
    $slides = db()->query("SELECT * FROM slider_images ORDER BY order_index ASC, id ASC")->fetchAll();
} catch (PDOException $e) { error_log($e->getMessage()); }

require __DIR__ . '/../includes/admin_header.php';

$success  = flash('success');
$flashErr = flash('error');
?>
<div class="admin-content">

    <div class="admin-page-header">
        <h1 class="admin-page-title"><?= e(t('admin.slider.title')) ?></h1>
        <p class="admin-page-sub"><?= e(t('admin.slider.sub')) ?></p>
    </div>

    <?php if ($success): ?>
    <div class="alert alert--success"><?= e($success) ?></div>
    <?php endif; ?>
    <?php if ($flashErr): ?>
    <div class="alert alert--danger"><?= e($flashErr) ?></div>
    <?php endif; ?>
    <?php if (!empty($errors['form'])): ?>
    <div class="alert alert--danger"><?= e($errors['form']) ?></div>
    <?php endif; ?>

    <div class="slider-layout">

        <!-- Upload form -->
        <div class="slider-layout__form">
            <div class="admin-form">
                <div class="admin-form__head">
                    <h2><?= e(t('admin.slider.add')) ?></h2>
                </div>

                <form action="<?= e(url('admin/slider.php')) ?>" method="post" enctype="multipart/form-data">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="create">

                    <div class="form-group form-group--upload">
                        <label for="image">Image <span class="form-required">*</span></label>
                        <label class="file-drop" for="image">
                            <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                                <path d="M4 16v1a2 2 0 002 2h12a2 2 0 002-2v-1M12 12V4m0 0L8 8m4-4l4 4"/>
                            </svg>
                            <span class="file-drop__text">Click to choose or drag &amp; drop</span>
                            <span class="file-drop__hint">JPG, PNG, GIF or WebP &middot; Max 5 MB</span>
                            <input type="file" id="image" name="image"
                                   accept="image/jpeg,image/png,image/gif,image/webp" required>
                        </label>
                        <?php if (!empty($errors['image'])): ?>
                        <span class="field-error"><?= e($errors['image']) ?></span>
                        <?php endif; ?>
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="btn btn--primary"><?= e(t('admin.slider.upload_btn')) ?></button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Slider images list -->
        <div class="slider-layout__list">
            <h2 style="font-size:1.1rem;font-weight:700;margin-bottom:16px;color:var(--primary)">
                <?= e(t('admin.slider.all_images')) ?> (<?= count($slides) ?>)
            </h2>

            <?php if (empty($slides)): ?>
            <div class="empty-state"><?= e(t('admin.slider.no_images')) ?></div>
            <?php else: ?>
            <div class="slider-admin-grid">
                <?php foreach ($slides as $s):
                    $st = (string)($s['status'] ?? 'active');
                ?>
                <div class="slider-admin-card">
                    <div class="slider-admin-card__img">
                        <img src="<?= e(image_url('slider', $s['image_path'])) ?>" alt="Slide <?= (int)$s['id'] ?>">
                        <span class="slider-admin-card__status pill pill--<?= $st === 'active' ? 'active' : 'inactive' ?>"><?= e($st) ?></span>
                    </div>
                    <div class="slider-admin-card__actions">
                        <form method="post" action="<?= e(url('admin/slider.php')) ?>">
                            <?= csrf_field() ?>
                            <input type="hidden" name="action" value="toggle">
                            <input type="hidden" name="id"     value="<?= (int)$s['id'] ?>">
                            <input type="hidden" name="status" value="<?= $st === 'active' ? 'inactive' : 'active' ?>">
                            <button type="submit" class="btn btn--ghost btn--sm">
                                <?= $st === 'active' ? e(t('admin.slider.deactivate')) : e(t('admin.slider.activate')) ?>
                            </button>
                        </form>
                        <form method="post" action="<?= e(url('admin/slider.php')) ?>">
                            <?= csrf_field() ?>
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="id"     value="<?= (int)$s['id'] ?>">
                            <button type="submit" class="btn btn--danger btn--sm"
                                    data-confirm="Delete this slider image?"><?= e(t('admin.delete')) ?></button>
                        </form>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>

    </div>

</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
