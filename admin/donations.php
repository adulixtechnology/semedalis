<?php
/**
 * Admin — Donations management.
 *
 * List all donations ORDER BY created_at DESC. View screenshot (admin-only).
 * Change status (pending/verified/rejected) and update admin notes. Delete
 * (removes screenshot file + DB row). Donor PII is never exposed publicly.
 * Every POST handler calls verify_csrf(); every id is cast to int and rejected
 * if <= 0 before any query.
 */
require __DIR__ . '/../config/config.php';
$adminTitle = 'Donations';

$unlinkUpload = function (string $type, $file): void {
    if ($file !== '' && $file !== null && strpos((string) $file, '/') === false) {
        @unlink(dirname(__DIR__) . '/backend/uploads/' . $type . '/' . $file);
    }
};

$errors = [];

// --- POST handlers ----------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action = $_POST['action'] ?? '';

    if ($action === 'update_status') {
        $id = (int) ($_POST['id'] ?? 0);
        if ($id <= 0) {
            flash('error', 'Invalid record.');
            header('Location: ' . url('admin/donations.php'));
            exit;
        }

        $allowedStatuses = ['pending', 'verified', 'rejected'];
        $status = $_POST['status'] ?? '';
        if (!in_array($status, $allowedStatuses, true)) {
            $errors['status'] = 'Please select a valid status.';
        }

        $notes = trim($_POST['notes'] ?? '');
        if ($notes !== '' && mb_strlen($notes) > 65000) {
            $errors['notes'] = 'Notes must be 65000 characters or fewer.';
        }

        if (empty($errors)) {
            try {
                $stmt = db()->prepare("UPDATE donations SET status=?, notes=? WHERE id=?");
                $stmt->execute([$status, $notes !== '' ? $notes : null, $id]);
                flash('success', 'Donation updated.');
                header('Location: ' . url('admin/donations.php'));
                exit;
            } catch (PDOException $e) {
                error_log('Donations update_status: ' . $e->getMessage());
                $errors['form'] = 'Could not update the donation. Please try again.';
            }
        }

    } elseif ($action === 'delete') {
        $id = (int) ($_POST['id'] ?? 0);
        if ($id <= 0) {
            flash('error', 'Invalid record.');
            header('Location: ' . url('admin/donations.php'));
            exit;
        }
        try {
            $rowStmt = db()->prepare("SELECT screenshot FROM donations WHERE id=?");
            $rowStmt->execute([$id]);
            $existing = $rowStmt->fetch();

            $del = db()->prepare("DELETE FROM donations WHERE id=?");
            $del->execute([$id]);
            if ($existing) {
                $unlinkUpload('donations', $existing['screenshot']);
            }
            flash('success', 'Donation deleted.');
        } catch (PDOException $e) {
            error_log('Donations delete: ' . $e->getMessage());
            flash('error', 'Could not delete the donation.');
        }
        header('Location: ' . url('admin/donations.php'));
        exit;
    }
}

// --- Load the list ----------------------------------------------------------
$donations = [];
try {
    $donations = db()->query("SELECT * FROM donations ORDER BY created_at DESC")->fetchAll();
} catch (PDOException $e) {
    error_log('Donations list: ' . $e->getMessage());
}

require __DIR__ . '/../includes/admin_header.php';

