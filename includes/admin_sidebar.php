<?php
/**
 * Admin sidebar navigation. Highlights the active page automatically.
 */
$currentPath = str_replace('\\', '/', $_SERVER['SCRIPT_FILENAME'] ?? '');
function sidebar_active(string $file, string $current): string {
    return (str_ends_with($current, '/' . $file)) ? ' class="active"' : '';
}
?>
<aside class="admin-sidebar">
    <nav>
        <div class="sidebar-section"><?= e(t('admin.sidebar.main')) ?></div>
        <a href="<?= e(url('admin/index.php')) ?>"<?= sidebar_active('index.php', $currentPath) ?>><?= e(t('admin.sidebar.dashboard')) ?></a>

        <div class="sidebar-section"><?= e(t('admin.sidebar.content')) ?></div>
        <a href="<?= e(url('admin/slider.php')) ?>"<?= sidebar_active('slider.php', $currentPath) ?>><?= e(t('admin.sidebar.slider')) ?></a>
        <a href="<?= e(url('admin/gallery.php')) ?>"<?= sidebar_active('gallery.php', $currentPath) ?>><?= e(t('admin.sidebar.gallery')) ?></a>
        <a href="<?= e(url('admin/announcements.php')) ?>"<?= sidebar_active('announcements.php', $currentPath) ?>><?= e(t('admin.sidebar.announcements')) ?></a>
        <a href="<?= e(url('admin/donations.php')) ?>"<?= sidebar_active('donations.php', $currentPath) ?>><?= e(t('admin.sidebar.donations')) ?></a>

        <div class="sidebar-section"><?= e(t('admin.sidebar.system')) ?></div>
        <a href="<?= e(url('admin/users.php')) ?>"<?= sidebar_active('users.php', $currentPath) ?>><?= e(t('admin.sidebar.users')) ?></a>
        <a href="<?= e(url('admin/settings.php')) ?>"<?= sidebar_active('settings.php', $currentPath) ?>><?= e(t('admin.sidebar.settings')) ?></a>
    </nav>
</aside>
