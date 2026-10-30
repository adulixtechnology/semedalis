<?php
/**
 * Admin dashboard — counts, pending donations, recent activity.
 */
require __DIR__ . '/../config/config.php';
$adminTitle = 'Dashboard';

$counts = ['slider' => 0, 'gallery_albums' => 0, 'announcements' => 0,
           'donations' => 0, 'pending' => 0, 'users' => 0];
try {
    $counts['slider']         = (int) db()->query("SELECT COUNT(*) FROM slider_images")->fetchColumn();
    $counts['gallery_albums'] = (int) db()->query("SELECT COUNT(*) FROM gallery_albums")->fetchColumn();
    $counts['announcements']  = (int) db()->query("SELECT COUNT(*) FROM announcements WHERE status='active'")->fetchColumn();
    $counts['donations']      = (int) db()->query("SELECT COUNT(*) FROM donations")->fetchColumn();
    $counts['pending']        = (int) db()->query("SELECT COUNT(*) FROM donations WHERE status='pending'")->fetchColumn();
    $counts['users']          = (int) db()->query("SELECT COUNT(*) FROM users")->fetchColumn();
} catch (PDOException $e) { error_log('Dashboard counts: ' . $e->getMessage()); }

$recentDonations = [];
try {
    $recentDonations = db()->query(
        "SELECT donor_name, amount, status, created_at FROM donations ORDER BY created_at DESC LIMIT 6"
    )->fetchAll();
} catch (PDOException $e) { error_log('Dashboard donations: ' . $e->getMessage()); }

$recentAnnouncements = [];
try {
    $recentAnnouncements = db()->query(
        "SELECT title, status, created_at FROM announcements ORDER BY created_at DESC LIMIT 6"
    )->fetchAll();
} catch (PDOException $e) { error_log('Dashboard announcements: ' . $e->getMessage()); }

