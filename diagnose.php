<?php
/**
 * NetworkTrouble - Interactive Diagnosis Wizard (Tahap 3)
 * Antarmuka multi-langkah interaktif berbasis Rule-Based Inference Engine murni (tanpa AI).
 */
require_once __DIR__ . '/config/app.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

$pageTitle = "Diagnose Network - Wizard Interaktif";
$pageDesc = "Pilih gejala gangguan jaringan Anda dan dapatkan analisis sistematis berbasis lapisan OSI serta langkah perbaikannya.";

$db = get_db();
$symptoms = [];
$categories = [];

if ($db) {
    try {
        $stmt = $db->query("SELECT s.*, c.name as category_name, c.slug as category_slug FROM network_symptoms s JOIN network_categories c ON s.category_id = c.id WHERE s.status = 'active' ORDER BY s.weight DESC, s.id ASC");
        $symptoms = $stmt->fetchAll();

        $catStmt = $db->query("SELECT id, name, slug, icon FROM network_categories WHERE status = 'active' ORDER BY id ASC");
        $categories = $catStmt->fetchAll();
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
                    <i class="bi bi-cpu me-1"></i> Rule-Based Diagnostic Engine
                </span>
                <h1 class="h2 fw-bold text-dark mb-2">Network Diagnostic Wizard</h1>
                <p class="text-secondary mb-0">
                    Ikuti 4 langkah terstruktur di bawah ini untuk mengidentifikasi anomali jaringan, memetakan lapisan OSI yang terdampak, dan memperoleh instruksi perbaikan terverifikasi.
                </p>
            </div>
        </div>
    </div>
</div>

<div class="py-5">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-9">

                <!-- Stepper Progress Indicator -->
                <div class="wizard-step-indicator mb-4">
                    <div class="step-item active" data-step="1">
                        <div class="step-circle">1</div>
                        <div class="step-label">Tipe Koneksi</div>
                    </div>
                    <div class="step-item" data-step="2">
                        <div class="step-circle">2</div>
                        <div class="step-label">Gejala Utama</div>
                    </div>
                    <div class="step-item" data-step="3">
                        <div class="step-circle">3</div>
                        <div class="step-label">Uji Adaptif</div>
                    </div>
                    <div class="step-item" data-step="4">
                        <div class="step-circle">4</div>
                        <div class="step-label">Keluhan Bebas</div>
                    </div>
                </div>

                <!-- Diagnosis Form Card -->
                <div class="nt-card p-4 p-md-5 bg-white border">
                    <form id="networkDiagnosisForm" action="<?= base_url('result.php'); ?>" method="POST">
                        <?= csrf_field(); ?>
                        <input type="hidden" name="classification_source" id="classificationSourceInput" value="Rule-Based">

                        <!-- STEP 1: Connection Type -->
                        <div class="wizard-step-panel" data-step="1">
                            <div class="d-flex align-items-center gap-2 mb-3">
                                <span class="badge bg-primary text-white rounded-circle p-2">1</span>
                                <h4 class="mb-0 fs-5 fw-bold">Pilih Jenis Koneksi yang Bermasalah</h4>
                            </div>
                            <p class="text-secondary small mb-4">
                                Jenis media transmisi menentukan cakupan pengujian awal untuk protokol Physical Layer (Layer 1) dan Data Link Layer (Layer 2).
                            </p>

                            <div class="row g-3">
                                <div class="col-md-6">
                                    <div class="selectable-card selected d-flex align-items-center gap-3">
                                        <input type="radio" name="connection_type" value="wifi" id="connWifi" class="form-check-input mt-0" checked>
                                        <label for="connWifi" class="mb-0 flex-grow-1 cursor-pointer">
                                            <div class="d-flex align-items-center gap-2">
                                                <i class="bi bi-wifi fs-4 text-primary"></i>
                                                <div>
                                                    <div class="fw-bold">WiFi / Wireless</div>
                                                    <div class="small text-muted">Koneksi nirkabel melalui Access Point / Router WiFi</div>
                                                </div>
                                            </div>
                                        </label>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="selectable-card d-flex align-items-center gap-3">
                                        <input type="radio" name="connection_type" value="lan" id="connLan" class="form-check-input mt-0">
                                        <label for="connLan" class="mb-0 flex-grow-1 cursor-pointer">
                                            <div class="d-flex align-items-center gap-2">
                                                <i class="bi bi-ethernet fs-4 text-primary"></i>
                                                <div>
                                                    <div class="fw-bold">Kabel LAN / Ethernet (RJ45)</div>
                                                    <div class="small text-muted">Kabel jaringan UTP tersambung ke port switch/router</div>
                                                </div>
                                            </div>
                                        </label>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="selectable-card d-flex align-items-center gap-3">
                                        <input type="radio" name="connection_type" value="fiber" id="connFiber" class="form-check-input mt-0">
                                        <label for="connFiber" class="mb-0 flex-grow-1 cursor-pointer">
                                            <div class="d-flex align-items-center gap-2">
                                                <i class="bi bi-router fs-4 text-primary"></i>
                                                <div>
                                                    <div class="fw-bold">Modem / Fiber Optik (ONT)</div>
                                                    <div class="small text-muted">Perangkat modem utama penyedia internet (ISP)</div>
                                                </div>
                                            </div>
                                        </label>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="selectable-card d-flex align-items-center gap-3">
                                        <input type="radio" name="connection_type" value="hotspot" id="connHotspot" class="form-check-input mt-0">
                                        <label for="connHotspot" class="mb-0 flex-grow-1 cursor-pointer">
                                            <div class="d-flex align-items-center gap-2">
                                                <i class="bi bi-phone fs-4 text-primary"></i>
                                                <div>
                                                    <div class="fw-bold">Mobile Hotspot / Tethering</div>
                                                    <div class="small text-muted">Berbagi koneksi seluler dari smartphone</div>
                                                </div>
                                            </div>
                                        </label>
                                    </div>
                                </div>

                                <div class="col-12">
                                    <div class="selectable-card d-flex align-items-center gap-3">
                                        <input type="radio" name="connection_type" value="unknown" id="connUnknown" class="form-check-input mt-0">
                                        <label for="connUnknown" class="mb-0 flex-grow-1 cursor-pointer">
                                            <div class="d-flex align-items-center gap-2">
                                                <i class="bi bi-question-circle fs-4 text-secondary"></i>
                                                <div>
                                                    <div class="fw-bold">Saya Tidak Yakin / Multi-Koneksi</div>
                                                    <div class="small text-muted">Sistem akan melakukan isolasi bertahap dari layer fisik ke aplikasi</div>
                                                </div>
                                            </div>
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- STEP 2: Main Symptoms -->
                        <div class="wizard-step-panel d-none" data-step="2">
                            <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-3">
                                <div class="d-flex align-items-center gap-2">
                                    <span class="badge bg-primary text-white rounded-circle p-2">2</span>
                                    <h4 class="mb-0 fs-5 fw-bold">Pilih Gejala Utama yang Anda Alami</h4>
                                </div>
                                <div style="min-width: 240px;">
                                    <div class="input-group input-group-sm">
                                        <span class="input-group-text bg-white border-end-0"><i class="bi bi-search text-muted"></i></span>
                                        <input type="text" id="symptomSearch" class="form-control border-start-0" placeholder="Cari gejala (misal: ping, kabel, dns)...">
                                    </div>
                                </div>
                            </div>

                            <p class="text-secondary small mb-3">
                                Centang satu atau beberapa gejala yang paling sesuai dengan kondisi jaringan Anda saat ini:
                            </p>

                            <!-- Category Filter Pills -->
                            <div class="d-flex flex-wrap gap-1 mb-3 pb-2 border-bottom">
                                <button type="button" class="btn btn-sm btn-outline-primary active symptom-filter-btn" data-filter="all">Semua Gejala</button>
                                <button type="button" class="btn btn-sm btn-outline-secondary symptom-filter-btn" data-filter="physical-layer-issue">Kabel / Fisik</button>
                                <button type="button" class="btn btn-sm btn-outline-secondary symptom-filter-btn" data-filter="wireless-issue">WiFi / Nirkabel</button>
                                <button type="button" class="btn btn-sm btn-outline-secondary symptom-filter-btn" data-filter="network-layer-issue">IP & DHCP</button>
                                <button type="button" class="btn btn-sm btn-outline-secondary symptom-filter-btn" data-filter="application-layer-issue">DNS & Web</button>
                                <button type="button" class="btn btn-sm btn-outline-secondary symptom-filter-btn" data-filter="performance-issue">Kecepatan & RTO</button>
                            </div>

                            <div class="row g-2" id="symptomsListContainer" style="max-height: 440px; overflow-y: auto; padding-right: 5px;">
                                <?php if (!empty($symptoms)): ?>
                                    <?php foreach ($symptoms as $sym): ?>
                                    <div class="col-md-6 symptom-item" data-category="<?= htmlspecialchars($sym['category_slug'] ?? ''); ?>">
                                        <div class="selectable-card p-3 d-flex align-items-start gap-3 h-100">
                                            <input type="checkbox" name="symptoms[]" value="<?= $sym['id']; ?>" id="sym_<?= $sym['id']; ?>" class="form-check-input mt-1">
                                            <label for="sym_<?= $sym['id']; ?>" class="mb-0 flex-grow-1 cursor-pointer">
                                                <div class="fw-semibold text-dark small mb-1"><?= htmlspecialchars($sym['symptom_name']); ?></div>
                                                <?php if (!empty($sym['symptom_description'])): ?>
                                                    <div class="text-muted" style="font-size: 0.78rem; line-height: 1.3;"><?= htmlspecialchars($sym['symptom_description']); ?></div>
                                                <?php endif; ?>
                                                <span class="badge bg-light text-secondary border mt-2" style="font-size: 0.68rem;">
                                                    <?= htmlspecialchars($sym['category_name']); ?> (Bobot: <?= $sym['weight']; ?>)
                                                </span>
                                            </label>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <div class="col-12 text-center py-4 text-muted">
                                        Gejala tidak ditemukan di database. Menggunakan mode deteksi manual.
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- STEP 3: Adaptive Follow-up Questions -->
                        <div class="wizard-step-panel d-none" data-step="3">
                            <div class="d-flex align-items-center gap-2 mb-3">
                                <span class="badge bg-primary text-white rounded-circle p-2">3</span>
                                <h4 class="mb-0 fs-5 fw-bold">Pertanyaan Tambahan Adaptif</h4>
                            </div>
                            <p class="text-secondary small mb-4">
                                Mesin inferensi menyusun pertanyaan ini secara dinamis berdasarkan pilihan media dan gejala Anda untuk mempersempit lapisan model OSI:
                            </p>

                            <div class="d-flex flex-column gap-3">
                                <!-- Q1: Other Devices -->
                                <div class="card p-3 border bg-light">
                                    <label class="fw-semibold text-dark mb-2">1. Apakah perangkat lain (seperti HP rekan kerja atau laptop lain) mengalami masalah yang sama?</label>
                                    <div class="d-flex flex-column flex-sm-row gap-3">
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="adaptive_answers[other_devices]" id="other_yes" value="yes">
                                            <label class="form-check-label small" for="other_yes">Hanya perangkat saya yang bermasalah (perangkat lain normal)</label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="adaptive_answers[other_devices]" id="other_no" value="no">
                                            <label class="form-check-label small" for="other_no">Semua perangkat di jaringan ini juga bermasalah</label>
                                        </div>
                                    </div>
                                </div>

                                <!-- Q2: Ping Gateway -->
                                <div class="card p-3 border bg-light">
                                    <label class="fw-semibold text-dark mb-2">2. Apakah Anda berhasil melakukan ping ke Default Gateway lokal (misal: <code>ping 192.168.1.1</code>)?</label>
                                    <div class="d-flex flex-wrap gap-3">
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="adaptive_answers[ping_gateway]" id="gw_yes" value="yes">
                                            <label class="form-check-label small" for="gw_yes">Ya, Reply normal</label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="adaptive_answers[ping_gateway]" id="gw_no" value="no">
                                            <label class="form-check-label small" for="gw_no">Tidak, Request Timed Out (RTO) / Unreachable</label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="adaptive_answers[ping_gateway]" id="gw_unsure" value="unsure" checked>
                                            <label class="form-check-label small" for="gw_unsure">Belum mencoba / Tidak tahu</label>
                                        </div>
                                    </div>
                                </div>

                                <!-- Q3: Ping IP vs Domain (DNS) -->
                                <div class="card p-3 border bg-light" id="adaptiveQuestionDns">
                                    <label class="fw-semibold text-dark mb-2">3. Jika Anda menjalankan <code>ping 8.8.8.8</code> di Command Prompt/Terminal, apakah berhasil?</label>
                                    <div class="d-flex flex-wrap gap-3">
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="adaptive_answers[ping_ip_ok]" id="ping_ip_yes" value="yes">
                                            <label class="form-check-label small" for="ping_ip_yes">Ya, Reply 8.8.8.8 berhasil, tapi website via nama domain tidak terbuka</label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="adaptive_answers[ping_ip_ok]" id="ping_ip_no" value="no">
                                            <label class="form-check-label small" for="ping_ip_no">Tidak, semua ping ke internet gagal</label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="adaptive_answers[ping_ip_ok]" id="ping_ip_unsure" value="unsure" checked>
                                            <label class="form-check-label small" for="ping_ip_unsure">Tidak yakin</label>
                                        </div>
                                    </div>
                                </div>

                                <!-- Q4: LAN LED (Adaptive for LAN) -->
                                <div class="card p-3 border bg-light" id="adaptiveQuestionLan" style="display: none;">
                                    <label class="fw-semibold text-dark mb-2">4. Apakah lampu LED indikator pada port LAN komputer/router Anda menyala?</label>
                                    <div class="d-flex gap-4">
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="adaptive_answers[lan_led]" id="lan_on" value="on">
                                            <label class="form-check-label small" for="lan_on">Menyala / Berkedip hijau/oranye</label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="adaptive_answers[lan_led]" id="lan_off" value="off">
                                            <label class="form-check-label small" for="lan_off">Mati total / Tidak ada indikator</label>
                                        </div>
                                    </div>
                                </div>

                                <!-- Q5: WiFi Status (Adaptive for WiFi) -->
                                <div class="card p-3 border bg-light" id="adaptiveQuestionWifi" style="display: none;">
                                    <label class="fw-semibold text-dark mb-2">4. Bagaimana status nama sinyal WiFi pada perangkat Anda?</label>
                                    <div class="d-flex gap-4">
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="adaptive_answers[wifi_status]" id="wifi_connected" value="connected">
                                            <label class="form-check-label small" for="wifi_connected">Terhubung, tetapi 'No Internet' / tanda seru</label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="adaptive_answers[wifi_status]" id="wifi_missing" value="missing">
                                            <label class="form-check-label small" for="wifi_missing">Nama WiFi (SSID) hilang atau sinyal drop acak</label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- STEP 4: Custom Description (Keyword Matching Engine) -->
                        <div class="wizard-step-panel d-none" data-step="4">
                            <div class="d-flex align-items-center justify-content-between mb-3">
                                <div class="d-flex align-items-center gap-2">
                                    <span class="badge bg-primary text-white rounded-circle p-2">4</span>
                                    <h4 class="mb-0 fs-5 fw-bold">Jelaskan Masalah Anda dalam Kata-Kata Sendiri (Opsional)</h4>
                                </div>
                                <span class="badge bg-primary-subtle text-primary border border-primary-subtle font-monospace">
                                    <i class="bi bi-search me-1"></i> Keyword Matching Engine
                                </span>
                            </div>
                            <p class="text-secondary small mb-3">
                                Jika masalah Anda memiliki detail tambahan atau tidak tercantum di daftar pilihan, tuliskan keluhan Anda di bawah ini. Mesin klasifikasi akan mendeteksi kata kunci teknis secara otomatis:
                            </p>

                            <div class="mb-3">
                                <textarea name="custom_text" id="customIssueText" class="form-control" rows="4" placeholder="Contoh: Lampu LAN mati setelah kabel tersenggol, atau bisa ping ke 8.8.8.8 tetapi browser menampilkan error DNS NXDOMAIN..."></textarea>
                            </div>

                            <!-- Live Keyword Detection Preview -->
                            <div id="detectedKeywordsBox" class="p-3 bg-light rounded-3 border mb-3 d-none">
                                <div class="small fw-bold text-dark mb-1"><i class="bi bi-tags-fill text-primary me-1"></i> Kata Kunci Teknis yang Terdeteksi:</div>
                                <div id="detectedKeywordsList" class="d-flex flex-wrap gap-1"></div>
                            </div>

                            <div class="p-3 bg-light rounded-3 border small text-muted">
                                <i class="bi bi-shield-check me-1 text-success"></i> <strong>Jaminan Diagnostik:</strong> Mesin inferensi mengevaluasi tipe media, bobot gejala, kondisi aturan majemuk, serta kata kunci teknis secara terukur.
                            </div>
                        </div>

                        <!-- Wizard Footer Navigation Buttons -->
                        <div class="d-flex align-items-center justify-content-between border-top pt-4 mt-4">
                            <button type="button" id="btnWizardPrev" class="btn btn-nt-outline d-none">
                                <i class="bi bi-arrow-left me-1"></i> Sebelumnya
                            </button>
                            <div></div>
                            <div>
                                <button type="button" id="btnWizardNext" class="btn btn-nt-primary">
                                    Lanjutkan <i class="bi bi-arrow-right ms-1"></i>
                                </button>
                                <button type="submit" id="btnWizardSubmit" class="btn btn-success d-none px-4 shadow">
                                    <i class="bi bi-play-circle-fill me-1"></i> Jalankan Diagnosis Jaringan
                                </button>
                            </div>
                        </div>

                    </form>
                </div>

            </div>
        </div>
    </div>
</div>

<script src="<?= base_url('assets/js/diagnosis-wizard.js'); ?>"></script>

<?php
require_once __DIR__ . '/includes/footer.php';
?>
