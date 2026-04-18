<!DOCTYPE html>
<?php $isRtl = $currentLang === 'ar'; ?>
<html lang="<?= $currentLang ?>" <?= $isRtl ? 'dir="rtl"' : '' ?>>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($pageTitle) ? clean($pageTitle) . ' | ' . SITE_NAME : SITE_NAME . ' — ' . SITE_TAGLINE ?></title>
    <meta name="description" content="SheWork$ — Rate companies on pay equity, culture and growth. Analyze your salary vs. male peers. Built for women.">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,600;0,700;1,600&family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/sheworks/css/style.css">
    <?php if ($isRtl): ?>
    <style>
        body { font-family: 'Segoe UI', Tahoma, Arial, sans-serif; }
        .logo { direction: ltr; unicode-bidi: embed; }
        .nav-search { direction: ltr; }
        .nav-search-input { direction: rtl; text-align: right; }
        .review-header { flex-direction: row-reverse; text-align: right; }
        .review-ratings { align-items: flex-start; }
        .review-section-label, .review-section-text, .review-advice { text-align: right; }
        .rating-row { flex-direction: row-reverse; }
        .score-cards { direction: rtl; }
        .form-group label { text-align: right; display: block; }
        input, select, textarea { text-align: right; direction: rtl; }
        .btn-translate { float: left; }
    </style>
    <?php endif; ?>
</head>
<body>

<?php
    $langParams = $_GET;
    unset($langParams['lang']);
    $baseQuery = $langParams ? '&' . http_build_query($langParams) : '';
?>
<nav class="navbar" id="navbar">
    <div class="nav-inner">
        <a href="/sheworks/index.php" class="logo">She<span class="logo-accent">Work$</span></a>
        <form class="nav-search" action="/sheworks/pages/company.php" method="GET">
            <input type="text" name="q" class="nav-search-input" placeholder="<?= __t('nav_search') ?>" value="<?= isset($_GET['q']) ? clean($_GET['q']) : '' ?>" autocomplete="off">
            <button type="submit" class="nav-search-btn" aria-label="Search">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>
            </button>
        </form>
        <button class="nav-hamburger" id="nav-hamburger" aria-label="Menu" aria-expanded="false">
            <span></span><span></span><span></span>
        </button>
        <ul class="nav-links" id="nav-links">
            <li><a href="/sheworks/pages/companies.php"><?= __t('nav_companies') ?></a></li>
            <li><a href="/sheworks/pages/pay-analysis.php"><?= __t('nav_pay_analysis') ?></a></li>
            <li><a href="/sheworks/pages/review.php" class="nav-cta"><?= __t('nav_review') ?></a></li>
            <li class="lang-switcher">
                <a href="?lang=en<?= $baseQuery ?>"    <?= $currentLang === 'en'    ? 'class="active-lang"' : '' ?>>🇬🇧</a>
                <a href="?lang=fr<?= $baseQuery ?>"    <?= $currentLang === 'fr'    ? 'class="active-lang"' : '' ?>>🇫🇷</a>
                <a href="?lang=zh-TW<?= $baseQuery ?>" <?= $currentLang === 'zh-TW' ? 'class="active-lang"' : '' ?>>🇹🇼</a>
                <a href="?lang=ar<?= $baseQuery ?>"    <?= $currentLang === 'ar'    ? 'class="active-lang"' : '' ?>>🇸🇦</a>
            </li>
        </ul>
    </div>
</nav>
<script>
(function(){
    var btn = document.getElementById('nav-hamburger');
    var links = document.getElementById('nav-links');
    btn.addEventListener('click', function(){
        var open = links.classList.toggle('nav-open');
        btn.classList.toggle('nav-open', open);
        btn.setAttribute('aria-expanded', open);
    });
    document.addEventListener('click', function(e){
        if (!e.target.closest('#navbar')) {
            links.classList.remove('nav-open');
            btn.classList.remove('nav-open');
            btn.setAttribute('aria-expanded', false);
        }
    });
}());
</script>
