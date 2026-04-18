<?php
require_once __DIR__ . '/config.php';
$db = db();

// Stats
$totalReviews   = $db->query("SELECT COUNT(*) FROM reviews WHERE approved=1")->fetch_row()[0];
$totalCompanies = $db->query("SELECT COUNT(*) FROM companies")->fetch_row()[0];
$totalSalaries  = $db->query("SELECT COUNT(*) FROM salaries WHERE approved=1")->fetch_row()[0];

// Top-rated companies (by avg pay equity rating — the most relevant metric)
$topCompanies = $db->query("
    SELECT c.id, c.name, c.slug, c.industry, c.hq_city, c.hq_country,
           ROUND(AVG(r.rating_overall),1)    AS avg_overall,
           ROUND(AVG(r.rating_pay_equity),1) AS avg_pay_equity,
           ROUND(AVG(r.rating_culture),1)    AS avg_culture,
           COUNT(r.id)                       AS review_count
    FROM companies c
    JOIN reviews r ON r.company_id = c.id AND r.approved = 1
    GROUP BY c.id
    HAVING review_count >= 1
    ORDER BY avg_pay_equity DESC, avg_overall DESC
    LIMIT 6
")->fetch_all(MYSQLI_ASSOC);

// Recent reviews (female only for homepage highlight)
$recentReviews = $db->query("
    SELECT r.job_title, r.field, r.rating_overall, r.rating_pay_equity,
           r.pros, r.cons, r.advice, r.created_at,
           c.name AS company_name, c.slug AS company_slug
    FROM reviews r
    JOIN companies c ON c.id = r.company_id
    WHERE r.approved = 1 AND r.gender = 'female'
    ORDER BY r.created_at DESC LIMIT 3
")->fetch_all(MYSQLI_ASSOC);
?>
<?php include __DIR__ . '/includes/header.php'; ?>

<!-- HERO -->
<section class="hero">
    <div style="position:absolute;inset:0;background:linear-gradient(160deg,rgba(90,20,70,0.60) 0%,rgba(70,15,55,0.40) 55%,rgba(60,10,45,0.15) 100%);pointer-events:none;z-index:0;"></div>
    <div class="hero-inner">
        <div class="hero-tag"><?= __t('hero_tag') ?></div>
        <h1 style="color:#fff;"><?= __t('hero_h1') ?><br></h1>
        <p class="hero-sub"><?= __t('hero_sub') ?></p>

        <!-- Big search -->
        <form class="hero-search-bar" action="/sheworks/pages/company.php" method="GET" style="margin-bottom:48px;">
            <input type="text" name="q" placeholder="<?= __t('hero_search') ?>" autocomplete="off" required>
            <button type="submit"><?= __t('hero_search_btn') ?></button>
        </form>

        <div class="hero-ctas">
            <a href="/sheworks/pages/review.php" class="btn btn-gold"><?= __t('hero_btn_review') ?></a>
            <a href="/sheworks/pages/submit-salary.php" class="btn btn-outline-white"><?= __t('hero_btn_salary') ?></a>
        </div>
    </div>
</section>

<!-- STATS -->
<div class="stats-bar">
    <div class="stats-bar-inner">
        <div class="stat"><span class="stat-n"><?= number_format($totalReviews) ?>+</span><div class="stat-l"><?= __t('stat_reviews') ?></div></div>
        <div class="stat"><span class="stat-n"><?= number_format($totalCompanies) ?>+</span><div class="stat-l"><?= __t('stat_companies') ?></div></div>
        <div class="stat"><span class="stat-n"><?= number_format($totalSalaries) ?>+</span><div class="stat-l"><?= __t('stat_salaries') ?></div></div>
        <div class="stat"><span class="stat-n">100%</span><div class="stat-l"><?= __t('stat_anon') ?></div></div>
    </div>
</div>

<!-- TOP COMPANIES -->
<?php if (!empty($topCompanies)): ?>
<section class="section section-warm">
    <div class="section-inner">
        <div class="sec-header">
            <span class="sec-eye"><?= __t('top_companies_eye') ?></span>
            <h2 class="sec-h"><?= __t('top_companies_h') ?></h2>
            <p class="sec-sub"><?= __t('top_companies_sub') ?></p>
        </div>
        <div class="company-grid">
            <?php foreach ($topCompanies as $co): ?>
            <a href="/sheworks/pages/company.php?slug=<?= urlencode($co['slug']) ?>" class="company-card">
                <div class="company-name"><?= clean($co['name']) ?></div>
                <div class="company-meta"><?= translateIndustry($co['industry'] ?? '') ?><?= $co['hq_city'] ? ' · ' . clean($co['hq_city']) : '' ?> · <?= $co['review_count'] ?> review<?= $co['review_count'] != 1 ? 's' : '' ?></div>

                <div class="rating-row">
                    <span class="rating-label"><?= __t('rating_overall') ?></span>
                    <span class="rating-val <?= ratingColor($co['avg_overall']) ?>"><?= $co['avg_overall'] ?>/5 <?= stars((int)round($co['avg_overall'])) ?></span>
                </div>
                <div class="rating-row">
                    <span class="rating-label"><?= __t('rating_pay') ?></span>
                    <span class="rating-val <?= ratingColor($co['avg_pay_equity']) ?>"><?= $co['avg_pay_equity'] ?>/5</span>
                </div>
                <div class="rating-row">
                    <span class="rating-label"><?= __t('rating_culture') ?></span>
                    <span class="rating-val <?= ratingColor($co['avg_culture']) ?>"><?= $co['avg_culture'] ?>/5</span>
                </div>
            </a>
            <?php endforeach; ?>
        </div>
        <div style="text-align:center; margin-top:36px;">
            <a href="/sheworks/pages/companies.php" class="btn btn-outline"><?= __t('browse_all') ?></a>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- RECENT REVIEWS -->
<?php if (!empty($recentReviews)): ?>
<section class="section section-dark">
    <div class="section-inner">
        <div class="sec-header">
            <span class="sec-eye sec-eye-white"><?= __t('community_eye') ?></span>
            <h2 class="sec-h" style="color:white;"><?= __t('community_h') ?></h2>
            <p class="sec-sub" style="color:rgba(255,255,255,0.55);"><?= __t('community_sub') ?></p>
        </div>
        <div class="review-list">
            <?php foreach ($recentReviews as $rev): ?>
            <div class="review-card">
                <div class="review-header">
                    <div>
                        <div class="review-title"><?= clean($rev['job_title']) ?> at <a href="/pages/company.php?slug=<?= urlencode($rev['company_slug']) ?>" style="color:var(--gold);text-decoration:none;"><?= clean($rev['company_name']) ?></a></div>
                        <div class="review-meta"><?= clean($rev['field']) ?> · <?= formatReviewDate($rev['created_at']) ?></div>
                    </div>
                    <div class="review-ratings">
                        <span class="mini-rating"><?= stars($rev['rating_overall']) ?> <?= __t('rating_overall') ?></span>
                        <span class="mini-rating <?= ratingColor($rev['rating_pay_equity']) ?>"><?= __t('rating_pay') ?> <?= $rev['rating_pay_equity'] ?>/5</span>
                    </div>
                </div>
                <?php if ($rev['pros']): ?>
                <div class="review-section">
                    <div class="review-section-label"><?= __t('pros') ?></div>
                    <div class="review-section-text"><?= clean($rev['pros']) ?></div>
                </div>
                <?php endif; ?>
                <?php if ($rev['cons']): ?>
                <div class="review-section">
                    <div class="review-section-label"><?= __t('cons') ?></div>
                    <div class="review-section-text"><?= clean($rev['cons']) ?></div>
                </div>
                <?php endif; ?>
                <?php if ($rev['advice']): ?>
                <div class="review-advice">💡 <?= clean($rev['advice']) ?></div>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- HOW IT WORKS -->
<section class="section section-cream">
    <div class="section-inner" style="text-align:center;">
        <span class="sec-eye"><?= __t('hiw_eye') ?></span>
        <h2 class="sec-h" style="margin-bottom:48px;"><?= __t('hiw_h') ?></h2>
        <div style="display:grid; grid-template-columns:1fr 1fr 1fr; gap:32px; max-width:1100px; margin:0 auto; text-align:left;">
            <div style="background:white; border-radius:var(--r); padding:32px; border:1px solid var(--border); border-top:4px solid var(--gold); display:flex; flex-direction:column; align-items:center; text-align:center;">
                <div style="font-size:2.4rem; margin-bottom:14px;">📝</div>
                <h3 style="font-family:var(--fd); font-size:1.4rem; margin-bottom:10px;"><?= __t('hiw_review_h') ?></h3>
                <p style="color:var(--muted); font-size:0.93rem; margin-bottom:20px; flex:1;"><?= __t('hiw_review_p') ?></p>
                <a href="/sheworks/pages/review.php" class="btn btn-primary"><?= __t('hiw_review_btn') ?></a>
            </div>
            <div style="background:white; border-radius:var(--r); padding:32px; border:1px solid var(--border); border-top:4px solid var(--rose); display:flex; flex-direction:column; align-items:center; text-align:center;">
                <div style="font-size:2.4rem; margin-bottom:14px;">📊</div>
                <h3 style="font-family:var(--fd); font-size:1.4rem; margin-bottom:10px;"><?= __t('hiw_salary_h') ?></h3>
                <p style="color:var(--muted); font-size:0.93rem; margin-bottom:20px; flex:1;"><?= __t('hiw_salary_p') ?></p>
                <a href="/sheworks/pages/submit-salary.php" class="btn btn-rose"><?= __t('hiw_salary_btn') ?></a>
            </div>
            <div style="background:white; border-radius:var(--r); padding:32px; border:1px solid var(--border); border-top:4px solid #7c3aed; display:flex; flex-direction:column; align-items:center; text-align:center;">
                <div style="font-size:2.4rem; margin-bottom:14px;">💜</div>
                <h3 style="font-family:var(--fd); font-size:1.4rem; margin-bottom:10px;"><?= __t('hiw_pay_h') ?></h3>
                <p style="color:var(--muted); font-size:0.93rem; margin-bottom:20px; flex:1;"><?= __t('hiw_pay_p') ?></p>
                <a href="/sheworks/pages/pay-analysis.php" class="btn btn-primary" style="background:#7c3aed;"><?= __t('hiw_pay_btn') ?></a>
            </div>
        </div>
    </div>
</section>

<?php include __DIR__ . '/includes/footer.php'; ?>
