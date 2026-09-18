<?php
/**
 * NetworkTrouble - Admin Layout Topbar
 */
$currentAdmin = get_current_admin();
$roleBadgeClass = match($currentAdmin['role'] ?? '') {
    'super_admin' => 'bg-danger-subtle text-danger border border-danger-subtle',
    'content_admin' => 'bg-info-subtle text-info border border-info-subtle',
    default => 'bg-primary-subtle text-primary border border-primary-subtle'
};
$roleLabel = match($currentAdmin['role'] ?? '') {
    'super_admin' => 'Super Admin',
    'content_admin' => 'Content Admin',
    default => 'Technical Admin'
};
?>
<header class="admin-topbar">
    <div class="d-flex align-items-center gap-3">
        <button type="button" class="btn btn-sm btn-outline-secondary d-lg-none" id="sidebarToggle" aria-label="Toggle Sidebar">
            <i class="bi bi-list fs-5"></i>
        </button>
        <div class="d-none d-sm-block">
            <span class="text-secondary small">Panel Administrasi & Diagnostik</span>
        </div>
    </div>

    <div class="d-flex align-items-center gap-3">
        <!-- AI Provider Badge -->
        <span class="badge bg-dark-subtle text-secondary border border-secondary-subtle d-none d-md-inline-flex align-items-center gap-1 py-2 px-3 rounded-pill">
            <i class="bi bi-cpu text-info"></i>
            <span>Engine: Weighted Rules + <?= defined('AI_MODE') && AI_MODE === 'live' ? 'Live AI' : 'Deterministic AI'; ?></span>
        </span>

        <!-- View Public Website -->
        <a href="<?= base_url(); ?>" target="_blank" class="btn btn-sm btn-outline-primary d-inline-flex align-items-center gap-1 rounded-pill px-3">
            <i class="bi bi-box-arrow-up-right"></i>
            <span class="d-none d-sm-inline">Lihat Website</span>
        </a>

        <!-- User Dropdown -->
        <div class="dropdown">
            <button class="btn btn-sm btn-dark dropdown-toggle d-flex align-items-center gap-2 rounded-pill px-3 py-1 border border-secondary-subtle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                <span class="avatar-circle"><?= strtoupper(substr($currentAdmin['name'] ?? 'A', 0, 1)); ?></span>
                <span class="d-none d-md-inline fw-semibold small"><?= htmlspecialchars($currentAdmin['name'] ?? 'Admin'); ?></span>
            </button>
            <ul class="dropdown-menu dropdown-menu-end shadow-lg border-secondary-subtle">
                <li class="dropdown-header">
                    <div class="fw-bold text-dark"><?= htmlspecialchars($currentAdmin['name'] ?? 'Admin'); ?></div>
                    <small class="text-muted"><?= htmlspecialchars($currentAdmin['email'] ?? ''); ?></small>
                    <div class="mt-1"><span class="badge <?= $roleBadgeClass; ?>"><?= $roleLabel; ?></span></div>
                </li>
                <li><hr class="dropdown-divider"></li>
                <li>
                    <a class="dropdown-item d-flex align-items-center gap-2" href="<?= base_url('admin/settings.php'); ?>">
                        <i class="bi bi-sliders text-secondary"></i> Pengaturan
                    </a>
                </li>
                <li>
                    <a class="dropdown-item d-flex align-items-center gap-2 text-danger" href="<?= base_url('admin/logout.php'); ?>">
                        <i class="bi bi-box-arrow-right"></i> Logout
                    </a>
                </li>
            </ul>
        </div>
    </div>
</header>
