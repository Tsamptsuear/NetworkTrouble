<?php
/**
 * NetworkTrouble - Unresolved Cases Management & Continuous Learning
 */
$adminTitle = 'Triage Kasus Belum Terpecahkan';
require_once __DIR__ . '/includes/admin_header.php';
require_role(['super_admin', 'technical_admin']);

$db = get_db_connection();
$errors = [];

// Handle update case status & notes
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_case'])) {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Token CSRF tidak valid.';
    } else {
        $caseId     = (int)($_POST['case_id'] ?? 0);
        $status     = in_array($_POST['case_status'] ?? '', ['open', 'investigating', 'resolved_rule_added', 'closed']) ? $_POST['case_status'] : 'open';
        $adminNotes = sanitize($_POST['admin_notes'] ?? '');

        if ($caseId > 0 && $db) {
            $stmt = $db->prepare("UPDATE unresolved_cases SET case_status = ?, admin_notes = ?, updated_at = NOW() WHERE id = ?");
            $stmt->execute([$status, $adminNotes, $caseId]);

            log_activity($currentAdmin['id'] ?? null, 'update_unresolved_case', 'unresolved_cases', $caseId);
            set_flash('success', 'Status kasus berhasil diperbarui.');
            header('Location: ' . base_url('admin/unresolved.php'));
            exit;
        }
    }
}

// Handle Convert to Keyword
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['convert_keyword'])) {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Token CSRF tidak valid.';
    } else {
        $caseId     = (int)($_POST['case_id'] ?? 0);
        $keyword    = strtolower(trim(sanitize($_POST['keyword'] ?? '')));
        $categoryId = (int)($_POST['category_id'] ?? 0);
        $weight     = (int)($_POST['weight'] ?? 15);

        if (empty($keyword) || $categoryId <= 0) {
            $errors[] = 'Keyword dan kategori target wajib diisi.';
        } elseif ($db) {
            try {
                // Insert keyword
                $ins = $db->prepare("INSERT INTO symptom_keywords (keyword, category_id, weight, created_at) VALUES (?, ?, ?, NOW())");
                $ins->execute([$keyword, $categoryId, $weight]);

                // Update case status to resolved_rule_added
                $stmt = $db->prepare("UPDATE unresolved_cases SET case_status = 'resolved_rule_added', admin_notes = CONCAT(IFNULL(admin_notes, ''), '\n[Sistem] Dikonversi menjadi keyword: ', ?), updated_at = NOW() WHERE id = ?");
                $stmt->execute([$keyword, $caseId]);

                log_activity($currentAdmin['id'] ?? null, 'convert_unresolved_to_keyword', 'symptom_keywords', $db->lastInsertId());
                set_flash('success', 'Keyword "' . htmlspecialchars($keyword) . '" berhasil dipelajari oleh engine!');
                header('Location: ' . base_url('admin/unresolved.php'));
                exit;
            } catch (PDOException $e) {
                $errors[] = 'Database error: ' . $e->getMessage();
            }
        }
    }
}

