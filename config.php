<?php
// ============================================================
// config.php  — SheWork$ configuration & mysqli helpers
// ============================================================
// ── Session & Language ───────────────────────────────────────
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$allowedLangs = ['en', 'fr', 'zh-TW', 'ar'];

if (isset($_GET['lang']) && in_array($_GET['lang'], $allowedLangs)) {
    $_SESSION['lang'] = $_GET['lang'];
}

$currentLang = $_SESSION['lang'] ?? 'en';

$langFile = __DIR__ . '/lang/' . $currentLang . '.php';
$t = file_exists($langFile) ? require $langFile : require __DIR__ . '/lang/en.php';

function __t(string $key): string {
    global $t;
    return htmlspecialchars($t[$key] ?? $key, ENT_QUOTES, 'UTF-8');
}

// ── Database configuration ──────────────────────────────────
define('DB_HOST', '');
define('DB_USER', '');
define('DB_PASS', '');
define('DB_NAME', 'sheworks');
define('SITE_NAME', 'SheWork$');
define('SITE_TAGLINE', 'Rate companies. Analyze your pay. Know your worth.');

// ── Database connection (singleton) ─────────────────────────
function db(): mysqli {
    static $conn = null;
    if ($conn === null) {
        mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
        try {
            $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
            $conn->set_charset('utf8mb4');
        } catch (mysqli_sql_exception $e) {
            error_log('DB connect failed: ' . $e->getMessage());
            die('<div style="font-family:sans-serif;padding:40px;color:#c00">Database unavailable. Please try again later.</div>');
        }
    }
    return $conn;
}

// ── Input helpers ────────────────────────────────────────────
function clean(string $v): string {
    return htmlspecialchars(strip_tags(trim($v)), ENT_QUOTES, 'UTF-8');
}

function userIP(): string {
    foreach (['HTTP_CLIENT_IP','HTTP_X_FORWARDED_FOR','REMOTE_ADDR'] as $k) {
        if (!empty($_SERVER[$k])) return trim(explode(',', $_SERVER[$k])[0]);
    }
    return '0.0.0.0';
}

// ── Formatting ───────────────────────────────────────────────
function fmt(float $n, string $sym = '$'): string {
    return $sym . number_format($n, 0);
}

function payGap(float $f, float $m): float {
    return $m > 0 ? round((($m - $f) / $m) * 100, 1) : 0;
}

function stars(int $r, int $max = 5): string {
    $out = '<span class="stars">';
    for ($i = 1; $i <= $max; $i++) {
        $out .= $i <= $r ? '★' : '☆';
    }
    return $out . '</span>';
}

function ratingColor(float $r): string {
    if ($r >= 4)   return 'rating-green';
    if ($r >= 2.5) return 'rating-amber';
    return 'rating-red';
}

function gapClass(float $gap): string {
    if ($gap > 15) return 'gap-high';
    if ($gap > 7)  return 'gap-medium';
    return 'gap-low';
}

// ── Gap rating category (NEW) ────────────────────────────────
// rating_gap_perceived: 1–5 scale where 1=no gap perceived, 5=extreme gap
function gapRatingCategory(float $r): string {
    if ($r <= 3)  return 'Low Gap';
    if ($r <= 4)  return 'Medium Gap';
    return 'High Gap (Male Dominated)';
}

function gapRatingClass(float $r): string {
    if ($r <= 3)  return 'gap-low';
    if ($r <= 4)  return 'gap-medium';
    return 'gap-high';
}

// ── Median salary calculation (NEW) ─────────────────────────
// Calculates median of female salaries for a given field
function medianFemaleSalary(string $field): float {
    global $db;
    $stmt = $db->prepare("SELECT salary FROM salaries WHERE field = ? AND gender = 'female' AND approved = 1 ORDER BY salary");
    $stmt->bind_param('s', $field);
    $stmt->execute();
    $result = $stmt->get_result();
    $salaries = [];
    while ($row = $result->fetch_assoc()) {
        $salaries[] = (float)$row['salary'];
    }
    $stmt->close();
    $count = count($salaries);
    if ($count == 0) return 0;
    sort($salaries);
    $middle = floor($count / 2);
    if ($count % 2 == 0) {
        return ($salaries[$middle - 1] + $salaries[$middle]) / 2;
    } else {
        return $salaries[$middle];
    }
}

// ── Date formatting with translated month names ──────────
function formatReviewDate(string $datetime): string {
    global $currentLang;
    $months = [
        'en'    => ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'],
        'fr'    => ['janv.','févr.','mars','avr.','mai','juin','juil.','août','sept.','oct.','nov.','déc.'],
        'zh-TW' => ['1月','2月','3月','4月','5月','6月','7月','8月','9月','10月','11月','12月'],
        'ar'    => ['يناير','فبراير','مارس','أبريل','مايو','يونيو','يوليو','أغسطس','سبتمبر','أكتوبر','نوفمبر','ديسمبر'],
    ];
    $t = strtotime($datetime);
    $m = (int)date('n', $t) - 1;
    $y = date('Y', $t);
    $names = $months[$currentLang] ?? $months['en'];
    return $names[$m] . ' ' . $y;
}

