<?php
require_once __DIR__ . '/../config.php';
$db = db();

$slug  = clean($_GET['slug'] ?? '');
$query = clean($_GET['q']    ?? '');

$company = null;
$notFound = false;

if ($slug) {
    $stmt = $db->prepare("SELECT * FROM companies WHERE slug = ? LIMIT 1");
    $stmt->bind_param('s', $slug);
    $stmt->execute();
    $company = $stmt->get_result()->fetch_assoc();
    $stmt->close();
} elseif ($query) {
    $stmt = $db->prepare("SELECT * FROM companies WHERE name LIKE ? ORDER BY name LIMIT 1");
    $like = '%' . $query . '%';
    $stmt->bind_param('s', $like);
    $stmt->execute();
    $company = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$company) {
        $stmt = $db->prepare("SELECT c.*, COUNT(r.id) AS review_count FROM companies c LEFT JOIN reviews r ON r.company_id = c.id WHERE c.name LIKE ? GROUP BY c.id ORDER BY review_count DESC LIMIT 12");
        $stmt->bind_param('s', $like);
        $stmt->execute();
        $searchResults = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        if (empty($searchResults)) {
            $notFound = true;
        }
    }
}

if (!$company && !isset($searchResults) && !$notFound) {
    header('Location: /sheworks/pages/companies.php');
    exit;
}

