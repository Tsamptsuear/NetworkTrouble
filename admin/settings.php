<?php
/**
 * NetworkTrouble - Settings Redirect
 * Settings have been unified into "Konten & Tampilan Website" (admin/content.php)
 */
require_once __DIR__ . '/../config/app.php';

// Redirect permanently to unified content & settings manager
header("HTTP/1.1 301 Moved Permanently");
header("Location: " . base_url('admin/content.php'));
exit;
