<?php
/**
 * NetworkTrouble - Modern Professional Homepage
 * Fully integrated with CMS database, Image Slider Carousel,
 * and synchronized Network Topology Diagnosis Simulation.
 */
require_once __DIR__ . '/config/app.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

$pageTitle = "Sistem Diagnosis Gangguan Jaringan Komputer Berbasis OSI Layer";
$pageDesc = get_site_setting('site_description', "Identifikasi anomali jaringan, pahami lapisan OSI yang terdampak, dan ikuti langkah troubleshooting terstruktur.");

$stats = get_stats_counts();

// Fetch dynamic CMS contents
$activeSliders  = get_active_sliders();
$heroBadge      = get_site_setting('hero_badge', 'Sistem Diagnosis Jaringan Aktif & Siap Uji');
$heroTitle      = get_site_setting('hero_title', 'Diagnose Network Problems with Confidence');
$heroSubtitle   = get_site_setting('hero_subtitle', 'Identify network issues, understand the OSI layer involved, and follow structured troubleshooting steps to restore connectivity swiftly.');
$ctaBtnText     = get_site_setting('cta_button_text', 'Start Diagnosis');
$ctaBtnUrl      = get_site_setting('cta_button_url', 'diagnose.php');
$secBtnText     = get_site_setting('secondary_button_text', 'Explore Network Guide');
$secBtnUrl      = get_site_setting('secondary_button_url', 'how-it-works.php');

$featureCards   = get_page_contents_by_prefix('home', 'feature_');
$howHeader      = get_page_content('home', 'how_it_works_header', [
    'title' => 'Bagaimana NetworkTrouble Bekerja',
    'subtitle' => 'Alur Diagnostik',
    'content' => 'Alur sistematis 4 langkah untuk mengubah ketidakpastian gangguan menjadi rencana aksi perbaikan yang terarah.'
]);
$howSteps       = get_page_contents_by_prefix('home', 'step_');

$audienceHeader = get_page_content('home', 'audience_header', [
    'title' => 'Dibuat untuk Praktisi & Pembelajar Jaringan',
    'subtitle' => 'Audiens & Pengguna',
    'content' => 'Menghilangkan kebiasaan asal cabut kabel atau restart sembarangan dengan memperkenalkan metodologi penelusuran gangguan berstandar sertifikasi (CompTIA Network+, CCNA, MTCNA).'
]);

$ctaRow = get_page_content('home', 'cta_section');
$ctaTitle = !empty($ctaRow['title']) ? $ctaRow['title'] : 'Siap Menganalisis Gangguan Jaringan Anda?';
$ctaPrimaryText = !empty($ctaRow['subtitle']) ? $ctaRow['subtitle'] : 'Mulai Diagnosis Sekarang';
$ctaPrimaryUrl = !empty($ctaRow['icon']) ? $ctaRow['icon'] : 'diagnose.php';
$ctaDesc = 'Hanya butuh 1-2 menit untuk menjawab wizard gejala dan mendapatkan diagnosa akurat dengan panduan perbaikan terverifikasi.';
$ctaSecondaryText = 'Lihat Panduan Manual';
$ctaSecondaryUrl = 'troubleshooting.php';

if (!empty($ctaRow['content'])) {
    $decodedCta = json_decode($ctaRow['content'], true);
    if (is_array($decodedCta)) {
        $ctaDesc = $decodedCta['desc'] ?? $ctaDesc;
        $ctaSecondaryText = $decodedCta['sec_btn'] ?? $ctaSecondaryText;
        $ctaSecondaryUrl = $decodedCta['sec_url'] ?? $ctaSecondaryUrl;
    } else {
        $ctaDesc = $ctaRow['content'];
    }
}

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';
?>

