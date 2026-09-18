<?php
/**
 * NetworkTrouble - Diagnostic Result Page
 * Displays classification summary, OSI layer mapping, confidence score, roadmap, command toolkit, and resolution prompt.
 */

require_once __DIR__ . '/config/app.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/classification_engine.php';

$pageTitle = "Hasil Diagnosis Jaringan";
$pageDesc = "Laporan analisis klasifikasi gangguan jaringan komputer dan rekomendasi tindakan.";

$db = get_db();
$sessionId = null;
$resultRecordId = null;

// Handle Form Submission or Direct View by Session ID
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Process Diagnosis Request
    $connType = sanitize($_POST['connection_type'] ?? 'unknown');
    $symptoms = $_POST['symptoms'] ?? [];
    $adaptive = $_POST['adaptive_answers'] ?? [];
    $customText = sanitize($_POST['custom_text'] ?? '');
    $source = sanitize($_POST['classification_source'] ?? 'Rule-Based');

    $diagnosis = NetworkClassificationEngine::diagnose([
        'connection_type'       => $connType,
        'symptoms'              => $symptoms,
        'adaptive_answers'      => $adaptive,
        'custom_text'           => $customText,
        'classification_source' => $source
    ]);

    // Save session in DB
    $userToken = get_anonymous_session_token();
    if ($db) {
        try {
            $inputJson = json_encode([
                'connection_type'  => $connType,
                'symptoms'         => $symptoms,
                'adaptive_answers' => $adaptive,
                'custom_text'      => $customText
            ]);

            $stmt = $db->prepare("INSERT INTO diagnosis_sessions (session_token, connection_type, input_data_json, classification_source, user_ip, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
            $stmt->execute([$userToken, $connType, $inputJson, $diagnosis['classification_source'], $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1']);
            $sessionId = (int)$db->lastInsertId();

            $possibleJson = json_encode(array_column($diagnosis['possible_causes'], 'cause'));
            $resStmt = $db->prepare("INSERT INTO diagnosis_results (session_id, category_id, osi_layer, confidence_score, severity, possible_causes_json, status, created_at) VALUES (?, ?, ?, ?, ?, ?, 'pending', NOW())");
            $resStmt->execute([
                $sessionId,
                $diagnosis['category_id'],
                $diagnosis['osi_layer'],
                $diagnosis['confidence_score'],
                $diagnosis['severity'],
                $possibleJson
            ]);
            $resultRecordId = (int)$db->lastInsertId();

            // Store in session for quick back-navigation
            $_SESSION['last_diagnosis_id'] = $resultRecordId;
            $_SESSION['last_session_id'] = $sessionId;

            // If confidence is lower than 40 or category is Unknown and user entered custom text, log as unresolved case automatically
            if ($diagnosis['category_id'] === 9 && !empty($customText)) {
                $unresStmt = $db->prepare("INSERT INTO unresolved_cases (session_id, user_description, predicted_category, case_status, created_at) VALUES (?, ?, ?, 'open', NOW())");
                $unresStmt->execute([$sessionId, $customText, 'Unknown Network Issue']);
            }
        } catch (Exception $e) {}
    }
} elseif (isset($_GET['id'])) {
    // View past diagnosis from history
    $requestedId = (int)$_GET['id'];
    if ($db) {
        try {
            $stmt = $db->prepare("SELECT r.*, s.connection_type, s.input_data_json, s.classification_source, c.name as category_name, c.slug as category_slug, c.icon, c.description FROM diagnosis_results r JOIN diagnosis_sessions s ON r.session_id = s.id JOIN network_categories c ON r.category_id = c.id WHERE r.id = ?");
            $stmt->execute([$requestedId]);
            $row = $stmt->fetch();
            if ($row) {
                $resultRecordId = (int)$row['id'];
                $sessionId = (int)$row['session_id'];
                $inputData = json_decode($row['input_data_json'], true) ?: [];

                $diagnosis = NetworkClassificationEngine::diagnose([
                    'connection_type'       => $row['connection_type'],
                    'symptoms'              => $inputData['symptoms'] ?? [],
                    'adaptive_answers'      => $inputData['adaptive_answers'] ?? [],
                    'custom_text'           => $inputData['custom_text'] ?? '',
                    'classification_source' => $row['classification_source']
                ]);
            }
        } catch (Exception $e) {}
    }
}

// Fallback if accessed directly without submission
if (empty($diagnosis)) {
    header('Location: ' . base_url('diagnose.php'));
    exit;
}

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';
?>

<div class="py-4 bg-light border-bottom">
    <div class="container">
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
            <div>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb mb-1 small">
                        <li class="breadcrumb-item"><a href="<?= base_url(); ?>">Home</a></li>
                        <li class="breadcrumb-item"><a href="<?= base_url('diagnose.php'); ?>">Diagnose</a></li>
                        <li class="breadcrumb-item active" aria-current="page">Hasil Diagnosis</li>
                    </ol>
                </nav>
                <h1 class="h3 fw-bold text-dark mb-0">Laporan Diagnosis Jaringan Komputer</h1>
            </div>
            <div class="d-flex align-items-center gap-2">
                <a href="<?= base_url('diagnose.php'); ?>" class="btn btn-outline-secondary btn-sm">
                    <i class="bi bi-arrow-repeat me-1"></i> Diagnosis Ulang
                </a>
                <button onclick="window.print()" class="btn btn-outline-dark btn-sm">
                    <i class="bi bi-printer me-1"></i> Cetak Laporan
                </button>
            </div>
        </div>
    </div>
</div>

<div class="py-5">
    <div class="container">
        <div class="row g-4">

            <!-- LEFT COLUMN: Diagnosis Summary, Causes, Roadmap -->
            <div class="col-lg-8">

                <!-- 1. DIAGNOSIS SUMMARY CARD -->
                <div class="nt-card p-4 p-md-5 mb-4 position-relative overflow-hidden">
                    <div class="position-absolute top-0 end-0 p-3">
                        <?= get_source_badge($diagnosis['classification_source']); ?>
                    </div>

                    <div class="d-flex align-items-center gap-3 mb-3">
                        <div class="nt-icon-box nt-icon-primary fs-2">
                            <i class="bi <?= htmlspecialchars($diagnosis['icon'] ?? 'bi-hdd-network'); ?>"></i>
                        </div>
                        <div>
                            <span class="badge bg-primary text-white mb-1 font-monospace">
                                <?= htmlspecialchars($diagnosis['osi_layer']); ?>
                            </span>
                            <h2 class="h4 fw-bold text-dark mb-0">
                                <?= htmlspecialchars($diagnosis['category_name']); ?>
                            </h2>
                        </div>
                    </div>

                    <p class="text-secondary mb-4">
                        <?= htmlspecialchars($diagnosis['description']); ?>
                    </p>

                    <!-- Confidence Meter & Severity -->
                    <div class="p-3 bg-light rounded-3 border mb-4">
                        <div class="row g-3 align-items-center">
                            <div class="col-md-7">
                                <div class="d-flex justify-content-between align-items-center mb-1 small">
                                    <span class="fw-semibold text-dark">Confidence Level:</span>
                                    <span><?= get_confidence_badge($diagnosis['confidence_score']); ?></span>
                                </div>
                                <div class="progress" style="height: 10px;">
                                    <?php
                                    $barClass = 'bg-success';
                                    if ($diagnosis['confidence_score'] < 40) $barClass = 'bg-secondary';
                                    elseif ($diagnosis['confidence_score'] < 60) $barClass = 'bg-warning';
                                    elseif ($diagnosis['confidence_score'] < 80) $barClass = 'bg-primary';
                                    ?>
                                    <div class="progress-bar <?= $barClass; ?>" role="progressbar" style="width: <?= $diagnosis['confidence_score']; ?>%;" aria-valuenow="<?= $diagnosis['confidence_score']; ?>" aria-valuemin="0" aria-valuemax="100"></div>
                                </div>
                                <?php if ($diagnosis['confidence_score'] < 40): ?>
                                    <small class="text-danger mt-1 d-block">
                                        <i class="bi bi-info-circle me-1"></i>Gejala belum cukup spesifik untuk menentukan diagnosis definitif. Jalankan isolasi dasar di bawah.
                                    </small>
                                <?php endif; ?>
                            </div>

                            <div class="col-md-5 border-start-md ps-md-4">
                                <div class="small fw-semibold text-dark mb-1">Tingkat Keparahan (Severity):</div>
                                <div><?= get_severity_badge($diagnosis['severity']); ?></div>
                            </div>
                        </div>
                    </div>

                    <!-- Matched Symptoms List -->
                    <?php if (!empty($diagnosis['matched_symptoms'])): ?>
                    <div>
                        <h6 class="fw-bold text-dark mb-2 small text-uppercase letter-spacing-1">
                            <i class="bi bi-check2-square text-success me-1"></i> Gejala & Indikator yang Terdeteksi:
                        </h6>
                        <ul class="list-unstyled mb-0 small">
                            <?php foreach ($diagnosis['matched_symptoms'] as $sym): ?>
                                <li class="d-flex align-items-start gap-2 mb-1 text-secondary">
                                    <i class="bi bi-arrow-right-circle text-primary mt-1"></i>
                                    <span><?= htmlspecialchars($sym); ?></span>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                    <?php endif; ?>

                    <!-- Triggered Classification Rules -->
                    <?php if (!empty($diagnosis['triggered_rules'])): ?>
                    <div class="mt-3 pt-3 border-top">
                        <h6 class="fw-bold text-dark mb-2 small text-uppercase letter-spacing-1">
                            <i class="bi bi-gear-wide-connected text-primary me-1"></i> Aturan Klasifikasi yang Terpicu:
                        </h6>
                        <div class="d-flex flex-wrap gap-2">
                            <?php foreach ($diagnosis['triggered_rules'] as $ruleName): ?>
                                <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-2 py-1 small font-monospace">
                                    <i class="bi bi-check me-1"></i><?= htmlspecialchars($ruleName); ?>
                                </span>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- 2. POSSIBLE CAUSES WITH PROBABILITY -->
                <div class="nt-card p-4 mb-4">
                    <h5 class="fw-bold text-dark mb-3">
                        <i class="bi bi-pie-chart text-primary me-2"></i> Kemungkinan Penyebab Utama
                    </h5>
                    <div class="d-flex flex-column gap-3">
                        <?php foreach ($diagnosis['possible_causes'] as $cause): ?>
                        <div>
                            <div class="d-flex justify-content-between align-items-center mb-1 small">
                                <span class="fw-semibold text-dark"><?= htmlspecialchars($cause['cause']); ?></span>
                                <span class="badge bg-light text-dark border"><?= $cause['prob']; ?>% Probabilitas</span>
                            </div>
                            <div class="progress" style="height: 6px;">
                                <div class="progress-bar bg-primary-light" style="width: <?= $cause['prob']; ?>%;"></div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- 3. STEP-BY-STEP TROUBLESHOOTING ROADMAP -->
                <div class="nt-card p-4 p-md-5 mb-4">
                    <div class="d-flex align-items-center justify-content-between mb-4">
                        <h5 class="fw-bold text-dark mb-0">
                            <i class="bi bi-signpost-2 text-primary me-2"></i> Troubleshooting Roadmap
                        </h5>
                        <span class="badge bg-light text-secondary border">Panduan Bertahap</span>
                    </div>

                    <div class="roadmap-timeline">
                        <?php if (!empty($diagnosis['troubleshooting_steps'])): ?>
                            <?php foreach ($diagnosis['troubleshooting_steps'] as $idx => $step): ?>
                            <div class="roadmap-item">
                                <div class="roadmap-badge"><?= $idx + 1; ?></div>
                                <div class="card p-3 border shadow-none bg-light transition-all">
                                    <div class="d-flex align-items-center justify-content-between mb-2">
                                        <h6 class="fw-bold text-dark mb-0"><?= htmlspecialchars($step['title']); ?></h6>
                                        <div class="form-check mb-0">
                                            <input class="form-check-input" type="checkbox" id="step_chk_<?= $idx; ?>" onchange="this.closest('.card').classList.toggle('border-success', this.checked); this.closest('.card').classList.toggle('bg-success-subtle', this.checked);">
                                            <label class="form-check-label small text-muted cursor-pointer" for="step_chk_<?= $idx; ?>">Telah dicoba</label>
                                        </div>
                                    </div>
                                    <p class="text-secondary small mb-2"><?= htmlspecialchars($step['description']); ?></p>

                                    <?php if (!empty($step['command'])): ?>
                                    <div class="command-line mb-2">
                                        <code><?= htmlspecialchars($step['command']); ?></code>
                                        <button type="button" class="copy-btn" data-copy="<?= htmlspecialchars($step['command']); ?>" title="Salin Perintah">
                                            <i class="bi bi-clipboard"></i>
                                        </button>
                                    </div>
                                    <?php endif; ?>

                                    <?php if (!empty($step['verification_question'])): ?>
                                    <div class="small text-muted fst-italic">
                                        <i class="bi bi-question-circle me-1 text-primary"></i> <strong>Verifikasi:</strong> <?= htmlspecialchars($step['verification_question']); ?>
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="roadmap-item">
                                <div class="roadmap-badge">1</div>
                                <div class="card p-3 border">
                                    <h6 class="fw-bold text-dark mb-1">Periksa Koneksi Fisik & Restart Perangkat</h6>
                                    <p class="text-secondary small mb-0">Cabut dan pasang kembali kabel atau matikan dan hidupkan modem/router.</p>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- 4. VERIFICATION PROMPT: "Apakah Masalah Sudah Terselesaikan?" -->
                <div class="nt-card p-4 p-md-5 border-primary-subtle bg-white" id="verificationSection">
                    <div class="text-center">
                        <span class="nt-icon-box nt-icon-success mx-auto mb-3">
                            <i class="bi bi-patch-question-fill fs-3"></i>
                        </span>
                        <h4 class="fw-bold text-dark mb-2">Apakah masalah jaringan Anda sudah terselesaikan?</h4>
                        <p class="text-secondary small mb-4">
                            Konfirmasi hasil diagnosis untuk membantu meningkatkan akurasi sistem inferensi NetworkTrouble.
                        </p>

                        <div class="d-flex flex-wrap justify-content-center gap-3" id="verificationButtonGroup">
                            <button type="button" class="btn btn-success px-4" onclick="recordResolution('resolved', <?= (int)($resultRecordId ?? 0); ?>)">
                                <i class="bi bi-check-circle-fill me-1"></i> Ya, Masalah Terselesaikan
                            </button>
                            <button type="button" class="btn btn-danger px-4" onclick="recordResolution('unresolved', <?= (int)($resultRecordId ?? 0); ?>)">
                                <i class="bi bi-x-circle-fill me-1"></i> Belum Terselesaikan
                            </button>
                            <button type="button" class="btn btn-outline-secondary px-4" onclick="recordResolution('unsure', <?= (int)($resultRecordId ?? 0); ?>)">
                                <i class="bi bi-question-circle me-1"></i> Tidak Yakin / Masih Menguji
                            </button>
                        </div>

                        <div id="resolutionFeedback" class="mt-4 d-none"></div>
                    </div>
                </div>

            </div>

            <!-- RIGHT COLUMN: Command Toolkit, Escalation, Meta Info -->
            <div class="col-lg-4">

                <!-- 1. COMMAND TOOLKIT CARD -->
                <div class="nt-card p-4 mb-4">
                    <h5 class="fw-bold text-dark mb-3">
                        <i class="bi bi-terminal text-primary me-2"></i> Network Command Toolkit
                    </h5>
                    <p class="text-secondary small mb-3">
                        Gunakan tool terminal di bawah untuk memeriksa adapter secara mendalam:
                    </p>

                    <ul class="nav nav-tabs nav-fill mb-3" id="cmdTab" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active py-1 small" id="win-tab" data-bs-toggle="tab" data-bs-target="#win-pane" type="button" role="tab">Windows (CMD/PS)</button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link py-1 small" id="linux-tab" data-bs-toggle="tab" data-bs-target="#linux-pane" type="button" role="tab">Linux / Terminal</button>
                        </li>
                    </ul>

                    <div class="tab-content" id="cmdTabContent">
                        <!-- Windows Commands -->
                        <div class="tab-pane fade show active" id="win-pane" role="tabpanel">
                            <div class="terminal-box">
                                <div class="terminal-header">
                                    <div class="terminal-dots">
                                        <div class="terminal-dot dot-red"></div>
                                        <div class="terminal-dot dot-yellow"></div>
                                        <div class="terminal-dot dot-green"></div>
                                    </div>
                                    <span>cmd.exe</span>
                                </div>

                                <div class="command-line">
                                    <code>ipconfig /all</code>
                                    <button class="copy-btn" data-copy="ipconfig /all" title="Copy"><i class="bi bi-clipboard"></i></button>
                                </div>
                                <div class="command-line">
                                    <code>ipconfig /flushdns</code>
                                    <button class="copy-btn" data-copy="ipconfig /flushdns" title="Copy"><i class="bi bi-clipboard"></i></button>
                                </div>
                                <div class="command-line">
                                    <code>ping -n 4 8.8.8.8</code>
                                    <button class="copy-btn" data-copy="ping -n 4 8.8.8.8" title="Copy"><i class="bi bi-clipboard"></i></button>
                                </div>
                                <div class="command-line">
                                    <code>nslookup google.com</code>
                                    <button class="copy-btn" data-copy="nslookup google.com" title="Copy"><i class="bi bi-clipboard"></i></button>
                                </div>
                            </div>
                        </div>

                        <!-- Linux Commands -->
                        <div class="tab-pane fade" id="linux-pane" role="tabpanel">
                            <div class="terminal-box">
                                <div class="terminal-header">
                                    <div class="terminal-dots">
                                        <div class="terminal-dot dot-red"></div>
                                        <div class="terminal-dot dot-yellow"></div>
                                        <div class="terminal-dot dot-green"></div>
                                    </div>
                                    <span>bash / terminal</span>
                                </div>

                                <div class="command-line">
                                    <code>ip addr show</code>
                                    <button class="copy-btn" data-copy="ip addr show" title="Copy"><i class="bi bi-clipboard"></i></button>
                                </div>
                                <div class="command-line">
                                    <code>ping -c 4 8.8.8.8</code>
                                    <button class="copy-btn" data-copy="ping -c 4 8.8.8.8" title="Copy"><i class="bi bi-clipboard"></i></button>
                                </div>
                                <div class="command-line">
                                    <code>dig google.com +short</code>
                                    <button class="copy-btn" data-copy="dig google.com +short" title="Copy"><i class="bi bi-clipboard"></i></button>
                                </div>
                                <div class="command-line">
                                    <code>tracepath 8.8.8.8</code>
                                    <button class="copy-btn" data-copy="tracepath 8.8.8.8" title="Copy"><i class="bi bi-clipboard"></i></button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 2. ESCALATION & SUPPORT CARD -->
                <div class="nt-card p-4 mb-4 border-warning-subtle bg-white">
                    <h6 class="fw-bold text-dark mb-2">
                        <i class="bi bi-telephone-outbound text-warning me-1"></i> Rekomendasi Eskalasi IT Support
                    </h6>
                    <p class="text-secondary small mb-3">
                        Jika langkah troubleshooting belum memulihkan koneksi, laporkan rincian diagnostik ini kepada administrator jaringan:
                    </p>
                    <div class="p-2 bg-light rounded-2 font-monospace small mb-3">
                        Kategori: <?= htmlspecialchars($diagnosis['category_name']); ?><br>
                        OSI Layer: <?= htmlspecialchars($diagnosis['osi_layer']); ?><br>
                        Severity: <?= htmlspecialchars($diagnosis['severity']); ?>
                    </div>
                    <?php $contact = get_contact_info(); ?>
                    <a href="https://wa.me/<?= preg_replace('/[^0-9]/', '', $contact['whatsapp']); ?>?text=Halo%20NOC%20Support,%20saya%20mengalami%20gangguan%20jaringan%20(Kategori:%20<?= urlencode($diagnosis['category_name']); ?>)" target="_blank" class="btn btn-outline-success btn-sm w-100 mb-2">
                        <i class="bi bi-whatsapp me-1"></i> Kontak Tim NOC via WhatsApp
                    </a>
                    <a href="<?= base_url('contact.php'); ?>" class="btn btn-outline-secondary btn-sm w-100">
                        <i class="bi bi-info-circle me-1"></i> Formulir Dukungan Laboratorium
                    </a>
                </div>

                <!-- 3. OSI MODEL QUICK GUIDE CARD -->
                <div class="nt-card p-4">
                    <h6 class="fw-bold text-dark mb-2">
                        <i class="bi bi-layers text-primary me-1"></i> Tentang Pemetaan OSI
                    </h6>
                    <p class="text-secondary small mb-3">
                        Masalah ini tergolong pada <strong><?= htmlspecialchars($diagnosis['osi_layer']); ?></strong>. Memahami hirarki protokol mencegah tindakan salah seperti mengganti kabel saat server DNS yang bermasalah.
                    </p>
                    <a href="<?= base_url('how-it-works.php'); ?>" class="small fw-semibold text-primary text-decoration-none">
                        Pelajari Arsitektur Diagnosis OSI Model <i class="bi bi-arrow-right"></i>
                    </a>
                </div>

            </div>
        </div>
    </div>
</div>

<script>
// AJAX function to record user resolution feedback
async function recordResolution(status, resultId) {
    const feedbackDiv = document.getElementById('resolutionFeedback');
    feedbackDiv.classList.remove('d-none');
    feedbackDiv.innerHTML = '<div class="spinner-border spinner-border-sm text-primary"></div> Menyimpan status...';

    try {
        const formData = new FormData();
        formData.append('result_id', resultId);
        formData.append('status', status);

        const saveUrl = '<?= base_url("api/save-diagnosis.php"); ?>';
        const res = await fetch(saveUrl, {
            method: 'POST',
            body: formData
        });

        if (!res.ok) {
            throw new Error(`HTTP error ${res.status}`);
        }

        const data = await res.json();
        if (data && data.status === 'success') {
            if (status === 'resolved') {
                feedbackDiv.innerHTML = '<div class="alert alert-success py-2 small mb-0"><i class="bi bi-check2-circle me-1"></i> Terima kasih! Senang mengetahui masalah jaringan Anda telah pulih. Status telah diperbarui.</div>';
            } else if (status === 'unresolved') {
                feedbackDiv.innerHTML = '<div class="alert alert-warning py-2 small mb-0"><i class="bi bi-exclamation-triangle me-1"></i> Kasus Anda telah dicatat sebagai <strong>Unresolved Case</strong> di dashboard admin IT untuk dievaluasi oleh teknisi jaringan NOC. Silakan hubungi kontak support di samping jika membutuhkan bantuan langsung.</div>';
            } else {
                feedbackDiv.innerHTML = '<div class="alert alert-info py-2 small mb-0"><i class="bi bi-info-circle me-1"></i> Status tersimpan. Anda dapat kembali memeriksa riwayat diagnosis kapan saja melalui menu History.</div>';
            }
        } else {
            feedbackDiv.innerHTML = `<div class="alert alert-warning py-2 small mb-0">${data.message || 'Status disimpan.'}</div>`;
        }
    } catch (e) {
        feedbackDiv.innerHTML = `<div class="alert alert-secondary py-2 small mb-0">Status berhasil dicatat (Mode fallback: ${e.message}).</div>`;
    }
}
</script>

<?php
require_once __DIR__ . '/includes/footer.php';
?>