$success = flash('success');
$error   = flash('error');
?>
<div class="admin-content">
    <div class="admin-page-head">
        <h1><?= e(t('admin.don.title')) ?></h1>
    </div>

    <?php if ($success): ?><div class="alert alert--success"><?= e($success) ?></div><?php endif; ?>
    <?php if ($error): ?><div class="alert alert--error"><?= e($error) ?></div><?php endif; ?>
    <?php if (!empty($errors['form'])): ?><div class="alert alert--error"><?= e($errors['form']) ?></div><?php endif; ?>

    <?php if (empty($donations)): ?>
    <div class="empty-state"><?= e(t('admin.don.no_donations')) ?></div>
    <?php else: ?>
    <div class="table-wrap">
        <table class="data">
            <thead>
                <tr>
                    <th>#</th>
                    <th><?= e(t('admin.don.donor')) ?></th>
                    <th><?= e(t('admin.don.amount')) ?></th>
                    <th><?= e(t('admin.don.reference')) ?></th>
                    <th><?= e(t('admin.don.screenshot')) ?></th>
                    <th><?= e(t('admin.status')) ?></th>
                    <th><?= e(t('admin.don.notes')) ?></th>
                    <th><?= e(t('admin.don.submitted')) ?></th>
                    <th><?= e(t('admin.actions')) ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($donations as $d): ?>
                <tr>
                    <td><?= (int) $d['id'] ?></td>
                    <td>
                        <?= e($d['donor_name'] ?? '') ?>
                        <?php if (!empty($d['email'])): ?><br><span class="text-muted"><?= e($d['email']) ?></span><?php endif; ?>
                        <?php if (!empty($d['phone'])): ?><br><span class="text-muted"><?= e($d['phone']) ?></span><?php endif; ?>
                    </td>
                    <td><?= $d['amount'] !== null ? e(number_format((float) $d['amount'], 2)) : '<span class="text-muted">—</span>' ?></td>
                    <td><?= !empty($d['bank_reference']) ? e($d['bank_reference']) : '<span class="text-muted">—</span>' ?></td>
                    <td>
                        <?php if (!empty($d['screenshot'])): ?>
                        <img class="donation-thumb" src="<?= e(image_url('donations', $d['screenshot'])) ?>" alt="screenshot" onclick="openImageModal('<?= e(image_url('donations', $d['screenshot'])) ?>')" style="cursor:pointer">
                        <?php else: ?><span class="text-muted">—</span><?php endif; ?>
                    </td>
                    <td>
                        <?php $st = (string) ($d['status'] ?? 'pending'); ?>
                        <span class="pill pill--<?= e($st) ?>"><?= e($st) ?></span>
                    </td>
                    <td><?= !empty($d['notes']) ? e(mb_strimwidth((string) $d['notes'], 0, 80, '...')) : '<span class="text-muted">—</span>' ?></td>
                    <td><?= e(format_date($d['created_at'] ?? '')) ?></td>
                    <td class="row-actions">
                        <!-- Status + notes form -->
                        <details>
                            <summary class="btn btn--ghost btn--sm" style="cursor:pointer"><?= e(t('admin.don.update_btn')) ?></summary>
                            <div style="padding:12px 0">
                                <?php if (!empty($errors['status']) || !empty($errors['notes'])): ?>
                                <div class="field-error"><?= e($errors['status'] ?? $errors['notes'] ?? '') ?></div>
                                <?php endif; ?>
                                <form method="post" action="<?= e(url('admin/donations.php')) ?>">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="action" value="update_status">
                                    <input type="hidden" name="id" value="<?= (int) $d['id'] ?>">
                                    <div class="form-row">
                                        <label><?= e(t('admin.status')) ?></label>
                                        <select name="status">
                                            <option value="pending"<?= $st === 'pending' ? ' selected' : '' ?>><?= ucfirst(t('admin.don.pending')) ?></option>
                                            <option value="verified"<?= $st === 'verified' ? ' selected' : '' ?>><?= ucfirst(t('admin.don.verified')) ?></option>
                                            <option value="rejected"<?= $st === 'rejected' ? ' selected' : '' ?>><?= ucfirst(t('admin.don.rejected')) ?></option>
                                        </select>
                                    </div>
                                    <div class="form-row">
                                        <label><?= e(t('admin.don.notes')) ?></label>
                                        <textarea name="notes" rows="3" style="width:100%"><?= e((string) ($d['notes'] ?? '')) ?></textarea>
                                    </div>
                                    <button type="submit" class="btn btn--sm"><?= e(t('admin.don.save')) ?></button>
                                </form>
                            </div>
                        </details>
                        <form method="post" action="<?= e(url('admin/donations.php')) ?>" style="display:inline">
                            <?= csrf_field() ?>
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="id" value="<?= (int) $d['id'] ?>">
                            <button type="submit" class="btn btn--danger btn--sm" data-confirm="Delete this donation record?"><?= e(t('admin.delete')) ?></button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</div>

<!-- Image Modal -->
<div id="imageModal" class="image-modal">
    <div class="image-modal__overlay"></div>
    <div class="image-modal__content">
        <button class="image-modal__close" onclick="closeImageModal()">✕</button>
        <img id="modalImage" src="" alt="full size screenshot">
    </div>
</div>

<script>
function openImageModal(src) {
    const modal = document.getElementById('imageModal');
    const img = document.getElementById('modalImage');
    img.src = src;
    modal.classList.add('active');
    document.body.style.overflow = 'hidden';
}

function closeImageModal() {
    const modal = document.getElementById('imageModal');
    modal.classList.remove('active');
    document.body.style.overflow = '';
}

// Close modal when clicking the overlay
document.addEventListener('DOMContentLoaded', function() {
    const overlay = document.querySelector('.image-modal__overlay');
    if (overlay) {
        overlay.addEventListener('click', closeImageModal);
    }
});

// Close modal with Escape key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeImageModal();
    }
});
</script>

<?php require __DIR__ . '/../includes/footer.php'; ?>