// Fetch categories for keyword conversion modal
$categories = [];
if ($db) {
    try {
        $categories = $db->query("SELECT id, name FROM network_categories ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {}
}

// Fetch unresolved cases
$cases = [];
if ($db) {
    try {
        $stmt = $db->query("
            SELECT u.*, s.session_token, s.connection_type
            FROM unresolved_cases u
            LEFT JOIN diagnosis_sessions s ON u.session_id = s.id
            ORDER BY FIELD(u.case_status, 'open', 'investigating', 'resolved_rule_added', 'closed'), u.created_at DESC
        ");
        $cases = $stmt->fetchAll(PDO::FETCH_ASSOC);
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
                    <h1 class="h3 fw-bold text-dark mb-1">Triage Kasus Belum Terpecahkan (Continuous Learning)</h1>
                    <p class="text-secondary mb-0">Tinjau keluhan teks bebas pengguna yang belum tercover oleh rule engine untuk meningkatkan basis pengetahuan.</p>
                </div>
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

            <!-- Cases Table -->
            <div class="admin-card">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Waktu & Sesi</th>
                                <th>Keluhan / Gejala Bebas Pengguna</th>
                                <th>Kategori Prediksi</th>
                                <th>Status Kasus</th>
                                <th>Catatan Admin</th>
                                <th class="text-end">Aksi Triage</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($cases)): ?>
                                <?php foreach ($cases as $c): ?>
                                <tr>
                                    <td>
                                        <span class="font-monospace fw-bold text-dark">#<?= substr($c['session_token'] ?? 'N/A', 0, 8); ?></span>
                                        <div class="text-muted small"><?= date('d M Y, H:i', strtotime($c['created_at'])); ?></div>
                                    </td>
                                    <td style="max-width: 320px;">
                                        <div class="p-2 bg-light rounded text-dark small border">
                                            "<?= htmlspecialchars($c['user_description']); ?>"
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge bg-secondary-subtle text-secondary">
                                            <?= htmlspecialchars($c['predicted_category'] ?? 'Unclassified'); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php
                                        $statusBadge = match($c['case_status']) {
                                            'open' => 'bg-danger-subtle text-danger',
                                            'investigating' => 'bg-warning-subtle text-warning',
                                            'resolved_rule_added' => 'bg-success-subtle text-success',
                                            'closed' => 'bg-secondary-subtle text-secondary',
                                            default => 'bg-light text-dark'
                                        };
                                        ?>
                                        <span class="badge <?= $statusBadge; ?>"><?= ucfirst(str_replace('_', ' ', $c['case_status'])); ?></span>
                                    </td>
                                    <td>
                                        <small class="text-muted"><?= htmlspecialchars(mb_strimwidth($c['admin_notes'] ?? '-', 0, 50, '...')); ?></small>
                                    </td>
                                    <td class="text-end">
                                        <button type="button" class="btn btn-outline-primary btn-xs py-1 px-2"
                                                onclick='openCaseModal(<?= json_encode($c); ?>)'>
                                            <i class="bi bi-pencil-square me-1"></i> Triage
                                        </button>
                                        <button type="button" class="btn btn-outline-success btn-xs py-1 px-2 ms-1"
                                                onclick='openConvertModal(<?= json_encode($c); ?>)'>
                                            <i class="bi bi-magic me-1"></i> + Keyword
                                        </button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6" class="text-center py-5 text-muted">
                                        <i class="bi bi-shield-check text-success fs-2 d-block mb-2"></i>
                                        Tidak ada kasus belum terpecahkan yang memerlukan triage.
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Modal Update Case -->
            <div class="modal fade" id="caseModal" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                        <form method="POST" action="">
                            <input type="hidden" name="csrf_token" value="<?= generate_csrf_token(); ?>">
                            <input type="hidden" name="update_case" value="1">
                            <input type="hidden" name="case_id" id="modalCaseId" value="0">

                            <div class="modal-header">
                                <h5 class="modal-title fw-bold">Triage Status Kasus</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <div class="mb-3">
                                    <label class="form-label small fw-semibold text-secondary">Deskripsi Gejala dari Pengguna:</label>
                                    <div class="p-3 bg-light rounded text-dark fst-italic small border" id="modalCaseDesc"></div>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label fw-semibold">Status Triage Kasus</label>
                                    <select name="case_status" id="modalCaseStatus" class="form-select">
                                        <option value="open">Open (Menunggu Ditinjau)</option>
                                        <option value="investigating">Investigating (Sedang Dipelajari)</option>
                                        <option value="resolved_rule_added">Resolved (Aturan/Keyword Sudah Ditambahkan)</option>
                                        <option value="closed">Closed (Ditutup)</option>
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label fw-semibold">Catatan Admin / Diagnosis Lanjutan</label>
                                    <textarea name="admin_notes" id="modalCaseNotes" class="form-control" rows="3" placeholder="Tambahkan catatan teknis tentang kasus ini..."></textarea>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                                <button type="submit" class="btn btn-primary fw-semibold px-4">Simpan Status</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Modal Convert to Keyword -->
            <div class="modal fade" id="convertModal" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                        <form method="POST" action="">
                            <input type="hidden" name="csrf_token" value="<?= generate_csrf_token(); ?>">
                            <input type="hidden" name="convert_keyword" value="1">
                            <input type="hidden" name="case_id" id="convertCaseId" value="0">

                            <div class="modal-header bg-success-subtle">
                                <h5 class="modal-title fw-bold text-success"><i class="bi bi-magic me-2"></i>Konversi ke Keyword Mesin</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <p class="small text-secondary mb-3">Ekstrak kata kunci dari keluhan ini agar di masa depan engine otomatis mengenali kategori masalah yang sesuai.</p>
                                
                                <div class="mb-3">
                                    <label class="form-label fw-semibold">Kata Kunci Baru (Keyword)</label>
                                    <input type="text" name="keyword" id="convertKeyword" class="form-control font-monospace" placeholder="Contoh: rto, flapping, loop" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label fw-semibold">Kategori Yang Ditargetkan</label>
                                    <select name="category_id" id="convertCat" class="form-select" required>
                                        <option value="">-- Pilih Kategori Jaringan --</option>
                                        <?php foreach ($categories as $cat): ?>
                                            <option value="<?= $cat['id']; ?>"><?= htmlspecialchars($cat['name']); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label fw-semibold">Bobot Skoring Keyword</label>
                                    <input type="number" name="weight" class="form-control" value="20" min="5" max="50" required>
                                    <div class="form-text small">Semakin tinggi bobot, semakin kuat kecocokan kategori saat pengguna mengetik keyword ini.</div>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                                <button type="submit" class="btn btn-success fw-semibold px-4">Tambahkan Keyword</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <script>
            function openCaseModal(c) {
                document.getElementById('modalCaseId').value = c.id;
                document.getElementById('modalCaseDesc').textContent = '"' + (c.user_description || '') + '"';
                document.getElementById('modalCaseStatus').value = c.case_status || 'open';
                document.getElementById('modalCaseNotes').value = c.admin_notes || '';
                
                const modal = new bootstrap.Modal(document.getElementById('caseModal'));
                modal.show();
            }

            function openConvertModal(c) {
                document.getElementById('convertCaseId').value = c.id;
                // Pre-fill keyword from first few words of description
                const cleanWord = (c.user_description || '').split(' ')[0].toLowerCase().replace(/[^a-z0-9]/g, '');
                document.getElementById('convertKeyword').value = cleanWord;

                const modal = new bootstrap.Modal(document.getElementById('convertModal'));
                modal.show();
            }
            </script>

<?php require_once __DIR__ . '/includes/admin_footer.php'; ?>
