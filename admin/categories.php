<?php
/**
 * NetworkTrouble - Manage Network Categories & OSI Layers
 */
$adminTitle = 'Kelola Kategori Gangguan';
require_once __DIR__ . '/includes/admin_header.php';
require_role(['super_admin', 'technical_admin']);

$db = get_db_connection();
$errors = [];

// Handle Delete Category
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $catId = (int)$_GET['delete'];
    if ($db) {
        try {
            $stmt = $db->prepare("DELETE FROM network_categories WHERE id = ?");
            $stmt->execute([$catId]);
            log_activity($currentAdmin['id'] ?? null, 'delete_category', 'network_categories', $catId);
            set_flash('success', 'Kategori jaringan berhasil dihapus.');
        } catch (PDOException $e) {
            set_flash('error', 'Gagal menghapus kategori: Masih ada gejala atau aturan yang terhubung.');
        }
        header('Location: ' . base_url('admin/categories.php'));
        exit;
    }
}

// Handle Add / Edit Category
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_category'])) {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Token CSRF tidak valid.';
    } else {
        $catId       = (int)($_POST['category_id'] ?? 0);
        $name        = sanitize($_POST['name'] ?? '');
        $osiLayer    = sanitize($_POST['osi_layer'] ?? '');
        $severity    = in_array($_POST['severity_default'] ?? '', ['Low', 'Medium', 'High', 'Critical']) ? $_POST['severity_default'] : 'Medium';
        $icon        = sanitize($_POST['icon'] ?? 'bi-hdd-network');
        $status      = in_array($_POST['status'] ?? '', ['active', 'inactive']) ? $_POST['status'] : 'active';
        $description = $_POST['description'] ?? '';
        $slug        = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $name)));

        if (empty($name)) {
            $errors[] = 'Nama kategori wajib diisi.';
        } elseif ($db) {
            try {
                if ($catId > 0) {
                    // Update
                    $stmt = $db->prepare("
                        UPDATE network_categories
                        SET name = ?, slug = ?, description = ?, osi_layer = ?, severity_default = ?, icon = ?, status = ?
                        WHERE id = ?
                    ");
                    $stmt->execute([$name, $slug, $description, $osiLayer, $severity, $icon, $status, $catId]);
                    log_activity($currentAdmin['id'] ?? null, 'update_category', 'network_categories', $catId);
                    set_flash('success', 'Kategori jaringan berhasil diperbarui.');
                } else {
                    // Insert
                    $stmt = $db->prepare("
                        INSERT INTO network_categories (name, slug, description, osi_layer, severity_default, icon, status, created_at)
                        VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
                    ");
                    $stmt->execute([$name, $slug, $description, $osiLayer, $severity, $icon, $status]);
                    log_activity($currentAdmin['id'] ?? null, 'create_category', 'network_categories', $db->lastInsertId());
                    set_flash('success', 'Kategori jaringan baru berhasil ditambahkan.');
                }
                header('Location: ' . base_url('admin/categories.php'));
                exit;
            } catch (PDOException $e) {
                $errors[] = 'Database error: ' . $e->getMessage();
            }
        }
    }
}