if ($company) {
    $pageTitle = clean($company['name']);
    $cid = (int)$company['id'];

    $scores = $db->query("
        SELECT
            COUNT(*) AS total,
            ROUND(AVG(rating_overall),1)   AS avg_overall,
            ROUND(AVG(rating_pay_equity),1) AS avg_pay_equity,
            ROUND(AVG(rating_culture),1)   AS avg_culture,
            ROUND(AVG(rating_growth),1)    AS avg_growth,
            ROUND(AVG(rating_flexibility),1) AS avg_flex,
            COUNT(CASE WHEN gender='female' THEN 1 END) AS female_count
        FROM reviews WHERE company_id = $cid AND approved = 1
    ")->fetch_assoc();

    // Locations for this company (only those with coordinates)
    $locations = $db->query("
        SELECT l.*, COUNT(r.id) AS review_count
        FROM locations l
        LEFT JOIN reviews r ON r.location_id = l.id AND r.approved = 1
        WHERE l.company_id = $cid AND l.lat IS NOT NULL
        GROUP BY l.id
        ORDER BY review_count DESC
    ")->fetch_all(MYSQLI_ASSOC);

    $reviews = $db->query("
        SELECT r.*, l.display_name AS loc_display, l.city AS loc_city
        FROM reviews r
        LEFT JOIN locations l ON l.id = r.location_id
        WHERE r.company_id = $cid AND r.approved = 1
        ORDER BY r.created_at DESC LIMIT 50
    ")->fetch_all(MYSQLI_ASSOC);

    $salaryData = $db->query("
        SELECT
            field,
            currency,
            ROUND(AVG(CASE WHEN gender='female' THEN salary END),0) AS avg_female,
            ROUND(AVG(CASE WHEN gender='male'   THEN salary END),0) AS avg_male,
            COUNT(*) AS total,
            COUNT(CASE WHEN gender='female' THEN 1 END) AS female_count
        FROM salaries
        WHERE company_id = $cid AND approved = 1
        GROUP BY field, currency
        HAVING total >= 1
        ORDER BY total DESC
    ")->fetch_all(MYSQLI_ASSOC);

    $overallPay = $db->query("
        SELECT
            ROUND(AVG(CASE WHEN gender='female' THEN salary END),0) AS avg_f,
            ROUND(AVG(CASE WHEN gender='male'   THEN salary END),0) AS avg_m
        FROM salaries WHERE company_id = $cid AND approved = 1
    ")->fetch_assoc();
    $overallGap = payGap((float)($overallPay['avg_f'] ?? 0), (float)($overallPay['avg_m'] ?? 0));
}
?>
<?php include __DIR__ . '/../includes/header.php'; ?>

<?php if ($notFound): ?>
<!-- "COMPANY NOT FOUND" -->
<div class="page-hero" style="background:var(--ink);color:white;padding:56px 32px;">
    <div style="max-width:860px;margin:0 auto;text-align:center;">
        <div style="font-size:3rem;margin-bottom:16px;">🔍</div>
        <h1 style="font-family:var(--fd);font-size:2.2rem;font-weight:700;color:white;margin-bottom:12px;">
            "<?= clean($query) ?>" <?= __t('company_hasnt_added') ?>
        </h1>
        <p style="color:rgba(255,255,255,0.6);font-size:1.05rem;max-width:560px;margin:0 auto 32px;">
            <?= __t('company_not_db_p') ?>
        </p>
        <div style="display:flex;gap:14px;justify-content:center;flex-wrap:wrap;">
            <a href="/sheworks/pages/review.php?company=<?= urlencode($query) ?>" class="btn btn-gold" style="font-size:1rem;padding:14px 28px;">
                <?= __t('company_first_review_btn') ?>
            </a>
            <a href="/sheworks/pages/submit-salary.php?company=<?= urlencode($query) ?>" class="btn btn-outline-white" style="font-size:1rem;padding:14px 28px;">
                <?= __t('company_submit_salary_alt') ?>
            </a>
        </div>
    </div>
</div>
<section class="section section-warm">
    <div class="section-inner" style="text-align:center;padding:40px 20px;">
        <p style="color:var(--muted);"><?= $t['company_browse_note'] ?></p>
    </div>
</section>

<?php elseif (isset($searchResults)): ?>
<!-- SEARCH RESULTS -->
<div class="page-hero" style="background:var(--ink);color:white;padding:48px 32px;">
    <div style="max-width:1240px;margin:0 auto;">
        <h1 style="font-family:var(--fd);font-size:2rem;font-weight:700;color:white;margin-bottom:8px;"><?= __t('company_search_results_pre') ?> "<?= clean($query) ?>"</h1>
        <p style="color:rgba(255,255,255,0.55);"><?= __t('company_no_exact') ?></p>
    </div>
</div>
<section class="section">
    <div class="section-inner">
        <div class="company-grid">
            <?php foreach ($searchResults as $co): ?>
                <a href="/sheworks/pages/company.php?slug=<?= urlencode($co['slug']) ?>" class="company-card">
                    <div class="company-name"><?= clean($co['name']) ?></div>
                    <div class="company-meta"><?= translateIndustry($co['industry'] ?? '') ?: 'Company' ?><?= $co['hq_city'] ? ' · ' . clean($co['hq_city']) : '' ?></div>
                    <p style="color:var(--muted);font-size:0.85rem;margin-top:8px;"><?= __t('company_click_to_view') ?></p>
                </a>
            <?php endforeach; ?>
        </div>

        <div style="margin-top:40px;padding:28px;background:var(--gold-pale);border-radius:var(--r);border:1px solid var(--border-gold);text-align:center;">
            <strong><?= __t('company_dont_see_pre') ?> "<?= clean($query) ?>"?</strong>
            <p style="color:var(--muted);margin:8px 0 16px;"><?= __t('company_dont_see_p') ?></p>
            <a href="/sheworks/pages/review.php?company=<?= urlencode($query) ?>" class="btn btn-primary"><?= __t('company_review_btn_pre') ?> "<?= clean($query) ?>" →</a>
        </div>
    </div>
</section>

<?php else: ?>
<!-- COMPANY PROFILE -->
<div class="company-hero">
    <div class="company-hero-inner">
        <?php if ($company['industry']): ?>
            <div class="industry-badge"><?= translateIndustry($company['industry']) ?></div>
        <?php endif; ?>
        <h1><?= clean($company['name']) ?></h1>
        <div class="company-hero-meta">
            <?= $company['hq_city'] ? clean($company['hq_city']) . ', ' : '' ?><?= clean($company['hq_country'] ?? '') ?>
            <?= $scores['total'] ? ' · ' . $scores['total'] . ' review' . ($scores['total'] != 1 ? 's' : '') : '' ?>
        </div>

        <?php if ($scores['total'] > 0): ?>
        <div class="score-cards">
            <?php
            $scoreItems = [
                [__t('company_overall_score'),      $scores['avg_overall']],
                [__t('company_pay_equity_score'),   $scores['avg_pay_equity']],
                [__t('company_culture_score'),      $scores['avg_culture']],
                [__t('company_growth_score'),       $scores['avg_growth']],
                [__t('company_flex_score'),         $scores['avg_flex']],
            ];
            foreach ($scoreItems as [$label, $val]):
                $cls = $val >= 4 ? 'green' : ($val >= 2.5 ? 'amber' : 'red');
            ?>
            <div class="score-card">
                <div class="label"><?= $label ?></div>
                <div class="val <?= $cls ?>"><?= $val ?></div>
                <div><?= stars((int)round($val)) ?></div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <div style="display:flex; gap:12px; margin-top:28px; flex-wrap:wrap;">
            <a href="/sheworks/pages/review.php?company=<?= urlencode($company['name']) ?>&slug=<?= urlencode($company['slug']) ?>" class="btn btn-gold"><?= __t('company_leave_review_btn') ?></a>
            <a href="/sheworks/pages/submit-salary.php?company=<?= urlencode($company['name']) ?>&slug=<?= urlencode($company['slug']) ?>" class="btn btn-outline-white"><?= __t('company_submit_salary_btn') ?></a>
        </div>
    </div>
</div>

<section class="section section-warm">
    <div class="section-inner">

        <?php if (!empty($locations)): ?>
        <!-- LOCATIONS MAP -->
        <div class="company-map-wrap">
            <div id="company-map" class="company-map"></div>
        </div>
        <?php endif; ?>

        <!-- TABS -->
        <div class="profile-tabs">
            <button class="tab-btn active" data-tab="reviews"><?= __t('company_tab_reviews') ?> <?= $scores['total'] ? '(' . $scores['total'] . ')' : '' ?></button>
            <button class="tab-btn" data-tab="pay"><?= __t('company_tab_pay_analysis') ?> <?= !empty($salaryData) ? '(' . count($salaryData) . ' ' . __t('company_tab_fields') . ')' : '' ?></button>
        </div>

        <?php if (!empty($locations)): ?>
        <!-- LOCATION FILTER -->
        <div class="location-filter-bar" id="location-filter-bar">
            <button class="loc-filter-btn active" data-loc="all"><?= __t('all_locations') ?></button>
            <?php foreach ($locations as $loc): ?>
            <button class="loc-filter-btn" data-loc="<?= (int)$loc['id'] ?>">
                <?= clean($loc['city'] ?: $loc['display_name']) ?>
                <span class="loc-review-count"><?= (int)$loc['review_count'] ?></span>
            </button>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <!-- REVIEWS TAB -->
        <div class="tab-panel active" id="tab-reviews">
            <?php if (empty($reviews)): ?>
                <div class="empty">
                    <h3><?= __t('company_no_reviews_h') ?></h3>
                    <p><?= __t('company_be_first_review') ?> <?= clean($company['name']) ?>.</p>
                    <a href="/sheworks/pages/review.php?company=<?= urlencode($company['name']) ?>&slug=<?= urlencode($company['slug']) ?>" class="btn btn-primary" style="display:inline-block;margin-top:16px;"><?= __t('company_write_review_btn') ?></a>
                </div>
            <?php else: ?>
                <div class="review-list">
                    <?php foreach ($reviews as $i => $rev): ?>
                    <div class="review-card" data-rev="<?= $i ?>" data-loc-id="<?= (int)($rev['location_id'] ?? 0) ?>">
                        <div class="review-header">
                            <div>
                                <div class="review-title"><?= clean($rev['job_title']) ?></div>
                                <div class="review-meta">
                                    <?= clean($rev['field']) ?> ·
                                    <?= ucfirst($rev['employment_status']) ?> <?= __t('company_employee') ?> ·
                                    <?= $rev['years_at_company'] ?> yr<?= $rev['years_at_company'] != 1 ? 's' : '' ?> ·
                                    <?= translateGender($rev['gender']) ?> ·
                                    <?= formatReviewDate($rev['created_at']) ?>
                                </div>
                                <?php if (!empty($rev['loc_city']) || !empty($rev['loc_display'])): ?>
                                <div class="location-badge">
                                    <svg width="11" height="14" viewBox="0 0 11 14" fill="none"><path d="M5.5 0C3.01 0 1 2.01 1 4.5c0 3.37 4.5 9.5 4.5 9.5S10 7.87 10 4.5C10 2.01 7.99 0 5.5 0zm0 6.5a2 2 0 1 1 0-4 2 2 0 0 1 0 4z" fill="currentColor"/></svg>
                                    <?= clean($rev['loc_city'] ?: explode(',', $rev['loc_display'])[0]) ?>
                                </div>
                                <?php endif; ?>
                            </div>
                            <div class="review-ratings">
                                <span class="mini-rating"><?= stars($rev['rating_overall']) ?> <?= __t('company_overall_score') ?></span>
                                <span class="mini-rating <?= ratingColor($rev['rating_pay_equity']) ?>"><?= __t('company_pay_equity_score') ?> <?= $rev['rating_pay_equity'] ?>/5</span>
                                <?php if (!empty($rev['rating_gap_perceived'])): ?>
                                <span class="mini-rating <?= gapRatingClass($rev['rating_gap_perceived']) ?>"><?= __t('rating_gap') ?> <?= $rev['rating_gap_perceived'] ?>/5</span>
                                <?php endif; ?>
                                <span class="mini-rating"><?= __t('company_culture_score') ?> <?= $rev['rating_culture'] ?>/5</span>
                            </div>
                        </div>
                        <?php if ($rev['pros']): ?>
                        <div class="review-section">
                            <div class="review-section-label"><?= __t('pros') ?></div>
                            <div class="review-section-text" data-field="pros" dir="auto"><?= clean($rev['pros']) ?></div>
                        </div>
                        <?php endif; ?>
                        <?php if ($rev['cons']): ?>
                        <div class="review-section">
                            <div class="review-section-label"><?= __t('cons') ?></div>
                            <div class="review-section-text" data-field="cons" dir="auto"><?= clean($rev['cons']) ?></div>
                        </div>
                        <?php endif; ?>
                        <?php if ($rev['advice']): ?>
                        <div class="review-advice" data-field="advice"><?= __t('company_advice_prefix') ?> <span class="advice-text" dir="auto"><?= clean($rev['advice']) ?></span></div>
                        <?php endif; ?>
                        <div style="margin-top:10px;">
                            <button class="btn-translate" data-rev="<?= $i ?>"
                                data-translate-label="<?= __t('translate_btn') ?>"
                                data-translating-label="<?= __t('translating') ?>"
                                data-original-label="<?= __t('show_original') ?>"
                                data-error-label="<?= __t('translate_error') ?>">
                                🌐 <?= __t('translate_btn') ?>
                            </button>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- PAY ANALYSIS TAB -->
        <div class="tab-panel" id="tab-pay">
            <?php if (!empty($overallPay['avg_f']) && !empty($overallPay['avg_m'])): ?>
            <div style="background:var(--ink);color:white;border-radius:var(--r);padding:28px 32px;margin-bottom:28px;display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:20px;">
                <div>
                    <div style="font-size:0.75rem;text-transform:uppercase;letter-spacing:0.1em;color:rgba(255,255,255,0.5);margin-bottom:6px;"><?= __t('company_overall_gap_pre') ?> <?= clean($company['name']) ?></div>
                    <div style="font-family:var(--fd);font-size:2.8rem;font-weight:700;line-height:1;"><?= $overallGap ?>%</div>
                    <div style="color:rgba(255,255,255,0.55);font-size:0.88rem;margin-top:4px;"><?= __t('company_women_earn_pre') ?> <?= 100 - $overallGap ?><?= __t('company_women_earn_post') ?></div>
                </div>
                <div style="display:flex;gap:24px;">
                    <div style="text-align:center;">
                        <div style="font-size:0.72rem;color:rgba(255,255,255,0.45);text-transform:uppercase;letter-spacing:0.08em;margin-bottom:4px;"><?= __t('company_women_avg') ?></div>
                        <div style="font-family:var(--fd);font-size:1.5rem;font-weight:700;color:#5EC99A;"><?= fmt($overallPay['avg_f']) ?></div>
                    </div>
                    <div style="text-align:center;">
                        <div style="font-size:0.72rem;color:rgba(255,255,255,0.45);text-transform:uppercase;letter-spacing:0.08em;margin-bottom:4px;"><?= __t('company_men_avg') ?></div>
                        <div style="font-family:var(--fd);font-size:1.5rem;font-weight:700;color:rgba(255,255,255,0.8);"><?= fmt($overallPay['avg_m']) ?></div>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <?php if (empty($salaryData)): ?>
                <div class="empty">
                    <h3><?= __t('company_no_salary_h') ?></h3>
                    <p><?= __t('company_be_first_salary') ?> <?= clean($company['name']) ?>.</p>
                    <a href="/sheworks/pages/submit-salary.php?company=<?= urlencode($company['name']) ?>&slug=<?= urlencode($company['slug']) ?>" class="btn btn-primary" style="display:inline-block;margin-top:16px;"><?= __t('company_submit_my_salary') ?></a>
                </div>
            <?php else: ?>
                <div class="pay-grid">
                    <?php foreach ($salaryData as $s):
                        $gap = payGap((float)($s['avg_female'] ?? 0), (float)($s['avg_male'] ?? 0));
                        $femalePct = $s['total'] > 0 ? round(($s['female_count'] / $s['total']) * 100) : 0;
                        $sym = $s['currency'] === 'CAD' ? 'C$' : ($s['currency'] === 'GBP' ? '£' : ($s['currency'] === 'EUR' ? '€' : '$'));
                    ?>
                    <div class="pay-card">
                        <h3><?= clean($s['field']) ?></h3>
                        <div class="submissions"><?= $s['total'] ?> <?= $s['total'] != 1 ? __t('field_avg_submissions') : __t('field_avg_submission') ?> · <?= $femalePct ?><?= __t('company_pct_women') ?></div>

                        <?php if ($s['avg_female']): ?>
                        <div class="salary-row">
                            <span class="gender-tag"><?= __t('company_women_avg') ?></span>
                            <span class="salary-val"><?= fmt($s['avg_female'], $sym) ?></span>
                        </div>
                        <?php endif; ?>
                        <?php if ($s['avg_male']): ?>
                        <div class="salary-row">
                            <span class="gender-tag"><?= __t('company_men_avg') ?></span>
                            <span class="salary-val"><?= fmt($s['avg_male'], $sym) ?></span>
                        </div>
                        <?php endif; ?>

                        <?php if ($gap > 0): ?>
                        <div class="gap-bar-wrap">
                            <div class="gap-bar-labels">
                                <span><?= __t('salary_pay_gap_lbl') ?></span>
                                <span class="gap-badge <?= gapClass($gap) ?>"><?= $gap ?>%</span>
                            </div>
                            <div class="gap-bar"><div class="gap-bar-fill" style="width:<?= min($gap * 4, 100) ?>%"></div></div>
                        </div>
                        <?php else: ?>
                            <span class="gap-badge gap-low" style="margin-top:12px;"><?= __t('company_no_gap') ?></span>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

    </div>
</section>
<?php endif; ?>

<style>
.btn-translate {
    background: none;
    border: 1px solid var(--border, #ddd);
    border-radius: 20px;
    padding: 4px 12px;
    font-size: 0.78rem;
    color: var(--muted, #888);
    cursor: pointer;
    transition: border-color 0.15s, color 0.15s;
}
.btn-translate:hover { border-color: var(--gold, #c9a227); color: var(--gold, #c9a227); }
.btn-translate:disabled { opacity: 0.6; cursor: default; }
</style>
<script>
(function () {
    const TARGET_LANG = <?= json_encode($currentLang === 'zh-TW' ? 'zh-TW' : $currentLang) ?>;

    async function translateText(text, targetLang) {
        const url = 'https://api.mymemory.translated.net/get?q=' +
            encodeURIComponent(text) + '&langpair=autodetect|' + encodeURIComponent(targetLang);
        const res = await fetch(url);
        const data = await res.json();
        if (data.responseStatus === 200) return data.responseData.translatedText;
        throw new Error(data.responseDetails || 'Translation failed');
    }

    document.addEventListener('click', async function (e) {
        const btn = e.target.closest('.btn-translate');
        if (!btn) return;

        const revId = btn.dataset.rev;
        const card = document.querySelector(`.review-card[data-rev="${revId}"]`);
        const isTranslated = btn.dataset.translated === '1';

        if (isTranslated) {
            // Restore originals
            card.querySelectorAll('[data-field]').forEach(el => {
                if (el.dataset.field === 'advice') {
                    el.querySelector('.advice-text').textContent = el.dataset.original;
                } else {
                    el.textContent = el.dataset.original;
                }
                delete el.dataset.original;
            });
            btn.textContent = '🌐 ' + btn.dataset.translateLabel;
            btn.dataset.translated = '0';
            return;
        }

        btn.disabled = true;
        btn.textContent = btn.dataset.translatingLabel;

        try {
            const fields = card.querySelectorAll('[data-field]');
            for (const el of fields) {
                const textEl = el.dataset.field === 'advice' ? el.querySelector('.advice-text') : el;
                const original = textEl.textContent.trim();
                if (!original) continue;
                el.dataset.original = original;
                const translated = await translateText(original, TARGET_LANG);
                textEl.textContent = translated;
            }
            btn.dataset.translated = '1';
            btn.textContent = '🌐 ' + btn.dataset.originalLabel;
        } catch (err) {
            btn.textContent = btn.dataset.errorLabel;
            setTimeout(() => {
                btn.textContent = '🌐 ' + btn.dataset.translateLabel;
                btn.disabled = false;
            }, 2000);
            return;
        }

        btn.disabled = false;
    });
}());
</script>

<?php if (!empty($locations)): ?>
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"/>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
(function () {
    const locations = <?= json_encode(array_map(fn($l) => [
        'id'           => (int)$l['id'],
        'lat'          => (float)$l['lat'],
        'lng'          => (float)$l['lng'],
        'city'         => $l['city'] ?: explode(',', $l['display_name'])[0],
        'display_name' => $l['display_name'],
        'review_count' => (int)$l['review_count'],
    ], $locations)) ?>;

    const map = L.map('company-map', { scrollWheelZoom: false });
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '© <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>',
        maxZoom: 19
    }).addTo(map);

    const markerIcon = L.divIcon({ className: 'map-pin', html: '<span></span>', iconSize: [18,18] });
    const markerIconActive = L.divIcon({ className: 'map-pin map-pin-active', html: '<span></span>', iconSize: [22,22] });

    const markers = locations.map(loc => {
        const m = L.marker([loc.lat, loc.lng], { icon: markerIcon }).addTo(map);
        m.bindPopup(
            `<strong>${loc.city}</strong><br><small>${loc.display_name}</small><br>${loc.review_count} review${loc.review_count !== 1 ? 's' : ''}`
        );
        m.on('click', () => activateLocation(loc.id, m));
        m._locId = loc.id;
        return m;
    });

    if (locations.length === 1) {
        map.setView([locations[0].lat, locations[0].lng], 14);
    } else {
        const group = L.featureGroup(markers);
        map.fitBounds(group.getBounds().pad(0.25));
    }

    function activateLocation(locId, clickedMarker) {
        // Update filter bar
        document.querySelectorAll('.loc-filter-btn').forEach(btn => {
            btn.classList.toggle('active', btn.dataset.loc === String(locId));
        });
        filterReviews(locId);
        // Scroll to reviews tab
        document.querySelector('[data-tab="reviews"]').click();
        document.getElementById('tab-reviews').scrollIntoView({ behavior: 'smooth', block: 'start' });
        // Reset icons
        markers.forEach(m => m.setIcon(m._locId === locId ? markerIconActive : markerIcon));
    }

    // Location filter bar click
    document.getElementById('location-filter-bar').addEventListener('click', function (e) {
        const btn = e.target.closest('.loc-filter-btn');
        if (!btn) return;
        document.querySelectorAll('.loc-filter-btn').forEach(b => b.classList.remove('active'));
        btn.classList.add('active');
        const locId = btn.dataset.loc === 'all' ? 'all' : parseInt(btn.dataset.loc);
        filterReviews(locId);
        // Update marker icons
        markers.forEach(m => {
            m.setIcon(locId === 'all' || m._locId === locId ? markerIconActive : markerIcon);
        });
        if (locId !== 'all') {
            const loc = locations.find(l => l.id === locId);
            if (loc) map.setView([loc.lat, loc.lng], 15);
        } else {
            const group = L.featureGroup(markers);
            map.fitBounds(group.getBounds().pad(0.25));
            markers.forEach(m => m.setIcon(markerIcon));
        }
    });

    function filterReviews(locId) {
        document.querySelectorAll('.review-card').forEach(card => {
            const cardLoc = parseInt(card.dataset.locId) || 0;
            card.hidden = locId !== 'all' && cardLoc !== locId;
        });
        const visible = document.querySelectorAll('.review-card:not([hidden])').length;
        const empty = document.querySelector('.review-list-empty');
        if (empty) empty.hidden = visible > 0;
    }
}());
</script>
<?php endif; ?>

<?php include __DIR__ . '/../includes/footer.php'; ?>