<!-- ============================================== -->
<!-- 1. HOMEPAGE IMAGE BANNER CAROUSEL SLIDER -->
<!-- ============================================== -->
<?php if (!empty($activeSliders)): ?>
<section class="nt-slider-section">
    <div class="nt-slider-wrapper">
        <div id="homepageCarousel" class="carousel slide nt-slider-carousel" data-bs-ride="carousel" data-bs-interval="5000" data-bs-pause="hover">
            <?php if (count($activeSliders) > 1): ?>
            <div class="carousel-indicators">
                <?php foreach ($activeSliders as $idx => $slide): ?>
                <button type="button" data-bs-target="#homepageCarousel" data-bs-slide-to="<?= $idx; ?>" class="<?= $idx === 0 ? 'active' : ''; ?>" aria-current="<?= $idx === 0 ? 'true' : 'false'; ?>" aria-label="Slide <?= $idx + 1; ?>"></button>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

            <div class="carousel-inner">
                <?php foreach ($activeSliders as $idx => $slide): ?>
                <div class="carousel-item position-relative <?= $idx === 0 ? 'active' : ''; ?>">
                    <img src="<?= base_url(htmlspecialchars($slide['file_path'])); ?>" class="nt-slider-img" alt="<?= htmlspecialchars($slide['alt_text'] ?: ($slide['title'] ?: 'NetworkTrouble Banner')); ?>" loading="<?= $idx === 0 ? 'eager' : 'lazy'; ?>">
                    <div class="nt-slider-overlay">
                        <div class="nt-slider-content">
                            <?php if (!empty($slide['title'])): ?>
                            <h2 class="nt-slider-title"><?= e($slide['title']); ?></h2>
                            <?php endif; ?>
                            <?php if (!empty($slide['caption'])): ?>
                            <p class="nt-slider-caption"><?= e($slide['caption']); ?></p>
                            <?php endif; ?>
                            <?php if (!empty($slide['button_text'])): ?>
                            <a href="<?= base_url(htmlspecialchars($slide['button_url'] ?: 'diagnose.php', ENT_QUOTES, 'UTF-8', false)); ?>" class="btn btn-primary nt-slider-btn d-inline-flex align-items-center gap-2">
                                <span><?= e($slide['button_text']); ?></span>
                                <i class="bi bi-arrow-right"></i>
                            </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <?php if (count($activeSliders) > 1): ?>
            <button class="carousel-control-prev" type="button" data-bs-target="#homepageCarousel" data-bs-slide="prev" aria-label="Previous">
                <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                <span class="visually-hidden">Previous</span>
            </button>
            <button class="carousel-control-next" type="button" data-bs-target="#homepageCarousel" data-bs-slide="next" aria-label="Next">
                <span class="carousel-control-next-icon" aria-hidden="true"></span>
                <span class="visually-hidden">Next</span>
            </button>
            <?php endif; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- ============================================== -->
