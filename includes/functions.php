<?php
/**
 * NetworkTrouble - Core Utility & Helper Functions
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/app.php';

if (!function_exists('get_db_connection')) {
    function get_db_connection(): ?PDO {
        return get_db();
    }
}

/**
 * Normalizes CMS plain text retrieved from database or input.
 * Resolves any legacy HTML entities (&amp;, &quot;, &#039;, &lt;, &gt;) if they were previously
 * stored in encoded form, ensuring the returned value is always pure normal text.
 */
function normalize_cms_text(?string $text): string {
    if ($text === null || $text === '') {
        return '';
    }
    if (strpos($text, '&') !== false) {
        // Decode up to 2 passes to safely resolve any legacy double-encoding (e.g. &amp;amp;)
        $decoded = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        if (strpos($decoded, '&amp;') !== false || strpos($decoded, '&quot;') !== false || strpos($decoded, '&lt;') !== false) {
            $decoded = html_entity_decode($decoded, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        }
        return $decoded;
    }
    return $text;
}

/**
 * Retrieve a site setting with fallback (normalized to clean text)
 */
function get_setting(string $key, string $default = ''): string {
    static $settingsCache = null;
    if ($settingsCache === null) {
        $settingsCache = [];
        $db = get_db();
        if ($db) {
            try {
                $stmt = $db->query("SELECT setting_key, setting_value FROM site_settings");
                while ($row = $stmt->fetch()) {
                    $settingsCache[$row['setting_key']] = normalize_cms_text($row['setting_value']);
                }
            } catch (Exception $e) {}
        }
    }
    return $settingsCache[$key] ?? $default;
}

function get_site_setting(string $key, string $default = ''): string {
    return get_setting($key, $default);
}

/**
 * Retrieve page content block from page_contents table (normalized to clean text)
 */
function get_page_content(string $pageSlug, string $sectionKey, array $fallback = []): array {
    static $contentCache = [];
    $cacheKey = $pageSlug . ':' . $sectionKey;
    if (isset($contentCache[$cacheKey])) {
        return $contentCache[$cacheKey];
    }

    $default = array_merge([
        'title' => '',
        'subtitle' => '',
        'content' => '',
        'icon' => '',
        'image_id' => null,
        'image_path' => null,
        'status' => 'published'
    ], $fallback);

    $db = get_db();
    if ($db) {
        try {
            $stmt = $db->prepare("
                SELECT pc.*, ma.file_path as image_path, ma.alt_text as image_alt
                FROM page_contents pc
                LEFT JOIN media_assets ma ON pc.image_id = ma.id
                WHERE pc.page_slug = ? AND pc.section_key = ?
                LIMIT 1
            ");
            $stmt->execute([$pageSlug, $sectionKey]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($row) {
                foreach ($row as $k => $v) {
                    if (is_string($v) && !str_starts_with(trim($v), '{')) {
                        $row[$k] = normalize_cms_text($v);
                    }
                }
                $contentCache[$cacheKey] = array_merge($default, $row);
                return $contentCache[$cacheKey];
            }
        } catch (Exception $e) {}
    }

    $contentCache[$cacheKey] = $default;
    return $default;
}

function getPageContent(string $pageSlug, string $sectionKey, array $fallback = []): array {
    return get_page_content($pageSlug, $sectionKey, $fallback);
}

/**
 * Retrieve list of page content blocks by section prefix (normalized to clean text)
 */
function get_page_contents_by_prefix(string $pageSlug, string $prefix): array {
    $results = [];
    $db = get_db();
    if ($db) {
        try {
            $stmt = $db->prepare("
                SELECT pc.*, ma.file_path as image_path, ma.alt_text as image_alt
                FROM page_contents pc
                LEFT JOIN media_assets ma ON pc.image_id = ma.id
                WHERE pc.page_slug = ? AND pc.section_key LIKE ?
                ORDER BY pc.display_order ASC, pc.id ASC
            ");
            $stmt->execute([$pageSlug, $prefix . '%']);
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            foreach ($results as &$row) {
                foreach ($row as $k => $v) {
                    if (is_string($v) && !str_starts_with(trim($v), '{')) {
                        $row[$k] = normalize_cms_text($v);
                    }
                }
            }
            unset($row);
        } catch (Exception $e) {}
    }
    return $results;
}

/**
 * Retrieve active homepage sliders with associated media (normalized to clean text)
 */
function get_active_sliders(): array {
    $sliders = [];
    $db = get_db();
    if ($db) {
        try {
            $stmt = $db->query("
                SELECT s.*, ma.file_path, ma.file_name, ma.alt_text, ma.file_size
                FROM slider_items s
                JOIN media_assets ma ON s.media_id = ma.id
                WHERE s.is_active = 1
                ORDER BY s.display_order ASC, s.id ASC
            ");
            $sliders = $stmt->fetchAll(PDO::FETCH_ASSOC);
            foreach ($sliders as &$s) {
                foreach ($s as $k => $v) {
                    if (is_string($v)) {
                        $s[$k] = normalize_cms_text($v);
                    }
                }
            }
            unset($s);
        } catch (Exception $e) {}
    }
    return $sliders;
}

/**
 * Retrieve specific contact setting with fallback
 */
function get_contact_setting(string $field, string $default = ''): string {
    $contact = get_contact_info();
    return $contact[$field] ?? $default;
}

/**
 * Retrieve contact settings (normalized to clean text)
 */
function get_contact_info(): array {
    static $contact = null;
    if ($contact !== null) {
        return $contact;
    }

    $default = [
        'contact_name'  => 'IT Support & NOC Network',
        'phone'         => '+62 812-3456-7890',
        'email'         => 'support@networktrouble.local',
        'address'       => 'Gedung Laboratorium Komputer & Jaringan, Lantai 3, IT Campus Center',
        'working_hours' => 'Senin - Jumat: 08:00 - 17:00 WIB',
        'whatsapp'      => '6281234567890'
    ];

    $db = get_db();
    if ($db) {
        try {
            $stmt = $db->query("SELECT * FROM contact_settings LIMIT 1");
            $res = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($res) {
                foreach ($res as $k => $v) {
                    if (is_string($v)) {
                        $res[$k] = normalize_cms_text($v);
                    }
                }
                $contact = array_merge($default, $res);
                return $contact;
            }
        } catch (Exception $e) {}
    }
    $contact = $default;
    return $contact;
}

/**
 * Retrieve aggregate stats dynamically from DB
 */
function get_stats_counts(): array {
    $counts = [
        'symptoms'       => 20,
        'categories'     => 9,
        'troubleshooting'=> 30,
        'diagnoses'      => 128,
        'resolved'       => 115,
        'unresolved'     => 13
    ];

    $db = get_db();
    if (!$db) {
        return $counts;
    }

    try {
        $counts['symptoms'] = (int)$db->query("SELECT COUNT(*) FROM network_symptoms WHERE status = 'active'")->fetchColumn();
        $counts['categories'] = (int)$db->query("SELECT COUNT(*) FROM network_categories WHERE status = 'active'")->fetchColumn();
        $counts['troubleshooting'] = (int)$db->query("SELECT COUNT(*) FROM troubleshooting_steps")->fetchColumn();
        $counts['diagnoses'] = (int)$db->query("SELECT COUNT(*) FROM diagnosis_sessions")->fetchColumn();
        $counts['resolved'] = (int)$db->query("SELECT COUNT(*) FROM diagnosis_results WHERE status = 'resolved'")->fetchColumn();
        $counts['unresolved'] = (int)$db->query("SELECT COUNT(*) FROM unresolved_cases WHERE case_status != 'closed'")->fetchColumn();
    } catch (Exception $e) {}

    return $counts;
}

/**
 * Log administrative activity
 */
function log_activity(?int $adminId, string $action, ?string $table = null, ?int $targetId = null): void {
    $db = get_db();
    if (!$db) return;

    try {
        $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
        $stmt = $db->prepare("INSERT INTO activity_logs (admin_id, action, target_table, target_id, ip_address, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
        $stmt->execute([$adminId, $action, $table, $targetId, $ip]);
    } catch (Exception $e) {}
}

/**
 * Formatting Utilities
 */
function format_date(?string $datetime, string $format = 'd M Y, H:i'): string {
    if (!$datetime) return '-';
    $time = strtotime($datetime);
    return date($format, $time);
}

function truncate_text(string $text, int $length = 100): string {
    $clean = strip_tags($text);
    if (mb_strlen($clean) <= $length) {
        return $clean;
    }
    return mb_substr($clean, 0, $length) . '...';
}

function get_severity_badge(string $severity): string {
    switch (strtolower($severity)) {
        case 'critical':
            return '<span class="badge bg-danger-subtle text-danger border border-danger-subtle px-2 py-1"><i class="bi bi-exclamation-triangle-fill me-1"></i>Critical</span>';
        case 'high':
            return '<span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle px-2 py-1"><i class="bi bi-exclamation-circle-fill me-1"></i>High</span>';
        case 'medium':
            return '<span class="badge bg-info-subtle text-info-emphasis border border-info-subtle px-2 py-1"><i class="bi bi-info-circle-fill me-1"></i>Medium</span>';
        case 'low':
        default:
            return '<span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1"><i class="bi bi-check-circle-fill me-1"></i>Low</span>';
    }
}

function get_confidence_badge(int $score): string {
    if ($score >= 80) {
        return '<span class="badge bg-success text-white px-2 py-1"><i class="bi bi-shield-check me-1"></i>' . $score . '% Sangat Kuat</span>';
    } elseif ($score >= 60) {
        return '<span class="badge bg-primary text-white px-2 py-1"><i class="bi bi-check2-circle me-1"></i>' . $score . '% Cukup Mungkin</span>';
    } elseif ($score >= 40) {
        return '<span class="badge bg-warning text-dark px-2 py-1"><i class="bi bi-exclamation-circle me-1"></i>' . $score . '% Perlu Uji Tambahan</span>';
    } else {
        return '<span class="badge bg-secondary text-white px-2 py-1"><i class="bi bi-question-circle me-1"></i>' . $score . '% Belum Cukup Jelas</span>';
    }
}

function get_source_badge(string $source): string {
    switch ($source) {
        case 'AI-Assisted':
        case 'AI-Assisted (Mock NLP)':
            return '<span class="badge bg-indigo text-white border"><i class="bi bi-cpu me-1"></i>AI-Assisted</span>';
        case 'Hybrid':
            return '<span class="badge bg-purple text-white border"><i class="bi bi-shuffle me-1"></i>Hybrid Engine</span>';
        case 'Rule-Based':
        default:
            return '<span class="badge bg-dark-subtle text-dark border"><i class="bi bi-diagram-3 me-1"></i>Rule-Based</span>';
    }
}

/**
 * Get or create anonymous session token for user history
 */
function get_anonymous_session_token(): string {
    if (empty($_SESSION['anonymous_token'])) {
        if (!empty($_COOKIE['networktrouble_token'])) {
            $_SESSION['anonymous_token'] = $_COOKIE['networktrouble_token'];
        } else {
            $token = 'nt_' . bin2hex(random_bytes(16));
            $_SESSION['anonymous_token'] = $token;
            setcookie('networktrouble_token', $token, time() + (86400 * 90), '/');
        }
    }
    return $_SESSION['anonymous_token'];
}
