<?php
/**
 * NetworkTrouble - Manage Symptoms & Weights
 */
$adminTitle = 'Kelola Gejala Jaringan';
require_once __DIR__ . '/includes/admin_header.php';
require_role(['super_admin', 'technical_admin']);

$db = get_db_connection();
$errors = [];

// Handle Delete Symptom
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $symId = (int)$_GET['delete'];
    if ($db) {
        try {
            $stmt = $db->prepare("DELETE FROM network_symptoms WHERE id = ?");
            $stmt->execute([$symId]);
            log_activity($currentAdmin['id'] ?? null, 'delete_symptom', 'network_symptoms', $symId);
            set_flash('success', 'Gejala jaringan berhasil dihapus.');
        } catch (PDOException $e) {
            set_flash('error', 'Gagal menghapus gejala: ' . $e->getMessage());
        }
        header('Location: ' . base_url('admin/symptoms.php'));
        exit;
    }
}

// Handle Add / Edit Symptom
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_symptom'])) {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Token CSRF tidak valid.';
    } else {
        $symId       = (int)($_POST['symptom_id'] ?? 0);
        $categoryId  = (int)($_POST['category_id'] ?? 0);
        $name        = sanitize($_POST['symptom_name'] ?? '');
        $weight      = max(1, min(100, (int)($_POST['weight'] ?? 10)));
        $status      = in_array($_POST['status'] ?? '', ['active', 'inactive']) ? $_POST['status'] : 'active';
        $description = sanitize($_POST['symptom_description'] ?? '');

        if (empty($name) || $categoryId <= 0) {
            $errors[] = 'Nama gejala dan kategori wajib dipilih.';
        } elseif ($db) {
            try {
                if ($symId > 0) {
                    $stmt = $db->prepare("
                        UPDATE network_symptoms
                        SET category_id = ?, symptom_name = ?, symptom_description = ?, weight = ?, status = ?
                        WHERE id = ?
                    ");
                    $stmt->execute([$categoryId, $name, $description, $weight, $status, $symId]);
                    log_activity($currentAdmin['id'] ?? null, 'update_symptom', 'network_symptoms', $symId);
                    set_flash('success', 'Gejala berhasil diperbarui.');
                } else {
                    $stmt = $db->prepare("
                        INSERT INTO network_symptoms (category_id, symptom_name, symptom_description, input_type, weight, status, created_at)
                        VALUES (?, ?, ?, 'checkbox', ?, ?, NOW())
                    ");
                    $stmt->execute([$categoryId, $name, $description, $weight, $status]);
                    log_activity($currentAdmin['id'] ?? null, 'create_symptom', 'network_symptoms', $db->lastInsertId());
                    set_flash('success', 'Gejala baru berhasil ditambahkan.');
                }
                header('Location: ' . base_url('admin/symptoms.php'));
                exit;
            } catch (PDOException $e) {
                $errors[] = 'Database error: ' . $e->getMessage();
            }
        }
    }
}

// Filter by category
$filterCat = isset($_GET['cat']) ? (int)$_GET['cat'] : 0;

// Fetch categories for dropdown
$categories = [];
if ($db) {
    try {
        $categories = $db->query("SELECT id, name FROM network_categories ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {}
}

// Fetch symptoms
$symptoms = [];
if ($db) {
    try {
        $sql = "
            SELECT s.*, c.name AS category_name, c.osi_layer
            FROM network_symptoms s
            LEFT JOIN network_categories c ON s.category_id = c.id
        ";
        if ($filterCat > 0) {
            $sql .= " WHERE s.category_id = " . $filterCat;
        }
        $sql .= " ORDER BY s.weight DESC, s.id ASC";
        $symptoms = $db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
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
                    <h1 class="h3 fw-bold text-dark mb-1">Daftar Gejala & Bobot Skoring (Weighted Engine)</h1>
                    <p class="text-secondary mb-0">Kelola indikasi gangguan, bobot probabilitas, dan relasi ke kategori masalah.</p>
                </div>
                <button type="button" class="btn btn-primary btn-sm rounded-pill px-3" data-bs-toggle="modal" data-bs-target="#symptomModal" onclick="openCreateSymptomModal()">
                    <i class="bi bi-plus-circle me-1"></i> Tambah Gejala Baru
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

            <!-- Filter Bar -->
            <div class="admin-card p-3 mb-4">
                <form method="GET" action="" class="row g-3 align-items-center">
                    <div class="col-md-4">
                        <label class="form-label small fw-semibold text-secondary mb-1">Filter Menurut Kategori:</label>
                        <select name="cat" class="form-select form-select-sm" onchange="this.form.submit()">
                            <option value="0">-- Tampilkan Semua Kategori --</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?= $cat['id']; ?>" <?= $filterCat === (int)$cat['id'] ? 'selected' : ''; ?>>
                                    <?= htmlspecialchars($cat['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <?php if ($filterCat > 0): ?>
                    <div class="col-md-2 mt-auto">
                        <a href="<?= base_url('admin/symptoms.php'); ?>" class="btn btn-light btn-sm w-100">Reset Filter</a>
                    </div>
                    <?php endif; ?>
                </form>
            </div>

            <!-- Table Card -->
            <div class="admin-card">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Gejala Gangguan</th>
                                <th>Kategori Dituju</th>
                                <th>Lapisan OSI</th>
                                <th>Bobot (Score)</th>
                                <th>Status</th>
                                <th class="text-end">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($symptoms)): ?>
                                <?php foreach ($symptoms as $s): ?>
                                <tr>
                                    <td>
                                        <div class="fw-bold text-dark"><?= htmlspecialchars($s['symptom_name']); ?></div>
                                        <?php if (!empty($s['symptom_description'])): ?>
                                            <small class="text-muted"><?= htmlspecialchars($s['symptom_description']); ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="badge bg-primary-subtle text-primary">
                                            <?= htmlspecialchars($s['category_name'] ?? 'Tidak Terhubung'); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <small class="text-secondary"><?= htmlspecialchars($s['osi_layer'] ?? '-'); ?></small>
                                    </td>
                                    <td>
                                        <span class="badge bg-warning-subtle text-dark border border-warning-subtle fw-bold">
                                            +<?= $s['weight']; ?> Poin
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge bg-<?= $s['status'] === 'active' ? 'success' : 'secondary'; ?>-subtle text-<?= $s['status'] === 'active' ? 'success' : 'secondary'; ?>">
                                            <?= ucfirst($s['status']); ?>
                                        </span>
                                    </td>
                                    <td class="text-end">
                                        <button type="button" class="btn btn-outline-secondary btn-xs py-1 px-2"
                                                onclick='openEditSymptomModal(<?= json_encode($s); ?>)'>
                                            <i class="bi bi-pencil me-1"></i> Edit
                                        </button>
                                        <a href="<?= base_url('admin/symptoms.php?delete=' . $s['id']); ?>" class="btn btn-outline-danger btn-xs py-1 px-2 ms-1" onclick="return confirm('Hapus gejala ini?')">
                                            <i class="bi bi-trash"></i>
                                        </a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6" class="text-center py-4 text-muted">Tidak ada gejala ditemukan.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Modal Add / Edit Symptom -->
            <div class="modal fade" id="symptomModal" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                        <form method="POST" action="">
                            <input type="hidden" name="csrf_token" value="<?= generate_csrf_token(); ?>">
                            <input type="hidden" name="save_symptom" value="1">
                            <input type="hidden" name="symptom_id" id="modalSymId" value="0">

                            <div class="modal-header">
                                <h5 class="modal-title fw-bold" id="modalSymTitle">Tambah Gejala Baru</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <div class="mb-3">
                                    <label class="form-label fw-semibold">Nama / Indikasi Gejala</label>
                                    <input type="text" name="symptom_name" id="modalSymName" class="form-control" placeholder="Contoh: Lampu indikator port LAN mati / tidak menyala" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label fw-semibold">Kategori Target</label>
                                    <select name="category_id" id="modalSymCat" class="form-select" required>
                                        <option value="">-- Pilih Kategori Masalah --</option>
                                        <?php foreach ($categories as $cat): ?>
                                            <option value="<?= $cat['id']; ?>"><?= htmlspecialchars($cat['name']); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="row g-3 mb-3">
                                    <div class="col-md-6">
                                        <label class="form-label fw-semibold">Bobot Skoring (1 - 50)</label>
                                        <input type="number" name="weight" id="modalSymWeight" class="form-control" min="1" max="100" value="15" required>
                                        <div class="form-text small">Semakin tinggi bobot, semakin kuat pengaruh gejala ini pada kategori.</div>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-semibold">Status</label>
                                        <select name="status" id="modalSymStatus" class="form-select">
                                            <option value="active">Active</option>
                                            <option value="inactive">Inactive</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label fw-semibold">Deskripsi Tambahan / Panduan Singkat</label>
                                    <textarea name="symptom_description" id="modalSymDesc" class="form-control" rows="2" placeholder="Penjelasan gejala untuk membantu pengguna..."></textarea>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                                <button type="submit" class="btn btn-primary fw-semibold px-4">Simpan Gejala</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <script>
            function openCreateSymptomModal() {
                document.getElementById('modalSymTitle').textContent = 'Tambah Gejala Baru';
                document.getElementById('modalSymId').value = '0';
                document.getElementById('modalSymName').value = '';
                document.getElementById('modalSymCat').value = '';
                document.getElementById('modalSymWeight').value = '15';
                document.getElementById('modalSymStatus').value = 'active';
                document.getElementById('modalSymDesc').value = '';
            }

            function openEditSymptomModal(s) {
                document.getElementById('modalSymTitle').textContent = 'Edit Gejala';
                document.getElementById('modalSymId').value = s.id;
                document.getElementById('modalSymName').value = s.symptom_name || '';
                document.getElementById('modalSymCat').value = s.category_id || '';
                document.getElementById('modalSymWeight').value = s.weight || '15';
                document.getElementById('modalSymStatus').value = s.status || 'active';
                document.getElementById('modalSymDesc').value = s.symptom_description || '';

                const modal = new bootstrap.Modal(document.getElementById('symptomModal'));
                modal.show();
            }
            </script>

<?php require_once __DIR__ . '/includes/admin_footer.php'; ?>
