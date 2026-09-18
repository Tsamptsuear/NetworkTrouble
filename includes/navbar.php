<?php
/**
 * NetworkTrouble - Public Site Navbar
 */
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/functions.php';
$currentScript = basename($_SERVER['PHP_SELF'] ?? '');
$navBrand = get_site_setting('site_name', 'NetworkTrouble');
$navBadge = get_site_setting('navbar_badge_text', 'v1.0 • Diagnostic AI');
$siteLogo = get_site_setting('site_logo', '');
// CTA Button strictly sourced from Hero Section configuration
$navBtnText = get_site_setting('cta_button_text', 'Start Diagnosis');
?>
<nav class="navbar navbar-expand-lg nt-navbar sticky-top">
    <div class="container">
        <a class="navbar-brand d-flex align-items-center gap-2" href="<?= base_url(); ?>">
            <?php 
            $logoFsPath = !empty($siteLogo) ? __DIR__ . '/../' . ltrim($siteLogo, '/\\') : '';
            if (!empty($siteLogo) && file_exists($logoFsPath)): 
            ?>
                <img src="<?= base_url(e($siteLogo)); ?>" alt="<?= e($navBrand); ?>" class="nt-brand-logo me-1" style="height: 38px; max-width: 140px; object-fit: contain;">
            <?php else: ?>
                <span class="nt-icon-box nt-icon-primary" style="width: 38px; height: 38px; font-size: 1.2rem;">
                    <i class="bi bi-hdd-network-fill"></i>
                </span>
            <?php endif; ?>
            <div class="d-flex flex-column">
                <span class="fw-bold tracking-tight text-dark lh-1" style="font-size: 1.25rem;">
                    <?= e($navBrand); ?>
                </span>
                <?php if (!empty($navBadge)): ?>
                <span class="brand-badge mt-1 align-self-start"><?= e($navBadge); ?></span>
                <?php endif; ?>
            </div>
        </a>

        <button class="navbar-toggler border-0 shadow-none" type="button" data-bs-toggle="collapse" data-bs-target="#navbarMain">
            <i class="bi bi-list fs-2 text-dark"></i>
        </button>

        <div class="collapse navbar-collapse" id="navbarMain">
            <ul class="navbar-nav mx-auto mb-2 mb-lg-0 gap-1">
                <li class="nav-item">
                    <a class="nav-link <?= $currentScript === 'index.php' ? 'active' : ''; ?>" href="<?= base_url(); ?>">
                        <i class="bi bi-house-door me-1"></i> Home
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= $currentScript === 'diagnose.php' ? 'active' : ''; ?>" href="<?= base_url('diagnose.php'); ?>">
                        <i class="bi bi-cpu me-1"></i> Diagnose
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= $currentScript === 'how-it-works.php' ? 'active' : ''; ?>" href="<?= base_url('how-it-works.php'); ?>">
                        <i class="bi bi-diagram-3 me-1"></i> How It Works
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= $currentScript === 'knowledge.php' ? 'active' : ''; ?>" href="<?= base_url('knowledge.php'); ?>">
                        <i class="bi bi-journal-code me-1"></i> Knowledge
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= $currentScript === 'troubleshooting.php' ? 'active' : ''; ?>" href="<?= base_url('troubleshooting.php'); ?>">
                        <i class="bi bi-tools me-1"></i> Guide
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= $currentScript === 'history.php' ? 'active' : ''; ?>" href="<?= base_url('history.php'); ?>">
                        <i class="bi bi-clock-history me-1"></i> History
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= $currentScript === 'contact.php' ? 'active' : ''; ?>" href="<?= base_url('contact.php'); ?>">
                        <i class="bi bi-headset me-1"></i> Contact
                    </a>
                </li>
            </ul>

            <div class="d-flex align-items-center gap-2 mt-3 mt-lg-0">
                <a href="<?= base_url('diagnose.php'); ?>" class="btn btn-nt-primary shadow-sm d-flex align-items-center gap-2">
                    <i class="bi bi-play-circle-fill"></i>
                    <span><?= e($navBtnText); ?></span>
                </a>
                
                <a href="<?= base_url('admin/login.php'); ?>" class="btn btn-outline-secondary btn-sm px-2 py-2" title="Portal Admin IT" data-bs-toggle="tooltip">
                    <i class="bi bi-person-lock fs-6"></i>
                </a>
            </div>
        </div>
    </div>
</nav>

<?php
$flash = get_flash();
if ($flash):
?>
<div class="container mt-3">
    <div class="alert alert-<?= e($flash['type']); ?> alert-dismissible fade show shadow-sm" role="alert">
        <div class="d-flex align-items-center gap-2">
            <?php if ($flash['type'] === 'success'): ?>
                <i class="bi bi-check-circle-fill fs-5"></i>
            <?php elseif ($flash['type'] === 'danger'): ?>
                <i class="bi bi-exclamation-octagon-fill fs-5"></i>
            <?php else: ?>
                <i class="bi bi-info-circle-fill fs-5"></i>
            <?php endif; ?>
            <div><?= e($flash['message']); ?></div>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
</div>
<?php endif; ?>
