<?php
require_once __DIR__ . '/../config.php';
$pageTitle = __t('about_page_title');
include __DIR__ . '/../includes/header.php';
?>

<!-- HERO -->
<section class="hero">
    <div class="hero-inner">
        <span class="hero-tag"><?= __t('about_hero_tag') ?></span>
        <h1><?= __t('about_hero_h1') ?></h1>
        <p class="hero-sub"><?= __t('about_hero_sub') ?></p>
    </div>
</section>

<!-- CONTENT -->
<section class="section section-warm">
    <div class="section-inner">

        <div class="sec-header">
            <span class="sec-eye"><?= __t('about_eye') ?></span>
            <h2 class="sec-h"><?= __t('about_h') ?></h2>
            <p class="sec-sub"><?= __t('about_sub') ?></p>
        </div>

        <p><?= __t('about_body') ?></p>

        <h3 class="sec-h" style="margin-top:40px;"><?= __t('about_what_h') ?></h3>
        <div class="company-grid" style="margin-top:20px;">
            <div class="company-card">
                <div class="company-name"><?= __t('about_card1_title') ?></div>
                <div class="company-meta"><?= __t('about_card1_meta') ?></div>
                <p><?= __t('about_card1_body') ?></p>
            </div>
            <div class="company-card">
                <div class="company-name"><?= __t('about_card2_title') ?></div>
                <div class="company-meta"><?= __t('about_card2_meta') ?></div>
                <p><?= __t('about_card2_body') ?></p>
            </div>
            <div class="company-card">
                <div class="company-name"><?= __t('about_card3_title') ?></div>
                <div class="company-meta"><?= __t('about_card3_meta') ?></div>
                <p><?= __t('about_card3_body') ?></p>
            </div>
        </div>

    </div>
</section>

<?php include __DIR__ . '/../includes/footer.php'; ?>
