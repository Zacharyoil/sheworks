<?php
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

require_once __DIR__ . '/../config.php';
$pageTitle = __t('nav_review');
$db = db();

$success = false;
$errors  = [];

// Pre-fill from query string (coming from company page)
$preCompany = clean($_GET['company'] ?? '');
$preSlug    = clean($_GET['slug']    ?? '');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!rateLimitOk('reviews')) {
        $errors[] = $t['err_rate_limit'];
    } else {
        $companyName     = clean($_POST['company_name']        ?? '');
        $companyIndustry = clean($_POST['company_industry']   ?? '');
        // Location fields (from Nominatim autocomplete)
        $locDisplay      = clean($_POST['location_display']   ?? '');
        $locCity         = clean($_POST['location_city']      ?? '');
        $locCountry      = clean($_POST['location_country']   ?? '');
        $locLat          = floatval($_POST['location_lat']    ?? 0);
        $locLng          = floatval($_POST['location_lng']    ?? 0);
        $jobTitle        = clean($_POST['job_title']          ?? '');
        $field           = clean($_POST['field']              ?? '');
        $gender          = clean($_POST['gender']             ?? '');
        $employment      = clean($_POST['employment_status']  ?? 'current');
        $yearsAt         = intval($_POST['years_at_company']  ?? 0);
        $rOverall        = intval($_POST['rating_overall']    ?? 0);
        $rPayEquity      = intval($_POST['rating_pay_equity'] ?? 0);
        $rGapPerceived   = intval($_POST['rating_gap_perceived'] ?? 0);
        $rCulture        = intval($_POST['rating_culture']    ?? 0);
        $rGrowth         = intval($_POST['rating_growth']     ?? 0);
        $rFlex           = intval($_POST['rating_flexibility'] ?? 0);
        $pros            = clean($_POST['pros']               ?? '');
        $cons            = clean($_POST['cons']               ?? '');
        $advice          = clean($_POST['advice']             ?? '');

        if (!$companyName)                      $errors[] = $t['err_company_name'];
        if (!$jobTitle)                         $errors[] = $t['err_job_title'];
        if (!$field)                            $errors[] = $t['err_select_field'];
        if (!$gender)                           $errors[] = $t['err_select_gender'];
        if (!in_array($rOverall,      [1,2,3,4,5])) $errors[] = $t['err_rate_overall'];
        if (!in_array($rPayEquity,    [1,2,3,4,5])) $errors[] = $t['err_rate_pay_equity'];
        if (!in_array($rGapPerceived, [1,2,3,4,5])) $errors[] = $t['err_rate_gap'];
        if (!in_array($rCulture,      [1,2,3,4,5])) $errors[] = $t['err_rate_culture'];
        if (!in_array($rGrowth,       [1,2,3,4,5])) $errors[] = $t['err_rate_growth'];
        if (!in_array($rFlex,         [1,2,3,4,5])) $errors[] = $t['err_rate_flexibility'];

        if (empty($errors)) {
            $companyId = findOrCreateCompany($companyName, $locCity, $locDisplay, $companyIndustry);
            $ip = userIP();

            // Create location record if user picked one from autocomplete
            $locationId = null;
            if ($locLat && $locLng && $locDisplay) {
                $locationId = findOrCreateLocation($companyId, $locDisplay, $locCity, $locCountry, $locLat, $locLng);
            }

            $stmt = $db->prepare("
                INSERT INTO reviews
                    (company_id, location_id, job_title, field, gender, employment_status, years_at_company,
                     rating_overall, rating_pay_equity, rating_gap_perceived, rating_culture,
                     rating_growth, rating_flexibility, pros, cons, advice, ip_address)
                VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)
            ");
            $stmt->bind_param(
                'iissssiiiiiiissss',
                $companyId, $locationId, $jobTitle, $field, $gender, $employment, $yearsAt,
                $rOverall, $rPayEquity, $rGapPerceived, $rCulture, $rGrowth, $rFlex,
                $pros, $cons, $advice, $ip
            );
            $stmt->execute();
            $stmt->close();
            $success = true;

            $slug = slugify($companyName);
        }
    }
}