<!-- 2. HERO SECTION & SIMULATED TOPOLOGY -->
<!-- ============================================== -->
<section class="nt-hero border-bottom">
    <div class="container">
        <div class="row align-items-center g-5">
            <!-- Left Column: Copy & CTA -->
            <div class="col-lg-6">
                <div class="nt-hero-badge mb-3">
                    <span class="pulse-dot"></span>
                    <span id="heroBadgeText"><?= e($heroBadge); ?></span>
                </div>

                <h1 class="nt-hero-title mb-3" id="heroTitleText">
                    <?= e($heroTitle); ?>
                </h1>

                <p class="nt-hero-subtitle mb-4" id="heroSubtitleText">
                    <?= e($heroSubtitle); ?>
                </p>

                <div class="d-flex flex-wrap align-items-center gap-3 mb-4">
                    <a href="<?= base_url(htmlspecialchars($ctaBtnUrl, ENT_QUOTES, 'UTF-8', false)); ?>" class="btn btn-nt-primary btn-lg shadow-sm d-flex align-items-center gap-2">
                        <i class="bi bi-play-circle-fill"></i>
                        <span><?= e($ctaBtnText); ?></span>
                    </a>
                    <a href="<?= base_url(htmlspecialchars($secBtnUrl, ENT_QUOTES, 'UTF-8', false)); ?>" class="btn btn-nt-outline btn-lg d-flex align-items-center gap-2">
                        <i class="bi bi-diagram-3"></i>
                        <span><?= e($secBtnText); ?></span>
                    </a>
                </div>

                <div class="d-flex align-items-center gap-4 text-secondary small pt-2 border-top">
                    <div class="d-flex align-items-center gap-2">
                        <i class="bi bi-shield-check text-success fs-5"></i>
                        <span>Rule-Based Inference Engine</span>
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <i class="bi bi-layers-half text-primary fs-5"></i>
                        <span>7 Lapisan OSI Terpetakan</span>
                    </div>
                </div>
            </div>

            <!-- Right Column: Interactive Network Topology Simulation (SVG & CSS) -->
            <div class="col-lg-6">
                <div class="topology-box bg-white border">
                    <!-- Simulation Header & Non-Claim Labels -->
                    <div class="d-flex flex-wrap align-items-center justify-content-between mb-2 border-bottom pb-2 gap-2">
                        <div class="d-flex align-items-center gap-2">
                            <span class="badge bg-primary-subtle text-primary border border-primary-subtle font-monospace">
                                <i class="bi bi-diagram-3 me-1"></i>SIMULATED NETWORK VIEW
                            </span>
                            <span class="text-muted small" style="font-size: 0.76rem;">Diagnosis Visualization</span>
                        </div>
                        <span id="topologyScenarioBadge" class="badge bg-secondary-subtle text-secondary border" style="font-size: 0.72rem;">
                            <i class="bi bi-check-circle-fill text-success me-1"></i>Simulasi Normal (All Healthy)
                        </span>
                    </div>

                    <!-- Scenario Switcher -->
                    <div class="d-flex align-items-center justify-content-between mb-3 bg-light p-2 rounded-3 border">
                        <div class="small text-secondary fw-semibold">
                            <i class="bi bi-sliders me-1 text-primary"></i>Simulasi Kasus:
                        </div>
                        <select id="topologyScenarioSelect" class="form-select form-select-sm w-auto font-monospace py-0" style="font-size: 0.78rem;">
                            <option value="healthy" selected>Normal (All Healthy)</option>
                            <option value="physical">Physical Layer (Kabel Putus / Link Down)</option>
                            <option value="network">Network Layer (DHCP APIPA 169.254)</option>
                            <option value="dns">Application Layer (DNS Resolver Down)</option>
                            <option value="wireless">Wireless (Sinyal WiFi Drop / RSSI Rendah)</option>
                            <option value="performance">Performance (High Latency WAN / Packet Loss)</option>
                            <option value="security">Security (IP Conflict & ARP Issue)</option>
                            <option value="gateway_down">Gateway Down (Default Gateway Unreachable)</option>
                            <option value="internet_down">Internet Down (Upstream WAN Disconnected)</option>
                        </select>
                    </div>

                    <!-- SVG Topology Diagram (Zero Jitter, Pure Color/Glow, Hierarchical Layout) -->
                    <svg viewBox="0 0 600 370" class="w-100 h-auto" style="overflow: visible;">
                        <defs>
                            <linearGradient id="lineGrad" x1="0%" y1="0%" x2="100%" y2="100%">
                                <stop offset="0%" stop-color="#3b82f6" stop-opacity="0.4"/>
                                <stop offset="50%" stop-color="#3b82f6" stop-opacity="0.9"/>
                                <stop offset="100%" stop-color="#10b981" stop-opacity="0.8"/>
                            </linearGradient>
                        </defs>

                        <!-- Connecting Lines with IDs -->
                        <!-- 1. WAN Cloud to Gateway Router -->
                        <line id="line-router-cloud" class="topology-line" x1="300" y1="68" x2="300" y2="105" stroke="#10b981" stroke-width="3"/>
                        <!-- 2. Gateway Router to Switch L2 -->
                        <line id="line-switch-router" class="topology-line" x1="280" y1="163" x2="220" y2="195" stroke="#3b82f6" stroke-width="2.5"/>
                        <!-- 3. Gateway Router to DNS Resolver -->
                        <line id="line-router-dns" class="topology-line" x1="320" y1="163" x2="410" y2="195" stroke="#f59e0b" stroke-width="2.5" stroke-dasharray="4,3"/>
                        <!-- 4. Switch to Client 1 (PC) -->
                        <line id="line-pc-switch" class="topology-line" x1="200" y1="253" x2="110" y2="290" stroke="#3b82f6" stroke-width="2.5" stroke-dasharray="6,4"/>
                        <!-- 5. Switch to Client 2 (Laptop) -->
                        <line id="line-laptop-switch" class="topology-line" x1="230" y1="253" x2="260" y2="290" stroke="#3b82f6" stroke-width="2.5" stroke-dasharray="6,4"/>
                        <!-- 6. Gateway Router / AP to Client 3 (Smartphone WiFi) -->
                        <line id="line-wifi-router" class="topology-line" x1="338" y1="145" x2="410" y2="290" stroke="#94a3b8" stroke-width="2" stroke-dasharray="5,4"/>

                        <!-- TIER 1 (Top): Internet / WAN Cloud -->
                        <g class="topology-node" data-node="cloud" transform="translate(262, 10)" tabindex="0" role="button" aria-label="WAN Cloud">
                            <rect width="76" height="58" rx="10" fill="#f0fdf4" stroke="#10b981" stroke-width="2"/>
                            <text x="38" y="24" text-anchor="middle" font-size="16">☁️</text>
                            <text x="38" y="39" text-anchor="middle" font-size="9" font-weight="600" fill="#0f172a">WAN / Cloud</text>
                            <text x="38" y="50" text-anchor="middle" font-size="7.5" fill="#9333ea">ISP Upstream</text>
                        </g>

                        <!-- TIER 2 (Upper Middle): Gateway Router -->
                        <g class="topology-node" data-node="gateway" transform="translate(262, 105)" tabindex="0" role="button" aria-label="Gateway Router">
                            <rect width="76" height="58" rx="10" fill="#f0fdf4" stroke="#10b981" stroke-width="2"/>
                            <text x="38" y="24" text-anchor="middle" font-size="16">🌐</text>
                            <text x="38" y="39" text-anchor="middle" font-size="9" font-weight="600" fill="#0f172a">Gateway Router</text>
                            <text x="38" y="50" text-anchor="middle" font-size="7.5" fill="#0284c7">192.168.1.1</text>
                        </g>

                        <!-- TIER 3 (Middle): Switch L2 & DNS Resolver -->
                        <!-- Node Switch L2 -->
                        <g class="topology-node" data-node="switch" transform="translate(182, 195)" tabindex="0" role="button" aria-label="Switch L2">
                            <rect width="76" height="58" rx="10" fill="#f0fdf4" stroke="#10b981" stroke-width="2"/>
                            <text x="38" y="24" text-anchor="middle" font-size="16">🔀</text>
                            <text x="38" y="39" text-anchor="middle" font-size="9" font-weight="600" fill="#0f172a">Switch L2</text>
                            <text x="38" y="50" text-anchor="middle" font-size="7.5" fill="#059669">VLAN 10</text>
                        </g>
                        <!-- Node DNS Server -->
                        <g class="topology-node" data-node="dns" transform="translate(372, 195)" tabindex="0" role="button" aria-label="DNS Resolver">
                            <rect width="76" height="58" rx="10" fill="#f0fdf4" stroke="#10b981" stroke-width="2"/>
                            <text x="38" y="24" text-anchor="middle" font-size="16">🗄️</text>
                            <text x="38" y="39" text-anchor="middle" font-size="9" font-weight="600" fill="#0f172a">DNS Resolver</text>
                            <text x="38" y="50" text-anchor="middle" font-size="7.5" fill="#b45309">8.8.8.8</text>
                        </g>

                        <!-- TIER 4 (Bottom): Client Endpoints -->
                        <!-- Client 1: Workstation PC (LAN) -->
                        <g class="topology-node" data-node="client-pc" transform="translate(72, 290)" tabindex="0" role="button" aria-label="Client 1 (PC)">
                            <rect width="76" height="58" rx="10" fill="#f0fdf4" stroke="#10b981" stroke-width="2"/>
                            <text x="38" y="24" text-anchor="middle" font-size="16">💻</text>
                            <text x="38" y="39" text-anchor="middle" font-size="9" font-weight="600" fill="#0f172a">Client 1 (PC)</text>
                            <text x="38" y="50" text-anchor="middle" font-size="7.5" fill="#64748b">192.168.1.15</text>
                        </g>
                        <!-- Client 2: Laptop Workstation (LAN) -->
                        <g class="topology-node" data-node="laptop" transform="translate(222, 290)" tabindex="0" role="button" aria-label="Client 2 (Laptop)">
                            <rect width="76" height="58" rx="10" fill="#f0fdf4" stroke="#10b981" stroke-width="2"/>
                            <text x="38" y="24" text-anchor="middle" font-size="16">💻</text>
                            <text x="38" y="39" text-anchor="middle" font-size="9" font-weight="600" fill="#0f172a">Client 2 (Laptop)</text>
                            <text x="38" y="50" text-anchor="middle" font-size="7.5" fill="#64748b">192.168.1.25</text>
                        </g>
                        <!-- Client 3: Smartphone (WiFi) -->
                        <g class="topology-node" data-node="smartphone" transform="translate(372, 290)" tabindex="0" role="button" aria-label="Client 3 (Mobile)">
                            <rect width="76" height="58" rx="10" fill="#f0fdf4" stroke="#10b981" stroke-width="2"/>
                            <text x="38" y="24" text-anchor="middle" font-size="16">📱</text>
                            <text x="38" y="39" text-anchor="middle" font-size="9" font-weight="600" fill="#0f172a">Client 3 (Mobile)</text>
                            <text x="38" y="50" text-anchor="middle" font-size="7.5" fill="#64748b">WiFi 5GHz</text>
                        </g>
                    </svg>

                    <!-- Status Pills Bar (Simulated) -->
                    <div class="row g-2 mt-2 pt-2 border-top text-center" style="font-size: 0.76rem;">
                        <div class="col-4">
                            <span class="text-muted d-block small">Physical Link (Sim)</span>
                            <span id="statusCablePill"><span class="badge bg-success-subtle text-success">Connected (Link OK)</span></span>
                        </div>
                        <div class="col-4">
                            <span class="text-muted d-block small">DHCP Status (Sim)</span>
                            <span id="statusDhcpPill"><span class="badge bg-success-subtle text-success">Valid Lease (192.168.1.15)</span></span>
                        </div>
                        <div class="col-4">
                            <span class="text-muted d-block small">DNS Resolver (Sim)</span>
                            <span id="statusDnsPill"><span class="badge bg-success-subtle text-success">Responding (18 ms)</span></span>
                        </div>
                    </div>

                    <div class="mt-2 text-center text-muted" style="font-size: 0.74rem;">
                        <i class="bi bi-cursor-fill text-primary me-1"></i> Klik pada node komponen untuk melihat peran, lapisan OSI, status nyata, & uji perintah CLI
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ============================================== -->
<!-- 3. REAL-TIME DATABASE STATS BAR -->
<!-- ============================================== -->
<section class="py-4 bg-white border-bottom">
    <div class="container">
        <div class="row g-3 text-center">
            <div class="col-6 col-md-3">
                <div class="p-2">
                    <div class="fs-2 fw-bold text-primary font-monospace"><?= number_format($stats['symptoms']); ?>+</div>
                    <div class="small text-secondary fw-medium">Total Symptoms Indexed</div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="p-2">
                    <div class="fs-2 fw-bold text-dark font-monospace"><?= number_format($stats['categories']); ?></div>
                    <div class="small text-secondary fw-medium">OSI & Network Categories</div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="p-2">
                    <div class="fs-2 fw-bold text-primary font-monospace"><?= number_format($stats['troubleshooting']); ?>+</div>
                    <div class="small text-secondary fw-medium">Troubleshooting Guides</div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="p-2">
                    <div class="fs-2 fw-bold text-success font-monospace"><?= number_format($stats['diagnoses']); ?></div>
                    <div class="small text-secondary fw-medium">Diagnoses Processed</div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ============================================== -->
