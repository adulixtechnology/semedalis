<?php
/**
 * Admin layout shell — white topbar, sidebar, main wrapper.
 */
require_admin();
$adminTitle = $adminTitle ?? 'Dashboard';
$adminUser  = $_SESSION['admin_username'] ?? 'Admin';
$adminInitial = strtoupper(mb_substr($adminUser, 0, 1));
$curLang = $_SESSION['lang'] ?? 'en';
$baseUri = strtok($_SERVER['REQUEST_URI'] ?? '', '?');
$params  = $_GET;
unset($params['lang']);
$queryEn = http_build_query(array_merge($params, ['lang' => 'en']));
$queryAm = http_build_query(array_merge($params, ['lang' => 'am']));
$hrefEn  = $baseUri . ($queryEn ? '?' . $queryEn : '');
$hrefAm  = $baseUri . ($queryAm ? '?' . $queryAm : '');
?>
<!doctype html>
<html lang="<?= $curLang === 'am' ? 'am' : 'en' ?>">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($adminTitle) ?> &middot; Semidalis Admin</title>
    <link rel="stylesheet" href="<?= e(url('assets/css/style.css')) ?>">
</head>
<body class="admin-body">

<header class="admin-topbar">
    <div class="admin-topbar__inner">
        <!-- Brand — aligned with sidebar width -->
        <a class="admin-topbar__brand" href="<?= e(url('admin/index.php')) ?>">
            <div>
                <span class="admin-topbar__brand-name">Semidalis</span>
                <span class="admin-topbar__brand-tag">Admin Panel</span>
            </div>
        </a>

        <!-- Right: user + logout -->
        <div class="admin-topbar__right">
            <div class="admin-topbar__user">
                <div class="admin-topbar__avatar"><?= e($adminInitial) ?></div>
                <span><?= e($adminUser) ?></span>
            </div>
            <div class="lang-toggle">
                <a href="<?= e($hrefEn) ?>" class="lang-toggle__btn<?= $curLang === 'en' ? ' lang-toggle__btn--active' : '' ?>">EN</a>
                <span class="lang-toggle__sep">|</span>
                <a href="<?= e($hrefAm) ?>" class="lang-toggle__btn<?= $curLang === 'am' ? ' lang-toggle__btn--active' : '' ?>">አማ</a>
            </div>
            <a class="admin-nav__logout" href="<?= e(url('admin/logout.php')) ?>">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
                <?= e(t('admin.logout')) ?>
            </a>
        </div>
    </div>
</header>

<main class="admin-main">
<?php require __DIR__ . '/admin_sidebar.php'; ?>
