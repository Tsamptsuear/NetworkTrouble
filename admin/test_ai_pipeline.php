<?php
/**
 * NetworkTrouble - AI Pipeline Verification Test
 * Run: http://localhost/networktrouble/admin/test_ai_pipeline.php
 * Requires super_admin session.
 */
$adminTitle = 'AI Pipeline Test';
require_once __DIR__ . '/includes/admin_header.php';
require_once __DIR__ . '/../config/ai.php';
require_once __DIR__ . '/../includes/classification_engine.php';
require_role(['super_admin']);

$testCases = [
    'wifi_no_internet'     => 'wifi terhubung tapi tanda seru kuning tidak bisa internet sama sekali',
    'cable_disconnect'     => 'kabel ethernet terdeteksi tapi internet mati, lampu switch berkedip',
    'all_devices_down'     => 'semua komputer di kantor tidak bisa internet, router menyala tapi IP 169.254',
    'slow_connection'      => 'internet lambat banget, buffering terus, ping tinggi 300ms',
    'dns_issue'            => 'bisa ping 8.8.8.8 tapi tidak bisa buka website, DNS server not responding',
    'wifi_not_found'       => 'sinyal wifi tiba-tiba hilang, SSID tidak muncul di daftar jaringan',
];

$results = [];
foreach ($testCases as $id => $prompt) {
    $interpret  = AISymptomInterpreter::interpret($prompt, true);
    $diagnosis  = null;
    $pipelineOk = false;

    if ($interpret['status'] === 'success' && !empty($interpret['data'])) {
        $d = $interpret['data'];
        $input = [
            'connection_type'   => $d['connection_type'] ?? 'unknown',
            'custom_text'       => $prompt,
        ];
        if (($d['connected'] ?? true) === false) {
            $input['adaptive_answers']['ping_gateway'] = 'no';
        }
        if (($d['internet_access'] ?? true) === false && ($d['connected'] ?? false) === true) {
            $input['adaptive_answers']['ping_ip_ok'] = 'yes';
        }
        try {
            $diagnosis = NetworkClassificationEngine::diagnose($input);
            $pipelineOk = !empty($diagnosis['category_name']);
        } catch (Throwable $e) {
            $diagnosis = ['error' => $e->getMessage()];
        }
    }

    $results[$id] = [
        'prompt'      => $prompt,
        'interpret'   => $interpret,
        'diagnosis'   => $diagnosis,
        'pipeline_ok' => $pipelineOk
    ];
}

