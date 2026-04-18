<?php
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);
require_once __DIR__ . '/../config.php';
$pageTitle = __t('pay_analysis_h');
$db = db();

$allFields  = jobFields();
$analysed   = false;
$errors     = [];

$flatFields = [];
foreach ($allFields as $cat => $flds) {
    foreach ($flds as $fn) $flatFields[] = $fn;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $monthlyPay  = floatval($_POST['monthly_pay'] ?? 0);
    $field       = clean($_POST['field']          ?? '');
    $userCity    = clean($_POST['city']           ?? '');

    if ($monthlyPay < 100)  $errors[] = $t['err_valid_monthly_pay'];
    if (!$field)            $errors[] = $t['err_select_field'];

    if (empty($errors)) {
        $annualPay = $monthlyPay * 12;
        $analysed  = true;

        $avgStmt = $db->prepare("
            SELECT
                ROUND(AVG(salary), 0)   AS avg_salary,
                COUNT(*)                AS total_submissions
            FROM salaries
            WHERE field = ? AND gender = 'female' AND approved = 1
        ");
        $avgStmt->bind_param('s', $field);
        $avgStmt->execute();
        $avgData = $avgStmt->get_result()->fetch_assoc();
        $avgStmt->close();

        $avgSalary   = (float)($avgData['avg_salary'] ?? 0);
        $totalSubs   = (int)($avgData['total_submissions'] ?? 0);

        $medianSalary = medianFemaleSalary($field);

        $gapStmt = $db->prepare("
            SELECT ROUND(AVG(rating_gap_perceived), 2) AS avg_gap_rating
            FROM reviews
            WHERE field = ? AND approved = 1
              AND rating_gap_perceived IS NOT NULL
              AND rating_gap_perceived > 0
        ");
        $gapStmt->bind_param('s', $field);
        $gapStmt->execute();
        $gapData = $gapStmt->get_result()->fetch_assoc();
        $gapStmt->close();
        $avgGapRating = (float)($gapData['avg_gap_rating'] ?? 0);

        $gapCategory  = $avgGapRating > 0 ? gapRatingCategory($avgGapRating) : 'Insufficient data';
        $isHighGap    = $avgGapRating > 4;
        $isMediumGap  = $avgGapRating > 3 && $avgGapRating <= 4;

        $belowAverage = ($avgSalary > 0 && $annualPay < $avgSalary);
        $diffFromAvg  = $avgSalary > 0 ? abs($annualPay - $avgSalary) : 0;
        $belowMedian  = ($medianSalary > 0 && $annualPay < $medianSalary);
        $diffFromMed  = $medianSalary > 0 ? abs($annualPay - $medianSalary) : 0;

        $topCompanies = [];
        if ($userCity && ($isHighGap || $belowAverage)) {
            $cityLike = '%' . $userCity . '%';
            $topStmt = $db->prepare("
                SELECT c.name, c.slug, c.hq_city,
                       ROUND(AVG(r.rating_overall), 1)     AS avg_overall,
                       ROUND(AVG(r.rating_pay_equity), 1)  AS avg_pay_equity,
                       COUNT(r.id)                          AS review_count
                FROM companies c
                JOIN reviews r ON r.company_id = c.id AND r.approved = 1
                WHERE c.hq_city LIKE ?
                  AND r.field = ?
                GROUP BY c.id
                HAVING review_count >= 1
                ORDER BY avg_pay_equity DESC, avg_overall DESC
                LIMIT 10
            ");
            $topStmt->bind_param('ss', $cityLike, $field);
            $topStmt->execute();
            $topCompanies = $topStmt->get_result()->fetch_all(MYSQLI_ASSOC);
            $topStmt->close();
        }

        $scenario = '';
        if ($isHighGap && $belowAverage)      $scenario = 'A';
        elseif ($isHighGap && !$belowAverage) $scenario = 'B';
        elseif (!$isHighGap && $belowAverage) $scenario = 'C';
    }
}
?>
<?php include __DIR__ . '/../includes/header.php'; ?>

<div class="form-page-hero">
    <h1><?= __t('pay_analysis_h') ?></h1>
    <p><?= __t('pay_analysis_p') ?></p>
</div>

<div class="form-wrap">

    <?php if (!empty($errors)): ?>
        <div class="alert alert-error"><?php foreach ($errors as $e): ?><div>⚠ <?= $e ?></div><?php endforeach; ?></div>
    <?php endif; ?>

    <div class="form-card">
        <form method="POST">
            <p class="form-section-title"><?= __t('form_title') ?></p>

            <div class="form-row">
                <div class="form-group">
                    <label for="monthly_pay"><?= __t('form_salary') ?></label>
                    <input type="number" name="monthly_pay" id="monthly_pay" class="form-control"
                        placeholder="e.g. 5000" min="100" required
                        value="<?= isset($_POST['monthly_pay']) ? floatval($_POST['monthly_pay']) : '' ?>">
                    <small style="color:var(--muted);font-size:0.78rem;margin-top:4px;display:block;"> <?= __t('form_salary_plc') ?></small>
                </div>
                <div class="form-group">
                    <label for="field"><?= __t('form_field') ?></label>
                    <select name="field" id="field" class="form-control" required>
                        <option value=""> <?= __t('form_field_slct') ?></option>
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

            <div class="form-group">
                <label for="city"><?= __t('form_city') ?> <span style="font-weight:400;color:var(--muted);font-size:0.8rem;">(<?= __t('form_city_opt') ?>)</span></label>
                <input type="text" name="city" id="city" class="form-control"
                    placeholder="<?= __t('form_city_plc') ?>" maxlength="100"
                    value="<?= clean($_POST['city'] ?? '') ?>">
            </div>

            <button type="submit" class="btn btn-primary" style="width:100%;margin-top:20px;justify-content:center;padding:15px;">
                <?= __t('form_analyze_btn') ?>
            </button>
        </form>
    </div>

    <?php if ($analysed): ?>
    <div style="margin-top:36px;">

        <?php if ($totalSubs === 0): ?>
        <div style="background:var(--gold-pale);border:1px solid var(--border-gold);border-radius:var(--r);padding:28px;text-align:center;">
            <div style="font-size:2rem;margin-bottom:8px;">📊</div>
            <strong><?= __t('no_results_h') ?>"<?= clean($field) ?>".</strong>
            <p style="color:var(--muted);margin-top:8px;"><?= __t('no_results_p') ?></p>
            <a href="/sheworks/pages/submit-salary.php" class="btn btn-primary" style="display:inline-block;margin-top:16px;"><?= __t('no_results_btn') ?></a>
        </div>

        <?php else: ?>

        <!-- COMPARISON BLOCK -->
        <div style="background:var(--ink);color:white;border-radius:var(--r);padding:32px;margin-bottom:24px;">
            <div style="font-size:0.72rem;text-transform:uppercase;letter-spacing:0.12em;color:rgba(255,255,255,0.45);margin-bottom:6px;">
                <?= __t('pay_vs_field_pre') ?> <?= clean($field) ?><?= __t('pay_vs_field_post') ?>
            </div>
            <div style="font-family:var(--fd);font-size:3rem;font-weight:700;color:white;line-height:1;margin-bottom:4px;">
                $<?= number_format($monthlyPay) ?>/mo
            </div>
            <p style="color:rgba(255,255,255,0.45);font-size:0.85rem;margin-bottom:28px;">$<?= number_format($annualPay) ?> <?= __t('annually_estimated') ?></p>

            <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:24px;">
                <div style="background:rgba(255,255,255,0.07);border-radius:var(--r-sm);padding:20px;">
                    <div style="font-size:0.7rem;color:rgba(255,255,255,0.45);text-transform:uppercase;letter-spacing:0.1em;margin-bottom:6px;"><?= __t('field_avg_lbl') ?></div>
                    <div style="font-family:var(--fd);font-size:1.7rem;font-weight:700;color:#5EC99A;">$<?= number_format($avgSalary) ?>/yr</div>
                    <div style="font-size:0.8rem;color:rgba(255,255,255,0.4);margin-top:4px;"><?= __t('field_avg_based') ?> <?= $totalSubs ?> <?= $totalSubs != 1 ? __t('field_avg_submissions') : __t('field_avg_submission') ?></div>
                </div>
                <div style="background:rgba(255,255,255,0.07);border-radius:var(--r-sm);padding:20px;">
                    <div style="font-size:0.7rem;color:rgba(255,255,255,0.45);text-transform:uppercase;letter-spacing:0.1em;margin-bottom:6px;"><?= __t('field_median_lbl') ?></div>
                    <div style="font-family:var(--fd);font-size:1.7rem;font-weight:700;color:#7EC8E3;">
                        <?= $medianSalary > 0 ? '$' . number_format($medianSalary) . '/yr' : 'N/A' ?>
                    </div>
                    <div style="font-size:0.8rem;color:rgba(255,255,255,0.4);margin-top:4px;"><?= __t('field_median_sub') ?></div>
                </div>
            </div>

            <div style="background:rgba(255,255,255,0.1);border-radius:var(--r-sm);padding:16px 20px;font-size:0.95rem;line-height:1.6;">
                <?php if ($avgSalary > 0): ?>
                    <?= __t('pay_avg_label') ?>
                    <?= __t('pay_your_pay_is') ?>
                    <strong style="color:<?= $belowAverage ? '#E87070' : '#5EC99A' ?>;">
                        $<?= number_format($diffFromAvg) ?> <?= $belowAverage ? __t('pay_below') : __t('pay_above') ?>
                    </strong>
                    <?= __t('pay_avg_suffix_pre') ?> <?= clean($field) ?><?= __t('pay_avg_suffix_post') ?>.
                    <?php if (!$belowAverage): ?>
                        <?= __t('pay_above_avg_congrats') ?>
                    <?php endif; ?>
                <?php endif; ?>
                <?php if ($medianSalary > 0): ?>
                    <br>
                    <?= __t('pay_median_label') ?>
                    <?= __t('pay_your_pay_is') ?>
                    <strong style="color:<?= $belowMedian ? '#E87070' : '#5EC99A' ?>;">
                        $<?= number_format($diffFromMed) ?> <?= $belowMedian ? __t('pay_below') : __t('pay_above') ?>
                    </strong>
                    <?= __t('pay_median_suffix_pre') ?> <?= clean($field) ?><?= __t('pay_median_suffix_post') ?>.
                <?php endif; ?>
            </div>
        </div>

        <!-- GAP RATING BLOCK -->
        <?php if ($avgGapRating > 0): ?>
        <div style="background:white;border:1px solid var(--border);border-radius:var(--r);padding:28px;margin-bottom:24px;">
            <div style="font-size:0.72rem;text-transform:uppercase;letter-spacing:0.12em;color:var(--muted);margin-bottom:12px;"><?= __t('gap_climate_pre') ?> <?= clean($field) ?><?= __t('gap_climate_post') ?></div>
            <div style="display:flex;align-items:center;gap:20px;flex-wrap:wrap;">
                <div style="font-family:var(--fd);font-size:2.5rem;font-weight:700;color:var(--ink);"><?= number_format($avgGapRating, 1) ?><span style="font-size:1.1rem;color:var(--muted)">/5</span></div>
                <div>
                    <span class="gap-badge <?= gapRatingClass($avgGapRating) ?>" style="font-size:0.9rem;padding:6px 14px;"><?= gapRatingCategory($avgGapRating) ?></span>
                    <p style="color:var(--muted);font-size:0.83rem;margin-top:6px;">
                        <?php if ($avgGapRating <= 3): ?>
                            <?= __t('gap_low_pre') ?> <?= clean($field) ?> <?= __t('gap_low_post') ?>
                        <?php elseif ($avgGapRating <= 4): ?>
                            <?= __t('gap_medium_pre') ?> <?= clean($field) ?> <?= __t('gap_medium_post') ?>
                        <?php else: ?>
                            <?= __t('gap_high_pre') ?> <?= clean($field) ?> <?= __t('gap_high_post') ?>
                        <?php endif; ?>
                    </p>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- SCENARIO-BASED ADVICE BLOCK -->
        <?php if ($scenario === 'A'): ?>
        <div style="background:linear-gradient(135deg,#1a1a2e 0%,#16213e 100%);color:white;border-radius:var(--r);padding:32px;margin-bottom:24px;">
            <div style="font-size:1.6rem;margin-bottom:10px;"><?= __t('scenario_a_h') ?></div>
            <p style="color:rgba(255,255,255,0.75);font-size:0.95rem;line-height:1.7;margin-bottom:20px;">
                <?= clean($field) ?> <?= __t('scenario_a_field_post') ?> <strong style="color:#5EC99A;"><?= __t('scenario_a_strong') ?></strong><?= __t('scenario_a_p_end') ?>
            </p>
            <ul style="color:rgba(255,255,255,0.7);line-height:2;padding-left:20px;font-size:0.9rem;">
                <li><?= __t('scenario_a_li1') ?></li>
                <li><?= __t('scenario_a_li2') ?></li>
                <li><?= __t('scenario_a_li3') ?></li>
                <li><?= __t('scenario_a_li4') ?></li>
                <li><?= __t('scenario_a_li5') ?></li>
            </ul>
        </div>
        <?php elseif ($scenario === 'C'): ?>
        <div style="background:linear-gradient(135deg,#0f3460 0%,#16213e 100%);color:white;border-radius:var(--r);padding:32px;margin-bottom:24px;">
            <div style="font-size:1.6rem;margin-bottom:10px;"><?= __t('scenario_c_h') ?></div>
            <p style="color:rgba(255,255,255,0.75);font-size:0.95rem;line-height:1.7;margin-bottom:16px;">
                <?= __t('pay_your_pay_is') ?> <?= __t('scenario_c_field_post') ?> <?= clean($field) ?>. <?= __t('scenario_c_p2') ?>
            </p>
            <ul style="color:rgba(255,255,255,0.7);line-height:2;padding-left:20px;font-size:0.9rem;">
                <li><?= __t('scenario_c_li1') ?></li>
                <li><?= __t('scenario_c_li2') ?></li>
                <li><?= __t('scenario_c_li3') ?></li>
                <li><?= __t('scenario_c_li4') ?></li>
            </ul>
        </div>
        <?php endif; ?>

        <!-- TOP COMPANIES IN YOUR CITY -->
        <?php if (($scenario === 'A' || $scenario === 'B') && $userCity): ?>
        <div style="margin-bottom:24px;">
            <h2 style="font-family:var(--fd);font-size:1.5rem;font-weight:700;margin-bottom:6px;color:var(--ink);">
                <?= __t('top_companies_for_pre') ?> <?= clean($field) ?> <?= __t('top_companies_for_mid') ?> <?= clean($userCity) ?><?= __t('top_companies_for_post') ?>
            </h2>
            <p style="color:var(--muted);font-size:0.88rem;margin-bottom:20px;"><?= __t('top_companies_city_sub') ?></p>

            <?php if (empty($topCompanies)): ?>
                <div style="background:var(--gold-pale);border:1px solid var(--border-gold);border-radius:var(--r);padding:24px;text-align:center;">
                    <strong><?= __t('no_companies_city_pre') ?> "<?= clean($userCity) ?>" <?= __t('no_companies_city_mid') ?></strong>
                    <p style="color:var(--muted);margin-top:8px;font-size:0.88rem;">
                        <?= __t('no_companies_city_p') ?>
                        <br><?= __t('no_companies_city_help') ?> <a href="/sheworks/pages/review.php" style="color:var(--gold);"><?= __t('no_companies_city_link') ?></a> <?= __t('no_companies_city_end') ?>
                    </p>
                </div>
            <?php else: ?>
                <div class="company-grid" style="grid-template-columns:repeat(auto-fill,minmax(260px,1fr));">
                    <?php foreach ($topCompanies as $i => $co): ?>
                    <a href="/sheworks/pages/company.php?slug=<?= urlencode($co['slug']) ?>" class="company-card" style="<?= $i === 0 ? 'border:2px solid var(--gold);' : '' ?>">
                        <?php if ($i === 0): ?>
                            <div style="font-size:0.7rem;text-transform:uppercase;letter-spacing:0.1em;color:var(--gold);font-weight:600;margin-bottom:4px;"><?= __t('top_rated_badge') ?></div>
                        <?php endif; ?>
                        <div class="company-name"><?= clean($co['name']) ?></div>
                        <div class="company-meta"><?= clean($co['hq_city'] ?? '') ?> · <?= $co['review_count'] ?> review<?= $co['review_count'] != 1 ? 's' : '' ?></div>
                        <div class="rating-row" style="margin-top:10px;">
                            <span class="rating-label"><?= __t('pay_equity_label') ?></span>
                            <span class="rating-val <?= ratingColor($co['avg_pay_equity']) ?>"><?= $co['avg_pay_equity'] ?>/5</span>
                        </div>
                        <div class="rating-row">
                            <span class="rating-label"><?= __t('overall_label_plain') ?></span>
                            <span class="rating-val <?= ratingColor($co['avg_overall']) ?>"><?= $co['avg_overall'] ?>/5 <?= stars((int)round($co['avg_overall'])) ?></span>
                        </div>
                    </a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
        <?php elseif (($scenario === 'A' || $scenario === 'B') && !$userCity): ?>
        <div style="background:var(--gold-pale);border:1px solid var(--border-gold);border-radius:var(--r);padding:24px;margin-bottom:24px;">
            <?= __t('want_local_recs') ?> <?= __t('want_local_recs_p') ?>
        </div>
        <?php endif; ?>

        <!-- Scenario B additional note -->
        <?php if ($scenario === 'B'): ?>
        <div style="background:linear-gradient(135deg,#1a1a2e 0%,#16213e 100%);color:white;border-radius:var(--r);padding:28px;margin-bottom:24px;">
            <div style="font-size:1.4rem;margin-bottom:10px;"><?= __t('field_alert_pre') ?> <?= clean($field) ?> <?= __t('field_male_dominated') ?></div>
            <p style="color:rgba(255,255,255,0.75);font-size:0.92rem;line-height:1.7;">
                <?= __t('scenario_b_p') ?> <?= clean($field) ?><?= __t('scenario_b_p_end') ?>
            </p>
        </div>
        <?php endif; ?>

        <div style="text-align:center;margin-top:28px;">
            <a href="/sheworks/pages/companies.php" class="btn btn-outline" style="margin-right:12px;"><?= __t('browse_all_companies_btn') ?></a>
            <a href="/sheworks/pages/submit-salary.php" class="btn btn-primary"><?= __t('hiw_salary_btn') ?> →</a>
        </div>

        <?php endif; // end $totalSubs > 0 ?>
    </div>
    <?php endif; // end $analysed ?>

</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
