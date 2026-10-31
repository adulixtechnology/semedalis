<?php
/**
 * Public site shell: doctype + head + nav + open <main>.
 */
$pageTitle = $pageTitle ?? 'Semidalis';
$hasHero   = $hasHero   ?? false;
$_lang     = $_SESSION['lang'] ?? 'en';

$_currentUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http')
    . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost')
    . ($_SERVER['REQUEST_URI'] ?? '/');
$_currentUrlClean = preg_replace('/([?&])lang=[^&]*(&|$)/', '$1', $_currentUrl);
$_currentUrlClean = rtrim($_currentUrlClean, '?&');
$_hrefEn = url('lang.php') . '?lang=en&redirect=' . urlencode($_currentUrlClean);
$_hrefAm = url('lang.php') . '?lang=am&redirect=' . urlencode($_currentUrlClean);

// SVG flag — UK (GB)
$_svgFlagEn = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 60 30" width="22" height="14" class="flag-svg" aria-hidden="true">
  <clipPath id="s"><path d="M0,0 v30 h60 v-30 z"/></clipPath>
  <clipPath id="t"><path d="M30,15 h30 v15 z v15 h-30 z h-30 v-15 z v-15 h30 z"/></clipPath>
  <g clip-path="url(#s)">
    <path d="M0,0 v30 h60 v-30 z" fill="#012169"/>
    <path d="M0,0 L60,30 M60,0 L0,30" stroke="#fff" stroke-width="6"/>
    <path d="M0,0 L60,30 M60,0 L0,30" clip-path="url(#t)" stroke="#C8102E" stroke-width="4"/>
    <path d="M30,0 v30 M0,15 h60" stroke="#fff" stroke-width="10"/>
    <path d="M30,0 v30 M0,15 h60" stroke="#C8102E" stroke-width="6"/>
  </g>
</svg>';

// SVG flag — Ethiopia
$_svgFlagAm = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 60 30" width="22" height="14" class="flag-svg" aria-hidden="true">
  <rect width="60" height="10" y="0"  fill="#078930"/>
  <rect width="60" height="10" y="10" fill="#FCDD09"/>
  <rect width="60" height="10" y="20" fill="#DA121A"/>
  <circle cx="30" cy="15" r="7" fill="#0F47AF"/>
  <polygon points="30,8.5 31.5,13 36,13 32.5,15.5 33.8,20 30,17.5 26.2,20 27.5,15.5 24,13 28.5,13" fill="#FCDD09"/>
</svg>';

$_activeFlagSvg   = $_lang === 'am' ? $_svgFlagAm : $_svgFlagEn;
$_activeLabel     = $_lang === 'am' ? 'አማ' : 'EN';
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

        <!-- Brand + language flag together on the left -->
        <div class="brand-lang">
            <a class="brand" href="<?= e(url('index.php')) ?>">Semidalis</a>
            <div class="lang-corner" id="langCorner">
                <button class="lang-corner__trigger" id="langTrigger" aria-haspopup="true" aria-expanded="false">
                    <?= $_activeFlagSvg ?>
                    <span><?= $_activeLabel ?></span>
                    <svg class="lang-corner__chevron" width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="6 9 12 15 18 9"/></svg>
                </button>
                <div class="lang-corner__dropdown" id="langDropdown" role="menu">
                    <a href="<?= e($_hrefEn) ?>" class="lang-corner__item<?= $_lang === 'en' ? ' lang-corner__item--active' : '' ?>" role="menuitem">
                        <?= $_svgFlagEn ?> <span>English</span>
                    </a>
                    <a href="<?= e($_hrefAm) ?>" class="lang-corner__item<?= $_lang === 'am' ? ' lang-corner__item--active' : '' ?>" role="menuitem">
                        <?= $_svgFlagAm ?> <span>አማርኛ</span>
                    </a>
                </div>
            </div>
        </div>

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
        </nav>

    </div>
</header>
<main class="site-main" id="site-main">
