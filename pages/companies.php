<?php
require_once __DIR__ . '/../config.php';
$pageTitle = 'Browse Companies';
$db = db();

$filterIndustry = clean($_GET['industry'] ?? '');
$filterSort     = clean($_GET['sort']     ?? 'pay_equity');

$allowedSorts = ['pay_equity' => 'avg_pay_equity', 'overall' => 'avg_overall', 'reviews' => 'review_count'];
$sortCol = $allowedSorts[$filterSort] ?? 'avg_pay_equity';

$industryWhere = '';
$params = [];
if ($filterIndustry) {
    $industryWhere = "AND c.industry = ?";
    $params[] = $filterIndustry;
}

$stmt = $db->prepare("
    SELECT c.id, c.name, c.slug, c.industry, c.hq_city, c.hq_country,
           ROUND(AVG(r.rating_overall),1)     AS avg_overall,
           ROUND(AVG(r.rating_pay_equity),1)  AS avg_pay_equity,
           ROUND(AVG(r.rating_culture),1)     AS avg_culture,
           ROUND(AVG(r.rating_growth),1)      AS avg_growth,
           ROUND(AVG(r.rating_flexibility),1) AS avg_flex,
           COUNT(r.id) AS review_count,
           COUNT(CASE WHEN r.gender='female' THEN 1 END) AS female_reviews
    FROM companies c
    LEFT JOIN reviews r ON r.company_id = c.id AND r.approved = 1
    WHERE 1=1 $industryWhere
    GROUP BY c.id
    ORDER BY $sortCol DESC, review_count DESC
    LIMIT 50
");
if ($filterIndustry) {
    $stmt->bind_param('s', $filterIndustry);
}
$stmt->execute();
$companies = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Industries for filter
$result = $db->query("SELECT DISTINCT industry FROM companies WHERE industry IS NOT NULL ORDER BY industry");
$industries = array_column($result->fetch_all(MYSQLI_ASSOC), 'industry');

// All locations with coordinates for the map
$mapLocations = $db->query("
    SELECT l.id, l.lat, l.lng, l.city, l.display_name, c.name AS company_name, c.slug,
           COUNT(r.id) AS review_count
    FROM locations l
    JOIN companies c ON c.id = l.company_id
    LEFT JOIN reviews r ON r.location_id = l.id AND r.approved = 1
    WHERE l.lat IS NOT NULL AND l.lng IS NOT NULL
    GROUP BY l.id
")->fetch_all(MYSQLI_ASSOC);
?>
<?php include __DIR__ . '/../includes/header.php'; ?>

<div style="background:var(--ink);color:white;padding:56px 32px 48px;">
    <div style="max-width:1240px;margin:0 auto;display:flex;align-items:flex-end;justify-content:space-between;flex-wrap:wrap;gap:16px;">
        <div>
            <h1 style="font-family:var(--fd);font-size:clamp(2rem,5vw,3rem);font-weight:700;letter-spacing:-0.02em;margin-bottom:8px;"><?= __t('browse_title') ?></h1>
            <p style="color:rgba(255,255,255,0.55);"><?= __t('browse_sub') ?></p>
        </div>
        <?php if (!empty($mapLocations)): ?>
        <div class="view-toggle" id="view-toggle">
            <button class="view-toggle-btn active" data-view="list"><?= __t('list_view') ?></button>
            <button class="view-toggle-btn" data-view="map"><?= __t('map_view') ?></button>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php if (!empty($mapLocations)): ?>
<div id="companies-map-panel" class="companies-map-panel" hidden>
    <div id="companies-map" class="companies-map"></div>
</div>
<?php endif; ?>

<section class="section section-warm" id="companies-list-panel">
    <div class="section-inner">

        <!-- Filters -->
        <form method="GET" class="filter-bar">
            <div class="form-group">
                <label><?= __t('filter_industry') ?></label>
                <select name="industry" class="form-control">
                    <option value=""><?= __t('filter_all') ?></option>
                    <?php foreach ($industries as $ind): ?>
                        <option value="<?= clean($ind) ?>" <?= $filterIndustry === $ind ? 'selected' : '' ?>><?= translateIndustry($ind) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label><?= __t('filter_sort') ?></label>
                <select name="sort" class="form-control">
                    <option value="pay_equity" <?= $filterSort === 'pay_equity' ? 'selected' : '' ?>><?= __t('sort_pay_equity') ?></option>
                    <option value="overall"    <?= $filterSort === 'overall'    ? 'selected' : '' ?>><?= __t('sort_overall') ?></option>
                    <option value="reviews"    <?= $filterSort === 'reviews'    ? 'selected' : '' ?>><?= __t('sort_reviews') ?></option>
                </select>
            </div>
            <div class="form-group" style="flex:0 0 auto;align-self:flex-end;">
                <button type="submit" class="btn btn-primary" style="width:auto;padding:12px 24px;"><?= __t('filter_apply') ?></button>
                <?php if ($filterIndustry): ?>
                    <a href="/sheworks/pages/companies.php" class="btn btn-outline" style="margin-left:8px;padding:12px 20px;"><?= __t('filter_clear') ?></a>
                <?php endif; ?>
            </div>
        </form>

        <?php if (empty($companies)): ?>
            <div class="empty"><h3><?= __t('no_companies') ?></h3></div>
        <?php else: ?>
            <div class="company-grid">
                <?php foreach ($companies as $co): ?>
                <a href="/sheworks/pages/company.php?slug=<?= urlencode($co['slug']) ?>" class="company-card">
                    <div class="company-name"><?= clean($co['name']) ?></div>
                    <div class="company-meta">
                        <?= translateIndustry($co['industry'] ?? '') ?><?= $co['hq_city'] ? ' · ' . clean($co['hq_city']) : '' ?>
                        · <?= $co['review_count'] ?> <?= __t('review_label') ?><?= $co['review_count'] != 1 ? 's' : '' ?>
                    </div>

                    <?php if ($co['review_count'] > 0): ?>
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
                    <div class="rating-row">
                        <span class="rating-label"><?= __t('rating_growth') ?></span>
                        <span class="rating-val <?= ratingColor($co['avg_growth']) ?>"><?= $co['avg_growth'] ?>/5</span>
                    </div>
                    <?php else: ?>
                    <p style="color:var(--muted);font-size:0.85rem;margin-top:8px;"><?= __t('no_reviews') ?></p>
                    <?php endif; ?>
                </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <div style="text-align:center;margin-top:40px;">
            <a href="/sheworks/pages/review.php" class="btn btn-primary"><?= __t('add_review_cta') ?></a>
        </div>
    </div>
</section>

<?php if (!empty($mapLocations)): ?>
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"/>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
(function () {
    const mapLocations = <?= json_encode(array_map(fn($l) => [
        'id'           => (int)$l['id'],
        'lat'          => (float)$l['lat'],
        'lng'          => (float)$l['lng'],
        'city'         => $l['city'] ?: explode(',', $l['display_name'])[0],
        'company_name' => $l['company_name'],
        'slug'         => $l['slug'],
        'review_count' => (int)$l['review_count'],
    ], $mapLocations)) ?>;

    let mapInitialised = false;
    let leafletMap;

    const toggle = document.getElementById('view-toggle');
    const listPanel = document.getElementById('companies-list-panel');
    const mapPanel  = document.getElementById('companies-map-panel');

    if (toggle) {
        toggle.addEventListener('click', function (e) {
            const btn = e.target.closest('.view-toggle-btn');
            if (!btn) return;
            document.querySelectorAll('.view-toggle-btn').forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
            if (btn.dataset.view === 'map') {
                listPanel.hidden = true;
                mapPanel.hidden  = false;
                if (!mapInitialised) initMap();
            } else {
                mapPanel.hidden  = true;
                listPanel.hidden = false;
            }
        });
    }

    function initMap() {
        mapInitialised = true;
        leafletMap = L.map('companies-map', { scrollWheelZoom: false });
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '© <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>',
            maxZoom: 19
        }).addTo(leafletMap);

        const markerIcon = L.divIcon({ className: 'map-pin', html: '<span></span>', iconSize: [18,18] });
        const markers = mapLocations.map(loc => {
            const m = L.marker([loc.lat, loc.lng], { icon: markerIcon }).addTo(leafletMap);
            m.bindPopup(
                `<strong><a href="/sheworks/pages/company.php?slug=${encodeURIComponent(loc.slug)}" style="color:var(--gold,#c8973a)">${loc.company_name}</a></strong>` +
                `<br><small>${loc.city}</small>` +
                `<br>${loc.review_count} review${loc.review_count !== 1 ? 's' : ''}`
            );
            return m;
        });

        if (markers.length === 1) {
            leafletMap.setView([mapLocations[0].lat, mapLocations[0].lng], 13);
        } else {
            leafletMap.fitBounds(L.featureGroup(markers).getBounds().pad(0.15));
        }
    }
}());
</script>
<?php endif; ?>

<?php include __DIR__ . '/../includes/footer.php'; ?>
