<?php
/**
 * NetworkTrouble - Activity Audit Logs
 */
$adminTitle = 'Log Aktivitas Sistem';
require_once __DIR__ . '/includes/admin_header.php';
require_role(['super_admin']);

$db = get_db_connection();

// Handle Clear Logs
if (isset($_POST['clear_logs'])) {
    if (verify_csrf_token($_POST['csrf_token'] ?? '')) {
        if ($db) {
            $db->query("TRUNCATE TABLE activity_logs");
            log_activity($currentAdmin['id'], 'clear_activity_logs', 'activity_logs', null);
            set_flash('success', 'Semua riwayat audit log telah dibersihkan.');
        }
    }
    header('Location: ' . base_url('admin/activity-logs.php'));
    exit;
}

// Fetch logs with admin details
$logs = [];
if ($db) {
    try {
        $stmt = $db->query("
            SELECT l.*, a.name AS admin_name, a.username AS admin_username, a.role AS admin_role
            FROM activity_logs l
            LEFT JOIN admins a ON l.admin_id = a.id
            ORDER BY l.created_at DESC
            LIMIT 150
        ");
        $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {}
}
?>

<div class="admin-layout-wrapper">
    <?php include __DIR__ . '/includes/admin_sidebar.php'; ?>

    <div class="admin-main">
        <?php include __DIR__ . '/includes/admin_topbar.php'; ?>

        <div class="admin-content">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
                <div>
                    <h1 class="h3 fw-bold text-dark mb-1">Riwayat Audit Aktivitas Admin</h1>
                    <p class="text-secondary mb-0">Catatan jejak aktivitas dan modifikasi data untuk keamanan sistem dan kepatuhan audit.</p>
                </div>
                <form method="POST" action="" onsubmit="return confirm('Apakah Anda yakin ingin menghapus seluruh riwayat log aktivitas?')">
                    <input type="hidden" name="csrf_token" value="<?= generate_csrf_token(); ?>">
                    <input type="hidden" name="clear_logs" value="1">
                    <button type="submit" class="btn btn-outline-danger btn-sm rounded-pill px-3">
                        <i class="bi bi-trash me-1"></i> Bersihkan Log
                    </button>
                </form>
            </div>

            <?php
            $flash = get_flash();
            if ($flash):
            ?>
            <div class="alert alert-<?= $flash['type'] === 'error' ? 'danger' : $flash['type']; ?> alert-dismissible fade show shadow-sm" role="alert">
                <?= htmlspecialchars($flash['message']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php endif; ?>

            <!-- Activity Table -->
            <div class="admin-card">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Waktu Kejadian</th>
                                <th>Administrator</th>
                                <th>Tindakan (Action)</th>
                                <th>Target Objek</th>
                                <th>IP Address</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($logs)): ?>
                                <?php foreach ($logs as $log): ?>
                                <tr>
                                    <td>
                                        <small class="text-dark font-monospace fw-semibold"><?= date('d M Y, H:i:s', strtotime($log['created_at'])); ?></small>
                                    </td>
                                    <td>
                                        <?php if (!empty($log['admin_name'])): ?>
                                            <div class="fw-bold text-dark"><?= htmlspecialchars($log['admin_name']); ?></div>
                                            <small class="text-muted">@<?= htmlspecialchars($log['admin_username']); ?></small>
                                        <?php else: ?>
                                            <span class="text-secondary small fst-italic">Sistem / Anonymous</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php
                                        $actionBadge = 'bg-primary-subtle text-primary';
                                        if (str_contains($log['action'], 'delete')) $actionBadge = 'bg-danger-subtle text-danger';
                                        if (str_contains($log['action'], 'login')) $actionBadge = 'bg-success-subtle text-success';
                                        if (str_contains($log['action'], 'update')) $actionBadge = 'bg-warning-subtle text-warning';
                                        ?>
                                        <span class="badge <?= $actionBadge; ?> font-monospace" style="font-size: 0.75rem;">
                                            <?= htmlspecialchars($log['action']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if (!empty($log['target_table'])): ?>
                                            <code class="text-dark"><?= htmlspecialchars($log['target_table']); ?></code>
                                            <?php if (!empty($log['target_id'])): ?>
                                                <span class="text-muted small">#<?= $log['target_id']; ?></span>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            <span class="text-muted small">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <small class="text-secondary font-monospace"><?= htmlspecialchars($log['ip_address'] ?? '-'); ?></small>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="5" class="text-center py-5 text-muted">Belum ada aktivitas yang tercatat dalam log audit.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

<?php require_once __DIR__ . '/includes/admin_footer.php'; ?>
