<?php
require_once __DIR__ . '/../config.php';
$pageTitle = __t('privacy_page_title');
include __DIR__ . '/../includes/header.php';
?>

<section class="container">
    <h1><?= __t('privacy_page_title') ?></h1>

    <p><em><?= __t('privacy_updated') ?>: <?= date('F j, Y') ?></em></p>

    <p><?= __t('privacy_intro') ?></p>

    <h2><?= __t('privacy_h1') ?></h2>

    <h3><?= __t('privacy_provided_h') ?></h3>
    <ul>
        <li><?= __t('privacy_provided_li1') ?></li>
        <li><?= __t('privacy_provided_li2') ?></li>
        <li><?= __t('privacy_provided_li3') ?></li>
        <li><?= __t('privacy_provided_li4') ?></li>
    </ul>

    <h3><?= __t('privacy_auto_h') ?></h3>
    <ul>
        <li><?= __t('privacy_auto_li1') ?></li>
        <li><?= __t('privacy_auto_li2') ?></li>
        <li><?= __t('privacy_auto_li3') ?></li>
    </ul>

    <h2><?= __t('privacy_h2') ?></h2>
    <ul>
        <li><?= __t('privacy_use_li1') ?></li>
        <li><?= __t('privacy_use_li2') ?></li>
        <li><?= __t('privacy_use_li3') ?></li>
        <li><?= __t('privacy_use_li4') ?></li>
    </ul>

    <h2><?= __t('privacy_h3') ?></h2>
    <p><?= __t('privacy_anon_p') ?></p>

    <h2><?= __t('privacy_h4') ?></h2>
    <p><?= __t('privacy_sharing_p') ?></p>

    <h2><?= __t('privacy_h5') ?></h2>
    <p><?= __t('privacy_cookies_p') ?></p>

    <h2><?= __t('privacy_h6') ?></h2>
    <p><?= __t('privacy_security_p') ?></p>

    <h2><?= __t('privacy_h7') ?></h2>
    <p><?= __t('privacy_rights_p') ?></p>

    <p>Email: privacy@sheworks.com</p>
</section>

<?php include __DIR__ . '/../includes/footer.php'; ?>
