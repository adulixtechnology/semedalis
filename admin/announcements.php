<?php
/**
 * Admin — Announcements CRUD.
 *
 * List ORDER BY created_at DESC. Create, edit, delete. Every POST handler calls
 * verify_csrf(); every id is cast to int and rejected if <= 0 before any query.
 * Uploads use the single-owner validate_upload() (fix #3) with type
 * 'announcements' (fix #2 — images go to backend/uploads/announcements/).
 */
require __DIR__ . '/../config/config.php';
$adminTitle = 'Announcements';

$unlinkUpload = function (string $type, $file): void {
    if ($file !== '' && $file !== null && strpos((string) $file, '/') === false) {
        @unlink(dirname(__DIR__) . '/backend/uploads/' . $type . '/' . $file);
    }
};

$errors = [];
$editingId = 0;
$editRow = null;

// --- POST handlers ----------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action = $_POST['action'] ?? '';

    if ($action === 'create') {
        $title    = trim($_POST['title'] ?? '');
        $content  = trim($_POST['content'] ?? '');
        $lang     = ($_POST['language'] ?? 'en') === 'am' ? 'am' : 'en';
        $status   = ($_POST['status'] ?? 'active') === 'inactive' ? 'inactive' : 'active';
        set_old('title', $title);
        set_old('content', $content);
        set_old('language', $lang);
        set_old('status', $status);

        if ($title === '') {
            $errors['title'] = 'Title is required.';
        } elseif (mb_strlen($title) > 255) {
            $errors['title'] = 'Title must be 255 characters or fewer.';
        }
        if ($content !== '' && mb_strlen($content) > 65000) {
            $errors['content'] = 'Content must be 65000 characters or fewer.';
        }

        $image = null;
        if (empty($errors)) {
            $upload = validate_upload('image', 'announcements'); // optional
            if ($upload !== '' && ctype_digit($upload[0] ?? '')) {
                $image = $upload;
            } elseif ($upload !== '' && !ctype_digit($upload[0] ?? '')) {
                $errors['image'] = $upload;
            }
        }

        if (empty($errors)) {
            try {
                $stmt = db()->prepare(
                    "INSERT INTO announcements (title, content, language, image_path, status) VALUES (?, ?, ?, ?, ?)"
                );
                $stmt->execute([
                    $title,
                    $content !== '' ? $content : null,
                    $lang,
                    $image,       // null if no upload
                    $status,
                ]);
                flash('success', 'Announcement created.');
                header('Location: ' . url('admin/announcements.php'));
                exit;
            } catch (PDOException $e) {
                if ($image !== null) { $unlinkUpload('announcements', $image); }
                error_log('Announcements create: ' . $e->getMessage());
                $errors['form'] = 'Could not save the announcement. Please try again.';
            }
        }

    } elseif ($action === 'update') {
        $id = (int) ($_POST['id'] ?? 0);
        if ($id <= 0) {
            flash('error', 'Invalid record.');
            header('Location: ' . url('admin/announcements.php'));
            exit;
        }
        $editingId = $id;

        $title    = trim($_POST['title'] ?? '');
        $content  = trim($_POST['content'] ?? '');
        $lang     = ($_POST['language'] ?? 'en') === 'am' ? 'am' : 'en';
        $status   = ($_POST['status'] ?? 'active') === 'inactive' ? 'inactive' : 'active';
        set_old('title', $title);
        set_old('content', $content);
        set_old('language', $lang);
        set_old('status', $status);

        if ($title === '') {
            $errors['title'] = 'Title is required.';
        } elseif (mb_strlen($title) > 255) {
            $errors['title'] = 'Title must be 255 characters or fewer.';
        }
        if ($content !== '' && mb_strlen($content) > 65000) {
            $errors['content'] = 'Content must be 65000 characters or fewer.';
        }

        $newImage = null;
        if (empty($errors)) {
            $upload = validate_upload('image', 'announcements'); // optional on edit
            if ($upload !== '' && ctype_digit($upload[0] ?? '')) {
                $newImage = $upload;
            } elseif ($upload !== '' && !ctype_digit($upload[0] ?? '')) {
                $errors['image'] = $upload;
            }
        }

        if (empty($errors)) {
            try {
                $oldStmt = db()->prepare("SELECT image_path FROM announcements WHERE id=?");
                $oldStmt->execute([$id]);
                $oldRow = $oldStmt->fetch();

                db()->beginTransaction();
                $stmt = db()->prepare(
                    "UPDATE announcements SET title=?, content=?, language=?, status=? WHERE id=?"
                );
                $stmt->execute([
                    $title,
                    $content !== '' ? $content : null,
                    $lang,
                    $status,
                    $id,
                ]);
                if ($newImage !== null) {
                    $up = db()->prepare("UPDATE announcements SET image_path=? WHERE id=?");
                    $up->execute([$newImage, $id]);
                }
                db()->commit();

                if ($newImage !== null && $oldRow && $oldRow['image_path'] !== '' && $oldRow['image_path'] !== null) {
                    $unlinkUpload('announcements', $oldRow['image_path']);
                }
                flash('success', 'Announcement updated.');
                header('Location: ' . url('admin/announcements.php'));
                exit;
            } catch (PDOException $e) {
                if (db()->inTransaction()) { db()->rollBack(); }
                if ($newImage !== null) { $unlinkUpload('announcements', $newImage); }
                error_log('Announcements update: ' . $e->getMessage());
                $errors['form'] = 'Could not update the announcement. Please try again.';
            }
        }

    } elseif ($action === 'delete') {
        $id = (int) ($_POST['id'] ?? 0);
        if ($id <= 0) {
            flash('error', 'Invalid record.');
            header('Location: ' . url('admin/announcements.php'));
            exit;
        }
        try {
            $rowStmt = db()->prepare("SELECT image_path FROM announcements WHERE id=?");
            $rowStmt->execute([$id]);
            $existing = $rowStmt->fetch();

            $del = db()->prepare("DELETE FROM announcements WHERE id=?");
            $del->execute([$id]);
            if ($existing) {
                $unlinkUpload('announcements', $existing['image_path']);
            }
            flash('success', 'Announcement deleted.');
        } catch (PDOException $e) {
            error_log('Announcements delete: ' . $e->getMessage());
            flash('error', 'Could not delete the announcement.');
        }
        header('Location: ' . url('admin/announcements.php'));
        exit;
    }
}