<!-- 4. 6 FEATURE CARDS (DYNAMIC FROM CMS) -->
<!-- ============================================== -->
<section class="py-5 bg-light">
    <div class="container">
        <div class="row justify-content-center text-center mb-5">
            <div class="col-lg-7">
                <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-3 py-1 mb-2 rounded-pill font-monospace">
                    Arsitektur Berstandar Industri
                </span>
                <h2 class="h3 fw-bold text-dark">Fitur Unggulan NetworkTrouble</h2>
                <p class="text-secondary small">
                    Dirancang khusus untuk memecahkan masalah konektivitas secara metodis tanpa tebak-tebakan.
                </p>
            </div>
        </div>

        <div class="row g-4">
            <?php 
            $defaultIconThemes = ['nt-icon-primary', 'nt-icon-success', 'nt-icon-warning', 'nt-icon-primary', 'nt-icon-success', 'nt-icon-warning'];
            if (!empty($featureCards)):
                $renderedCount = 0;
                foreach ($featureCards as $idx => $card):
                    if (($card['status'] ?? 'published') !== 'published') continue;
                    $themeClass = $defaultIconThemes[$renderedCount % count($defaultIconThemes)];
                    $iconClass = !empty($card['icon']) ? htmlspecialchars($card['icon']) : 'bi-cpu';
                    $renderedCount++;
            ?>
            <div class="col-md-6 col-lg-4">
                <div class="nt-card card-hover p-4 h-100">
                    <div class="nt-icon-box <?= $themeClass; ?> mb-3">
                        <i class="bi <?= $iconClass; ?>"></i>
                    </div>
                    <h5 class="fw-bold text-dark mb-2"><?= e($card['title']); ?></h5>
                    <p class="text-secondary small mb-0">
                        <?= e($card['content']); ?>
                    </p>
                </div>
            </div>
            <?php 
                endforeach;
            endif;
            ?>
        </div>
    </div>
