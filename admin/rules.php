<?php
/**
 * NetworkTrouble - Manage Classification Rules
 */
$adminTitle = 'Kelola Aturan Klasifikasi (Rules)';
require_once __DIR__ . '/includes/admin_header.php';
require_role(['super_admin', 'technical_admin']);

$db = get_db_connection();
$errors = [];

// Handle Delete Rule
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $ruleId = (int)$_GET['delete'];
    if ($db) {
        try {
            $stmt = $db->prepare("DELETE FROM classification_rules WHERE id = ?");
            $stmt->execute([$ruleId]);
            log_activity($currentAdmin['id'] ?? null, 'delete_rule', 'classification_rules', $ruleId);
            set_flash('success', 'Aturan klasifikasi berhasil dihapus.');
        } catch (PDOException $e) {
            set_flash('error', 'Gagal menghapus aturan: ' . $e->getMessage());
        }
        header('Location: ' . base_url('admin/rules.php'));
        exit;
    }
}

// Handle Add / Edit Rule
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_rule'])) {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Token CSRF tidak valid.';
    } else {
        $ruleId         = (int)($_POST['rule_id'] ?? 0);
        $ruleName       = sanitize($_POST['rule_name'] ?? '');
        $categoryId     = (int)($_POST['category_id'] ?? 0);
        $score          = (int)($_POST['score'] ?? 30);
        $priority       = (int)($_POST['priority'] ?? 1);
        $status         = in_array($_POST['status'] ?? '', ['active', 'inactive']) ? $_POST['status'] : 'active';
        $conditionsRaw  = trim($_POST['conditions_json'] ?? '');

        // Validate JSON
        $decoded = json_decode($conditionsRaw, true);
        if ($decoded === null && !empty($conditionsRaw)) {
            $errors[] = 'Format kondisi JSON tidak valid. Pastikan format sintaks JSON benar.';
        } elseif (empty($ruleName) || $categoryId <= 0) {
            $errors[] = 'Nama aturan dan kategori target wajib diisi.';
        } elseif ($db) {
            $conditionsJson = json_encode($decoded ?: ['symptoms' => []], JSON_UNESCAPED_UNICODE);
            try {
                if ($ruleId > 0) {
                    $stmt = $db->prepare("
                        UPDATE classification_rules
                        SET rule_name = ?, category_id = ?, conditions_json = ?, score = ?, priority = ?, status = ?
                        WHERE id = ?
                    ");
                    $stmt->execute([$ruleName, $categoryId, $conditionsJson, $score, $priority, $status, $ruleId]);
                    log_activity($currentAdmin['id'] ?? null, 'update_rule', 'classification_rules', $ruleId);
                    set_flash('success', 'Aturan klasifikasi berhasil diperbarui.');
                } else {
                    $stmt = $db->prepare("
                        INSERT INTO classification_rules (rule_name, category_id, conditions_json, score, priority, status, created_at)
                        VALUES (?, ?, ?, ?, ?, ?, NOW())
                    ");
                    $stmt->execute([$ruleName, $categoryId, $conditionsJson, $score, $priority, $status]);
                    log_activity($currentAdmin['id'] ?? null, 'create_rule', 'classification_rules', $db->lastInsertId());
                    set_flash('success', 'Aturan klasifikasi baru berhasil ditambahkan.');
                }
                header('Location: ' . base_url('admin/rules.php'));
                exit;
            } catch (PDOException $e) {
                $errors[] = 'Database error: ' . $e->getMessage();
            }
        }
    }
}

// Fetch categories for dropdown
$categories = [];
if ($db) {
    try {
        $categories = $db->query("SELECT id, name FROM network_categories ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {}
}

// Fetch rules with category info
$rules = [];
if ($db) {
    try {
        $stmt = $db->query("
            SELECT r.*, c.name AS category_name, c.osi_layer
            FROM classification_rules r
            LEFT JOIN network_categories c ON r.category_id = c.id
            ORDER BY r.priority ASC, r.id ASC
        ");
        $rules = $stmt->fetchAll(PDO::FETCH_ASSOC);
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
                    <h1 class="h3 fw-bold text-dark mb-1">Aturan Klasifikasi Jaringan (Rules Engine)</h1>
                    <p class="text-secondary mb-0">Definisi kondisi multigejala dan pengali bobot confidence score.</p>
                </div>
                <button type="button" class="btn btn-primary btn-sm rounded-pill px-3" data-bs-toggle="modal" data-bs-target="#ruleModal" onclick="openCreateRuleModal()">
                    <i class="bi bi-plus-circle me-1"></i> Buat Aturan Baru
                </button>
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

            <?php if (!empty($errors)): ?>
            <div class="alert alert-danger shadow-sm">
                <ul class="mb-0 ps-3">
                    <?php foreach ($errors as $err): ?>
                        <li><?= htmlspecialchars($err); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <?php endif; ?>

            <!-- Rules Table -->
            <div class="admin-card">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Prioritas</th>
                                <th>Nama Aturan</th>
                                <th>Kategori Keputusan</th>
                                <th>Kondisi Gejala (JSON)</th>
                                <th>Skor Bonus</th>
                                <th>Status</th>
                                <th class="text-end">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($rules)): ?>
                                <?php foreach ($rules as $r): ?>
                                <tr>
                                    <td>
                                        <span class="badge bg-light text-dark border">Level <?= $r['priority']; ?></span>
                                    </td>
                                    <td>
                                        <div class="fw-bold text-dark"><?= htmlspecialchars($r['rule_name']); ?></div>
                                    </td>
                                    <td>
                                        <span class="badge bg-primary-subtle text-primary">
                                            <?= htmlspecialchars($r['category_name'] ?? 'Tidak Diketahui'); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php
                                        $cond = json_decode($r['conditions_json'], true);
                                        $symptomList = $cond['symptoms'] ?? [];
                                        ?>
                                        <small class="font-monospace text-muted d-block text-truncate" style="max-width: 260px;" title="<?= htmlspecialchars($r['conditions_json']); ?>">
                                            <?= htmlspecialchars(json_encode($symptomList)); ?>
                                        </small>
                                    </td>
                                    <td>
                                        <span class="badge bg-success-subtle text-success fw-bold">+<?= $r['score']; ?></span>
                                    </td>
                                    <td>
                                        <span class="badge bg-<?= $r['status'] === 'active' ? 'success' : 'secondary'; ?>-subtle text-<?= $r['status'] === 'active' ? 'success' : 'secondary'; ?>">
                                            <?= ucfirst($r['status']); ?>
                                        </span>
                                    </td>
                                    <td class="text-end">
                                        <button type="button" class="btn btn-outline-secondary btn-xs py-1 px-2"
                                                onclick='openEditRuleModal(<?= json_encode($r); ?>)'>
                                            <i class="bi bi-pencil me-1"></i> Edit
                                        </button>
                                        <a href="<?= base_url('admin/rules.php?delete=' . $r['id']); ?>" class="btn btn-outline-danger btn-xs py-1 px-2 ms-1" onclick="return confirm('Hapus aturan ini?')">
                                            <i class="bi bi-trash"></i>
                                        </a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="7" class="text-center py-4 text-muted">Belum ada aturan klasifikasi tersimpan.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Modal Add / Edit Rule -->
            <div class="modal fade" id="ruleModal" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                        <form method="POST" action="">
                            <input type="hidden" name="csrf_token" value="<?= generate_csrf_token(); ?>">
                            <input type="hidden" name="save_rule" value="1">
                            <input type="hidden" name="rule_id" id="modalRuleId" value="0">

                            <div class="modal-header">
                                <h5 class="modal-title fw-bold" id="modalRuleTitle">Buat Aturan Baru</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <div class="mb-3">
                                    <label class="form-label fw-semibold">Nama Aturan (Rule Name)</label>
                                    <input type="text" name="rule_name" id="modalRuleName" class="form-control" placeholder="Contoh: Deteksi Masalah DNS & Resolusi Domain" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label fw-semibold">Kategori Klasifikasi</label>
                                    <select name="category_id" id="modalRuleCat" class="form-select" required>
                                        <option value="">-- Pilih Kategori Tujuan --</option>
                                        <?php foreach ($categories as $cat): ?>
                                            <option value="<?= $cat['id']; ?>"><?= htmlspecialchars($cat['name']); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="row g-3 mb-3">
                                    <div class="col-md-4">
                                        <label class="form-label fw-semibold">Prioritas</label>
                                        <input type="number" name="priority" id="modalRulePriority" class="form-control" min="1" max="100" value="1" required>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label fw-semibold">Bobot Tambahan</label>
                                        <input type="number" name="score" id="modalRuleScore" class="form-control" min="1" max="100" value="30" required>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label fw-semibold">Status</label>
                                        <select name="status" id="modalRuleStatus" class="form-select">
                                            <option value="active">Active</option>
                                            <option value="inactive">Inactive</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label fw-semibold">Kondisi Gejala JSON (`conditions_json`)</label>
                                    <textarea name="conditions_json" id="modalRuleCond" class="form-control font-monospace" rows="4" required></textarea>
                                    <div class="form-text small">Format contoh: <code>{"symptoms": [1, 3], "min_match": 1}</code> atau nama kode gejala.</div>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                                <button type="submit" class="btn btn-primary fw-semibold px-4">Simpan Aturan</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <script>
            function openCreateRuleModal() {
                document.getElementById('modalRuleTitle').textContent = 'Buat Aturan Baru';
                document.getElementById('modalRuleId').value = '0';
                document.getElementById('modalRuleName').value = '';
                document.getElementById('modalRuleCat').value = '';
                document.getElementById('modalRulePriority').value = '1';
                document.getElementById('modalRuleScore').value = '30';
                document.getElementById('modalRuleStatus').value = 'active';
                document.getElementById('modalRuleCond').value = '{\n  "symptoms": ["dns_probe_finished", "ping_ip_ok_domain_fail"]\n}';
            }

            function openEditRuleModal(r) {
                document.getElementById('modalRuleTitle').textContent = 'Edit Aturan';
                document.getElementById('modalRuleId').value = r.id;
                document.getElementById('modalRuleName').value = r.rule_name || '';
                document.getElementById('modalRuleCat').value = r.category_id || '';
                document.getElementById('modalRulePriority').value = r.priority || '1';
                document.getElementById('modalRuleScore').value = r.score || '30';
                document.getElementById('modalRuleStatus').value = r.status || 'active';
                document.getElementById('modalRuleCond').value = r.conditions_json || '';

                const modal = new bootstrap.Modal(document.getElementById('ruleModal'));
                modal.show();
            }
            </script>

<?php require_once __DIR__ . '/includes/admin_footer.php'; ?>