$allFields  = jobFields();
$industries = ['Technology','Healthcare','Finance','Education','Legal','Media & Creative','Engineering','Retail','Government','Non-profit','Other'];
?>
<?php include __DIR__ . '/../includes/header.php'; ?>

<div class="form-page-hero">
    <h1><?= __t('nav_review') ?></h1>
    <p><?= __t('review_hero_p') ?></p>
</div>

<div class="form-wrap">
    <?php if ($success): ?>
        <div class="alert alert-success" style="font-size:1rem;padding:22px 24px;margin-top:24px;">
            <?= $t['review_success'] ?>
            <br><br>
            <a href="/sheworks/pages/company.php?slug=<?= urlencode(slugify($_POST['company_name'] ?? '')) ?>" class="btn btn-primary" style="display:inline-block;margin-top:10px;"><?= __t('review_view_company_btn') ?> <?= clean($_POST['company_name'] ?? '') ?> →</a>
        </div>
    <?php else: ?>
        <?php if (!empty($errors)): ?>
            <div class="alert alert-error">
                <?php foreach ($errors as $e): ?><div>⚠ <?= $e ?></div><?php endforeach; ?>
            </div>
        <?php endif; ?>

        <div class="form-card">
            <form method="POST">

                <p class="form-section-title"><?= __t('section_company_role') ?></p>

                <div class="form-group">
                    <label for="company_name"><?= __t('label_company_name') ?></label>
                    <input type="text" name="company_name" id="company_name" class="form-control"
                        placeholder="e.g. Google, City Hospital…" maxlength="200" required
                        value="<?= $preCompany ?: clean($_POST['company_name'] ?? '') ?>">
                    <small style="color:var(--muted);font-size:0.78rem;margin-top:4px;display:block;"><?= __t('label_company_name_hint') ?></small>
                </div>

                <div class="form-group">
                    <label for="location_search"><?= __t('label_branch_location') ?></label>
                    <div class="location-autocomplete-wrap">
                        <input type="text" id="location_search" class="form-control"
                            placeholder="e.g. 123 Main St, New York…" autocomplete="off"
                            value="<?= clean($_POST['location_display'] ?? '') ?>">
                        <ul id="location_suggestions" class="location-suggestions" hidden></ul>
                    </div>
                    <input type="hidden" name="location_display"  id="location_display"  value="<?= clean($_POST['location_display']  ?? '') ?>">
                    <input type="hidden" name="location_city"     id="location_city"     value="<?= clean($_POST['location_city']     ?? '') ?>">
                    <input type="hidden" name="location_country"  id="location_country"  value="<?= clean($_POST['location_country']  ?? '') ?>">
                    <input type="hidden" name="location_lat"      id="location_lat"      value="<?= clean($_POST['location_lat']      ?? '') ?>">
                    <input type="hidden" name="location_lng"      id="location_lng"      value="<?= clean($_POST['location_lng']      ?? '') ?>">
                    <small style="color:var(--muted);font-size:0.78rem;margin-top:4px;display:block;"><?= __t('label_branch_location_hint') ?></small>
                </div>

                <div class="form-group">
                    <label for="company_industry"><?= __t('filter_industry') ?></label>
                    <select name="company_industry" id="company_industry" class="form-control">
                        <option value=""><?= __t('select_industry_opt') ?></option>
                        <?php foreach ($industries as $ind): ?>
                            <option value="<?= clean($ind) ?>" <?= (($_POST['company_industry'] ?? '') === $ind) ? 'selected' : '' ?>><?= clean($ind) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="job_title"><?= __t('label_job_title') ?></label>
                        <input type="text" name="job_title" id="job_title" class="form-control"
                            placeholder="e.g. Senior Engineer" maxlength="200" required
                            value="<?= clean($_POST['job_title'] ?? '') ?>">
                    </div>
                    <div class="form-group">
                        <label for="field"><?= __t('form_field') ?></label>
                        <select name="field" id="field" class="form-control" required>
                            <option value=""><?= __t('form_field_slct') ?></option>
                            <?php foreach ($allFields as $cat => $flds): ?>
                                <optgroup label="<?= clean($cat) ?>">
                                    <?php foreach ($flds as $fn): ?>
                                        <option value="<?= clean($fn) ?>" <?= (($_POST['field'] ?? '') === $fn) ? 'selected' : '' ?>><?= clean($fn) ?></option>
                                    <?php endforeach; ?>
                                </optgroup>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="employment_status"><?= __t('label_status') ?></label>
                        <select name="employment_status" id="employment_status" class="form-control">
                            <option value="current"><?= __t('status_current') ?></option>
                            <option value="former"><?= __t('status_former') ?></option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="years_at_company"><?= __t('label_years_company') ?></label>
                        <select name="years_at_company" id="years_at_company" class="form-control">
                            <option value="0"><?= __t('years_less_1') ?></option>
                            <?php for ($i = 1; $i <= 20; $i++): ?>
                                <option value="<?= $i ?>"><?= $i ?> year<?= $i > 1 ? 's' : '' ?></option>
                            <?php endfor; ?>
                            <option value="21"><?= __t('years_20plus') ?></option>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label for="gender"><?= __t('label_gender') ?></label>
                    <select name="gender" id="gender" class="form-control" required>
                        <option value=""><?= __t('gender_select') ?></option>
                        <option value="female" <?= (($_POST['gender'] ?? '') === 'female') ? 'selected' : '' ?>><?= __t('gender_female') ?></option>
                        <option value="male"   <?= (($_POST['gender'] ?? '') === 'male')   ? 'selected' : '' ?>><?= __t('gender_male') ?></option>
                        <option value="non_binary" <?= (($_POST['gender'] ?? '') === 'non_binary') ? 'selected' : '' ?>><?= __t('gender_non_binary') ?></option>
                        <option value="prefer_not_to_say" <?= (($_POST['gender'] ?? '') === 'prefer_not_to_say') ? 'selected' : '' ?>><?= __t('gender_prefer_not') ?></option>
                    </select>
                </div>

                <p class="form-section-title"><?= __t('section_ratings') ?></p>
                <p style="color:var(--muted);font-size:0.85rem;margin-bottom:20px;"><?= __t('ratings_hint') ?></p>

                <?php
                $ratingFields = [
                    ['rating_overall',       'rating_overall_label', 'rating_overall_hint'],
                    ['rating_pay_equity',    'rating_pay_label',     'rating_pay_hint'],
                    ['rating_gap_perceived', 'rating_gap_label',     'rating_gap_hint'],
                    ['rating_culture',       'rating_culture_label', 'rating_culture_hint'],
                    ['rating_growth',        'rating_growth_label',  'rating_growth_hint'],
                    ['rating_flexibility',   'rating_flex_label',    'rating_flex_hint'],
                ];
                foreach ($ratingFields as [$name, $labelKey, $hintKey]):
                    $current = intval($_POST[$name] ?? 0);
                    $isGapRating = ($name === 'rating_gap_perceived');
                ?>
                <div class="form-group">
                    <label><?= __t($labelKey) ?><?= $isGapRating ? ' <span style="font-weight:400;color:var(--muted);font-size:0.8rem;">(' . __t('rating_gap_scale') . ')</span>' : '' ?></label>
                    <div class="star-rating <?= $isGapRating ? 'star-rating-gap' : '' ?>" id="sr-<?= $name ?>">
                        <?php for ($i = 5; $i >= 1; $i--): ?>
                            <input type="radio" name="<?= $name ?>" id="<?= $name ?>-<?= $i ?>" value="<?= $i ?>" <?= $current === $i ? 'checked' : '' ?> required>
                            <label for="<?= $name ?>-<?= $i ?>" title="<?= $i ?> stars">★</label>
                        <?php endfor; ?>
                    </div>
                    <?php if ($isGapRating): ?>
                    <div style="display:flex;justify-content:space-between;font-size:0.72rem;color:var(--muted);margin-top:4px;max-width:180px;">
                        <span><?= __t('rating_gap_low_scale') ?></span><span><?= __t('rating_gap_high_scale') ?></span>
                    </div>
                    <?php endif; ?>
                    <small style="color:var(--muted);font-size:0.75rem;margin-top:4px;display:block;"><?= $t[$hintKey] ?></small>
                </div>
                <?php endforeach; ?>

                <p class="form-section-title"><?= __t('section_your_review') ?></p>

                <div class="form-group">
                    <label for="pros"><?= __t('label_pros') ?></label>
                    <textarea name="pros" id="pros" class="form-control" rows="3" maxlength="800" placeholder="<?= __t('pros_placeholder') ?>"><?= clean($_POST['pros'] ?? '') ?></textarea>
                </div>

                <div class="form-group">
                    <label for="cons"><?= __t('label_cons') ?></label>
                    <textarea name="cons" id="cons" class="form-control" rows="3" maxlength="800" placeholder="<?= __t('cons_placeholder') ?>"><?= clean($_POST['cons'] ?? '') ?></textarea>
                </div>

                <div class="form-group">
                    <label for="advice"><?= __t('label_advice') ?></label>
                    <textarea name="advice" id="advice" class="form-control" rows="2" maxlength="500" placeholder="<?= __t('advice_placeholder') ?>"><?= clean($_POST['advice'] ?? '') ?></textarea>
                </div>

                <div class="privacy-note">
                    <?= __t('review_privacy_note') ?>
                </div>

                <button type="submit" class="btn btn-primary" style="width:100%;margin-top:24px;justify-content:center;padding:15px;"><?= __t('review_submit_btn') ?></button>
            </form>
        </div>
    <?php endif; ?>