</section>

<!-- ============================================== -->
<!-- 5. HOW IT WORKS SECTION (DYNAMIC FROM CMS) -->
<!-- ============================================== -->
<section class="py-5 bg-white border-top border-bottom">
    <div class="container">
        <div class="row justify-content-center text-center mb-5">
            <div class="col-lg-7">
                <span class="badge bg-indigo-subtle text-primary border px-3 py-1 mb-2 rounded-pill font-monospace">
                    <?= e($howHeader['subtitle'] ?? 'Alur Diagnostik'); ?>
                </span>
                <h2 class="h3 fw-bold text-dark"><?= e($howHeader['title'] ?? 'Bagaimana NetworkTrouble Bekerja'); ?></h2>
                <p class="text-secondary small">
                    <?= e($howHeader['content'] ?? 'Alur sistematis 4 langkah untuk mengubah ketidakpastian gangguan menjadi rencana aksi perbaikan yang terarah.'); ?>
                </p>
            </div>
        </div>

        <div class="row g-4">
            <?php 
            if (!empty($howSteps)):
                foreach ($howSteps as $idx => $step):
                    $stepNum = !empty($step['icon']) ? htmlspecialchars($step['icon'], ENT_QUOTES, 'UTF-8', false) : (string)($idx + 1);
            ?>
            <div class="col-md-6 col-lg-3">
                <div class="nt-card p-4 h-100 border bg-light text-center">
                    <div class="nt-step-badge mx-auto mb-3"><?= $stepNum; ?></div>
                    <h5 class="fw-bold text-dark mb-2"><?= e($step['title']); ?></h5>
                    <p class="text-secondary small mb-0">
                        <?= e($step['content']); ?>
                    </p>
                </div>
            </div>
            <?php 
                endforeach;
            endif; 
            ?>
        </div>
    </div>
