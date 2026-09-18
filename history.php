<?php
/**
 * NetworkTrouble - User Diagnosis History
 */
require_once __DIR__ . '/config/app.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

$pageTitle = "Riwayat Diagnosis Jaringan";
$pageDesc = "Lihat daftar analisis gangguan jaringan sebelumnya yang pernah Anda lakukan.";

$db = get_db();
$userToken = get_anonymous_session_token();
$historyList = [];

if ($db) {
    try {
        $stmt = $db->prepare("SELECT r.*, s.connection_type, s.classification_source, s.created_at as session_date, c.name as category_name, c.icon FROM diagnosis_results r JOIN diagnosis_sessions s ON r.session_id = s.id JOIN network_categories c ON r.category_id = c.id WHERE s.session_token = ? ORDER BY r.created_at DESC LIMIT 30");
        $stmt->execute([$userToken]);
        $historyList = $stmt->fetchAll();
    } catch (Exception $e) {}
}

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';
?>

<div class="py-5 bg-light border-bottom">
    <div class="container">
        <div class="row justify-content-center text-center">
            <div class="col-lg-8">
                <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-3 py-1 mb-2 rounded-pill font-monospace">
                    <i class="bi bi-clock-history me-1"></i> Sesi Diagnostik
                </span>
                <h1 class="h2 fw-bold text-dark mb-2">Riwayat Diagnosis Anda</h1>
                <p class="text-secondary mb-0">
                    Daftar penelusuran gangguan jaringan yang pernah Anda jalankan pada perangkat ini. Anda dapat meninjau kembali langkah perbaikan atau memperbarui status pemecahan masalah.
                </p>
            </div>
        </div>
    </div>
</div>

<div class="py-5">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-10">
                <?php if (!empty($historyList)): ?>
                    <div class="nt-card overflow-hidden">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr class="small text-uppercase text-secondary">
                                        <th class="ps-4">Waktu Diagnosis</th>
                                        <th>Kategori Masalah</th>
                                        <th>OSI Layer</th>
                                        <th>Confidence</th>
                                        <th>Status Pemecahan</th>
                                        <th>Source</th>
                                        <th class="text-end pe-4">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($historyList as $item): ?>
                                    <tr>
                                        <td class="ps-4 text-secondary small">
                                            <?= format_date($item['session_date']); ?>
                                        </td>
                                        <td>
                                            <div class="d-flex align-items-center gap-2">
                                                <i class="bi <?= htmlspecialchars($item['icon'] ?? 'bi-hdd-network'); ?> text-primary"></i>
                                                <span class="fw-semibold text-dark"><?= htmlspecialchars($item['category_name']); ?></span>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="badge bg-light text-dark border font-monospace small">
                                                <?= htmlspecialchars($item['osi_layer']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?= get_confidence_badge((int)$item['confidence_score']); ?>
                                        </td>
                                        <td>
                                            <?php if ($item['status'] === 'resolved'): ?>
                                                <span class="badge bg-success-subtle text-success border border-success-subtle">
                                                    <i class="bi bi-check2-circle me-1"></i>Resolved
                                                </span>
                                            <?php elseif ($item['status'] === 'unresolved'): ?>
                                                <span class="badge bg-danger-subtle text-danger border border-danger-subtle">
                                                    <i class="bi bi-x-circle me-1"></i>Unresolved
                                                </span>
                                            <?php else: ?>
                                                <span class="badge bg-secondary-subtle text-secondary border">
                                                    <i class="bi bi-clock me-1"></i>Pending
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?= get_source_badge($item['classification_source']); ?>
                                        </td>
                                        <td class="text-end pe-4">
                                            <a href="<?= base_url('result.php?id=' . $item['id']); ?>" class="btn btn-outline-primary btn-sm">
                                                <i class="bi bi-arrow-up-right me-1"></i> Buka Hasil
                                            </a>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="nt-card p-5 text-center">
                        <div class="nt-icon-box nt-icon-primary mx-auto mb-3" style="width: 60px; height: 60px; font-size: 1.8rem;">
                            <i class="bi bi-inbox"></i>
                        </div>
                        <h4 class="fw-bold text-dark mb-2">Belum Ada Riwayat Diagnosis</h4>
                        <p class="text-secondary small mb-4 mx-auto" style="max-width: 500px;">
                            Anda belum menjalankan diagnosis gangguan jaringan pada sesi browser ini. Silakan mulai diagnosis untuk meneliti kendala koneksi Anda.
                        </p>
                        <a href="<?= base_url('diagnose.php'); ?>" class="btn btn-nt-primary">
                            <i class="bi bi-play-circle-fill me-1"></i> Mulai Diagnosis Sekarang
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php
require_once __DIR__ . '/includes/footer.php';
?>
