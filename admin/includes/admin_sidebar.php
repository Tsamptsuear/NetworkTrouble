<?php
/**
 * NetworkTrouble - Admin Layout Sidebar
 * Responsive sidebar with RBAC permission filtering and active route indicators.
 */
$currentAdmin = get_current_admin();
$role = $currentAdmin['role'] ?? 'technical_admin';
$activePage = basename($_SERVER['PHP_SELF'] ?? '');
?>
<aside class="admin-sidebar">
    <a href="<?= base_url('admin/index.php'); ?>" class="admin-brand">
        <span class="badge bg-primary p-2 rounded-3"><i class="bi bi-hdd-network-fill fs-5"></i></span>
        <div>
            <span class="lh-1 d-block">NetworkTrouble</span>
            <span class="badge bg-secondary-subtle text-light" style="font-size: 0.65rem; letter-spacing: 0.05em;">ADMIN PANEL</span>
        </div>
    </a>

    <!-- Navigation List -->
    <ul class="sidebar-nav">
        <!-- 1. OVERVIEW -->
        <li class="sidebar-heading">Dashboard</li>
        <li class="nav-item">
            <a class="nav-link <?= $activePage === 'index.php' ? 'active' : ''; ?>" href="<?= base_url('admin/index.php'); ?>">
                <i class="bi bi-grid-1x2"></i>
                <span>Overview</span>
            </a>
        </li>

        <!-- 2. CONTENT MANAGEMENT SYSTEM (CMS) -->
        <?php if ($role === 'super_admin' || $role === 'content_admin'): ?>
        <li class="sidebar-heading">Content Management</li>
        <li class="nav-item">
            <a class="nav-link <?= $activePage === 'content.php' ? 'active' : ''; ?>" href="<?= base_url('admin/content.php'); ?>">
                <i class="bi bi-window-desktop"></i>
                <span>Konten & Tampilan Website</span>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?= $activePage === 'media.php' ? 'active' : ''; ?>" href="<?= base_url('admin/media.php'); ?>">
                <i class="bi bi-sliders"></i>
                <span>Media & Slider Manager</span>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?= $activePage === 'contact.php' ? 'active' : ''; ?>" href="<?= base_url('admin/contact.php'); ?>">
                <i class="bi bi-headset"></i>
                <span>Contact Information</span>
            </a>
        </li>
        <?php endif; ?>

        <!-- 3. DIAGNOSTIC & RULES ENGINE -->
        <?php if ($role === 'super_admin' || $role === 'technical_admin'): ?>
        <li class="sidebar-heading">Diagnostic Engine</li>
        <li class="nav-item">
            <a class="nav-link <?= $activePage === 'categories.php' ? 'active' : ''; ?>" href="<?= base_url('admin/categories.php'); ?>">
                <i class="bi bi-diagram-3"></i>
                <span>Network Categories & OSI</span>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?= $activePage === 'symptoms.php' ? 'active' : ''; ?>" href="<?= base_url('admin/symptoms.php'); ?>">
                <i class="bi bi-activity"></i>
                <span>Symptoms & Weights</span>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?= $activePage === 'rules.php' ? 'active' : ''; ?>" href="<?= base_url('admin/rules.php'); ?>">
                <i class="bi bi-shuffle"></i>
                <span>Classification Rules</span>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?= $activePage === 'troubleshooting.php' ? 'active' : ''; ?>" href="<?= base_url('admin/troubleshooting.php'); ?>">
                <i class="bi bi-signpost-2"></i>
                <span>Troubleshooting Steps</span>
            </a>
        </li>
        <?php endif; ?>

        <!-- 4. REPORTS & CASE TRIAGE -->
        <?php if ($role === 'super_admin' || $role === 'technical_admin'): ?>
        <li class="sidebar-heading">Reports & Triage</li>
        <li class="nav-item">
            <a class="nav-link <?= $activePage === 'reports.php' ? 'active' : ''; ?>" href="<?= base_url('admin/reports.php'); ?>">
                <i class="bi bi-bar-chart-line"></i>
                <span>Diagnosis Reports</span>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?= $activePage === 'unresolved.php' ? 'active' : ''; ?>" href="<?= base_url('admin/unresolved.php'); ?>">
                <i class="bi bi-exclamation-triangle"></i>
                <span>Unresolved Cases</span>
            </a>
        </li>
        <?php endif; ?>

        <!-- 5. SYSTEM & AI CONFIGURATION (SUPER ADMIN) -->
        <?php if ($role === 'super_admin'): ?>
        <li class="sidebar-heading">System & Security</li>
        <li class="nav-item">
            <a class="nav-link <?= $activePage === 'admins.php' ? 'active' : ''; ?>" href="<?= base_url('admin/admins.php'); ?>">
                <i class="bi bi-people"></i>
                <span>Admin Users & RBAC</span>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?= $activePage === 'ai-settings.php' ? 'active' : ''; ?>" href="<?= base_url('admin/ai-settings.php'); ?>">
                <i class="bi bi-cpu"></i>
                <span>AI Module Settings</span>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?= $activePage === 'activity-logs.php' ? 'active' : ''; ?>" href="<?= base_url('admin/activity-logs.php'); ?>">
                <i class="bi bi-clock-history"></i>
                <span>Activity Audit Logs</span>
            </a>
        </li>
        <?php endif; ?>
    </ul>
</aside>