// Fetch categories with symptom counts
$categories = [];
if ($db) {
    try {
        $stmt = $db->query("
            SELECT c.*,
                   COUNT(DISTINCT s.id) as symptom_count,
                   COUNT(DISTINCT r.id) as rule_count
            FROM network_categories c
            LEFT JOIN network_symptoms s ON s.category_id = c.id
            LEFT JOIN classification_rules r ON r.category_id = c.id
            GROUP BY c.id
            ORDER BY c.id ASC
        ");
        $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
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
                    <h1 class="h3 fw-bold text-dark mb-1">Kategori Masalah Jaringan & Lapisan OSI</h1>
                    <p class="text-secondary mb-0">Kelola 6 kategori utama klasifikasi dan pemetaan layer ISO/OSI 7 layer.</p>
                </div>
                <button type="button" class="btn btn-primary btn-sm rounded-pill px-3" data-bs-toggle="modal" data-bs-target="#categoryModal" onclick="openCreateModal()">
                    <i class="bi bi-plus-circle me-1"></i> Tambah Kategori Baru
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

            <!-- Table Card -->
            <div class="admin-card">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Kategori</th>
                                <th>Lapisan OSI</th>
                                <th>Tingkat Keparahan</th>
                                <th>Total Gejala</th>
                                <th>Total Rules</th>
                                <th>Status</th>
                                <th class="text-end">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($categories)): ?>
                                <?php foreach ($categories as $c): ?>
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center gap-2">
                                            <div class="p-2 rounded bg-primary-subtle text-primary">
                                                <i class="bi <?= htmlspecialchars($c['icon'] ?: 'bi-hdd-network'); ?> fs-5"></i>
                                            </div>
                                            <div>
                                                <div class="fw-bold text-dark"><?= htmlspecialchars($c['name']); ?></div>
                                                <small class="text-muted font-monospace"><?= htmlspecialchars($c['slug']); ?></small>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle">
                                            <?= htmlspecialchars($c['osi_layer']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php
                                        $sevBadge = match($c['severity_default']) {
                                            'Critical' => 'bg-danger-subtle text-danger',
                                            'High' => 'bg-warning-subtle text-warning',
                                            'Low' => 'bg-info-subtle text-info',
                                            default => 'bg-primary-subtle text-primary'
                                        };
                                        ?>
                                        <span class="badge <?= $sevBadge; ?>"><?= $c['severity_default']; ?></span>
                                    </td>
                                    <td>
                                        <span class="fw-semibold text-dark"><?= $c['symptom_count']; ?></span> Gejala
                                    </td>
                                    <td>
                                        <span class="fw-semibold text-dark"><?= $c['rule_count']; ?></span> Aturan
                                    </td>
                                    <td>
                                        <span class="badge bg-<?= $c['status'] === 'active' ? 'success' : 'secondary'; ?>-subtle text-<?= $c['status'] === 'active' ? 'success' : 'secondary'; ?>">
                                            <?= ucfirst($c['status']); ?>
                                        </span>
                                    </td>
                                    <td class="text-end">
                                        <button type="button" class="btn btn-outline-secondary btn-xs py-1 px-2"
                                                onclick='openEditModal(<?= json_encode($c); ?>)'>
                                            <i class="bi bi-pencil me-1"></i> Edit
                                        </button>
                                        <a href="<?= base_url('admin/categories.php?delete=' . $c['id']); ?>" class="btn btn-outline-danger btn-xs py-1 px-2 ms-1" onclick="return confirm('Yakin ingin menghapus kategori ini?')">
                                            <i class="bi bi-trash"></i>
                                        </a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="7" class="text-center py-4 text-muted">Tidak ada kategori ditemukan.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Modal Add / Edit Category -->
            <div class="modal fade" id="categoryModal" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                        <form method="POST" action="">
                            <input type="hidden" name="csrf_token" value="<?= generate_csrf_token(); ?>">
                            <input type="hidden" name="save_category" value="1">
                            <input type="hidden" name="category_id" id="modalCatId" value="0">

                            <div class="modal-header">
                                <h5 class="modal-title fw-bold" id="modalTitle">Tambah Kategori Baru</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <div class="mb-3">
                                    <label class="form-label fw-semibold">Nama Kategori</label>
                                    <input type="text" name="name" id="modalName" class="form-control" placeholder="Contoh: Gateway & Routing Failure" required>
                                </div>
                                <div class="row g-3 mb-3">
                                    <div class="col-md-6">
                                        <label class="form-label fw-semibold">Lapisan OSI</label>
                                        <select name="osi_layer" id="modalOsi" class="form-select" required>
                                            <option value="Layer 1 - Physical">Layer 1 - Physical</option>
                                            <option value="Layer 2 - Data Link">Layer 2 - Data Link</option>
                                            <option value="Layer 3 - Network">Layer 3 - Network</option>
                                            <option value="Layer 4 - Transport">Layer 4 - Transport</option>
                                            <option value="Layer 5 - Session">Layer 5 - Session</option>
                                            <option value="Layer 6 - Presentation">Layer 6 - Presentation</option>
                                            <option value="Layer 7 - Application">Layer 7 - Application</option>
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-semibold">Tingkat Keparahan</label>
                                        <select name="severity_default" id="modalSeverity" class="form-select" required>
                                            <option value="Low">Low</option>
                                            <option value="Medium" selected>Medium</option>
                                            <option value="High">High</option>
                                            <option value="Critical">Critical</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="row g-3 mb-3">
                                    <div class="col-md-6">
                                        <label class="form-label fw-semibold">Icon Bootstrap Icons</label>
                                        <input type="text" name="icon" id="modalIcon" class="form-control" placeholder="bi-hdd-network" value="bi-hdd-network">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-semibold">Status</label>
                                        <select name="status" id="modalStatus" class="form-select">
                                            <option value="active">Active</option>
                                            <option value="inactive">Inactive</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label fw-semibold">Penjelasan Singkat Masalah</label>
                                    <textarea name="description" id="modalDesc" class="form-control" rows="3" placeholder="Deskripsi teknis tentang jenis masalah jaringan ini..."></textarea>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                                <button type="submit" class="btn btn-primary fw-semibold px-4">Simpan Kategori</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <script>
            function openCreateModal() {
                document.getElementById('modalTitle').textContent = 'Tambah Kategori Baru';
                document.getElementById('modalCatId').value = '0';
                document.getElementById('modalName').value = '';
                document.getElementById('modalOsi').value = 'Layer 3 - Network';
                document.getElementById('modalSeverity').value = 'Medium';
                document.getElementById('modalIcon').value = 'bi-hdd-network';
                document.getElementById('modalStatus').value = 'active';
                document.getElementById('modalDesc').value = '';
            }

            function openEditModal(c) {
                document.getElementById('modalTitle').textContent = 'Edit Kategori';
                document.getElementById('modalCatId').value = c.id;
                document.getElementById('modalName').value = c.name || '';
                document.getElementById('modalOsi').value = c.osi_layer || 'Layer 3 - Network';
                document.getElementById('modalSeverity').value = c.severity_default || 'Medium';
                document.getElementById('modalIcon').value = c.icon || 'bi-hdd-network';
                document.getElementById('modalStatus').value = c.status || 'active';
                document.getElementById('modalDesc').value = c.description || '';
                
                const modal = new bootstrap.Modal(document.getElementById('categoryModal'));
                modal.show();
            }
            </script>

<?php require_once __DIR__ . '/includes/admin_footer.php'; ?>