// ── Gender translation ───────────────────────────────────
function translateGender(string $gender): string {
    $map = [
        'female'        => 'gender_female',
        'male'          => 'gender_male',
        'non_binary'    => 'gender_non_binary',
        'prefer_not'    => 'gender_prefer_not',
        'prefer not to say' => 'gender_prefer_not',
    ];
    global $t;
    $key = $map[strtolower($gender)] ?? null;
    return $key ? htmlspecialchars($t[$key] ?? $gender, ENT_QUOTES, 'UTF-8') : htmlspecialchars(ucfirst($gender), ENT_QUOTES, 'UTF-8');
}

// ── Industry translation ─────────────────────────────────
function translateIndustry(string $industry): string {
    global $t;
    $key = 'industry_' . str_replace(' ', '_', $industry);
    return htmlspecialchars($t[$key] ?? $industry, ENT_QUOTES, 'UTF-8');
}

// ── Slug generator ───────────────────────────────────────────
function slugify(string $s): string {
    $s = mb_strtolower(trim($s));
    $s = preg_replace('/[^a-z0-9\s-]/', '', $s);
    $s = preg_replace('/[\s-]+/', '-', $s);
    return trim($s, '-');
}

// ── Rate limit: max 3 submissions per IP per day ─────────────
function rateLimitOk(string $table): bool {
    $ip  = userIP();
    $db  = db();
    $stmt = $db->prepare(
        "SELECT COUNT(*) FROM `$table` WHERE ip_address = ? AND created_at > DATE_SUB(NOW(), INTERVAL 1 DAY)"
    );
    $stmt->bind_param('s', $ip);
    $stmt->execute();
    $stmt->bind_result($cnt);
    $stmt->fetch();
    $stmt->close();
    return $cnt < 999; //put back to 3 for production, 999 for testing
}

// ── Find or create company by name, return id (UPDATED) ──────
// Now accepts optional city, address, and industry for new companies
function findOrCreateCompany(string $name, string $city = '', string $address = '', string $industry = ''): int {
    $db   = db();
    $slug = slugify($name);

    $stmt = $db->prepare("SELECT id FROM companies WHERE slug = ? LIMIT 1");
    $stmt->bind_param('s', $slug);
    $stmt->execute();
    $stmt->bind_result($id);
    $stmt->fetch();
    $stmt->close();

    if ($id) {
        // Update city/address if provided and currently missing
        if ($city || $address) {
            $upd = $db->prepare("UPDATE companies SET hq_city = COALESCE(NULLIF(hq_city,''), ?), hq_country = COALESCE(NULLIF(hq_country,''), ?) WHERE id = ?");
            $upd->bind_param('ssi', $city, $address, $id);
            $upd->execute();
            $upd->close();
        }
        return (int)$id;
    }

    // create new company
    $stmt = $db->prepare("INSERT INTO companies (name, slug, hq_city, hq_country, industry) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param('sssss', $name, $slug, $city, $address, $industry);
    $stmt->execute();
    $id = (int)$db->insert_id;
    $stmt->close();
    return $id;
}

// ── Find or create a location record, return id ─────────────
// Matches by proximity (~150 m) to avoid duplicates from minor geocoding drift.
function findOrCreateLocation(int $companyId, string $displayName, string $city, string $country, float $lat, float $lng): int {
    $db = db();
    $stmt = $db->prepare(
        "SELECT id FROM locations WHERE company_id = ? AND ABS(lat - ?) < 0.0015 AND ABS(lng - ?) < 0.0015 LIMIT 1"
    );
    $stmt->bind_param('idd', $companyId, $lat, $lng);
    $stmt->execute();
    $stmt->bind_result($id);
    $stmt->fetch();
    $stmt->close();
    if ($id) return (int)$id;

    $stmt = $db->prepare(
        "INSERT INTO locations (company_id, display_name, city, country, lat, lng) VALUES (?,?,?,?,?,?)"
    );
    $stmt->bind_param('isssdd', $companyId, $displayName, $city, $country, $lat, $lng);
    $stmt->execute();
    $id = (int)$db->insert_id;
    $stmt->close();
    return $id;
}

// ── Job fields list ─────────────────────────────────────────
function jobFields(): array {
    return [
        'Technology'   => ['Software Engineering','Data Science','Product Management','UX/UI Design','DevOps / Infrastructure','Cybersecurity'],
        'Business'     => ['Marketing','Finance','Human Resources','Sales','Operations','Consulting'],
        'Healthcare'   => ['Nursing','Medicine / Doctor','Pharmacy','Physiotherapy'],
        'Education'    => ['Teaching / Education','Research / Academia'],
        'Legal'        => ['Law','Compliance'],
        'Creative'     => ['Journalism / Media','Architecture','Graphic Design'],
        'Social'       => ['Social Work','Non-profit'],
        'Engineering'  => ['Engineering (Civil)','Engineering (Mechanical)','Engineering (Chemical)'],
    ];
}
