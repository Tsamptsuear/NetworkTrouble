<?php
/**
 * NetworkTrouble - AI Module Settings
 * Admin panel for configuring AI providers and testing the interpreter pipeline.
 */
$adminTitle = 'Pengaturan Modul AI';
require_once __DIR__ . '/includes/admin_header.php';
require_once __DIR__ . '/../config/ai.php';
require_role(['super_admin']);

$db = get_db_connection();
$errors = [];

// ─── Handle Settings Update (pure form POST, no AJAX) ────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_ai_settings'])) {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Token CSRF tidak valid.';
    } else {
        $provider   = sanitize($_POST['provider'] ?? 'mock');
        $endpoint   = sanitize($_POST['api_endpoint'] ?? '');
        $apiKey     = sanitize($_POST['api_key'] ?? '');
        $modelName  = sanitize($_POST['model_name'] ?? 'gpt-4o-mini');
        $timeout    = max(3, min(60, (int)($_POST['timeout'] ?? 8)));
        $enabled    = isset($_POST['enabled']) ? 1 : 0;

        if ($db) {
            try {
                $count = (int)$db->query("SELECT COUNT(*) FROM ai_settings")->fetchColumn();
                if ($count === 0) {
                    $stmt = $db->prepare("INSERT INTO ai_settings (provider, api_endpoint, api_key, model_name, enabled, timeout, updated_at) VALUES (?, ?, ?, ?, ?, ?, NOW())");
                    $stmt->execute([$provider, $endpoint, $apiKey, $modelName, $enabled, $timeout]);
                } else {
                    $stmt = $db->prepare("UPDATE ai_settings SET provider=?, api_endpoint=?, api_key=?, model_name=?, enabled=?, timeout=?, updated_at=NOW() LIMIT 1");
                    $stmt->execute([$provider, $endpoint, $apiKey, $modelName, $enabled, $timeout]);
                }
                log_activity($currentAdmin['id'], 'update_ai_settings', 'ai_settings', 1);
                set_flash('success', 'Konfigurasi AI interpreter berhasil diperbarui!');
                header('Location: ' . base_url('admin/ai-settings.php'));
                exit;
            } catch (PDOException $e) {
                $errors[] = 'Gagal menyimpan: ' . $e->getMessage();
            }
        }
    }
}

// ─── Fetch current AI settings ───────────────────────────────────
$aiRow = null;
if ($db) {
    try { $aiRow = $db->query("SELECT * FROM ai_settings LIMIT 1")->fetch(PDO::FETCH_ASSOC); }
    catch (PDOException $e) {}
}
$provider   = $aiRow['provider']     ?? 'mock';
$endpoint   = $aiRow['api_endpoint'] ?? 'https://api.openai.com/v1/chat/completions';
$apiKey     = $aiRow['api_key']      ?? '';
$modelName  = $aiRow['model_name']   ?? 'gpt-4o-mini';
$timeout    = (int)($aiRow['timeout'] ?? 8);
$enabled    = (bool)($aiRow['enabled'] ?? 1);

// API URL for AJAX — injected into JS
$aiApiUrl = base_url('api/ai-interpret.php');
?>