</section>

<!-- ============================================== -->
<!-- 6. TARGET AUDIENCE SECTION -->
<!-- ============================================== -->
<section class="py-5 bg-light border-bottom">
    <div class="container">
        <div class="row align-items-center g-5">
            <div class="col-lg-5">
                <span class="badge bg-indigo-subtle text-primary border px-3 py-1 mb-2 rounded-pill font-monospace">
                    <?= e($audienceHeader['subtitle'] ?? 'Audiens & Pengguna'); ?>
                </span>
                <h3 class="fw-bold text-dark mb-3"><?= e($audienceHeader['title'] ?? 'Dibuat untuk Praktisi & Pembelajar Jaringan'); ?></h3>
                <p class="text-secondary">
                    <?= e($audienceHeader['content'] ?? 'Menghilangkan kebiasaan asal cabut kabel atau restart sembarangan dengan memperkenalkan metodologi penelusuran gangguan berstandar sertifikasi (CompTIA Network+, CCNA, MTCNA).'); ?>
                </p>
                <div class="d-flex flex-column gap-2">
                    <div class="d-flex align-items-center gap-2">
                        <i class="bi bi-check-circle-fill text-primary"></i>
                        <span class="small fw-semibold">Mahasiswa Teknologi Informasi & Sistem Komputer</span>
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <i class="bi bi-check-circle-fill text-primary"></i>
                        <span class="small fw-semibold">Siswa SMK Jurusan Teknik Komputer & Jaringan (TKJ)</span>
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <i class="bi bi-check-circle-fill text-primary"></i>
                        <span class="small fw-semibold">Laboran & Pengelola Laboratorium Komputer Kampus / Sekolah</span>
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <i class="bi bi-check-circle-fill text-primary"></i>
                        <span class="small fw-semibold">Teknisi Jaringan Pemula & Helpdesk IT Support</span>
                    </div>
                </div>
            </div>

            <div class="col-lg-7">
                <div class="row g-3">
                    <div class="col-sm-6">
                        <div class="p-4 bg-light rounded-3 border h-100">
                            <h6 class="fw-bold text-dark"><i class="bi bi-mortarboard text-primary me-2"></i>Edukasi Praktis</h6>
                            <p class="text-secondary small mb-0">Membantu siswa dan mahasiswa menghubungkan teori 7 Lapisan OSI dengan kasus troubleshoot di dunia nyata.</p>
                        </div>
                    </div>
                    <div class="col-sm-6">
                        <div class="p-4 bg-light rounded-3 border h-100">
                            <h6 class="fw-bold text-dark"><i class="bi bi-speedometer text-success me-2"></i>Respons Cepat NOC</h6>
                            <p class="text-secondary small mb-0">Mempercepat waktu Mean Time to Resolution (MTTR) bagi tim IT support saat menangani komplain pengguna.</p>
                        </div>
                    </div>
                    <div class="col-sm-6">
                        <div class="p-4 bg-light rounded-3 border h-100">
                            <h6 class="fw-bold text-dark"><i class="bi bi-terminal text-info me-2"></i>Command-Ready</h6>
                            <p class="text-secondary small mb-0">Dilengkapi toolkit baris perintah Windows (CMD/PowerShell) dan Linux yang siap disalin dalam satu klik.</p>
                        </div>
                    </div>
                    <div class="col-sm-6">
                        <div class="p-4 bg-light rounded-3 border h-100">
                            <h6 class="fw-bold text-dark"><i class="bi bi-patch-check text-warning me-2"></i>Verifikasi Hasil</h6>
                            <p class="text-secondary small mb-0">Menampung kasus yang belum terselesaikan ke dalam antrean review teknisi agar aturan inferensi terus disempurnakan.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ============================================== -->
