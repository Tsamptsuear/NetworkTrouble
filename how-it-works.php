<?php
/**
 * NetworkTrouble - How It Works Page
 */
require_once __DIR__ . '/config/app.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

$pageTitle = "Cara Kerja Sistem & Pemetaan OSI Layer";
$pageDesc = "Pahami algoritma inferensi, pemetaan 7 Lapisan OSI Model, dan sistem skoring confidence NetworkTrouble.";

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';
?>

<div class="py-5 bg-light border-bottom">
    <div class="container">
        <div class="row justify-content-center text-center">
            <div class="col-lg-8">
                <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-3 py-1 mb-2 rounded-pill font-monospace">
                    <i class="bi bi-diagram-3 me-1"></i> Metodologi & Algoritma
                </span>
                <h1 class="h2 fw-bold text-dark mb-2">Bagaimana NetworkTrouble Bekerja?</h1>
                <p class="text-secondary mb-0">
                    Kombinasi antara kerangka teoritis 7 Lapisan OSI Model, mesin inferensi rule-based berbobot, dan fallback keyword matching untuk menghasilkan rekomendasi yang objektif.
                </p>
            </div>
        </div>
    </div>
</div>

<div class="py-5">
    <div class="container">

        <!-- 1. DIAGNOSIS PIPELINE FLOW -->
        <div class="nt-card p-4 p-md-5 mb-5">
            <h3 class="h4 fw-bold text-dark mb-4 text-center">
                <i class="bi bi-arrow-right-circle text-primary me-2"></i> Alur Pemrosesan Diagnostik
            </h3>

            <div class="row g-3 text-center">
                <div class="col-md-2 col-6">
                    <div class="p-3 bg-light rounded-3 border h-100">
                        <div class="badge bg-primary mb-2">Tahap 1</div>
                        <h6 class="fw-bold mb-1">Input Gejala</h6>
                        <p class="text-muted small mb-0">Pilihan jenis koneksi, gejala utama, dan keluhan bebas.</p>
                    </div>
                </div>
                <div class="col-md-2 col-6">
                    <div class="p-3 bg-light rounded-3 border h-100">
                        <div class="badge bg-info text-dark mb-2">Tahap 2</div>
                        <h6 class="fw-bold mb-1">Uji Adaptif</h6>
                        <p class="text-muted small mb-0">Pertanyaan adaptif memisahkan layer lokal vs gateway vs internet.</p>
                    </div>
                </div>
                <div class="col-md-2 col-6">
                    <div class="p-3 bg-light rounded-3 border h-100">
                        <div class="badge bg-warning text-dark mb-2">Tahap 3</div>
                        <h6 class="fw-bold mb-1">Rule Engine</h6>
                        <p class="text-muted small mb-0">Pemberian bobot skor dan evaluasi aturan logika majemuk.</p>
                    </div>
                </div>
                <div class="col-md-2 col-6">
                    <div class="p-3 bg-light rounded-3 border h-100">
                        <div class="badge bg-success mb-2">Tahap 4</div>
                        <h6 class="fw-bold mb-1">Pemetaan OSI</h6>
                        <p class="text-muted small mb-0">Menentukan layer terdampak & menghitung confidence score.</p>
                    </div>
                </div>
                <div class="col-md-2 col-6">
                    <div class="p-3 bg-light rounded-3 border h-100">
                        <div class="badge bg-primary mb-2">Tahap 5</div>
                        <h6 class="fw-bold mb-1">Roadmap Aksi</h6>
                        <p class="text-muted small mb-0">Instruksi CLI (CMD/Linux) terverifikasi untuk perbaikan.</p>
                    </div>
                </div>
                <div class="col-md-2 col-6">
                    <div class="p-3 bg-light rounded-3 border h-100">
                        <div class="badge bg-dark mb-2">Tahap 6</div>
                        <h6 class="fw-bold mb-1">Feedback Loop</h6>
                        <p class="text-muted small mb-0">Konfirmasi berhasil atau catat unresolved case untuk admin.</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- 2. THE 7 OSI LAYERS IN TROUBLESHOOTING -->
        <div class="row g-4 align-items-center mb-5">
            <div class="col-lg-5">
                <span class="badge bg-primary-subtle text-primary border px-3 py-1 mb-2 rounded-pill font-monospace">
                    Model Konseptual
                </span>
                <h3 class="fw-bold text-dark mb-3">Mengapa Menggunakan Pendekatan OSI Layer?</h3>
                <p class="text-secondary small">
                    Saat jaringan mati, banyak teknisi pemula terjebak mencoba solusi acak seperti mengotak-atik setting DNS padahal kabel LAN tidak terpasang. Model OSI menyediakan pendekatan <strong>Bottom-Up</strong>:
                </p>
                <div class="d-flex flex-column gap-3 small">
                    <div class="d-flex align-items-start gap-2">
                        <i class="bi bi-1-circle-fill text-primary mt-1"></i>
                        <div><strong>Isolasi Masalah Cepat:</strong> Masalah pada Layer 1 membuat seluruh Layer di atasnya otomatis tidak berfungsi.</div>
                    </div>
                    <div class="d-flex align-items-start gap-2">
                        <i class="bi bi-2-circle-fill text-primary mt-1"></i>
                        <div><strong>Mencegah Konfigurasi Sia-sia:</strong> Tidak mengubah setting IP jika link wireless belum terhubung.</div>
                    </div>
                    <div class="d-flex align-items-start gap-2">
                        <i class="bi bi-3-circle-fill text-primary mt-1"></i>
                        <div><strong>Standar Komunikasi Tim:</strong> Memudahkan penyampaian laporan teknis ke sesama engineer atau NOC.</div>
                    </div>
                </div>
            </div>

            <div class="col-lg-7">
                <div class="d-flex flex-column gap-2">
                    <!-- Layer 7 -->
                    <div class="p-3 bg-white rounded-3 border d-flex align-items-center justify-content-between shadow-sm">
                        <div class="d-flex align-items-center gap-3">
                            <span class="badge bg-dark font-monospace">Layer 7</span>
                            <div>
                                <h6 class="mb-0 fw-bold">Application Layer</h6>
                                <span class="text-muted small">DNS, HTTP, HTTPS, DHCP, FTP, Web Browser</span>
                            </div>
                        </div>
                        <span class="badge bg-primary-subtle text-primary">Contoh: DNS NXDOMAIN</span>
                    </div>

                    <!-- Layer 4 -->
                    <div class="p-3 bg-white rounded-3 border d-flex align-items-center justify-content-between shadow-sm">
                        <div class="d-flex align-items-center gap-3">
                            <span class="badge bg-dark font-monospace">Layer 4</span>
                            <div>
                                <h6 class="mb-0 fw-bold">Transport Layer</h6>
                                <span class="text-muted small">TCP Handshake, UDP, Port 80/443/53, Firewall Filter</span>
                            </div>
                        </div>
                        <span class="badge bg-info-subtle text-info">Contoh: Connection Timed Out</span>
                    </div>

                    <!-- Layer 3 -->
                    <div class="p-3 bg-white rounded-3 border d-flex align-items-center justify-content-between shadow-sm">
                        <div class="d-flex align-items-center gap-3">
                            <span class="badge bg-dark font-monospace">Layer 3</span>
                            <div>
                                <h6 class="mb-0 fw-bold">Network Layer</h6>
                                <span class="text-muted small">IP Addressing, Subnetting, Default Gateway, ICMP Ping</span>
                            </div>
                        </div>
                        <span class="badge bg-warning-subtle text-warning-emphasis">Contoh: IP APIPA 169.254.x.x</span>
                    </div>

                    <!-- Layer 2 -->
                    <div class="p-3 bg-white rounded-3 border d-flex align-items-center justify-content-between shadow-sm">
                        <div class="d-flex align-items-center gap-3">
                            <span class="badge bg-dark font-monospace">Layer 2</span>
                            <div>
                                <h6 class="mb-0 fw-bold">Data Link Layer</h6>
                                <span class="text-muted small">Ethernet Frame, MAC Address, Switch L2, ARP Table</span>
                            </div>
                        </div>
                        <span class="badge bg-secondary-subtle text-secondary">Contoh: Duplex Mismatch</span>
                    </div>

                    <!-- Layer 1 -->
                    <div class="p-3 bg-white rounded-3 border d-flex align-items-center justify-content-between shadow-sm">
                        <div class="d-flex align-items-center gap-3">
                            <span class="badge bg-dark font-monospace">Layer 1</span>
                            <div>
                                <h6 class="mb-0 fw-bold">Physical Layer</h6>
                                <span class="text-muted small">Kabel UTP RJ45, Fiber Optic, Radio WiFi (RF), Transceiver</span>
                            </div>
                        </div>
                        <span class="badge bg-danger-subtle text-danger">Contoh: Kabel Putus / LED Mati</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- 3. CONFIDENCE SCORING LOGIC -->
        <div class="nt-card p-4 p-md-5">
            <h4 class="fw-bold text-dark mb-3">
                <i class="bi bi-shield-check text-success me-2"></i> Sistem Skoring & Penentuan Confidence
            </h4>
            <p class="text-secondary small mb-4">
                Sistem tidak sekadar menebak, melainkan menghitung rasio bobot gejala dan verifikasi kondisi. Tingkat kepastian diagnosis dibagi ke dalam 4 tingkatan:
            </p>

            <div class="row g-3">
                <div class="col-md-3 col-sm-6">
                    <div class="p-3 bg-success-subtle border border-success-subtle rounded-3 h-100">
                        <div class="fw-bold text-success mb-1">80% - 100%</div>
                        <div class="small fw-semibold text-dark mb-1">Diagnosis Kuat</div>
                        <p class="text-secondary small mb-0">Gejala sangat spesifik dan beberapa kondisi aturan saling mengonfirmasi (misal: Ping IP berhasil tapi nama domain gagal = 100% DNS issue).</p>
                    </div>
                </div>
                <div class="col-md-3 col-sm-6">
                    <div class="p-3 bg-primary-subtle border border-primary-subtle rounded-3 h-100">
                        <div class="fw-bold text-primary mb-1">60% - 79%</div>
                        <div class="small fw-semibold text-dark mb-1">Diagnosis Cukup Mungkin</div>
                        <p class="text-secondary small mb-0">Pola gejala mayoritas cocok dengan kategori tertentu, namun memerlukan verifikasi satu parameter tambahan.</p>
                    </div>
                </div>
                <div class="col-md-3 col-sm-6">
                    <div class="p-3 bg-warning-subtle border border-warning-subtle rounded-3 h-100">
                        <div class="fw-bold text-warning-emphasis mb-1">40% - 59%</div>
                        <div class="small fw-semibold text-dark mb-1">Perlu Pemeriksaan Tambahan</div>
                        <p class="text-secondary small mb-0">Gejala tumpang tindih antara dua layer (misal sinyal lambat bisa karena WiFi interferensi atau bandwidth ISP penuh).</p>
                    </div>
                </div>
                <div class="col-md-3 col-sm-6">
                    <div class="p-3 bg-secondary-subtle border border-secondary-subtle rounded-3 h-100">
                        <div class="fw-bold text-secondary mb-1">&lt; 40%</div>
                        <div class="small fw-semibold text-dark mb-1">Gejala Belum Cukup Jelas</div>
                        <p class="text-secondary small mb-0">Sistem mengalihkan ke kategori <em>Unknown Network Issue</em> untuk mencegah tindakan salah, dan mencatat kasus ke antrean admin.</p>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>

<?php
require_once __DIR__ . '/includes/footer.php';
?>
