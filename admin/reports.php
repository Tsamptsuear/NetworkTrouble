<?php
/**
 * NetworkTrouble - Diagnosis Reports & Analytics
 */
$adminTitle = 'Laporan Sesi Diagnosis';
require_once __DIR__ . '/includes/admin_header.php';
require_role(['super_admin', 'technical_admin']);

$db = get_db_connection();

// Filters
$filterCat    = isset($_GET['cat']) ? (int)$_GET['cat'] : 0;
$filterStatus = sanitize($_GET['status'] ?? '');
$filterDate   = sanitize($_GET['date'] ?? '');

// Fetch Categories for filter dropdown
$categories = [];
if ($db) {
    try {
        $categories = $db->query("SELECT id, name FROM network_categories ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {}
}

// Build query
$whereClauses = [];
$params = [];

if ($filterCat > 0) {
    $whereClauses[] = "r.category_id = ?";
    $params[] = $filterCat;
}
if (!empty($filterStatus) && in_array($filterStatus, ['resolved', 'unresolved', 'pending'])) {
    $whereClauses[] = "r.status = ?";
    $params[] = $filterStatus;
}
if (!empty($filterDate)) {
    $whereClauses[] = "DATE(s.created_at) = ?";
    $params[] = $filterDate;
}

$whereSql = !empty($whereClauses) ? "WHERE " . implode(" AND ", $whereClauses) : "";

$reports = [];
$totalRows = 0;
$resolvedCount = 0;
$avgConfidence = 0;

if ($db) {
    try {
        $sql = "
            SELECT s.id as session_id, s.session_token, s.connection_type, s.classification_source, s.created_at, s.input_data_json,
                   r.id as result_id, r.confidence_score, r.status as resolution_status, r.severity, r.osi_layer, r.possible_causes_json,
                   c.name as category_name, c.icon as category_icon
            FROM diagnosis_sessions s
            LEFT JOIN diagnosis_results r ON r.session_id = s.id
            LEFT JOIN network_categories c ON r.category_id = c.id
            $whereSql
            ORDER BY s.created_at DESC
            LIMIT 100
        ";
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $reports = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $totalRows = count($reports);
        if ($totalRows > 0) {
            $totalScore = 0;
            foreach ($reports as $row) {
                $totalScore += (int)($row['confidence_score'] ?? 0);
                if (($row['resolution_status'] ?? '') === 'resolved') {
                    $resolvedCount++;
                }
            }
            $avgConfidence = round($totalScore / $totalRows);
        }
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
                    <h1 class="h3 fw-bold text-dark mb-1">Laporan & Riwayat Sesi Diagnosis</h1>
                    <p class="text-secondary mb-0">Audit sesi pengguna, akurasi klasifikasi jaringan, dan rasio penyelesaian masalah.</p>
                </div>
                <button type="button" class="btn btn-outline-secondary btn-sm rounded-pill px-3" onclick="window.print()">
                    <i class="bi bi-printer me-1"></i> Cetak / Export PDF
                </button>
            </div>

            <!-- Summary KPI Badges -->
            <div class="row g-3 mb-4">
                <div class="col-sm-4">
                    <div class="admin-stat-card">
                        <div>
                            <div class="text-secondary small fw-semibold">Sesi Ditampilkan</div>
                            <h4 class="fw-bold mb-0 text-dark"><?= $totalRows; ?> Sesi</h4>
                        </div>
                        <div class="admin-stat-icon bg-primary-subtle text-primary">
                            <i class="bi bi-file-earmark-bar-graph"></i>
                        </div>
                    </div>
                </div>
                <div class="col-sm-4">
                    <div class="admin-stat-card">
                        <div>
                            <div class="text-secondary small fw-semibold">Rata-rata Akurasi (Confidence)</div>
                            <h4 class="fw-bold mb-0 text-dark"><?= $avgConfidence; ?>%</h4>
                        </div>
                        <div class="admin-stat-icon bg-success-subtle text-success">
                            <i class="bi bi-speedometer2"></i>
                        </div>
                    </div>
                </div>
                <div class="col-sm-4">
                    <div class="admin-stat-card">
                        <div>
                            <div class="text-secondary small fw-semibold">Terselesaikan (Resolved)</div>
                            <h4 class="fw-bold mb-0 text-dark"><?= $totalRows > 0 ? round(($resolvedCount / $totalRows) * 100) : 0; ?>%</h4>
                        </div>
                        <div class="admin-stat-icon bg-info-subtle text-info">
                            <i class="bi bi-check2-circle"></i>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Filter Card -->
            <div class="admin-card p-3 mb-4">
                <form method="GET" action="" class="row g-2 align-items-center">
                    <div class="col-md-4">
                        <label class="form-label small fw-semibold text-secondary mb-1">Kategori Masalah:</label>
                        <select name="cat" class="form-select form-select-sm" onchange="this.form.submit()">
                            <option value="0">-- Semua Kategori --</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?= $cat['id']; ?>" <?= $filterCat === (int)$cat['id'] ? 'selected' : ''; ?>>
                                    <?= htmlspecialchars($cat['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small fw-semibold text-secondary mb-1">Status Resolusi:</label>
                        <select name="status" class="form-select form-select-sm" onchange="this.form.submit()">
                            <option value="">-- Semua Status --</option>
                            <option value="resolved" <?= $filterStatus === 'resolved' ? 'selected' : ''; ?>>Selesai (Resolved)</option>
                            <option value="unresolved" <?= $filterStatus === 'unresolved' ? 'selected' : ''; ?>>Belum Selesai (Unresolved)</option>
                            <option value="pending" <?= $filterStatus === 'pending' ? 'selected' : ''; ?>>Pending Feedback</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small fw-semibold text-secondary mb-1">Tanggal Sesi:</label>
                        <input type="date" name="date" class="form-control form-control-sm" value="<?= htmlspecialchars($filterDate); ?>" onchange="this.form.submit()">
                    </div>
                    <?php if ($filterCat > 0 || !empty($filterStatus) || !empty($filterDate)): ?>
                    <div class="col-md-2 mt-auto">
                        <a href="<?= base_url('admin/reports.php'); ?>" class="btn btn-light btn-sm w-100">Reset Filter</a>
                    </div>
                    <?php endif; ?>
                </form>
            </div>

            <!-- Reports Table -->
            <div class="admin-card">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Token Sesi</th>
                                <th>Waktu</th>
                                <th>Koneksi</th>
                                <th>Kategori Klasifikasi</th>
                                <th>Keyakinan</th>
                                <th>Metode</th>
                                <th>Resolusi</th>
                                <th class="text-end">Rincian</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($reports)): ?>
                                <?php foreach ($reports as $r): ?>
                                <tr>
                                    <td>
                                        <span class="fw-bold font-monospace text-dark">#<?= substr($r['session_token'] ?? 'N/A', 0, 10); ?></span>
                                    </td>
                                    <td>
                                        <small class="text-secondary"><?= date('d M Y, H:i', strtotime($r['created_at'])); ?></small>
                                    </td>
                                    <td>
                                        <span class="badge bg-light text-dark border"><?= ucfirst(htmlspecialchars($r['connection_type'] ?? '-')); ?></span>
                                    </td>
                                    <td>
                                        <span class="badge bg-primary-subtle text-primary border border-primary-subtle">
                                            <i class="bi <?= htmlspecialchars($r['category_icon'] ?? 'bi-hdd-network'); ?> me-1"></i>
                                            <?= htmlspecialchars($r['category_name'] ?? 'Belum Ditentukan'); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="fw-bold text-<?= (int)($r['confidence_score'] ?? 0) >= 80 ? 'success' : 'primary'; ?>">
                                            <?= round((float)($r['confidence_score'] ?? 0)); ?>%
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge bg-secondary-subtle text-secondary" style="font-size: 0.72rem;">
                                            <?= htmlspecialchars($r['classification_source'] ?? 'Rule-Based'); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if (($r['resolution_status'] ?? '') === 'resolved'): ?>
                                            <span class="badge bg-success-subtle text-success"><i class="bi bi-check-circle me-1"></i>Resolved</span>
                                        <?php elseif (($r['resolution_status'] ?? '') === 'unresolved'): ?>
                                            <span class="badge bg-danger-subtle text-danger"><i class="bi bi-x-circle me-1"></i>Unresolved</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary-subtle text-secondary">Pending</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-end">
                                        <button type="button" class="btn btn-outline-primary btn-xs py-1 px-2"
                                                onclick='viewReportModal(<?= htmlspecialchars(json_encode($r), ENT_QUOTES, 'UTF-8'); ?>)'>
                                            <i class="bi bi-eye me-1"></i> Detail
                                        </button>
                                        <a href="<?= base_url('result.php?session=' . urlencode($r['session_token'])); ?>" target="_blank" class="btn btn-outline-secondary btn-xs py-1 px-2 ms-1" title="Lihat Hasil Publik">
                                            <i class="bi bi-box-arrow-up-right"></i>
                                        </a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="8" class="text-center py-5 text-muted">Tidak ada riwayat laporan yang cocok dengan kriteria.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Detail Modal -->
            <div class="modal fade" id="reportDetailModal" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered modal-lg">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title fw-bold" id="detailModalToken">Detail Sesi Diagnosis</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <div class="row g-3 mb-3">
                                <div class="col-md-6">
                                    <label class="small text-secondary fw-semibold">Kategori Terdiagnosa:</label>
                                    <div class="h6 text-primary fw-bold mb-0" id="detailModalCat">-</div>
                                </div>
                                <div class="col-md-3">
                                    <label class="small text-secondary fw-semibold">Lapisan OSI:</label>
                                    <div class="h6 text-dark fw-bold mb-0" id="detailModalOsi">-</div>
                                </div>
                                <div class="col-md-3">
                                    <label class="small text-secondary fw-semibold">Confidence Score:</label>
                                    <div class="h6 text-success fw-bold mb-0" id="detailModalScore">-</div>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="small text-secondary fw-semibold">Data Input Gejala Pengguna (JSON):</label>
                                <pre class="bg-light p-3 rounded font-monospace small" id="detailModalInput" style="max-height: 180px; overflow-y: auto;"></pre>
                            </div>
                            <div class="mb-3">
                                <label class="small text-secondary fw-semibold">Kemungkinan Penyebab & Analisis:</label>
                                <div class="bg-light p-3 rounded small" id="detailModalCauses"></div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                        </div>
                    </div>
                </div>
            </div>

            <script>
            function viewReportModal(r) {
                document.getElementById('detailModalToken').textContent = 'Sesi #' + (r.session_token || 'N/A');
                document.getElementById('detailModalCat').textContent = r.category_name || 'Tidak Diketahui';
                document.getElementById('detailModalOsi').textContent = r.osi_layer || '-';
                document.getElementById('detailModalScore').textContent = (r.confidence_score || 0) + '%';
                
                try {
                    const parsedInput = JSON.parse(r.input_data_json || '{}');
                    document.getElementById('detailModalInput').textContent = JSON.stringify(parsedInput, null, 2);
                } catch(e) {
                    document.getElementById('detailModalInput').textContent = r.input_data_json || '-';
                }

                try {
                    const parsedCauses = JSON.parse(r.possible_causes_json || '[]');
                    if (Array.isArray(parsedCauses) && parsedCauses.length > 0) {
                        let html = '<ul class="mb-0 ps-3">';
                        parsedCauses.forEach(c => { html += '<li>' + (typeof c === 'string' ? c : (c.cause || JSON.stringify(c))) + '</li>'; });
                        html += '</ul>';
                        document.getElementById('detailModalCauses').innerHTML = html;
                    } else {
                        document.getElementById('detailModalCauses').textContent = 'Tidak ada catatan penyebab.';
                    }
                } catch(e) {
                    document.getElementById('detailModalCauses').textContent = r.possible_causes_json || '-';
                }

                const modal = new bootstrap.Modal(document.getElementById('reportDetailModal'));
                modal.show();
            }
            </script>

<?php require_once __DIR__ . '/includes/admin_footer.php'; ?>