// --- Load the edit target (GET ?id=) ---------------------------------------
$idParam = isset($_GET['id']) ? (int) $_GET['id'] : 0;
if ($idParam > 0 && $editingId === 0) {
    $editingId = $idParam;
}
if ($editingId > 0) {
    try {
        $estmt = db()->prepare("SELECT * FROM announcements WHERE id=?");
        $estmt->execute([$editingId]);
        $editRow = $estmt->fetch();
        if (!$editRow) {
            $editingId = 0;
        }
    } catch (PDOException $e) {
        error_log('Announcements edit load: ' . $e->getMessage());
        $editingId = 0;
    }
}

// --- Load the list ----------------------------------------------------------
$announcements = [];
try {
    $announcements = db()->query("SELECT * FROM announcements ORDER BY created_at DESC")->fetchAll();
} catch (PDOException $e) {
    error_log('Announcements list: ' . $e->getMessage());
}

require __DIR__ . '/../includes/admin_header.php';

$success = flash('success');
$error   = flash('error');

$isEdit  = $editingId > 0 && $editRow;
$formAct = $isEdit ? 'update' : 'create';
$curLang = old('language', $isEdit ? (string) ($editRow['language'] ?? 'en') : 'en');
$curStat = old('status', $isEdit ? (string) ($editRow['status'] ?? 'active') : 'active');
?>
<div class="admin-content">
    <div class="admin-page-head">
        <h1><?= e(t('admin.ann.title')) ?></h1>
    </div>

    <?php if ($success): ?><div class="alert alert--success"><?= e($success) ?></div><?php endif; ?>
    <?php if ($error): ?><div class="alert alert--error"><?= e($error) ?></div><?php endif; ?>
    <?php if (!empty($errors['form'])): ?><div class="alert alert--error"><?= e($errors['form']) ?></div><?php endif; ?>

    <div class="announcements-layout">
        <!-- Left Column: Form -->
        <div class="announcements-layout__form">
            <div class="form-card">
                <h2 class="mt-0"><?= $isEdit ? e(t('admin.ann.edit')) : e(t('admin.ann.new')) ?></h2>
                <?php if ($isEdit): ?>
                <p class="text-muted" style="margin-bottom: 20px;"><a href="<?= e(url('admin/announcements.php')) ?>"><?= e(t('admin.ann.cancel_edit')) ?></a> &middot; <a href="<?= e(url('admin/index.php')) ?>">Back to dashboard</a></p>
                <?php endif; ?>

                <form action="<?= e(url('admin/announcements.php')) ?>" method="post" enctype="multipart/form-data">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="<?= e($formAct) ?>">
                    <?php if ($isEdit): ?><input type="hidden" name="id" value="<?= (int) $editingId ?>"><?php endif; ?>

                    <div class="form-group">
                        <label for="a_title"><?= e(t('admin.ann.ann_title')) ?> <span class="field-req">*</span></label>
                        <input type="text" id="a_title" name="title" value="<?= e(old('title', $isEdit ? (string) ($editRow['title'] ?? '') : '')) ?>" maxlength="255" placeholder="Enter announcement title" required>
                        <?php if (!empty($errors['title'])): ?><div class="field-error"><?= e($errors['title']) ?></div><?php endif; ?>
                    </div>

                    <div class="form-group">
                        <label for="a_content"><?= e(t('admin.ann.content')) ?></label>
                        <textarea id="a_content" name="content" rows="6" placeholder="Enter announcement details..." style="min-height: 120px;"><?= e(old('content', $isEdit ? (string) ($editRow['content'] ?? '') : '')) ?></textarea>
                        <?php if (!empty($errors['content'])): ?><div class="field-error"><?= e($errors['content']) ?></div><?php endif; ?>
                    </div>

                    <div class="form-row-inline">
                        <div class="form-group">
                            <label for="a_language"><?= e(t('admin.ann.language')) ?></label>
                            <select id="a_language" name="language">
                                <option value="en"<?= $curLang === 'en' ? ' selected' : '' ?>><?= e(t('admin.ann.lang_en')) ?></option>
                                <option value="am"<?= $curLang === 'am' ? ' selected' : '' ?>><?= e(t('admin.ann.lang_am')) ?></option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="a_status"><?= e(t('admin.status')) ?></label>
                            <select id="a_status" name="status">
                                <option value="active"<?= $curStat === 'active' ? ' selected' : '' ?>><?= e(t('admin.ann.status_active')) ?></option>
                                <option value="inactive"<?= $curStat === 'inactive' ? ' selected' : '' ?>><?= e(t('admin.ann.status_inactive')) ?></option>
                            </select>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="a_image"><?= e(t('admin.ann.image')) ?></label>
                        <?php if ($isEdit && !empty($editRow['image_path'])): ?>
                        <div style="margin-bottom: 12px;">
                            <img class="thumb" src="<?= e(image_url('announcements', $editRow['image_path'])) ?>" alt="current" style="width: 100px; height: 70px; border-radius: var(--radius-sm); border: 1px solid var(--border);">
                        </div>
                        <?php endif; ?>
                        <input type="file" id="a_image" name="image" accept="image/jpeg,image/png,image/gif,image/webp">
                        <div class="form-hint">JPG, PNG, GIF or WebP. Max 5 MB.<?= $isEdit ? ' Leave blank to keep the current image.' : '' ?></div>
                        <?php if (!empty($errors['image'])): ?><div class="field-error"><?= e($errors['image']) ?></div><?php endif; ?>
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="btn btn--primary"><?= $isEdit ? e(t('admin.ann.update_btn')) : e(t('admin.ann.publish_btn')) ?></button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Right Column: List -->
        <div class="announcements-layout__list">
            <h2 style="font-size: 1.1rem; font-weight: 700; margin-bottom: 16px; color: var(--primary);"><?= e(t('admin.ann.all')) ?> (<?= count($announcements) ?>)</h2>
            
            <div class="table-wrap">
                <table class="data">
                    <thead>
                        <tr>
                            <th><?= e(t('admin.ann.ann_title')) ?></th>
                            <th><?= e(t('admin.ann.language')) ?></th>
                            <th><?= e(t('admin.status')) ?></th>
                            <th><?= e(t('admin.ann.published')) ?></th>
                            <th><?= e(t('admin.actions')) ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($announcements)): ?>
                        <tr><td colspan="5"><div class="empty-state"><?= e(t('admin.ann.no_ann')) ?></div></td></tr>
                        <?php else: foreach ($announcements as $ann): ?>
                        <tr>
                            <td><strong><?= e($ann['title'] ?? '') ?></strong></td>
                            <td><?= e(strtoupper((string) ($ann['language'] ?? 'en'))) ?></td>
                            <td>
                                <?php $st = (string) ($ann['status'] ?? 'active'); ?>
                                <span class="pill pill--<?= $st === 'active' ? 'active' : 'inactive' ?>"><?= e($st) ?></span>
                            </td>
                            <td><span class="text-muted"><?= e(format_date($ann['created_at'] ?? '')) ?></span></td>
                            <td class="row-actions">
                                <a class="btn btn--ghost btn--sm" href="<?= e(url('admin/announcements.php?id=' . (int) $ann['id'])) ?>"><?= e(t('admin.edit')) ?></a>
                                <form method="post" action="<?= e(url('admin/announcements.php')) ?>" style="display:inline">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?= (int) $ann['id'] ?>">
                                    <button type="submit" class="btn btn--danger btn--sm" data-confirm="Delete this announcement?"><?= e(t('admin.delete')) ?></button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
