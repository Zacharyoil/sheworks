<?php
require_once __DIR__ . '/../config.php';
$pageTitle = __t('contact_page_title');
include __DIR__ . '/../includes/header.php';
?>

<section class="container">
    <h1><?= __t('contact_h1') ?></h1>

    <p><?= __t('contact_intro1') ?></p>
    <p><?= __t('contact_intro2') ?></p>

    <div class="contact-info">
        <p><strong><?= __t('contact_email_label') ?>:</strong> support@sheworks.com</p>
        <p><strong><?= __t('contact_privacy_label') ?>:</strong> privacy@sheworks.com</p>
    </div>

    <h2><?= __t('contact_report_h') ?></h2>
    <p><?= __t('contact_report_p') ?></p>

    <ul>
        <li><?= __t('contact_report_li1') ?></li>
        <li><?= __t('contact_report_li2') ?></li>
        <li><?= __t('contact_report_li3') ?></li>
    </ul>

    <p><?= __t('contact_response') ?></p>
</section>

<?php include __DIR__ . '/../includes/footer.php'; ?>