$passCount = count(array_filter($results, fn($r) => $r['interpret']['status'] === 'success'));
$diagCount = count(array_filter($results, fn($r) => $r['pipeline_ok']));
$total     = count($results);
?>
<div class="admin-layout-wrapper">
    <?php include __DIR__ . '/includes/admin_sidebar.php'; ?>
    <div class="admin-main">
        <?php include __DIR__ . '/includes/admin_topbar.php'; ?>
        <div class="admin-content">
            <h1 class="h4 fw-bold mb-1">AI Pipeline Verification</h1>
            <p class="text-secondary mb-4">Menguji <?= $total; ?> kasus secara otomatis.</p>

            <!-- Summary -->
            <div class="row g-3 mb-4">
                <div class="col-md-4">
                    <div class="admin-card text-center p-3 <?= $passCount === $total ? 'border-success' : 'border-warning'; ?>">
                        <div class="fs-2 fw-bold <?= $passCount === $total ? 'text-success' : 'text-warning'; ?>"><?= $passCount; ?>/<?= $total; ?></div>
                        <small class="text-secondary">Interpreter Pass</small>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="admin-card text-center p-3 <?= $diagCount === $total ? 'border-success' : 'border-warning'; ?>">
                        <div class="fs-2 fw-bold <?= $diagCount === $total ? 'text-success' : 'text-warning'; ?>"><?= $diagCount; ?>/<?= $total; ?></div>
                        <small class="text-secondary">Full Pipeline Pass</small>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="admin-card text-center p-3">
                        <div class="fs-2 fw-bold text-primary"><?= $results[array_key_first($results)]['interpret']['source'] ?? 'N/A'; ?></div>
                        <small class="text-secondary">Provider Aktif</small>
                    </div>
                </div>
            </div>

            <!-- Results Table -->
            <?php foreach ($results as $id => $r): ?>
            <div class="admin-card mb-3">
                <div class="admin-card-header d-flex justify-content-between align-items-center">
                    <span class="fw-semibold text-dark"><?= htmlspecialchars($id); ?></span>
                    <div class="d-flex gap-2">
                        <?php if ($r['interpret']['status'] === 'success'): ?>
                            <span class="badge bg-success-subtle text-success border border-success-subtle">Interpreter OK</span>
                        <?php else: ?>
                            <span class="badge bg-danger-subtle text-danger border border-danger-subtle">Interpreter FAIL</span>
                        <?php endif; ?>
                        <?php if ($r['pipeline_ok']): ?>
                            <span class="badge bg-primary-subtle text-primary border border-primary-subtle">Pipeline OK</span>
                        <?php else: ?>
                            <span class="badge bg-warning-subtle text-warning border border-warning-subtle">Pipeline MISS</span>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="admin-card-body">
                    <p class="small text-secondary mb-2"><em>"<?= htmlspecialchars($r['prompt']); ?>"</em></p>

                    <?php if ($r['interpret']['status'] === 'success' && !empty($r['interpret']['data'])): ?>
                        <?php $d = $r['interpret']['data']; ?>
                        <div class="row g-2 mb-2">
                            <div class="col-auto"><span class="badge bg-primary"><?= htmlspecialchars($d['connection_type']); ?></span></div>
                            <div class="col-auto"><span class="badge <?= $d['connected'] ? 'bg-success' : 'bg-danger'; ?>">Connected: <?= $d['connected'] ? 'Ya' : 'Tidak'; ?></span></div>
                            <div class="col-auto"><span class="badge <?= $d['internet_access'] ? 'bg-success' : 'bg-danger'; ?>">Internet: <?= $d['internet_access'] ? 'Ya' : 'Tidak'; ?></span></div>
                        </div>
                        <?php if (!empty($d['possible_symptoms'])): ?>
                        <div class="d-flex flex-wrap gap-1 mb-2">
                            <?php foreach ($d['possible_symptoms'] as $s): ?>
                                <span class="badge bg-warning-subtle text-warning border border-warning-subtle"><?= htmlspecialchars($s); ?></span>
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>
                    <?php elseif ($r['interpret']['status'] === 'error'): ?>
                        <div class="text-danger small"><i class="bi bi-exclamation-triangle me-1"></i><?= htmlspecialchars($r['interpret']['error_message'] ?? ''); ?></div>
                    <?php endif; ?>

                    <?php if (!empty($r['diagnosis']) && !isset($r['diagnosis']['error'])): ?>
                        <div class="mt-2 p-2 bg-light rounded-2 small">
                            <strong>Diagnosa:</strong> <?= htmlspecialchars($r['diagnosis']['category_name'] ?? '-'); ?>
                            &nbsp;|&nbsp; Confidence: <strong><?= $r['diagnosis']['confidence_score'] ?? 0; ?>%</strong>
                            &nbsp;|&nbsp; Severity: <?= htmlspecialchars($r['diagnosis']['severity'] ?? '-'); ?>
                        </div>
                    <?php elseif (isset($r['diagnosis']['error'])): ?>
                        <div class="text-warning small mt-2"><i class="bi bi-exclamation-circle me-1"></i><?= htmlspecialchars($r['diagnosis']['error']); ?></div>
                    <?php endif; ?>

                    <!-- Debug log -->
                    <?php if (!empty($r['interpret']['debug_log'])): ?>
                    <div class="mt-2">
                        <a class="small text-muted text-decoration-none" data-bs-toggle="collapse" href="#dl_<?= $id; ?>" role="button" aria-expanded="false">
                            <i class="bi bi-bug me-1"></i>Debug Log <i class="bi bi-chevron-down"></i>
                        </a>
                        <div class="collapse mt-2" id="dl_<?= $id; ?>">
                            <div class="p-2 bg-dark text-light rounded-2 font-monospace" style="max-height:200px;overflow-y:auto;font-size:0.7rem;">
                                <?php foreach ($r['interpret']['debug_log'] as $entry): ?>
                                    <div class="mb-1 pb-1 border-bottom border-secondary">
                                        <span class="text-info">[<?= htmlspecialchars($entry['step']); ?>]</span>
                                        <?php foreach (array_filter($entry, fn($k) => $k !== 'step', ARRAY_FILTER_USE_KEY) as $k => $v): ?>
                                            <span class="text-warning"><?= htmlspecialchars($k); ?></span>=<span class="text-success"><?= htmlspecialchars(is_array($v) ? json_encode($v) : (is_bool($v) ? ($v?'true':'false') : $v)); ?></span>&nbsp;
                                        <?php endforeach; ?>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>

        </div>
    </div>
</div>
<?php require_once __DIR__ . '/includes/admin_footer.php'; ?>
