<?php
/**
 * NetworkTrouble - Admin Logout Handler
 */
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../includes/auth.php';

logout_admin();
set_flash('success', 'Anda telah berhasil logout dari sistem.');
header('Location: ' . base_url('admin/login.php'));
exit;
