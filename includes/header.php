<?php
/**
 * Public site shell: doctype + head + nav + open <main>.
 * Pages call this AFTER `require config/config.php` and may set $pageTitle.
 * Set $hasHero = true on pages that have a full-screen hero slider so the
 * nav starts transparent and turns solid on scroll. All other pages get the
 * solid nav immediately.
 */
$pageTitle = $pageTitle ?? 'Semidalis';
$hasHero   = $hasHero   ?? false;
$_lang     = $_SESSION['lang'] ?? 'en';
?>
<!doctype html>
<html lang="<?= $_lang === 'am' ? 'am' : 'en' ?>">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($pageTitle) ?> &middot; Semidalis</title>
    <link rel="stylesheet" href="<?= e(url('assets/css/style.css')) ?>">
</head>
<body>
<header class="site-header<?= $hasHero ? ' site-header--hero' : ' scrolled' ?>">
    <div class="container site-header__inner">
        <a class="brand" href="<?= e(url('index.php')) ?>">Semidalis</a>
        <button class="nav-toggle" type="button" aria-label="Toggle navigation" aria-expanded="false">
            <span></span><span></span><span></span>
        </button>
        <nav class="main-nav" id="main-nav">
            <a href="<?= e(url('index.php')) ?>"><?= t('nav.home') ?></a>
            <a href="<?= e(url('pages/gallery.php')) ?>"><?= t('nav.gallery') ?></a>
            <a href="<?= e(url('pages/announcements.php')) ?>"><?= t('nav.announcements') ?></a>
            <a href="<?= e(url('pages/donations.php')) ?>"><?= t('nav.donations') ?></a>
            <a href="<?= e(url('pages/about.php')) ?>"><?= t('nav.about') ?></a>
            <a href="<?= e(url('pages/contact.php')) ?>"><?= t('nav.contact') ?></a>
            <div class="lang-toggle">
                <a href="<?= e(url('lang.php?lang=en')) ?>"
                   class="lang-btn<?= $_lang === 'en' ? ' lang-btn--active' : '' ?>"
                   aria-label="Switch to English">EN</a>
                <a href="<?= e(url('lang.php?lang=am')) ?>"
                   class="lang-btn<?= $_lang === 'am' ? ' lang-btn--active' : '' ?>"
                   aria-label="Switch to Amharic">አማ</a>
            </div>
        </nav>
    </div>
</header>
<main class="site-main" id="site-main">
