<?php
/**
 * NetworkTrouble - Public Site Header
 */
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/functions.php';

$siteName = get_setting('site_name', 'NetworkTrouble');
$siteTagline = get_setting('site_tagline', 'Understand Your Network. Solve Problems Smarter.');
$siteFavicon = get_setting('site_favicon', '');
$pageMetaTitle = isset($pageTitle) ? "$pageTitle | $siteName" : "$siteName - $siteTagline";
$pageMetaDesc = isset($pageDesc) ? $pageDesc : get_setting('site_description', 'Sistem klasifikasi dan diagnosis awal gangguan jaringan komputer berbasis OSI Layer dan rule engine.');
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageMetaTitle); ?></title>
    <?php 
    $faviconFsPath = !empty($siteFavicon) ? __DIR__ . '/../' . ltrim($siteFavicon, '/\\') : '';
    if (!empty($siteFavicon) && file_exists($faviconFsPath)): 
    ?>
    <link rel="icon" href="<?= base_url(e($siteFavicon)); ?>">
    <?php endif; ?>
    <meta name="description" content="<?= e($pageMetaDesc); ?>">
    <meta name="keywords" content="<?= e(get_setting('meta_keywords', 'network troubleshooting, osi layer, diagnosis jaringan, ping, dns, wifi')); ?>">
    <meta name="author" content="NetworkTrouble System">

    <!-- Open Graph Meta Tags -->
    <meta property="og:title" content="<?= e($pageMetaTitle); ?>">
    <meta property="og:description" content="<?= e($pageMetaDesc); ?>">
    <meta property="og:type" content="website">
    <meta property="og:url" content="<?= base_url(); ?>">

    <!-- Google Fonts: Inter & Poppins -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Poppins:wght@500;600;700&display=swap" rel="stylesheet">

    <!-- Bootstrap 5 & Bootstrap Icons CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    <!-- Custom CSS (with dynamic cache-buster) -->
    <link rel="stylesheet" href="<?= base_url('assets/css/style.css?v=' . filemtime(__DIR__ . '/../assets/css/style.css')); ?>">
</head>
<body>

<?php
// Display graceful alert if database is not imported or MySQL is not running
if (!is_database_ready()):
?>
<div class="alert alert-warning text-center rounded-0 mb-0 py-2 border-bottom" role="alert">
    <div class="container d-flex align-items-center justify-content-center gap-2 small">
        <i class="bi bi-exclamation-triangle-fill text-warning"></i>
        <span><strong>Database Belum Terdeteksi:</strong> Silakan buat database <code>networktrouble_db</code> dan import file <code>database/networktrouble_db.sql</code> via phpMyAdmin XAMPP.</span>
    </div>
</div>
<?php endif; ?>