</div>

<script>
(function () {
    const searchEl = document.getElementById('location_search');
    const suggestEl = document.getElementById('location_suggestions');
    if (!searchEl) return;
    let debounce;

    searchEl.addEventListener('input', function () {
        clearTimeout(debounce);
        // Clear hidden fields when user edits the text
        ['location_display','location_city','location_country','location_lat','location_lng']
            .forEach(id => document.getElementById(id).value = '');
        const q = this.value.trim();
        if (q.length < 3) { suggestEl.hidden = true; return; }
        debounce = setTimeout(() => fetchSuggestions(q), 400);
    });

    async function fetchSuggestions(q) {
        try {
            const res = await fetch(
                'https://nominatim.openstreetmap.org/search?format=json&limit=6&addressdetails=1&q=' +
                encodeURIComponent(q),
                { headers: { 'Accept-Language': 'en' } }
            );
            renderSuggestions(await res.json());
        } catch (e) { suggestEl.hidden = true; }
    }

    function renderSuggestions(items) {
        suggestEl.innerHTML = '';
        if (!items.length) { suggestEl.hidden = true; return; }
        items.forEach(item => {
            const li = document.createElement('li');
            li.textContent = item.display_name;
            li.addEventListener('mousedown', e => { e.preventDefault(); selectLocation(item); });
            suggestEl.appendChild(li);
        });
        suggestEl.hidden = false;
    }

    function selectLocation(item) {
        const addr = item.address || {};
        const city = addr.city || addr.town || addr.village || addr.county || '';
        searchEl.value = item.display_name;
        document.getElementById('location_display').value  = item.display_name;
        document.getElementById('location_city').value     = city;
        document.getElementById('location_country').value  = addr.country || '';
        document.getElementById('location_lat').value      = item.lat;
        document.getElementById('location_lng').value      = item.lon;
        suggestEl.hidden = true;
    }

    document.addEventListener('click', function (e) {
        if (!suggestEl.contains(e.target) && e.target !== searchEl) suggestEl.hidden = true;
    });
}());
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>

<?php
/*
── MIGRATION SQL ────────────────────────────────────────────────────────────
Run this once on your database to add the new columns:

ALTER TABLE reviews
  ADD COLUMN rating_gap_perceived TINYINT UNSIGNED NOT NULL DEFAULT 1
  AFTER rating_pay_equity;

ALTER TABLE companies
  ADD COLUMN IF NOT EXISTS hq_city    VARCHAR(120) NULL,
  ADD COLUMN IF NOT EXISTS hq_country VARCHAR(200) NULL;

-- hq_country is reused for the address string for backward compat.
-- If you prefer a separate column, add hq_address VARCHAR(200) NULL instead.
────────────────────────────────────────────────────────────────────────────
*/
?>
