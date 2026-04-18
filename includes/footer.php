
<footer class="footer">
    <div class="footer-inner">
        <div class="footer-brand">
            <a href="/sheworks/index.php" class="logo" style="color:white;">She<span class="logo-accent">Work$</span></a>
            <p><?= __t('footer_tagline') ?></p>
        </div>
        <div class="footer-col">
            <h4><?= __t('footer_explore') ?></h4>
            <ul>
                <li><a href="/sheworks/pages/companies.php"><?= __t('footer_browse_companies') ?></a></li>
                <li><a href="/sheworks/pages/review.php"><?= __t('footer_leave_review') ?></a></li>
                <li><a href="/sheworks/pages/submit-salary.php"><?= __t('footer_submit_salary') ?></a></li>
                <li><a href="/sheworks/pages/pay-analysis.php"><?= __t('footer_pay_analysis') ?></a></li>
            </ul>
        </div>
        <div class="footer-col">
            <h4><?= __t('footer_about') ?></h4>
            <ul>
                <li><a href="/sheworks/pages/about.php"><?= __t('footer_mission') ?></a></li>
                <li><a href="/sheworks/pages/privacy.php"><?= __t('footer_privacy') ?></a></li>
                <li><a href="/sheworks/pages/contact.php"><?= __t('footer_contact') ?></a></li>
            </ul>
        </div>
    </div>
    <div class="footer-bottom">
        <p>&copy; <?= date('Y') ?> SheWork$ — <?= __t('footer_copy') ?></p>
    </div>
</footer>
<script src="/sheworks/js/main.js"></script>
</body>
</html>
