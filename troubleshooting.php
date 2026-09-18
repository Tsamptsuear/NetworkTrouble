<?php
/**
 * NetworkTrouble - Troubleshooting Guide Directory
 */
require_once __DIR__ . '/config/app.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/classification_engine.php';

$pageTitle = "Panduan Langkah Troubleshooting Jaringan";
$pageDesc = "Daftar prosedur baku penanganan masalah jaringan berdasarkan lapisan OSI dan kategori anomali.";

$categories = NetworkClassificationEngine::getCategories();
$db = get_db();

// Load steps and commands grouped by category
$guideData = [];
foreach ($categories as $catId => $cat) {
    $steps = NetworkClassificationEngine::getTroubleshootingSteps($catId);
    $cmds = NetworkClassificationEngine::getCommandsForCategory($catId);
    $guideData[$catId] = [
        'info'  => $cat,
        'steps' => $steps,
        'cmds'  => $cmds
    ];
}

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';
?>

<div class="py-5 bg-light border-bottom">
    <div class="container">
        <div class="row justify-content-center text-center">
            <div class="col-lg-8">
                <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-3 py-1 mb-2 rounded-pill font-monospace">
                    <i class="bi bi-tools me-1"></i> Standar Operasional Prosedur (SOP)
                </span>
                <h1 class="h2 fw-bold text-dark mb-2">Troubleshooting Guide Directory</h1>
                <p class="text-secondary mb-0">
                    Panduan terstruktur langkah demi langkah untuk setiap kategori gangguan jaringan beserta baris perintah terminal yang siap dieksekusi.
                </p>
            </div>
        </div>
    </div>
</div>

<div class="py-5">
    <div class="container">
        <div class="row g-4">
            <!-- Sidebar Category Navigation -->
            <div class="col-lg-3">
                <div class="nt-card p-3 sticky-top" style="top: 90px; z-index: 10;">
                    <h6 class="fw-bold text-dark px-2 mb-3 text-uppercase small letter-spacing-1">
                        Daftar Kategori OSI
                    </h6>
                    <div class="nav flex-column nav-pills gap-1" id="v-pills-tab" role="tablist" aria-orientation="vertical">
                        <?php $first = true; foreach ($categories as $catId => $cat): ?>
                        <button class="nav-link text-start py-2 px-3 small <?= $first ? 'active' : ''; ?>" id="tab-btn-<?= $catId; ?>" data-bs-toggle="pill" data-bs-target="#tab-pane-<?= $catId; ?>" type="button" role="tab">
                            <i class="bi <?= htmlspecialchars($cat['icon']); ?> me-2"></i>
                            <?= htmlspecialchars($cat['name']); ?>
                        </button>
                        <?php $first = false; endforeach; ?>
                    </div>

                    <div class="mt-4 pt-3 border-top px-2">
                        <small class="text-muted d-block mb-2">Ingin analisis otomatis?</small>
                        <a href="<?= base_url('diagnose.php'); ?>" class="btn btn-nt-primary btn-sm w-100">
                            <i class="bi bi-cpu me-1"></i> Jalankan Wizard
                        </a>
                    </div>
                </div>
            </div>

            <!-- Main Content Area: Tab Panes -->
            <div class="col-lg-9">
                <div class="tab-content" id="v-pills-tabContent">
                    <?php $first = true; foreach ($guideData as $catId => $data): ?>
                    <div class="tab-pane fade <?= $first ? 'show active' : ''; ?>" id="tab-pane-<?= $catId; ?>" role="tabpanel">
                        
                        <!-- Category Header Card -->
                        <div class="nt-card p-4 mb-4">
                            <div class="d-flex align-items-center justify-content-between mb-2">
                                <span class="badge bg-primary font-monospace"><?= htmlspecialchars($data['info']['osi_layer']); ?></span>
                                <?= get_severity_badge($data['info']['severity_default']); ?>
                            </div>
                            <h3 class="h4 fw-bold text-dark mb-2">
                                <i class="bi <?= htmlspecialchars($data['info']['icon']); ?> text-primary me-2"></i>
                                <?= htmlspecialchars($data['info']['name']); ?>
                            </h3>
                            <p class="text-secondary small mb-0">
                                <?= htmlspecialchars($data['info']['description']); ?>
                            </p>
                        </div>

                        <!-- Roadmap Steps -->
                        <div class="nt-card p-4 mb-4">
                            <h5 class="fw-bold text-dark mb-4">
                                <i class="bi bi-list-check text-primary me-2"></i> Urutan Langkah Penanganan (Roadmap)
                            </h5>

                            <?php if (!empty($data['steps'])): ?>
                                <div class="roadmap-timeline">
                                    <?php foreach ($data['steps'] as $idx => $st): ?>
                                    <div class="roadmap-item">
                                        <div class="roadmap-badge"><?= $idx + 1; ?></div>
                                        <div class="card p-3 border shadow-none bg-light">
                                            <h6 class="fw-bold text-dark mb-1"><?= htmlspecialchars($st['title']); ?></h6>
                                            <p class="text-secondary small mb-2"><?= htmlspecialchars($st['description']); ?></p>

                                            <?php if (!empty($st['command'])): ?>
                                            <div class="command-line mb-2">
                                                <code><?= htmlspecialchars($st['command']); ?></code>
                                                <button type="button" class="copy-btn" data-copy="<?= htmlspecialchars($st['command']); ?>" title="Salin Perintah">
                                                    <i class="bi bi-clipboard"></i>
                                                </button>
                                            </div>
                                            <?php endif; ?>

                                            <?php if (!empty($st['verification_question'])): ?>
                                            <div class="small text-muted fst-italic">
                                                <i class="bi bi-question-circle me-1 text-primary"></i> <strong>Verifikasi:</strong> <?= htmlspecialchars($st['verification_question']); ?>
                                            </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php else: ?>
                                <div class="alert alert-secondary small mb-0">Belum ada langkah khusus yang ditambahkan untuk kategori ini.</div>
                            <?php endif; ?>
                        </div>

                        <!-- Relevant CLI Commands -->
                        <?php if (!empty($data['cmds'])): ?>
                        <div class="nt-card p-4">
                            <h5 class="fw-bold text-dark mb-3">
                                <i class="bi bi-terminal text-primary me-2"></i> Perintah Terkait (CLI Tools)
                            </h5>
                            <div class="d-flex flex-column gap-3">
                                <?php foreach ($data['cmds'] as $cmd): ?>
                                <div class="border rounded-3 p-3 bg-light">
                                    <div class="d-flex justify-content-between align-items-center mb-1">
                                        <span class="fw-bold small text-dark"><?= htmlspecialchars($cmd['name']); ?></span>
                                        <span class="badge bg-dark-subtle text-dark text-uppercase" style="font-size: 0.7rem;"><?= htmlspecialchars($cmd['platform']); ?></span>
                                    </div>
                                    <div class="command-line my-2">
                                        <code><?= htmlspecialchars($cmd['command']); ?></code>
                                        <button class="copy-btn" data-copy="<?= htmlspecialchars($cmd['command']); ?>"><i class="bi bi-clipboard"></i></button>
                                    </div>
                                    <p class="small text-secondary mb-0"><?= htmlspecialchars($cmd['description']); ?></p>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <?php endif; ?>

                    </div>
                    <?php $first = false; endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
require_once __DIR__ . '/includes/footer.php';
?>