require __DIR__ . '/../includes/admin_header.php';
?>
<div class="admin-content dash-page">

    <!-- ── Header ── -->
    <div class="dash-head">
        <div class="dash-head__left">
            <h1 class="dash-head__title"><?= e(t('admin.sidebar.dashboard')) ?></h1>
            <p class="dash-head__sub"><?= e(t('admin.dash.welcome')) ?>, <strong><?= e($_SESSION['admin_username'] ?? 'Admin') ?></strong></p>
        </div>
        <div class="dash-head__date">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
            <?= date('l, F j, Y') ?>
        </div>
    </div>

    <!-- ── Stat cards ── -->
    <div class="dash-stats">

        <a class="dash-stat dash-stat--warning" href="<?= e(url('admin/donations.php')) ?>">
            <div class="dash-stat__icon">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
            </div>
            <div class="dash-stat__body">
                <div class="dash-stat__num"><?= $counts['pending'] ?></div>
                <div class="dash-stat__label"><?= e(t('admin.dash.pending_donations')) ?></div>
            </div>
            <div class="dash-stat__arrow">→</div>
        </a>

        <a class="dash-stat dash-stat--success" href="<?= e(url('admin/donations.php')) ?>">
            <div class="dash-stat__icon">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
            </div>
            <div class="dash-stat__body">
                <div class="dash-stat__num"><?= $counts['donations'] ?></div>
                <div class="dash-stat__label"><?= e(t('admin.dash.total_donations')) ?></div>
            </div>
            <div class="dash-stat__arrow">→</div>
        </a>

        <a class="dash-stat dash-stat--primary" href="<?= e(url('admin/announcements.php')) ?>">
            <div class="dash-stat__icon">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 2L11 13"/><path d="M22 2L15 22 11 13 2 9l20-7z"/></svg>
            </div>
            <div class="dash-stat__body">
                <div class="dash-stat__num"><?= $counts['announcements'] ?></div>
                <div class="dash-stat__label"><?= e(t('admin.dash.active_announcements')) ?></div>
            </div>
            <div class="dash-stat__arrow">→</div>
        </a>

        <a class="dash-stat dash-stat--accent" href="<?= e(url('admin/gallery.php')) ?>">
            <div class="dash-stat__icon">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>
            </div>
            <div class="dash-stat__body">
                <div class="dash-stat__num"><?= $counts['gallery_albums'] ?></div>
                <div class="dash-stat__label"><?= e(t('admin.dash.gallery_albums')) ?></div>
            </div>
            <div class="dash-stat__arrow">→</div>
        </a>

        <a class="dash-stat dash-stat--info" href="<?= e(url('admin/slider.php')) ?>">
            <div class="dash-stat__icon">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="7" width="20" height="15" rx="2"/><polyline points="17 2 12 7 7 2"/></svg>
            </div>
            <div class="dash-stat__body">
                <div class="dash-stat__num"><?= $counts['slider'] ?></div>
                <div class="dash-stat__label"><?= e(t('admin.dash.slider_images')) ?></div>
            </div>
            <div class="dash-stat__arrow">→</div>
        </a>

        <a class="dash-stat dash-stat--muted" href="<?= e(url('admin/users.php')) ?>">
            <div class="dash-stat__icon">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
            </div>
            <div class="dash-stat__body">
                <div class="dash-stat__num"><?= $counts['users'] ?></div>
                <div class="dash-stat__label"><?= e(t('admin.dash.total_users')) ?></div>
            </div>
            <div class="dash-stat__arrow">→</div>
        </a>

    </div>

    <!-- ── Main content: panels + quick actions ── -->
    <div class="dash-main">

        <!-- Recent Donations -->
        <div class="dash-panel">
            <div class="dash-panel__head">
                <h2><?= e(t('admin.dash.recent_donations')) ?></h2>
                <a href="<?= e(url('admin/donations.php')) ?>" class="dash-panel__link"><?= e(t('admin.dash.view_all')) ?></a>
            </div>
            <?php if (empty($recentDonations)): ?>
            <div class="dash-panel__empty"><?= e(t('admin.dash.no_donations')) ?></div>
            <?php else: ?>
            <table class="dash-table">
                <thead>
                    <tr><th><?= e(t('admin.dash.donor')) ?></th><th><?= e(t('admin.dash.amount')) ?></th><th><?= e(t('admin.date')) ?></th><th><?= e(t('admin.status')) ?></th></tr>
                </thead>
                <tbody>
                <?php foreach ($recentDonations as $d): $st = (string)($d['status'] ?? 'pending'); ?>
                <tr>
                    <td><strong><?= e($d['donor_name'] ?? 'Anonymous') ?></strong></td>
                    <td><?= $d['amount'] !== null ? e(number_format((float)$d['amount'], 2)) : '—' ?></td>
                    <td class="text-muted"><?= e(format_date($d['created_at'])) ?></td>
                    <td><span class="pill pill--<?= e($st) ?>"><?= e($st) ?></span></td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>
        </div>

        <!-- Recent Announcements -->
        <div class="dash-panel">
            <div class="dash-panel__head">
                <h2><?= e(t('admin.dash.recent_announcements')) ?></h2>
                <a href="<?= e(url('admin/announcements.php')) ?>" class="dash-panel__link"><?= e(t('admin.dash.view_all')) ?></a>
            </div>
            <?php if (empty($recentAnnouncements)): ?>
            <div class="dash-panel__empty"><?= e(t('admin.dash.no_announcements')) ?></div>
            <?php else: ?>
            <table class="dash-table">
                <thead>
                    <tr><th><?= e(t('admin.dash.title_col')) ?></th><th><?= e(t('admin.date')) ?></th><th><?= e(t('admin.status')) ?></th></tr>
                </thead>
                <tbody>
                <?php foreach ($recentAnnouncements as $ann): $ast = (string)($ann['status'] ?? 'active'); ?>
                <tr>
                    <td><strong><?= e($ann['title'] ?? '') ?></strong></td>
                    <td class="text-muted"><?= e(format_date($ann['created_at'])) ?></td>
                    <td><span class="pill pill--<?= $ast === 'active' ? 'active' : 'inactive' ?>"><?= e($ast) ?></span></td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>
        </div>

        <!-- Quick Actions -->
        <div class="dash-panel dash-quick">
            <div class="dash-panel__head">
                <h2><?= e(t('admin.dash.quick_actions')) ?></h2>
            </div>
            <div class="dash-quick__grid">

                <a href="<?= e(url('admin/announcements.php')) ?>" class="dash-quick__btn">
                    <div class="dash-quick__btn-icon">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 2L11 13"/><path d="M22 2L15 22 11 13 2 9l20-7z"/></svg>
                    </div>
                    <div>
                        <div class="dash-quick__btn-title"><?= e(t('admin.dash.new_announcement')) ?></div>
                        <div class="dash-quick__btn-sub"><?= e(t('admin.dash.publish_public')) ?></div>
                    </div>
                </a>

                <a href="<?= e(url('admin/gallery.php')) ?>" class="dash-quick__btn">
                    <div class="dash-quick__btn-icon">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>
                    </div>
                    <div>
                        <div class="dash-quick__btn-title"><?= e(t('admin.dash.add_gallery')) ?></div>
                        <div class="dash-quick__btn-sub"><?= e(t('admin.dash.upload_photos')) ?></div>
                    </div>
                </a>

                <a href="<?= e(url('admin/slider.php')) ?>" class="dash-quick__btn">
                    <div class="dash-quick__btn-icon">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="7" width="20" height="15" rx="2"/><polyline points="17 2 12 7 7 2"/></svg>
                    </div>
                    <div>
                        <div class="dash-quick__btn-title"><?= e(t('admin.dash.manage_slider')) ?></div>
                        <div class="dash-quick__btn-sub"><?= e(t('admin.dash.home_page_images')) ?></div>
                    </div>
                </a>

                <a href="<?= e(url('admin/donations.php')) ?>" class="dash-quick__btn">
                    <div class="dash-quick__btn-icon">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 11 12 14 22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg>
                    </div>
                    <div>
                        <div class="dash-quick__btn-title"><?= e(t('admin.dash.review_donations')) ?></div>
                        <div class="dash-quick__btn-sub"><?= $counts['pending'] ?> <?= e(t('admin.dash.pending')) ?></div>
                    </div>
                </a>

                <a href="<?= e(url('admin/users.php')) ?>" class="dash-quick__btn">
                    <div class="dash-quick__btn-icon">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                    </div>
                    <div>
                        <div class="dash-quick__btn-title"><?= e(t('admin.dash.manage_users')) ?></div>
                        <div class="dash-quick__btn-sub"><?= $counts['users'] ?> <?= e(t('admin.dash.registered')) ?></div>
                    </div>
                </a>

                <a href="<?= e(url('admin/settings.php')) ?>" class="dash-quick__btn">
                    <div class="dash-quick__btn-icon">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 1 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 1 1-2.83-2.83l.06-.06A1.65 1.65 0 0 0 4.68 15a1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 1 1 2.83-2.83l.06.06A1.65 1.65 0 0 0 9 4.68a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 1 1 2.83 2.83l-.06.06A1.65 1.65 0 0 0 19.4 9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>
                    </div>
                    <div>
                        <div class="dash-quick__btn-title"><?= e(t('admin.dash.site_settings')) ?></div>
                        <div class="dash-quick__btn-sub"><?= e(t('admin.dash.contact_about_donate')) ?></div>
                    </div>
                </a>

            </div>
        </div>

    </div>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