<!-- 7. CALL TO ACTION BANNER (DYNAMIC FROM CMS) -->
<!-- ============================================== -->
<section class="py-5 bg-dark text-white position-relative overflow-hidden">
    <div class="container py-4 text-center position-relative" style="z-index: 2;">
        <h2 class="h3 fw-bold text-white mb-3"><?= e($ctaTitle); ?></h2>
        <p class="text-secondary mx-auto mb-4" style="max-width: 600px;">
            <?= e($ctaDesc); ?>
        </p>
        <div class="d-flex justify-content-center gap-3 flex-wrap">
            <a href="<?= base_url(htmlspecialchars($ctaPrimaryUrl, ENT_QUOTES, 'UTF-8', false)); ?>" class="btn btn-primary btn-lg shadow px-4">
                <i class="bi bi-play-circle-fill me-1"></i> <?= e($ctaPrimaryText); ?>
            </a>
            <a href="<?= base_url(htmlspecialchars($ctaSecondaryUrl, ENT_QUOTES, 'UTF-8', false)); ?>" class="btn btn-outline-light btn-lg px-4">
                <i class="bi bi-book me-1"></i> <?= e($ctaSecondaryText); ?>
            </a>
        </div>
    </div>
</section>

<!-- ============================================== -->
<!-- 8. TOPOLOGY NODE DETAIL MODAL -->
<!-- ============================================== -->
<div class="modal fade" id="topologyNodeModal" tabindex="-1" aria-labelledby="topologyNodeModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header border-bottom">
                <div class="d-flex align-items-center gap-2">
                    <span id="modalNodeIcon" class="fs-4">💻</span>
                    <div>
                        <h5 class="modal-title fw-bold text-dark mb-0" id="modalNodeName">Nama Komponen</h5>
                        <span id="modalNodeLayer" class="badge bg-light text-secondary border font-monospace small">OSI Layer</span>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <!-- Status Badge -->
                <div class="d-flex align-items-center justify-content-between mb-3 pb-2 border-bottom">
                    <span class="small text-muted">Status Simulasi Saat Ini:</span>
                    <span id="modalNodeStatusBadge" class="badge bg-success-subtle text-success">Healthy</span>
                </div>

                <!-- Role / Function -->
                <div class="mb-3">
                    <h6 class="fw-bold text-dark small text-uppercase letter-spacing-1 mb-1">
                        <i class="bi bi-info-circle text-primary me-1"></i> Fungsi Komponen:
                    </h6>
                    <p id="modalNodeRole" class="text-secondary small mb-2 fw-medium"></p>
                    <p id="modalNodeDesc" class="text-secondary small mb-0"></p>
                </div>

                <!-- Relationship with Diagnosis -->
                <div class="p-3 bg-light rounded-3 border mb-3">
                    <h6 class="fw-bold text-dark small mb-1">
                        <i class="bi bi-diagram-3-fill text-primary me-1"></i> Kondisi & Hubungan Diagnostik:
                    </h6>
                    <p id="modalNodeDiag" class="text-secondary small mb-0" style="line-height: 1.5;"></p>
                </div>

                <!-- CLI Commands -->
                <div class="mb-2">
                    <h6 class="fw-bold text-dark small text-uppercase letter-spacing-1 mb-2">
                        <i class="bi bi-terminal text-primary me-1"></i> Pemeriksaan / Perintah Terkait (CLI):
                    </h6>
                    <div id="modalNodeCommands"></div>
                </div>
            </div>
            <div class="modal-footer border-top bg-light">
                <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Tutup</button>
                <a href="<?= base_url('diagnose.php'); ?>" id="modalNodeDiagnoseBtn" class="btn btn-nt-primary btn-sm">
                    <i class="bi bi-play-circle-fill me-1"></i> Buka Wizard Diagnosis
                </a>
            </div>
        </div>
    </div>
</div>

<script src="<?= base_url('assets/js/topology-simulation.js') . '?v=' . filemtime(__DIR__ . '/assets/js/topology-simulation.js'); ?>"></script>

<?php
require_once __DIR__ . '/includes/footer.php';
?>