<div class="admin-layout-wrapper">
    <?php include __DIR__ . '/includes/admin_sidebar.php'; ?>

    <div class="admin-main">
        <?php include __DIR__ . '/includes/admin_topbar.php'; ?>

        <div class="admin-content">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
                <div>
                    <h1 class="h3 fw-bold text-dark mb-1">Pengaturan Integrasi AI &amp; LLM Engine</h1>
                    <p class="text-secondary mb-0">Atur penyedia interpreter dan uji analisis secara real-time dengan teks keluhan bebas.</p>
                </div>
            </div>

            <?php $flash = get_flash(); if ($flash): ?>
            <div class="alert alert-<?= $flash['type'] === 'error' ? 'danger' : $flash['type']; ?> alert-dismissible fade show shadow-sm">
                <?= htmlspecialchars($flash['message']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php endif; ?>

            <?php if (!empty($errors)): ?>
            <div class="alert alert-danger shadow-sm">
                <ul class="mb-0 ps-3"><?php foreach ($errors as $e): ?><li><?= htmlspecialchars($e); ?></li><?php endforeach; ?></ul>
            </div>
            <?php endif; ?>

            <div class="row g-4">

                <!-- ── Settings Column ──────────────────────────── -->
                <div class="col-lg-7">
                    <div class="admin-card">
                        <div class="admin-card-header">
                            <h6 class="fw-bold text-dark mb-0"><i class="bi bi-cpu me-2 text-primary"></i>Konfigurasi Provider &amp; Endpoint</h6>
                        </div>
                        <div class="admin-card-body">
                            <form method="POST" action="" id="settingsForm">
                                <input type="hidden" name="csrf_token" value="<?= generate_csrf_token(); ?>">
                                <input type="hidden" name="save_ai_settings" value="1">

                                <div class="form-check form-switch mb-4 p-3 bg-light rounded-3 border">
                                    <input class="form-check-input ms-0 me-3" type="checkbox" role="switch" id="enabled" name="enabled" <?= $enabled ? 'checked' : ''; ?>>
                                    <label class="form-check-label fw-bold text-dark" for="enabled">Aktifkan Interpreter AI</label>
                                    <div class="form-text small text-secondary">Jika dimatikan, sistem menggunakan rule-based keyword tanpa AI.</div>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label fw-semibold">Penyedia AI (AI Provider)</label>
                                    <select name="provider" id="aiProviderSelect" class="form-select" onchange="updateProviderDefaults()">
                                        <option value="mock"     <?= $provider === 'mock'     ? 'selected' : ''; ?>>Mock Deterministic Engine (Offline, Tanpa API Key)</option>
                                        <option value="openai"   <?= $provider === 'openai'   ? 'selected' : ''; ?>>OpenAI (GPT-4o-mini)</option>
                                        <option value="gemini"   <?= $provider === 'gemini'   ? 'selected' : ''; ?>>Google Gemini (Gemini 1.5 Flash)</option>
                                        <option value="deepseek" <?= $provider === 'deepseek' ? 'selected' : ''; ?>>DeepSeek (DeepSeek Chat API)</option>
                                    </select>
                                    <div class="form-text small">Rekomendasi XAMPP lokal: <strong>Mock Deterministic Engine</strong>.</div>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label fw-semibold">API Endpoint URL</label>
                                    <input type="text" name="api_endpoint" id="apiEndpoint" class="form-control font-monospace" value="<?= htmlspecialchars($endpoint); ?>" required>
                                    <div class="form-text small text-secondary" id="endpointHint">Endpoint disesuaikan otomatis berdasarkan provider.</div>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label fw-semibold">API Key</label>
                                    <div class="input-group">
                                        <input type="password" name="api_key" id="apiKeyInput" class="form-control font-monospace" value="<?= htmlspecialchars($apiKey); ?>" placeholder="sk-...">
                                        <button class="btn btn-outline-secondary" type="button" onclick="toggleKeyVisibility()" title="Tampilkan/Sembunyikan">
                                            <i class="bi bi-eye" id="eyeIcon"></i>
                                        </button>
                                    </div>
                                    <div class="form-text small">Tidak diperlukan untuk mode Mock.</div>
                                </div>

                                <div class="row g-3 mb-4">
                                    <div class="col-md-7">
                                        <label class="form-label fw-semibold">Nama Model</label>
                                        <input type="text" name="model_name" id="modelName" class="form-control font-monospace" value="<?= htmlspecialchars($modelName); ?>" placeholder="gpt-4o-mini">
                                    </div>
                                    <div class="col-md-5">
                                        <label class="form-label fw-semibold">Timeout (Detik)</label>
                                        <input type="number" name="timeout" class="form-control" value="<?= $timeout; ?>" min="3" max="60">
                                    </div>
                                </div>

                                <div class="d-flex justify-content-end">
                                    <button type="submit" class="btn btn-primary fw-semibold px-4">
                                        <i class="bi bi-check2-circle me-1"></i> Simpan Konfigurasi AI
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- ── Test Column ───────────────────────────────── -->
                <div class="col-lg-5">

                    <!-- Connection Test -->
                    <div class="admin-card mb-4">
                        <div class="admin-card-header">
                            <h6 class="fw-bold text-dark mb-0"><i class="bi bi-plug me-2 text-info"></i>Uji Koneksi API</h6>
                        </div>
                        <div class="admin-card-body">
                            <p class="small text-secondary mb-3">Verifikasi API key dan endpoint tanpa menjalankan interpretasi penuh.</p>
                            <button type="button" id="btnTestConn" class="btn btn-outline-info btn-sm w-100 fw-semibold" onclick="runConnectionTest()">
                                <i class="bi bi-lightning-charge me-1"></i> Test Connection
                            </button>
                            <div id="connTestResult" class="mt-3"></div>
                        </div>
                    </div>

                    <!-- Interpreter Simulation — AJAX based, no page reload -->
                    <div class="admin-card">
                        <div class="admin-card-header">
                            <h6 class="fw-bold text-dark mb-0"><i class="bi bi-play-circle me-2 text-success"></i>Uji Simulasi Interpreter</h6>
                        </div>
                        <div class="admin-card-body">

                            <!-- Standalone textarea (NOT part of any form) -->
                            <div class="mb-2">
                                <label class="form-label small fw-semibold" for="testPromptInput">Kalimat Keluhan Pengguna:</label>
                                <textarea id="testPromptInput" class="form-control form-control-sm" rows="3"
                                    placeholder="Ketik keluhan jaringan di sini, lalu klik tombol uji..."
                                >wifi terhubung tapi tanda seru kuning tidak bisa internet sama sekali</textarea>
                                <div class="d-flex justify-content-between mt-1">
                                    <span class="form-text small text-secondary">Ganti teks → klik tombol → hasil berubah secara real-time.</span>
                                    <span class="form-text small text-muted" id="charCounter">0 karakter</span>
                                </div>
                            </div>

                            <div class="d-flex gap-2 mb-3">
                                <button type="button" id="btnInterpreter" class="btn btn-outline-success btn-sm flex-fill fw-semibold" onclick="runTest('interpreter')">
                                    <i class="bi bi-send me-1"></i> Uji Interpreter
                                </button>
                                <button type="button" id="btnPipeline" class="btn btn-success btn-sm flex-fill fw-semibold" onclick="runTest('pipeline')">
                                    <i class="bi bi-diagram-3 me-1"></i> Full Pipeline
                                </button>
                            </div>

                            <!-- Dynamic Result Panel -->
                            <div id="testResultPanel">
                                <div class="text-center py-4 text-muted" id="resultPlaceholder">
                                    <i class="bi bi-robot fs-2 d-block mb-2 text-secondary opacity-50"></i>
                                    <span class="small">Ketik keluhan di atas lalu klik tombol untuk melihat analisis AI secara real-time.</span>
                                </div>
                            </div>

                        </div>
                    </div>

                </div><!-- /.col-lg-5 -->
            </div><!-- /.row -->
        </div><!-- /.admin-content -->
    </div><!-- /.admin-main -->
</div>

<script>
(function () {
    'use strict';

    // ── Constants injected from PHP ──────────────────────────────
    const API_URL    = <?= json_encode($aiApiUrl); ?>;
    const CSRF_TOKEN = <?= json_encode(generate_csrf_token()); ?>;

    // ── State ────────────────────────────────────────────────────
    let abortController = null; // AbortController for in-flight test request
    let requestSerial   = 0;    // monotonically increasing — latest wins

    // ── Textarea char counter ────────────────────────────────────
    const textarea = document.getElementById('testPromptInput');
    const counter  = document.getElementById('charCounter');

    function updateCounter() {
        counter.textContent = textarea.value.length + ' karakter';
    }
    textarea.addEventListener('input', updateCounter);
    updateCounter();

    // ── Main test runner ─────────────────────────────────────────
    window.runTest = async function (mode) {
        const prompt = textarea.value.trim();
        if (!prompt) {
            showValidationError('Masukkan teks keluhan sebelum menguji.');
            return;
        }

        // Cancel any in-flight request
        if (abortController) {
            abortController.abort();
        }
        abortController = new AbortController();
        const mySerial  = ++requestSerial;

        setButtonsLoading(true, mode);
        showLoading(prompt, mode);

        const payload = {
            user_text : prompt,
            debug     : true,
            pipeline  : mode === 'pipeline' ? '1' : '0'
        };

        try {
            const resp = await fetch(API_URL, {
                method  : 'POST',
                headers : {
                    'Content-Type'    : 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-Token'    : CSRF_TOKEN
                },
                body    : JSON.stringify(payload),
                signal  : abortController.signal,
                cache   : 'no-store'
            });

            // Race-condition guard — discard if a newer request exists
            if (mySerial !== requestSerial) return;

            let result;
            try {
                result = await resp.json();
            } catch (_) {
                result = { status: 'error', error_message: 'Server mengembalikan respons bukan JSON.', input_text: prompt };
            }

            if (mySerial !== requestSerial) return;

            renderResult(result, mode);

        } catch (err) {
            if (err.name === 'AbortError') return; // Intentional cancel — do nothing
            if (mySerial !== requestSerial) return;
            renderNetworkError(err.message, prompt);
        } finally {
            if (mySerial === requestSerial) {
                setButtonsLoading(false, mode);
            }
        }
    };

    // ── Connection Test ──────────────────────────────────────────
    window.runConnectionTest = async function () {
        const btn = document.getElementById('btnTestConn');
        const panel = document.getElementById('connTestResult');

        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Menguji koneksi...';
        panel.innerHTML = '';

        try {
            const resp = await fetch(API_URL.replace('ai-interpret.php', 'ai-connection-test.php'), {
                method : 'POST',
                headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                body   : JSON.stringify({ csrf_token: CSRF_TOKEN }),
                cache  : 'no-store'
            });

            let r;
            try { r = await resp.json(); }
            catch (_) { r = { status: 'error', message: 'Respons server tidak valid.' }; }

            const isOk = r.status === 'ok';
            panel.innerHTML = `
                <div class="p-3 rounded-3 border ${isOk ? 'bg-success-subtle border-success-subtle' : 'bg-danger-subtle border-danger-subtle'}">
                    <div class="d-flex align-items-center gap-2 mb-1">
                        <i class="bi ${isOk ? 'bi-check-circle-fill text-success' : 'bi-x-circle-fill text-danger'}"></i>
                        <span class="fw-semibold small ${isOk ? 'text-success' : 'text-danger'}">${isOk ? 'Koneksi Berhasil' : 'Koneksi Gagal'}</span>
                        ${r.provider ? `<span class="badge bg-light text-dark border ms-auto small">${esc(r.provider)}</span>` : ''}
                    </div>
                    <p class="small mb-0 ${isOk ? 'text-success' : 'text-danger'}">${esc(r.message ?? 'Tidak ada pesan.')}</p>
                    ${r.latency_ms > 0 ? `<small class="text-muted">Latency: ${r.latency_ms}ms</small>` : ''}
                </div>`;
        } catch (err) {
            panel.innerHTML = `<div class="alert alert-danger small py-2 mb-0">Gagal terhubung ke server: ${esc(err.message)}</div>`;
        } finally {
            btn.disabled = false;
            btn.innerHTML = '<i class="bi bi-lightning-charge me-1"></i> Test Connection';
        }
    };

    // ── UI Helpers ────────────────────────────────────────────────

    function setButtonsLoading(isLoading, mode) {
        const btnI = document.getElementById('btnInterpreter');
        const btnP = document.getElementById('btnPipeline');

        if (isLoading && mode === 'interpreter') {
            btnI.disabled = true;
            btnI.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Menganalisis...';
            btnP.disabled = true;
        } else if (isLoading && mode === 'pipeline') {
            btnP.disabled = true;
            btnP.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Pipeline berjalan...';
            btnI.disabled = true;
        } else {
            btnI.disabled = false;
            btnI.innerHTML = '<i class="bi bi-send me-1"></i> Uji Interpreter';
            btnP.disabled = false;
            btnP.innerHTML = '<i class="bi bi-diagram-3 me-1"></i> Full Pipeline';
        }
    }

    function showLoading(prompt, mode) {
        const panel = document.getElementById('testResultPanel');
        const modeLabel = mode === 'pipeline' ? 'Full Pipeline' : 'Interpreter';
        panel.innerHTML = `
            <div class="border rounded-3 p-3 bg-light" id="loadingCard">
                <div class="d-flex align-items-center gap-2 mb-3">
                    <div class="spinner-border spinner-border-sm text-primary" role="status" aria-hidden="true"></div>
                    <span class="fw-semibold small text-primary">Sedang menganalisis teks terbaru dengan ${esc(modeLabel)}...</span>
                </div>
                <div class="p-2 bg-white rounded-2 border-start border-3 border-primary ps-3">
                    <small class="text-muted d-block mb-1">Input yang dikirim ke backend:</small>
                    <span class="small text-dark fst-italic">"${esc(prompt.substring(0, 120))}${prompt.length > 120 ? '…' : ''}"</span>
                </div>
                <div class="mt-2 text-muted small">
                    <i class="bi bi-info-circle me-1"></i>
                    Hasil sebelumnya sudah dihapus. Menunggu respons terbaru…
                </div>
            </div>`;
    }

    function showValidationError(msg) {
        const panel = document.getElementById('testResultPanel');
        panel.innerHTML = `
            <div class="alert alert-warning d-flex align-items-center gap-2 mb-0" role="alert">
                <i class="bi bi-exclamation-triangle-fill text-warning"></i>
                <span class="small">${esc(msg)}</span>
            </div>`;
    }

    function renderNetworkError(errMsg, prompt) {
        const panel = document.getElementById('testResultPanel');
        panel.innerHTML = `
            <div class="border rounded-3 p-3 bg-danger-subtle">
                <div class="d-flex align-items-center gap-2 mb-2">
                    <i class="bi bi-wifi-off text-danger"></i>
                    <span class="fw-semibold small text-danger">Koneksi ke Server Gagal</span>
                </div>
                <p class="small text-danger mb-2">${esc(errMsg)}</p>
                <div class="p-2 bg-white rounded-2 border small text-muted">
                    Input: <em>"${esc(prompt.substring(0, 80))}${prompt.length > 80 ? '…' : ''}"</em>
                </div>
            </div>`;
    }

    function renderResult(result, mode) {
        const panel = document.getElementById('testResultPanel');
        const inputText = result.input_text ?? '';

        let html = '';

        // ── Input echo ────────────────────────────────────────────
        html += `
            <div class="mb-3 p-2 bg-light rounded-2 border-start border-3 border-primary ps-3">
                <small class="text-muted d-block mb-1">Input yang diuji:</small>
                <span class="small text-dark fw-semibold fst-italic">"${esc(inputText)}"</span>
            </div>`;

        if (result.status === 'error') {
            // ── Error card ─────────────────────────────────────────
            html += `
                <div class="border rounded-3 p-3 bg-danger-subtle">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <i class="bi bi-x-octagon-fill text-danger"></i>
                        <span class="fw-semibold small text-danger">Interpreter Error</span>
                        <span class="badge bg-light text-dark border ms-auto">${esc(result.source ?? '')}</span>
                    </div>
                    <p class="small mb-1"><span class="text-muted">Kode:</span> <code class="text-danger">${esc(result.error_code ?? 'unknown')}</code></p>
                    <p class="small text-danger mb-0">${esc(result.error_message ?? 'Kesalahan tidak diketahui.')}</p>
                </div>`;
        } else if (result.status === 'success') {
            const d = result.data ?? {};

            // ── Interpreter success ────────────────────────────────
            html += `<div class="border rounded-3 p-3 bg-white mb-3">`;

            // Header row
            html += `<div class="d-flex justify-content-between align-items-center mb-3">
                <span class="badge bg-success-subtle text-success border border-success-subtle"><i class="bi bi-check2-circle me-1"></i>Interpreter OK</span>
                <small class="text-muted">${esc(result.source ?? '')}</small>
            </div>`;

            // Connection metrics
            const connBadge    = d.connection_type ? `<span class="badge bg-primary">${esc(d.connection_type)}</span>` : '';
            const connectedBadge = d.connected
                ? `<span class="badge bg-success-subtle text-success border border-success-subtle"><i class="bi bi-link-45deg me-1"></i>Connected</span>`
                : `<span class="badge bg-danger-subtle text-danger border border-danger-subtle"><i class="bi bi-link-break me-1"></i>Disconnected</span>`;
            const internetBadge = d.internet_access
                ? `<span class="badge bg-success-subtle text-success border border-success-subtle"><i class="bi bi-globe me-1"></i>Internet OK</span>`
                : `<span class="badge bg-danger-subtle text-danger border border-danger-subtle"><i class="bi bi-globe2 me-1"></i>No Internet</span>`;

            html += `<div class="d-flex flex-wrap gap-2 mb-3">${connBadge}${connectedBadge}${internetBadge}</div>`;

            // Symptoms
            if (d.possible_symptoms && d.possible_symptoms.length > 0) {
                html += `<div class="mb-2">
                    <small class="text-secondary d-block mb-1"><i class="bi bi-clipboard2-pulse me-1"></i>Gejala Terdeteksi:</small>
                    <div class="d-flex flex-wrap gap-1">
                        ${d.possible_symptoms.map(s => `<span class="badge bg-warning-subtle text-warning border border-warning-subtle">${esc(s)}</span>`).join('')}
                    </div>
                </div>`;
            }

            // Keywords
            if (d.extracted_keywords && d.extracted_keywords.length > 0) {
                html += `<div class="mb-2">
                    <small class="text-secondary d-block mb-1"><i class="bi bi-tags me-1"></i>Keywords:</small>
                    <div class="d-flex flex-wrap gap-1">
                        ${d.extracted_keywords.map(k => `<span class="badge bg-light text-dark border">${esc(k)}</span>`).join('')}
                    </div>
                </div>`;
            }

            // Summary
            if (d.summary) {
                html += `<div class="p-2 bg-light rounded-2 border-start border-3 border-success mt-2 ps-3">
                    <small class="text-secondary d-block mb-1">Ringkasan Analisis:</small>
                    <p class="small text-dark mb-0 fst-italic">${esc(d.summary)}</p>
                </div>`;
            }

            html += `</div>`; // close interpreter card

            // ── Full Pipeline result ─────────────────────────────
            if (mode === 'pipeline') {
                if (result.diagnosis && !result.diagnosis.error) {
                    const diag = result.diagnosis;
                    const cs   = diag.confidence_score ?? 0;
                    const barClass = cs >= 80 ? 'bg-success' : cs >= 60 ? 'bg-primary' : cs >= 40 ? 'bg-warning' : 'bg-secondary';

                    html += `<div class="border rounded-3 p-3 bg-white border-primary-subtle">`;
                    html += `<div class="d-flex justify-content-between align-items-center mb-3">
                        <span class="badge bg-primary-subtle text-primary border border-primary-subtle"><i class="bi bi-diagram-3 me-1"></i>Full Pipeline — Diagnosis</span>
                        <small class="text-muted">Layer: ${esc(diag.osi_layer ?? '–')}</small>
                    </div>`;

                    html += `<h6 class="fw-bold text-dark mb-1">${esc(diag.category_name ?? '–')}</h6>`;

                    // Confidence bar
                    html += `<div class="d-flex align-items-center gap-2 mb-3">
                        <div class="progress flex-fill" style="height:8px;">
                            <div class="progress-bar ${barClass}" style="width:${cs}%;"></div>
                        </div>
                        <span class="badge ${barClass} bg-opacity-75">${cs}% confidence</span>
                        <span class="badge bg-warning-subtle text-warning">${esc(diag.severity ?? '–')}</span>
                    </div>`;

                    if (diag.possible_causes && diag.possible_causes.length > 0) {
                        html += `<div class="mb-2">
                            <small class="text-secondary d-block mb-1"><i class="bi bi-search me-1"></i>Kemungkinan Penyebab:</small>
                            ${diag.possible_causes.slice(0, 3).map(c =>
                                `<div class="small text-dark d-flex justify-content-between border-bottom py-1">
                                    <span>• ${esc(c.cause)}</span>
                                    <span class="text-muted ms-2 text-nowrap">${c.prob}%</span>
                                </div>`
                            ).join('')}
                        </div>`;
                    }

                    if (diag.description) {
                        html += `<div class="p-2 bg-light rounded-2 border-start border-3 border-primary ps-3 mt-2">
                            <small class="text-secondary d-block mb-1">Penjelasan:</small>
                            <p class="small text-dark mb-0">${esc(diag.description)}</p>
                        </div>`;
                    }

                    html += `</div>`; // close pipeline card

                } else if (result.diagnosis && result.diagnosis.error) {
                    html += `<div class="border rounded-3 p-3 bg-warning-subtle">
                        <div class="d-flex align-items-center gap-2 mb-1">
                            <i class="bi bi-exclamation-triangle text-warning"></i>
                            <span class="fw-semibold small text-warning">Pipeline Classification Error</span>
                        </div>
                        <p class="small text-warning mb-0">${esc(result.diagnosis.error)}</p>
                    </div>`;
                }
            }

            // ── Debug Log (collapsible) ───────────────────────────
            if (result.debug_log && result.debug_log.length > 0) {
                const debugId = 'debugLog_' + Date.now();
                html += `<div class="mt-3 pt-2 border-top">
                    <a class="small text-muted text-decoration-none d-flex align-items-center gap-1"
                       data-bs-toggle="collapse" href="#${debugId}" role="button" aria-expanded="false">
                        <i class="bi bi-bug"></i> Debug Log <i class="bi bi-chevron-down ms-1"></i>
                    </a>
                    <div class="collapse mt-2" id="${debugId}">
                        <div class="p-2 bg-dark text-light rounded-2 font-monospace overflow-auto" style="max-height:220px;font-size:0.7rem;">
                            ${result.debug_log.map(entry => {
                                const step = esc(entry.step ?? '');
                                const others = Object.entries(entry)
                                    .filter(([k]) => k !== 'step')
                                    .map(([k, v]) => {
                                        const display = typeof v === 'object' ? JSON.stringify(v) : String(v);
                                        return `<span class="text-warning">${esc(k)}</span>=<span class="text-success">${esc(display)}</span>`;
                                    }).join(' ');
                                return `<div class="mb-1 pb-1 border-bottom border-secondary"><span class="text-info">[${step}]</span> ${others}</div>`;
                            }).join('')}
                        </div>
                    </div>
                </div>`;
            }
        }

        panel.innerHTML = html;
    }

    // ── Utility ──────────────────────────────────────────────────
    function esc(str) {
        if (str === null || str === undefined) return '';
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    // ── Provider selector helper ─────────────────────────────────
    window.updateProviderDefaults = function () {
        const sel  = document.getElementById('aiProviderSelect').value;
        const ep   = document.getElementById('apiEndpoint');
        const mdl  = document.getElementById('modelName');
        const hint = document.getElementById('endpointHint');

        if (sel === 'mock') {
            ep.value   = 'local://mock-deterministic';
            mdl.value  = 'mock-network-expert-v1';
            hint.textContent = 'Mock engine berjalan lokal, tidak memerlukan API eksternal.';
        } else if (sel === 'openai') {
            ep.value   = 'https://api.openai.com/v1/chat/completions';
            mdl.value  = 'gpt-4o-mini';
            hint.textContent = 'OpenAI Chat Completions API.';
        } else if (sel === 'gemini') {
            ep.value   = 'https://generativelanguage.googleapis.com/v1beta/models/';
            mdl.value  = 'gemini-1.5-flash';
            hint.textContent = 'Gemini endpoint dibangun otomatis dari model name. API key dikirim via query string.';
        } else if (sel === 'deepseek') {
            ep.value   = 'https://api.deepseek.com/chat/completions';
            mdl.value  = 'deepseek-chat';
            hint.textContent = 'DeepSeek Chat API (format kompatibel OpenAI).';
        }
    };

    window.toggleKeyVisibility = function () {
        const input = document.getElementById('apiKeyInput');
        const icon  = document.getElementById('eyeIcon');
        if (input.type === 'password') {
            input.type = 'text';
            icon.classList.replace('bi-eye', 'bi-eye-slash');
        } else {
            input.type = 'password';
            icon.classList.replace('bi-eye-slash', 'bi-eye');
        }
    };

}());
</script>

<?php require_once __DIR__ . '/includes/admin_footer.php'; ?>
