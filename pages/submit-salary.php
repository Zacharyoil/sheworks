<?php
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

require_once __DIR__ . '/../config.php';
$pageTitle = __t('submit_salary_h');
$db = db();

$success = false;
$errors  = [];
$preCompany = clean($_GET['company'] ?? '');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!rateLimitOk('salaries')) {
        $errors[] = $t['err_rate_limit_salary'];
    } else {
        $companyName = clean($_POST['company_name']  ?? '');
        $companyCity = clean($_POST['company_city']  ?? '');
        $jobTitle    = clean($_POST['job_title']     ?? '');
        $field       = clean($_POST['field']         ?? '');
        $gender      = clean($_POST['gender']        ?? '');
        $salary      = floatval($_POST['salary']     ?? 0);
        $currency    = clean($_POST['currency']      ?? 'USD');
        $country     = clean($_POST['country']       ?? '');
        $yearsExp    = intval($_POST['years_experience'] ?? 0);
        $education   = $_POST['education_level'] ?? 'bachelors';
        $isFirst     = isset($_POST['is_first_job']) ? 1 : 0;

        $allowed_education = ['high_school', 'bachelors', 'masters', 'phd', 'other'];
        if (!in_array($education, $allowed_education)) {
            $education = 'other';
        }

        if (!$companyName) $errors[] = $t['err_company_name'];
        if (!$jobTitle)    $errors[] = $t['err_job_title'];
        if (!$field)       $errors[] = $t['err_select_field'];
        if (!$gender)      $errors[] = $t['err_select_gender'];
        if (!$country)     $errors[] = $t['err_select_country'];
        if ($salary < 500) $errors[] = $t['err_valid_salary'];

        if (empty($errors)) {
            $companyId = findOrCreateCompany($companyName, $companyCity);
            $ip = userIP();

            $stmt = $db->prepare("
                INSERT INTO salaries
                    (company_id, job_title, field, gender, salary, currency, country,
                     years_experience, education_level, is_first_job, ip_address)
                VALUES (?,?,?,?,?,?,?,?,?,?,?)
            ");
            $stmt->bind_param(
                'issssdssiss',
                $companyId, $jobTitle, $field, $gender, $salary, $currency, $country,
                $yearsExp, $education, $isFirst, $ip
            );
            $stmt->execute();
            $stmt->close();

            $slug = slugify($companyName);
            $cid  = $companyId;

            $cmpStmt = $db->prepare("
                SELECT
                    ROUND(AVG(CASE WHEN gender='female' THEN salary END),0) AS avg_female,
                    ROUND(AVG(CASE WHEN gender='male'   THEN salary END),0) AS avg_male,
                    ROUND(AVG(salary),0) AS avg_overall,
                    COUNT(*) AS total
                FROM salaries
                WHERE company_id=? AND field=? AND approved=1
            ");
            $cmpStmt->bind_param('is', $cid, $field);
            $cmpStmt->execute();
            $comparison = $cmpStmt->get_result()->fetch_assoc();
            $cmpStmt->close();

            $success = true;
        }
    }
}

$allFields = jobFields();
$countries = ["Afghanistan", "Albania", "Algeria", "American Samoa", "Andorra", "Angola", "Anguilla", "Antarctica", "Antigua and Barbuda", "Argentina", "Armenia", "Aruba", "Australia", "Austria", "Azerbaijan", "Bahamas", "Bahrain", "Bangladesh", "Barbados", "Belarus", "Belgium", "Belize", "Benin", "Bermuda", "Bhutan", "Bolivia", "Bosnia and Herzegowina", "Botswana", "Bouvet Island", "Brazil", "British Indian Ocean Territory", "Brunei Darussalam", "Bulgaria", "Burkina Faso", "Burundi", "Cambodia", "Cameroon", "Canada", "Cape Verde", "Cayman Islands", "Central African Republic", "Chad", "Chile", "China", "Christmas Island", "Cocos (Keeling) Islands", "Colombia", "Comoros", "Congo", "Congo, the Democratic Republic of the", "Cook Islands", "Costa Rica", "Cote d'Ivoire", "Croatia (Hrvatska)", "Cuba", "Cyprus", "Czech Republic", "Denmark", "Djibouti", "Dominica", "Dominican Republic", "East Timor", "Ecuador", "Egypt", "El Salvador", "Equatorial Guinea", "Eritrea", "Estonia", "Ethiopia", "Falkland Islands (Malvinas)", "Faroe Islands", "Fiji", "Finland", "France", "France Metropolitan", "French Guiana", "French Polynesia", "French Southern Territories", "Gabon", "Gambia", "Georgia", "Germany", "Ghana", "Gibraltar", "Greece", "Greenland", "Grenada", "Guadeloupe", "Guam", "Guatemala", "Guinea", "Guinea-Bissau", "Guyana", "Haiti", "Heard and Mc Donald Islands", "Holy See (Vatican City State)", "Honduras", "Hong Kong", "Hungary", "Iceland", "India", "Indonesia", "Iran (Islamic Republic of)", "Iraq", "Ireland", "Israel", "Italy", "Jamaica", "Japan", "Jordan", "Kazakhstan", "Kenya", "Kiribati", "Korea, Democratic People's Republic of", "Korea, Republic of", "Kuwait", "Kyrgyzstan", "Lao, People's Democratic Republic", "Latvia", "Lebanon", "Lesotho", "Liberia", "Libyan Arab Jamahiriya", "Liechtenstein", "Lithuania", "Luxembourg", "Macau", "Macedonia, The Former Yugoslav Republic of", "Madagascar", "Malawi", "Malaysia", "Maldives", "Mali", "Malta", "Marshall Islands", "Martinique", "Mauritania", "Mauritius", "Mayotte", "Mexico", "Micronesia, Federated States of", "Moldova, Republic of", "Monaco", "Mongolia", "Montserrat", "Morocco", "Mozambique", "Myanmar", "Namibia", "Nauru", "Nepal", "Netherlands", "Netherlands Antilles", "New Caledonia", "New Zealand", "Nicaragua", "Niger", "Nigeria", "Niue", "Norfolk Island", "Northern Mariana Islands", "Norway", "Oman", "Pakistan", "Palau", "Panama", "Papua New Guinea", "Paraguay", "Peru", "Philippines", "Pitcairn", "Poland", "Portugal", "Puerto Rico", "Qatar", "Reunion", "Romania", "Russian Federation", "Rwanda", "Saint Kitts and Nevis", "Saint Lucia", "Saint Vincent and the Grenadines", "Samoa", "San Marino", "Sao Tome and Principe", "Saudi Arabia", "Senegal", "Seychelles", "Sierra Leone", "Singapore", "Slovakia (Slovak Republic)", "Slovenia", "Solomon Islands", "Somalia", "South Africa", "South Georgia and the South Sandwich Islands", "Spain", "Sri Lanka", "St. Helena", "St. Pierre and Miquelon", "Sudan", "Suriname", "Svalbard and Jan Mayen Islands", "Swaziland", "Sweden", "Switzerland", "Syrian Arab Republic", "Taiwan, Province of China", "Tajikistan", "Tanzania, United Republic of", "Thailand", "Togo", "Tokelau", "Tonga", "Trinidad and Tobago", "Tunisia", "Turkey", "Turkmenistan", "Turks and Caicos Islands", "Tuvalu", "Uganda", "Ukraine", "United Arab Emirates", "United Kingdom", "United States", "United States Minor Outlying Islands", "Uruguay", "Uzbekistan", "Vanuatu", "Venezuela", "Vietnam", "Virgin Islands (British)", "Virgin Islands (U.S.)", "Wallis and Futuna Islands", "Western Sahara", "Yemen", "Yugoslavia", "Zambia", "Zimbabwe"];
$currencies = ['USD' => 'USD — US Dollar','CAD' => 'CAD — Canadian Dollar','GBP' => 'GBP — British Pound','EUR' => 'EUR — Euro','AUD' => 'AUD — Australian Dollar','JPY' => 'JPY — Japanese Yen','INR' => 'INR — Indian Rupee'];
?>
<?php include __DIR__ . '/../includes/header.php'; ?>

<div class="form-page-hero">
    <h1><?= __t('submit_salary_h') ?></h1>
    <p><?= __t('submit_salary_p') ?></p>
</div>

<div class="form-wrap">
    <?php if ($success): ?>
        <div style="margin-top:28px;">
            <div class="alert alert-success"><?= __t('salary_success') ?></div>

            <?php if (!empty($comparison) && $comparison['total'] >= 2): ?>
            <div style="background:var(--ink);color:white;border-radius:var(--r);padding:32px;margin-bottom:20px;">
                <div style="font-size:0.72rem;text-transform:uppercase;letter-spacing:0.12em;color:rgba(255,255,255,0.45);margin-bottom:8px;">
                    <?= clean($_POST['field']) ?> at <?= clean($_POST['company_name']) ?>
                </div>
                <div style="font-family:var(--fd);font-size:3rem;font-weight:700;color:white;line-height:1;margin-bottom:6px;">
                    <?= $_POST['currency'] === 'GBP' ? '£' : ($_POST['currency'] === 'EUR' ? '€' : '$') ?><?= number_format(floatval($_POST['salary'])) ?>
                </div>
                <p style="color:rgba(255,255,255,0.5);font-size:0.85rem;"><?= __t('salary_your_submitted') ?></p>

                <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:20px;margin-top:28px;">
                    <div style="background:rgba(255,255,255,0.07);border-radius:var(--r-sm);padding:18px;text-align:center;">
                        <div style="font-size:0.7rem;color:rgba(255,255,255,0.45);text-transform:uppercase;letter-spacing:0.1em;margin-bottom:6px;"><?= __t('salary_women_avg_lbl') ?></div>
                        <div style="font-family:var(--fd);font-size:1.6rem;font-weight:700;color:#5EC99A;"><?= $comparison['avg_female'] ? '$'.number_format($comparison['avg_female']) : 'N/A' ?></div>
                    </div>
                    <div style="background:rgba(255,255,255,0.07);border-radius:var(--r-sm);padding:18px;text-align:center;">
                        <div style="font-size:0.7rem;color:rgba(255,255,255,0.45);text-transform:uppercase;letter-spacing:0.1em;margin-bottom:6px;"><?= __t('salary_men_avg_lbl') ?></div>
                        <div style="font-family:var(--fd);font-size:1.6rem;font-weight:700;color:rgba(255,255,255,0.8);"><?= $comparison['avg_male'] ? '$'.number_format($comparison['avg_male']) : 'N/A' ?></div>
                    </div>
                    <?php $gap = payGap((float)($comparison['avg_female'] ?? 0), (float)($comparison['avg_male'] ?? 0)); ?>
                    <div style="background:rgba(255,255,255,0.07);border-radius:var(--r-sm);padding:18px;text-align:center;">
                        <div style="font-size:0.7rem;color:rgba(255,255,255,0.45);text-transform:uppercase;letter-spacing:0.1em;margin-bottom:6px;"><?= __t('salary_pay_gap_lbl') ?></div>
                        <div style="font-family:var(--fd);font-size:1.6rem;font-weight:700;color:<?= $gap > 10 ? '#E87070' : '#5EC99A' ?>;"><?= $gap ?>%</div>
                    </div>
                </div>
            </div>
            <?php else: ?>
            <div style="background:var(--gold-pale);border-radius:var(--r);padding:24px;border:1px solid var(--border-gold);">
                <?= __t('salary_first_submit_msg') ?>
            </div>
            <?php endif; ?>

            <div style="display:flex;gap:12px;margin-top:20px;flex-wrap:wrap;">
                <a href="/sheworks/pages/company.php?slug=<?= urlencode(slugify($_POST['company_name'] ?? '')) ?>" class="btn btn-primary"><?= __t('salary_view_company_btn') ?></a>
                <a href="/sheworks/pages/submit-salary.php" class="btn btn-outline"><?= __t('salary_submit_another_btn') ?></a>
                <a href="/sheworks/pages/pay-analysis.php" class="btn btn-outline"><?= __t('salary_full_analysis_btn') ?></a>
            </div>
        </div>

    <?php else: ?>
        <?php if (!empty($errors)): ?>
            <div class="alert alert-error"><?php foreach ($errors as $e): ?><div>⚠ <?= $e ?></div><?php endforeach; ?></div>
        <?php endif; ?>

        <div class="form-card">
            <form method="POST">

                <p class="form-section-title"><?= __t('section_your_company') ?></p>

                <div class="form-group">
                    <label for="company_name"><?= __t('label_company_name') ?></label>
                    <input type="text" name="company_name" id="company_name" class="form-control"
                        placeholder="e.g. Google, Amazon, City Hospital…" maxlength="200" required
                        value="<?= $preCompany ?: clean($_POST['company_name'] ?? '') ?>">
                </div>

                <div class="form-group">
                    <label for="company_city"><?= __t('label_company_city') ?> <span style="font-weight:400;color:var(--muted);font-size:0.8rem;">(<?= __t('label_company_city_opt') ?>)</span></label>
                    <input type="text" name="company_city" id="company_city" class="form-control"
                        placeholder="e.g. New York, Toronto, London…" maxlength="100"
                        value="<?= clean($_POST['company_city'] ?? '') ?>">
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="job_title"><?= __t('label_job_title_plain') ?></label>
                        <input type="text" name="job_title" id="job_title" class="form-control"
                            placeholder="e.g. Software Engineer L4" maxlength="200" required
                            value="<?= clean($_POST['job_title'] ?? '') ?>">
                    </div>
                    <div class="form-group">
                        <label for="field"><?= __t('form_field') ?></label>
                        <select name="field" id="field" class="form-control" required>
                            <option value=""><?= __t('gender_select') ?></option>
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
                        <label for="years_experience"><?= __t('label_years_exp') ?></label>
                        <select name="years_experience" id="years_experience" class="form-control">
                            <option value="0"><?= __t('years_less_1') ?></option>
                            <?php for ($i = 1; $i <= 20; $i++): ?>
                                <option value="<?= $i ?>"><?= $i ?> year<?= $i > 1 ? 's' : '' ?></option>
                            <?php endfor; ?>
                            <option value="21"><?= __t('years_20plus') ?></option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="education_level"><?= __t('label_education') ?></label>
                        <select name="education_level" id="education_level" class="form-control">
                            <option value="high_school"><?= __t('edu_high_school') ?></option>
                            <option value="bachelors" selected><?= __t('edu_bachelors') ?></option>
                            <option value="masters"><?= __t('edu_masters') ?></option>
                            <option value="phd"><?= __t('edu_phd') ?></option>
                            <option value="other"><?= __t('edu_other') ?></option>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label class="checkbox-label">
                        <input type="checkbox" name="is_first_job" value="1"> <?= __t('label_first_job') ?>
                    </label>
                </div>

                <p class="form-section-title"><?= __t('section_salary_info') ?></p>

                <div class="form-row">
                    <div class="form-group">
                        <label for="salary"><?= __t('label_annual_salary') ?></label>
                        <input type="number" name="salary" id="salary" class="form-control"
                            placeholder="e.g. 95000" min="500" required
                            value="<?= isset($_POST['salary']) ? floatval($_POST['salary']) : '' ?>">
                    </div>
                    <div class="form-group">
                        <label for="currency"><?= __t('label_currency') ?></label>
                        <select name="currency" id="currency" class="form-control">
                            <?php foreach ($currencies as $code => $label): ?>
                                <option value="<?= $code ?>" <?= (($_POST['currency'] ?? 'USD') === $code) ? 'selected' : '' ?>><?= $label ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label for="country"><?= __t('label_country') ?></label>
                    <select name="country" id="country" class="form-control" required>
                        <option value=""><?= __t('select_country') ?></option>
                        <?php foreach ($countries as $c): ?>
                            <option value="<?= clean($c) ?>" <?= (($_POST['country'] ?? '') === $c) ? 'selected' : '' ?>><?= clean($c) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <p class="form-section-title"><?= __t('section_about_you') ?></p>

                <div class="form-group">
                    <label for="gender"><?= __t('label_gender') ?></label>
                    <select name="gender" id="gender" class="form-control" required>
                        <option value=""><?= __t('gender_select') ?></option>
                        <option value="female" <?= (($_POST['gender'] ?? '') === 'female') ? 'selected' : '' ?>><?= __t('gender_female') ?></option>
                        <option value="male"   <?= (($_POST['gender'] ?? '') === 'male')   ? 'selected' : '' ?>><?= __t('gender_male') ?></option>
                        <option value="non_binary" <?= (($_POST['gender'] ?? '') === 'non_binary') ? 'selected' : '' ?>><?= __t('gender_non_binary') ?></option>
                        <option value="prefer_not_to_say"><?= __t('gender_prefer_not') ?></option>
                    </select>
                </div>

                <div class="privacy-note">
                    <?= __t('salary_privacy_note') ?>
                </div>

                <button type="submit" class="btn btn-primary" style="width:100%;margin-top:24px;justify-content:center;padding:15px;">
                    <?= __t('salary_submit_btn') ?>
                </button>
            </form>
        </div>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
