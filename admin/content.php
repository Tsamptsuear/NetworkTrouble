<?php
/**
 * NetworkTrouble - Website Content & Appearance Manager (CMS)
 * Central management for site identity, hero, features, steps, CTA, contact & footer.
 */
$adminTitle = 'Konten & Tampilan Website';
require_once __DIR__ . '/includes/admin_header.php';
require_role(['super_admin', 'content_admin']);

$db = get_db_connection();
$errors = [];
$activeTab = $_GET['tab'] ?? 'identity';
if ($activeTab === 'navbar') {
    $activeTab = 'identity';
}
if ($activeTab === 'contact') {
    header('Location: ' . base_url('admin/contact.php'));
    exit;
}
$validTabs = ['identity', 'hero', 'features', 'steps', 'cta', 'footer'];
if (!in_array($activeTab, $validTabs, true)) {
    $activeTab = 'identity';
}

// ----------------------------------------------------
// 1. FORM HANDLERS
// ----------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Token CSRF tidak valid. Silakan muat ulang halaman.';
    } else {
        $action = $_POST['cms_action'] ?? '';

        // SECTION 1: Site Identity & Branding
        if ($action === 'save_identity') {
            $siteName        = sanitize($_POST['site_name'] ?? 'NetworkTrouble');
            $siteTagline     = sanitize($_POST['site_tagline'] ?? '');
            $navbarBadgeText = sanitize($_POST['navbar_badge_text'] ?? 'v1.0 • Diagnostic AI');
            $siteLogo        = sanitize($_POST['site_logo'] ?? '');
            $siteFavicon     = sanitize($_POST['site_favicon'] ?? '');
            $siteDescription = sanitize($_POST['site_description'] ?? '');
            $metaKeywords    = sanitize($_POST['meta_keywords'] ?? '');

            $identitySettings = [
                'site_name'         => $siteName,
                'navbar_brand_text' => $siteName, // Synchronized with site_name so legacy readers match
                'site_tagline'      => $siteTagline,
                'navbar_badge_text' => $navbarBadgeText,
                'site_logo'         => $siteLogo,
                'site_favicon'      => $siteFavicon,
                'site_description'  => $siteDescription,
                'meta_keywords'     => $metaKeywords
            ];

            if ($db) {
                try {
                    $stmt = $db->prepare("
                        INSERT INTO site_settings (setting_key, setting_value, setting_type)
                        VALUES (?, ?, 'text')
                        ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value), updated_at = NOW()
                    ");
                    foreach ($identitySettings as $k => $v) {
                        $stmt->execute([$k, $v]);
                    }

                    // Update navbar page_contents row for consistency
                    try {
                        $stmtPc = $db->prepare("
                            UPDATE page_contents 
                            SET title = ?, subtitle = ?, updated_at = NOW()
                            WHERE page_slug = 'home' AND section_key = 'navbar'
                        ");
                        $stmtPc->execute([$siteName, $navbarBadgeText]);
                    } catch (Exception $e) {}

                    log_activity($currentAdmin['id'] ?? null, 'update_site_identity', 'site_settings', null);
                    set_flash('success', 'Identitas website berhasil diperbarui.');
                    header('Location: ' . base_url('admin/content.php?tab=identity'));
                    exit;
                } catch (PDOException $e) {
                    $errors[] = 'Gagal menyimpan identitas: ' . $e->getMessage();
                }
            }
        }

        // SECTION 3: Hero Section
        elseif ($action === 'save_hero') {
            $heroBadge      = sanitize($_POST['hero_badge'] ?? '');
            $heroTitle      = sanitize($_POST['hero_title'] ?? '');
            $heroSubtitle   = sanitize($_POST['hero_subtitle'] ?? '');
            $ctaBtnText     = sanitize($_POST['cta_button_text'] ?? 'Start Diagnosis');
            $ctaBtnUrl      = sanitize($_POST['cta_button_url'] ?? 'diagnose.php');
            $secondaryText  = sanitize($_POST['secondary_button_text'] ?? 'Explore Network Guide');
            $secondaryUrl   = sanitize($_POST['secondary_button_url'] ?? 'how-it-works.php');

            if ($db) {
                try {
                    // Update site_settings
                    $stmt = $db->prepare("
                        INSERT INTO site_settings (setting_key, setting_value, setting_type)
                        VALUES (?, ?, 'text')
                        ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value), updated_at = NOW()
                    ");
                    $stmt->execute(['hero_badge', $heroBadge]);
                    $stmt->execute(['hero_title', $heroTitle]);
                    $stmt->execute(['hero_subtitle', $heroSubtitle]);
                    $stmt->execute(['cta_button_text', $ctaBtnText]);
                    $stmt->execute(['navbar_button_text', $ctaBtnText]);
                    $stmt->execute(['cta_button_url', $ctaBtnUrl]);
                    $stmt->execute(['secondary_button_text', $secondaryText]);
                    $stmt->execute(['secondary_button_url', $secondaryUrl]);

                    // Update page_contents
                    $stmtPc = $db->prepare("
                        INSERT INTO page_contents (page_slug, section_key, title, subtitle, content, updated_at)
                        VALUES ('home', 'hero', ?, ?, ?, NOW())
                        ON DUPLICATE KEY UPDATE title = VALUES(title), subtitle = VALUES(subtitle), content = VALUES(content), updated_at = NOW()
                    ");
                    $stmtPc->execute([$heroTitle, $heroBadge, $heroSubtitle]);

                    log_activity($currentAdmin['id'] ?? null, 'update_hero', 'page_contents', null);
                    set_flash('success', 'Hero section homepage berhasil diperbarui!');
                    header('Location: ' . base_url('admin/content.php?tab=hero'));
                    exit;
                } catch (PDOException $e) {
                    $errors[] = 'Gagal menyimpan hero: ' . $e->getMessage();
                }
            }
        }

        // SECTION 4: Feature Cards (6 cards)
        elseif ($action === 'save_features') {
            if ($db) {
                try {
                    $stmt = $db->prepare("
                        INSERT INTO page_contents (page_slug, section_key, title, subtitle, content, icon, display_order, status, updated_at)
                        VALUES ('home', ?, ?, ?, ?, ?, ?, ?, NOW())
                        ON DUPLICATE KEY UPDATE title = VALUES(title), subtitle = VALUES(subtitle), content = VALUES(content), icon = VALUES(icon), display_order = VALUES(display_order), status = VALUES(status), updated_at = NOW()
                    ");

                    for ($i = 1; $i <= 6; $i++) {
                        $key      = "feature_$i";
                        $title    = sanitize($_POST["feature_{$i}_title"] ?? '');
                        $sub      = sanitize($_POST["feature_{$i}_subtitle"] ?? '');
                        $desc     = sanitize($_POST["feature_{$i}_content"] ?? '');
                        $icon     = sanitize($_POST["feature_{$i}_icon"] ?? 'bi-cpu');
                        $status   = isset($_POST["feature_{$i}_status"]) ? 'published' : 'draft';

                        $stmt->execute([$key, $title, $sub, $desc, $icon, $i, $status]);
                    }

                    log_activity($currentAdmin['id'] ?? null, 'update_feature_cards', 'page_contents', null);
                    set_flash('success', '6 Kartu Fitur Unggulan berhasil diperbarui!');
                    header('Location: ' . base_url('admin/content.php?tab=features'));
                    exit;
                } catch (PDOException $e) {
                    $errors[] = 'Gagal menyimpan feature cards: ' . $e->getMessage();
                }
            }
        }

        // SECTION 5: How It Works (4 Steps)
        elseif ($action === 'save_how_it_works') {
            if ($db) {
                try {
                    // Header
                    $headTitle = sanitize($_POST['how_title'] ?? 'Bagaimana NetworkTrouble Bekerja');
                    $headSub   = sanitize($_POST['how_subtitle'] ?? 'Alur Diagnostik');
                    $headDesc  = sanitize($_POST['how_desc'] ?? '');

                    $stmt = $db->prepare("
                        INSERT INTO page_contents (page_slug, section_key, title, subtitle, content, updated_at)
                        VALUES ('home', 'how_it_works_header', ?, ?, ?, NOW())
                        ON DUPLICATE KEY UPDATE title = VALUES(title), subtitle = VALUES(subtitle), content = VALUES(content), updated_at = NOW()
                    ");
                    $stmt->execute([$headTitle, $headSub, $headDesc]);

                    // Steps 1-4
                    $stmtStep = $db->prepare("
                        INSERT INTO page_contents (page_slug, section_key, title, subtitle, content, icon, display_order, status, updated_at)
                        VALUES ('home', ?, ?, ?, ?, ?, ?, 'published', NOW())
                        ON DUPLICATE KEY UPDATE title = VALUES(title), subtitle = VALUES(subtitle), content = VALUES(content), icon = VALUES(icon), display_order = VALUES(display_order), updated_at = NOW()
                    ");

                    for ($s = 1; $s <= 4; $s++) {
                        $key   = "step_$s";
                        $title = sanitize($_POST["step_{$s}_title"] ?? '');
                        $sub   = sanitize($_POST["step_{$s}_subtitle"] ?? "Langkah $s");
                        $desc  = sanitize($_POST["step_{$s}_desc"] ?? '');
                        $icon  = sanitize($_POST["step_{$s}_icon"] ?? (string)$s);

                        $stmtStep->execute([$key, $title, $sub, $desc, $icon, $s]);
                    }

                    log_activity($currentAdmin['id'] ?? null, 'update_how_it_works', 'page_contents', null);
                    set_flash('success', 'Alur Cara Kerja (How It Works) berhasil disimpan!');
                    header('Location: ' . base_url('admin/content.php?tab=steps'));
                    exit;
                } catch (PDOException $e) {
                    $errors[] = 'Gagal menyimpan how it works: ' . $e->getMessage();
                }
            }
        }

        // SECTION 6: Call To Action (CTA Banner)
        elseif ($action === 'save_cta') {
            $ctaTitle = sanitize($_POST['cta_title'] ?? '');
            $ctaDesc  = sanitize($_POST['cta_desc'] ?? '');
            $ctaBtn   = sanitize($_POST['cta_btn_text'] ?? 'Mulai Diagnosis Sekarang');
            $ctaUrl   = sanitize($_POST['cta_btn_url'] ?? 'diagnose.php');
            $secBtn   = sanitize($_POST['cta_sec_text'] ?? 'Lihat Panduan Manual');
            $secUrl   = sanitize($_POST['cta_sec_url'] ?? 'troubleshooting.php');

            if ($db) {
                try {
                    $combinedContent = json_encode([
                        'desc' => $ctaDesc,
                        'sec_btn' => $secBtn,
                        'sec_url' => $secUrl
                    ], JSON_UNESCAPED_UNICODE);

                    $stmt = $db->prepare("
                        INSERT INTO page_contents (page_slug, section_key, title, subtitle, content, icon, updated_at)
                        VALUES ('home', 'cta_section', ?, ?, ?, ?, NOW())
                        ON DUPLICATE KEY UPDATE title = VALUES(title), subtitle = VALUES(subtitle), content = VALUES(content), icon = VALUES(icon), updated_at = NOW()
                    ");
                    $stmt->execute([$ctaTitle, $ctaBtn, $combinedContent, $ctaUrl]);

                    log_activity($currentAdmin['id'] ?? null, 'update_cta', 'page_contents', null);
                    set_flash('success', 'Banner Call To Action (CTA) berhasil diperbarui!');
                    header('Location: ' . base_url('admin/content.php?tab=cta'));
                    exit;
                } catch (PDOException $e) {
                    $errors[] = 'Gagal menyimpan CTA: ' . $e->getMessage();
                }
            }
        }

        // SECTION 6: Footer
        elseif ($action === 'save_footer') {
            $footerAbout     = sanitize($_POST['footer_about'] ?? '');
            $footerCopyright = sanitize($_POST['footer_copyright'] ?? '');

            if ($db) {
                try {
                    $stmt = $db->prepare("
                        INSERT INTO site_settings (setting_key, setting_value, setting_type)
                        VALUES (?, ?, 'text')
                        ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value), updated_at = NOW()
                    ");
                    $stmt->execute(['footer_about', $footerAbout]);
                    $stmt->execute(['footer_copyright', $footerCopyright]);

                    log_activity($currentAdmin['id'] ?? null, 'update_footer', 'site_settings', null);
                    set_flash('success', 'Pengaturan teks footer berhasil disimpan!');
                    header('Location: ' . base_url('admin/content.php?tab=footer'));
                    exit;
                } catch (PDOException $e) {
                    $errors[] = 'Gagal menyimpan footer: ' . $e->getMessage();
                }
            }
        }
    }
}

// ----------------------------------------------------
// 2. FETCH ALL ACTIVE DATA
// ----------------------------------------------------
$settings = [];
if ($db) {
    try {
        $rawSettings = $db->query("SELECT setting_key, setting_value FROM site_settings")->fetchAll(PDO::FETCH_KEY_PAIR) ?: [];
        foreach ($rawSettings as $k => $v) {
            $settings[$k] = normalize_cms_text($v);
        }
    } catch (PDOException $e) {}
}

// Helper to get raw page_contents row
function get_db_content($db, $slug, $key) {
    if (!$db) return [];
    try {
        $stmt = $db->prepare("SELECT * FROM page_contents WHERE page_slug = ? AND section_key = ? LIMIT 1");
        $stmt->execute([$slug, $key]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
        if ($row) {
            foreach ($row as $k => $v) {
                if (is_string($v) && !str_starts_with(trim($v), '{')) {
                    $row[$k] = normalize_cms_text($v);
                }
            }
        }
        return $row;
    } catch (Exception $e) {
        return [];
    }
}

// Feature cards
$features = [];
for ($i = 1; $i <= 6; $i++) {
    $row = get_db_content($db, 'home', "feature_$i");
    $features[$i] = [
        'title' => $row['title'] ?? "Fitur $i",
        'subtitle' => $row['subtitle'] ?? '',
        'content' => $row['content'] ?? '',
        'icon' => $row['icon'] ?? 'bi-cpu',
        'status' => $row['status'] ?? 'published'
    ];
}

// How it works
$howHeader = get_db_content($db, 'home', 'how_it_works_header');
$steps = [];
for ($s = 1; $s <= 4; $s++) {
    $steps[$s] = get_db_content($db, 'home', "step_$s");
}

// CTA Section
$ctaRow = get_db_content($db, 'home', 'cta_section');
$ctaData = [
    'title' => $ctaRow['title'] ?? 'Siap Menganalisis Gangguan Jaringan Anda?',
    'btn_text' => $ctaRow['subtitle'] ?? 'Mulai Diagnosis Sekarang',
    'btn_url' => $ctaRow['icon'] ?? 'diagnose.php',
    'desc' => 'Hanya butuh 1-2 menit untuk menjawab wizard gejala dan mendapatkan diagnosa akurat dengan panduan perbaikan terverifikasi.',
    'sec_btn' => 'Lihat Panduan Manual',
    'sec_url' => 'troubleshooting.php'
];
if (!empty($ctaRow['content'])) {
    $decoded = json_decode($ctaRow['content'], true);
    if (is_array($decoded)) {
        $ctaData['desc'] = $decoded['desc'] ?? $ctaData['desc'];
        $ctaData['sec_btn'] = $decoded['sec_btn'] ?? $ctaData['sec_btn'];
        $ctaData['sec_url'] = $decoded['sec_url'] ?? $ctaData['sec_url'];
    } else {
        $ctaData['desc'] = $ctaRow['content'];
    }
}

// Fetch Media library for media pickers
$mediaAssets = [];
if ($db) {
    try {
        $mediaAssets = $db->query("SELECT id, file_name, file_path, alt_text FROM media_assets ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC) ?: [];
    } catch (Exception $e) {}
}
?>

<div class="admin-layout-wrapper">
    <?php include __DIR__ . '/includes/admin_sidebar.php'; ?>

    <div class="admin-main">
        <?php include __DIR__ . '/includes/admin_topbar.php'; ?>

        <div class="admin-content">
            <!-- Breadcrumb & Action Header -->
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
                <div>
                    <h1 class="h3 fw-bold text-dark mb-1">Konten & Tampilan Website (CMS)</h1>
                    <p class="text-secondary mb-0">Pusat pengelolaan teks, hero, fitur, alur kerja, CTA, dan identitas website.</p>
                </div>
                <div class="d-flex gap-2">
                    <a href="<?= base_url(); ?>" target="_blank" class="btn btn-outline-primary btn-sm rounded-pill px-3 shadow-sm">
                        <i class="bi bi-box-arrow-up-right me-1"></i> Pratinjau Website
                    </a>
                </div>
            </div>

            <?php
            $flash = get_flash();
            if ($flash):
            ?>
            <div class="alert alert-<?= $flash['type'] === 'error' ? 'danger' : $flash['type']; ?> alert-dismissible fade show shadow-sm" role="alert">
                <i class="bi bi-check-circle-fill me-2"></i><?= htmlspecialchars($flash['message']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php endif; ?>

            <?php if (!empty($errors)): ?>
            <div class="alert alert-danger shadow-sm">
                <ul class="mb-0 ps-3">
                    <?php foreach ($errors as $err): ?>
                        <li><?= htmlspecialchars($err); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <?php endif; ?>

            <!-- NAVIGATION TABS -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body p-2 bg-light rounded">
                    <ul class="nav nav-pills flex-nowrap overflow-auto gap-1" id="cmsTabs" role="tablist">
                        <li class="nav-item">
                            <a class="nav-link <?= $activeTab === 'identity' ? 'active fw-bold' : 'text-dark'; ?>" href="?tab=identity">
                                <i class="bi bi-globe me-1"></i> 1. Identitas
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?= $activeTab === 'hero' ? 'active fw-bold' : 'text-dark'; ?>" href="?tab=hero">
                                <i class="bi bi-stars me-1"></i> 2. Hero Section
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?= $activeTab === 'features' ? 'active fw-bold' : 'text-dark'; ?>" href="?tab=features">
                                <i class="bi bi-grid-3x2-gap me-1"></i> 3. Fitur Card
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?= $activeTab === 'steps' ? 'active fw-bold' : 'text-dark'; ?>" href="?tab=steps">
                                <i class="bi bi-diagram-3 me-1"></i> 4. Cara Kerja
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?= $activeTab === 'cta' ? 'active fw-bold' : 'text-dark'; ?>" href="?tab=cta">
                                <i class="bi bi-megaphone me-1"></i> 5. CTA Section
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?= $activeTab === 'footer' ? 'active fw-bold' : 'text-dark'; ?>" href="?tab=footer">
                                <i class="bi bi-layout-text-window-reverse me-1"></i> 6. Footer
                            </a>
                        </li>
                    </ul>
                </div>
            </div>

            <!-- TAB CONTENT CONTAINER -->
            <div class="tab-content">
                
                <!-- ============================================== -->
                <!-- 1. IDENTITAS WEBSITE & BRANDING -->
                <!-- ============================================== -->
                <?php if ($activeTab === 'identity'): ?>
                <div class="admin-card">
                    <div class="admin-card-header bg-white py-3">
                        <h5 class="fw-bold text-dark mb-0"><i class="bi bi-globe me-2 text-primary"></i>Bagian 1 — Identitas Website & Branding</h5>
                    </div>
                    <div class="admin-card-body p-4">
                        <form method="POST" action="">
                            <input type="hidden" name="csrf_token" value="<?= generate_csrf_token(); ?>">
                            <input type="hidden" name="cms_action" value="save_identity">

                            <div class="row g-4">
                                <!-- A. IDENTITAS WEBSITE -->
                                <div class="col-12">
                                    <div class="card border p-4 bg-white shadow-none mb-2">
                                        <h6 class="fw-bold text-dark mb-3 border-bottom pb-2">
                                            <i class="bi bi-globe2 text-primary me-2"></i>Identitas Website
                                        </h6>
                                        <div class="row g-3">
                                            <div class="col-md-6">
                                                <label class="form-label fw-semibold">Nama Situs / Platform *</label>
                                                <input type="text" name="site_name" class="form-control" value="<?= e($settings['site_name'] ?? 'NetworkTrouble'); ?>" required>
                                                <div class="form-text small">Sumber utama nama website: digunakan pada brand navbar, title dokumen browser, dan metadata.</div>
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label fw-semibold">Badge Versi / Tagline Kecil</label>
                                                <input type="text" name="navbar_badge_text" class="form-control" value="<?= e($settings['navbar_badge_text'] ?? 'v1.0 • Diagnostic AI'); ?>">
                                                <div class="form-text small">Ditampilkan pada badge kecil di bawah logo/nama platform pada navbar.</div>
                                            </div>
                                            <div class="col-12">
                                                <label class="form-label fw-semibold">Slogan / Tagline *</label>
                                                <input type="text" name="site_tagline" class="form-control" value="<?= e($settings['site_tagline'] ?? 'Understand Your Network. Solve Problems Smarter.'); ?>" required>
                                                <div class="form-text small">Slogan utama yang digunakan pada title dokumen dan metadata platform.</div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- B. LOGO & BRANDING -->
                                <div class="col-12">
                                    <div class="card border p-4 bg-white shadow-none mb-2">
                                        <div class="d-flex justify-content-between align-items-center mb-3 border-bottom pb-2">
                                            <h6 class="fw-bold text-dark mb-0">
                                                <i class="bi bi-palette text-primary me-2"></i>Logo & Branding
                                            </h6>
                                            <a href="<?= base_url('admin/media.php?tab=media'); ?>" target="_blank" class="btn btn-outline-primary btn-sm rounded-pill px-3">
                                                <i class="bi bi-upload me-1"></i> Buka Media Manager
                                            </a>
                                        </div>
                                        <div class="row g-4">
                                            <!-- Logo Website -->
                                            <div class="col-md-6">
                                                <label class="form-label fw-semibold">Logo Website (Navbar)</label>
                                                <select name="site_logo" id="siteLogoSelect" class="form-select mb-2" onchange="updateLogoPreview(this.value)">
                                                    <option value="" <?= empty($settings['site_logo']) ? 'selected' : ''; ?>>-- Tanpa Logo Gambar (Gunakan Icon Bawaan) --</option>
                                                    <?php foreach ($mediaAssets as $asset): ?>
                                                        <option value="<?= e($asset['file_path']); ?>" <?= (($settings['site_logo'] ?? '') === $asset['file_path']) ? 'selected' : ''; ?>>
                                                            <?= e($asset['file_name']); ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                                <div class="form-text small mb-2">Pilih gambar logo dari Media Library untuk ditampilkan di sisi kiri navbar.</div>
                                                
                                                <div class="p-3 bg-light border rounded text-center d-flex align-items-center justify-content-center" style="min-height: 80px;">
                                                    <img id="logoPreviewImg" src="<?= !empty($settings['site_logo']) ? base_url(e($settings['site_logo'])) : ''; ?>" 
                                                         alt="Logo Preview" 
                                                         style="max-height: 48px; max-width: 100%; object-fit: contain; <?= empty($settings['site_logo']) ? 'display:none;' : ''; ?>">
                                                    <span id="logoPreviewPlaceholder" class="text-muted small" style="<?= !empty($settings['site_logo']) ? 'display:none;' : ''; ?>">
                                                        <i class="bi bi-image me-1"></i> Menggunakan icon bawaan platform
                                                    </span>
                                                </div>
                                            </div>

                                            <!-- Favicon Website -->
                                            <div class="col-md-6">
                                                <label class="form-label fw-semibold">Favicon Website (Tab Browser)</label>
                                                <select name="site_favicon" id="siteFaviconSelect" class="form-select mb-2" onchange="updateFaviconPreview(this.value)">
                                                    <option value="" <?= empty($settings['site_favicon']) ? 'selected' : ''; ?>>-- Default Favicon (Browser / Bawaan) --</option>
                                                    <?php foreach ($mediaAssets as $asset): ?>
                                                        <option value="<?= e($asset['file_path']); ?>" <?= (($settings['site_favicon'] ?? '') === $asset['file_path']) ? 'selected' : ''; ?>>
                                                            <?= e($asset['file_name']); ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                                <div class="form-text small mb-2">Pilih ikon dari Media Library untuk ditampilkan pada tab browser (&lt;link rel="icon"&gt;).</div>

                                                <div class="p-3 bg-light border rounded text-center d-flex align-items-center justify-content-center" style="min-height: 80px;">
                                                    <img id="faviconPreviewImg" src="<?= !empty($settings['site_favicon']) ? base_url(e($settings['site_favicon'])) : ''; ?>" 
                                                         alt="Favicon Preview" 
                                                         style="width: 32px; height: 32px; object-fit: contain; <?= empty($settings['site_favicon']) ? 'display:none;' : ''; ?>">
                                                    <span id="faviconPreviewPlaceholder" class="text-muted small" style="<?= !empty($settings['site_favicon']) ? 'display:none;' : ''; ?>">
                                                        <i class="bi bi-app me-1"></i> Favicon browser bawaan
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- C. OPTIMASI SEO -->
                                <div class="col-12">
                                    <div class="card border p-4 bg-white shadow-none">
                                        <h6 class="fw-bold text-dark mb-3 border-bottom pb-2">
                                            <i class="bi bi-search text-primary me-2"></i>Optimasi Mesin Pencari (SEO)
                                        </h6>
                                        <div class="mb-3">
                                            <label class="form-label fw-semibold">Deskripsi Singkat (SEO Meta Description)</label>
                                            <textarea name="site_description" class="form-control" rows="3" placeholder="Deskripsi ringkas platform untuk mesin pencari..."><?= e($settings['site_description'] ?? ''); ?></textarea>
                                            <div class="form-text small">Digunakan sebagai tag &lt;meta name="description"&gt; dan Open Graph di seluruh halaman website.</div>
                                        </div>
                                        <div class="mb-2">
                                            <label class="form-label fw-semibold">Meta Keywords</label>
                                            <input type="text" name="meta_keywords" class="form-control" value="<?= e($settings['meta_keywords'] ?? ''); ?>" placeholder="troubleshooting, osi layer, ping, dns, wifi">
                                            <div class="form-text small">Kata kunci pencarian dipisahkan dengan koma (,).</div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="d-flex justify-content-end pt-3 border-top mt-4">
                                <button type="submit" class="btn btn-primary px-4 fw-semibold shadow-sm">
                                    <i class="bi bi-save me-1"></i> Simpan Identitas
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <script>
                function updateLogoPreview(path) {
                    const img = document.getElementById('logoPreviewImg');
                    const ph = document.getElementById('logoPreviewPlaceholder');
                    if (path) {
                        img.src = '<?= base_url(); ?>' + path.replace(/^\/+/, '');
                        img.style.display = 'inline-block';
                        ph.style.display = 'none';
                    } else {
                        img.style.display = 'none';
                        ph.style.display = 'inline-block';
                    }
                }

                function updateFaviconPreview(path) {
                    const img = document.getElementById('faviconPreviewImg');
                    const ph = document.getElementById('faviconPreviewPlaceholder');
                    if (path) {
                        img.src = '<?= base_url(); ?>' + path.replace(/^\/+/, '');
                        img.style.display = 'inline-block';
                        ph.style.display = 'none';
                    } else {
                        img.style.display = 'none';
                        ph.style.display = 'inline-block';
                    }
                }
                </script>
                <?php endif; ?>

                <!-- ============================================== -->
                <!-- 2. HERO HOMEPAGE -->
                <!-- ============================================== -->
                <?php if ($activeTab === 'hero'): ?>
                <div class="admin-card">
                    <div class="admin-card-header bg-white py-3">
                        <h5 class="fw-bold text-dark mb-0"><i class="bi bi-stars me-2 text-primary"></i>Bagian 2 — Konten Hero Section Homepage</h5>
                    </div>
                    <div class="admin-card-body p-4">
                        <form method="POST" action="">
                            <input type="hidden" name="csrf_token" value="<?= generate_csrf_token(); ?>">
                            <input type="hidden" name="cms_action" value="save_hero">

                            <div class="mb-3">
                                <label class="form-label fw-semibold">Teks Badge Hero (Pill)</label>
                                <input type="text" name="hero_badge" class="form-control" value="<?= htmlspecialchars($settings['hero_badge'] ?? 'Sistem Diagnosis Jaringan Aktif & Siap Uji'); ?>" required>
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-semibold">Headline / Judul Utama Hero *</label>
                                <input type="text" name="hero_title" class="form-control form-control-lg fw-bold" value="<?= htmlspecialchars($settings['hero_title'] ?? 'Diagnose Network Problems with Confidence'); ?>" required>
                                <div class="form-text small">Contoh: "Diagnose Network Problems with Confidence" atau "Smart Network Diagnosis for Everyone"</div>
                            </div>

                            <div class="mb-4">
                                <label class="form-label fw-semibold">Subheadline / Deskripsi Hero *</label>
                                <textarea name="hero_subtitle" class="form-control" rows="3" required><?= htmlspecialchars($settings['hero_subtitle'] ?? 'Identify network issues, understand the OSI layer involved, and follow structured troubleshooting steps to restore connectivity swiftly.'); ?></textarea>
                            </div>

                            <div class="row g-3 mb-4">
                                <div class="col-md-6">
                                    <div class="card p-3 bg-light border">
                                        <h6 class="fw-bold text-dark mb-2"><i class="bi bi-play-circle-fill text-primary me-1"></i>Tombol Utama (Primary)</h6>
                                        <div class="mb-2">
                                            <label class="form-label small fw-semibold">Label Tombol</label>
                                            <input type="text" name="cta_button_text" class="form-control form-control-sm" value="<?= htmlspecialchars($settings['cta_button_text'] ?? 'Start Diagnosis'); ?>" required>
                                        </div>
                                        <div>
                                            <label class="form-label small fw-semibold">URL / Tujuan Link</label>
                                            <input type="text" name="cta_button_url" class="form-control form-control-sm" value="<?= htmlspecialchars($settings['cta_button_url'] ?? 'diagnose.php'); ?>" required>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="card p-3 bg-light border">
                                        <h6 class="fw-bold text-dark mb-2"><i class="bi bi-diagram-3 text-secondary me-1"></i>Tombol Kedua (Secondary)</h6>
                                        <div class="mb-2">
                                            <label class="form-label small fw-semibold">Label Tombol</label>
                                            <input type="text" name="secondary_button_text" class="form-control form-control-sm" value="<?= htmlspecialchars($settings['secondary_button_text'] ?? 'Explore Network Guide'); ?>" required>
                                        </div>
                                        <div>
                                            <label class="form-label small fw-semibold">URL / Tujuan Link</label>
                                            <input type="text" name="secondary_button_url" class="form-control form-control-sm" value="<?= htmlspecialchars($settings['secondary_button_url'] ?? 'how-it-works.php'); ?>" required>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="d-flex justify-content-end pt-3 border-top">
                                <button type="submit" class="btn btn-primary px-4 fw-semibold">
                                    <i class="bi bi-save me-1"></i> Simpan Hero Section
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
                <?php endif; ?>

                <!-- ============================================== -->
                <!-- 3. FEATURE CARDS -->
                <!-- ============================================== -->
                <?php if ($activeTab === 'features'): ?>
                <div class="admin-card">
                    <div class="admin-card-header bg-white py-3 d-flex justify-content-between align-items-center">
                        <div>
                            <h5 class="fw-bold text-dark mb-0"><i class="bi bi-grid-3x2-gap me-2 text-primary"></i>Bagian 3 — 6 Kartu Fitur Unggulan Homepage</h5>
                            <small class="text-muted">Atur judul, deskripsi, icon Bootstrap Icons, serta visibilitas tampil masing-masing kartu.</small>
                        </div>
                    </div>
                    <div class="admin-card-body p-4">
                        <form method="POST" action="">
                            <input type="hidden" name="csrf_token" value="<?= generate_csrf_token(); ?>">
                            <input type="hidden" name="cms_action" value="save_features">

                            <div class="row g-4 mb-4">
                                <?php for ($i = 1; $i <= 6; $i++): $feat = $features[$i]; ?>
                                <div class="col-lg-6">
                                    <div class="card border h-100 shadow-none">
                                        <div class="card-header bg-light py-2 d-flex justify-content-between align-items-center">
                                            <span class="fw-bold text-primary small">Kartu Fitur #<?= $i; ?></span>
                                            <div class="form-check form-switch mb-0">
                                                <input class="form-check-input" type="checkbox" role="switch" name="feature_<?= $i; ?>_status" value="published" <?= $feat['status'] === 'published' ? 'checked' : ''; ?> id="feat_status_<?= $i; ?>">
                                                <label class="form-check-label small fw-semibold" for="feat_status_<?= $i; ?>">Tampil</label>
                                            </div>
                                        </div>
                                        <div class="card-body p-3">
                                            <div class="row g-2 mb-2">
                                                <div class="col-8">
                                                    <label class="form-label small fw-semibold">Judul Fitur</label>
                                                    <input type="text" name="feature_<?= $i; ?>_title" class="form-control form-control-sm fw-bold" value="<?= htmlspecialchars($feat['title']); ?>" required>
                                                </div>
                                                <div class="col-4">
                                                    <label class="form-label small fw-semibold">Icon (BI class)</label>
                                                    <div class="input-group input-group-sm">
                                                        <span class="input-group-text"><i class="bi <?= htmlspecialchars($feat['icon']); ?>"></i></span>
                                                        <input type="text" name="feature_<?= $i; ?>_icon" class="form-control" value="<?= htmlspecialchars($feat['icon']); ?>" placeholder="bi-cpu">
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="mb-2">
                                                <label class="form-label small fw-semibold">Label Subjudul / Tag</label>
                                                <input type="text" name="feature_<?= $i; ?>_subtitle" class="form-control form-control-sm" value="<?= htmlspecialchars($feat['subtitle']); ?>">
                                            </div>
                                            <div>
                                                <label class="form-label small fw-semibold">Deskripsi Fitur</label>
                                                <textarea name="feature_<?= $i; ?>_content" class="form-control form-control-sm" rows="3" required><?= htmlspecialchars($feat['content']); ?></textarea>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <?php endfor; ?>
                            </div>

                            <div class="d-flex justify-content-end pt-3 border-top">
                                <button type="submit" class="btn btn-primary px-4 fw-semibold">
                                    <i class="bi bi-save me-1"></i> Simpan Seluruh Kartu Fitur
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
                <?php endif; ?>

                <!-- ============================================== -->
                <!-- 4. HOW IT WORKS (4 STEPS) -->
                <!-- ============================================== -->
                <?php if ($activeTab === 'steps'): ?>
                <div class="admin-card">
                    <div class="admin-card-header bg-white py-3">
                        <h5 class="fw-bold text-dark mb-0"><i class="bi bi-diagram-3 me-2 text-primary"></i>Bagian 4 — 4 Langkah Alur Diagnostik (How It Works)</h5>
                    </div>
                    <div class="admin-card-body p-4">
                        <form method="POST" action="">
                            <input type="hidden" name="csrf_token" value="<?= generate_csrf_token(); ?>">
                            <input type="hidden" name="cms_action" value="save_how_it_works">

                            <!-- Section Header -->
                            <div class="card p-3 bg-light border mb-4">
                                <h6 class="fw-bold text-dark mb-2">Header Section How It Works</h6>
                                <div class="row g-2">
                                    <div class="col-md-6">
                                        <label class="form-label small fw-semibold">Judul Section</label>
                                        <input type="text" name="how_title" class="form-control form-control-sm" value="<?= htmlspecialchars($howHeader['title'] ?? 'Bagaimana NetworkTrouble Bekerja'); ?>" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label small fw-semibold">Label Tag Badge</label>
                                        <input type="text" name="how_subtitle" class="form-control form-control-sm" value="<?= htmlspecialchars($howHeader['subtitle'] ?? 'Alur Diagnostik'); ?>">
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label small fw-semibold">Deskripsi Pengantar Section</label>
                                        <input type="text" name="how_desc" class="form-control form-control-sm" value="<?= htmlspecialchars($howHeader['content'] ?? 'Alur sistematis 4 langkah untuk mengubah ketidakpastian gangguan menjadi rencana aksi perbaikan yang terarah.'); ?>">
                                    </div>
                                </div>
                            </div>

                            <!-- 4 Steps -->
                            <div class="row g-3 mb-4">
                                <?php for ($s = 1; $s <= 4; $s++): $step = $steps[$s]; ?>
                                <div class="col-md-6 col-xl-3">
                                    <div class="card border h-100 p-3 bg-white shadow-none">
                                        <div class="d-flex align-items-center gap-2 mb-2 pb-2 border-bottom">
                                            <span class="badge bg-primary rounded-circle" style="width: 24px; height: 24px; display: flex; align-items: center; justify-content: center;"><?= $s; ?></span>
                                            <span class="fw-bold text-dark small">Langkah <?= $s; ?></span>
                                        </div>
                                        <div class="mb-2">
                                            <label class="form-label small fw-semibold">Judul Langkah</label>
                                            <input type="text" name="step_<?= $s; ?>_title" class="form-control form-control-sm fw-bold" value="<?= htmlspecialchars($step['title'] ?? "Step $s"); ?>" required>
                                        </div>
                                        <div class="mb-2">
                                            <label class="form-label small fw-semibold">Tag Langkah</label>
                                            <input type="text" name="step_<?= $s; ?>_subtitle" class="form-control form-control-sm" value="<?= htmlspecialchars($step['subtitle'] ?? "Langkah $s"); ?>">
                                        </div>
                                        <div>
                                            <label class="form-label small fw-semibold">Deskripsi Langkah</label>
                                            <textarea name="step_<?= $s; ?>_desc" class="form-control form-control-sm" rows="3" required><?= htmlspecialchars($step['content'] ?? ''); ?></textarea>
                                        </div>
                                    </div>
                                </div>
                                <?php endfor; ?>
                            </div>

                            <div class="d-flex justify-content-end pt-3 border-top">
                                <button type="submit" class="btn btn-primary px-4 fw-semibold">
                                    <i class="bi bi-save me-1"></i> Simpan Alur Cara Kerja
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
                <?php endif; ?>

                <!-- ============================================== -->
                <!-- 5. CTA SECTION -->
                <!-- ============================================== -->
                <?php if ($activeTab === 'cta'): ?>
                <div class="admin-card">
                    <div class="admin-card-header bg-white py-3">
                        <h5 class="fw-bold text-dark mb-0"><i class="bi bi-megaphone me-2 text-primary"></i>Bagian 5 — Banner Call To Action (CTA)</h5>
                    </div>
                    <div class="admin-card-body p-4">
                        <form method="POST" action="">
                            <input type="hidden" name="csrf_token" value="<?= generate_csrf_token(); ?>">
                            <input type="hidden" name="cms_action" value="save_cta">

                            <div class="mb-3">
                                <label class="form-label fw-semibold">Judul Banner CTA *</label>
                                <input type="text" name="cta_title" class="form-control form-control-lg fw-bold" value="<?= htmlspecialchars($ctaData['title']); ?>" required>
                            </div>

                            <div class="mb-4">
                                <label class="form-label fw-semibold">Deskripsi Ajakan *</label>
                                <textarea name="cta_desc" class="form-control" rows="3" required><?= htmlspecialchars($ctaData['desc']); ?></textarea>
                            </div>

                            <div class="row g-3 mb-4">
                                <div class="col-md-6">
                                    <div class="card p-3 bg-light border">
                                        <h6 class="fw-bold text-dark mb-2">Tombol Utama CTA</h6>
                                        <div class="mb-2">
                                            <label class="form-label small fw-semibold">Label Tombol</label>
                                            <input type="text" name="cta_btn_text" class="form-control form-control-sm" value="<?= htmlspecialchars($ctaData['btn_text']); ?>" required>
                                        </div>
                                        <div>
                                            <label class="form-label small fw-semibold">URL Tombol</label>
                                            <input type="text" name="cta_btn_url" class="form-control form-control-sm" value="<?= htmlspecialchars($ctaData['btn_url']); ?>" required>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="card p-3 bg-light border">
                                        <h6 class="fw-bold text-dark mb-2">Tombol Sekunder CTA</h6>
                                        <div class="mb-2">
                                            <label class="form-label small fw-semibold">Label Tombol</label>
                                            <input type="text" name="cta_sec_text" class="form-control form-control-sm" value="<?= htmlspecialchars($ctaData['sec_btn']); ?>" required>
                                        </div>
                                        <div>
                                            <label class="form-label small fw-semibold">URL Tombol</label>
                                            <input type="text" name="cta_sec_url" class="form-control form-control-sm" value="<?= htmlspecialchars($ctaData['sec_url']); ?>" required>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="d-flex justify-content-end pt-3 border-top">
                                <button type="submit" class="btn btn-primary px-4 fw-semibold">
                                    <i class="bi bi-save me-1"></i> Simpan Banner CTA
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
                <?php endif; ?>

                <!-- ============================================== -->
                <!-- 6. FOOTER -->
                <!-- ============================================== -->
                <?php if ($activeTab === 'footer'): ?>
                <div class="admin-card">
                    <div class="admin-card-header bg-white py-3">
                        <h5 class="fw-bold text-dark mb-0"><i class="bi bi-layout-text-window-reverse me-2 text-primary"></i>Bagian 6 — Teks & Hak Cipta Footer</h5>
                    </div>
                    <div class="admin-card-body p-4">
                        <form method="POST" action="">
                            <input type="hidden" name="csrf_token" value="<?= generate_csrf_token(); ?>">
                            <input type="hidden" name="cms_action" value="save_footer">

                            <div class="mb-4">
                                <label class="form-label fw-semibold">Deskripsi Tentang Platform di Footer</label>
                                <textarea name="footer_about" class="form-control" rows="3"><?= htmlspecialchars($settings['footer_about'] ?? 'NetworkTrouble adalah platform edukasi dan diagnostik awal untuk mempermudah teknisi, siswa, dan pengelola laboratorium dalam mendiagnosis anomali jaringan komputer secara terstruktur.'); ?></textarea>
                            </div>

                            <div class="mb-4">
                                <label class="form-label fw-semibold">Teks Hak Cipta (Copyright)</label>
                                <input type="text" name="footer_copyright" class="form-control" value="<?= htmlspecialchars($settings['footer_copyright'] ?? '© 2026 NetworkTrouble. All rights reserved. Built for professional network engineers and learners.'); ?>" required>
                            </div>

                            <div class="d-flex justify-content-end pt-3 border-top">
                                <button type="submit" class="btn btn-primary px-4 fw-semibold">
                                    <i class="bi bi-save me-1"></i> Simpan Pengaturan Footer
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
                <?php endif; ?>

            </div>

<?php require_once __DIR__ . '/includes/admin_footer.php'; ?>
