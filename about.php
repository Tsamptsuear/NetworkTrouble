<?php
/**
 * NetworkTrouble - About Page
 * Penjelasan komprehensif tentang tujuan, masalah yang diselesaikan, manfaat,
 * sistem klasifikasi, pemetaan OSI Layer, dan troubleshooting berbasis pengetahuan.
 */
require_once __DIR__ . '/config/app.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

$pageTitle = "Tentang Aplikasi NetworkTrouble";
$pageDesc = "Pelajari tujuan, pemetaan OSI Layer, arsitektur klasifikasi, dan manfaat platform NetworkTrouble untuk mahasiswa serta teknisi jaringan.";

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';
?>

<!-- HEADER / HERO TENTANG KAMI -->
<section class="py-5 bg-light border-bottom">
    <div class="container">
        <div class="row justify-content-center text-center">
            <div class="col-lg-9">
                <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-3 py-1 mb-2 rounded-pill font-monospace">
                    <i class="bi bi-info-circle me-1"></i> Profil Platform & Filosofi Sistem
                </span>
                <h1 class="h2 fw-bold text-dark mb-3">Tentang NetworkTrouble</h1>
                <p class="text-secondary lead fs-6 mb-0">
                    Sistem pakar diagnostik dan edukasi jaringan komputer yang dirancang untuk mentransformasi kebiasaan coba-coba (trial & error) menjadi proses investigasi teknis yang terstruktur, berbasis data, dan terpetakan pada model OSI.
                </p>
            </div>
        </div>
    </div>
</section>

<!-- 6 PILAR UTAMA SESUAI SPESIFIKASI -->
<section class="py-5">
    <div class="container">

        <!-- 1. TUJUAN APLIKASI -->
        <div class="row g-4 align-items-center mb-5 pb-4 border-bottom">
            <div class="col-lg-6">
                <div class="d-inline-flex align-items-center gap-2 px-3 py-1 bg-primary-subtle text-primary rounded-pill small font-monospace mb-3">
                    <i class="bi bi-bullseye"></i> 01. Tujuan Aplikasi
                </div>
                <h2 class="h3 fw-bold text-dark mb-3">Membangun Disiplin Diagnostik yang Terukur</h2>
                <p class="text-secondary">
                    Tujuan utama <strong>NetworkTrouble</strong> adalah menyediakan sarana pendukung keputusan (decision support) sekaligus platform pembelajaran interaktif bagi siapapun yang berhadapan dengan insiden konektivitas jaringan komputer.
                </p>
                <p class="text-secondary mb-4">
                    Alih-alih langsung melakukan tindakan drastis seperti mereset router kantor atau menginstal ulang sistem operasi, NetworkTrouble menuntun pengguna untuk mengumpulkan fakta gejala, mengidentifikasi akar masalah, dan menerapkan langkah pemulihan secara proporsional.
                </p>
                <div class="row g-3">
                    <div class="col-sm-6">
                        <div class="p-3 bg-light rounded-3 border">
                            <div class="fw-bold text-dark mb-1"><i class="bi bi-check-circle-fill text-success me-1"></i> Standarisasi SOP</div>
                            <small class="text-muted">Prosedur penanganan insiden yang konsisten dan dapat direplikasi.</small>
                        </div>
                    </div>
                    <div class="col-sm-6">
                        <div class="p-3 bg-light rounded-3 border">
                            <div class="fw-bold text-dark mb-1"><i class="bi bi-mortarboard-fill text-primary me-1"></i> Edukasi Praktis</div>
                            <small class="text-muted">Menghubungkan kurikulum teori jaringan dengan realitas teknis lapangan.</small>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="nt-card p-4 p-md-5 bg-white border">
                    <h5 class="fw-bold text-dark mb-3"><i class="bi bi-diagram-3-fill text-primary me-2"></i>Visi Rekayasa Sistem</h5>
                    <p class="text-secondary small mb-3">
                        NetworkTrouble dibangun dengan prinsip modularitas tinggi:
                    </p>
                    <ul class="list-unstyled d-flex flex-column gap-3 small mb-0">
                        <li class="d-flex align-items-start gap-2">
                            <i class="bi bi-check2-circle text-primary mt-1"></i>
                            <div><strong>Transparansi Keputusan:</strong> Pengguna mengetahui alasan mengapa suatu kategori dipilih berdasarkan bobot gejala.</div>
                        </li>
                        <li class="d-flex align-items-start gap-2">
                            <i class="bi bi-check2-circle text-primary mt-1"></i>
                            <div><strong>Platform Agnostik:</strong> Mendukung verifikasi perintah sistem operasi Windows (Command Prompt/PowerShell) dan Linux (Bash).</div>
                        </li>
                        <li class="d-flex align-items-start gap-2">
                            <i class="bi bi-check2-circle text-primary mt-1"></i>
                            <div><strong>Penyimpanan Jejak Kasus:</strong> Menyediakan riwayat sesi untuk memudahkan pelaporan eskalasi ke tim NOC senior.</div>
                        </li>
                    </ul>
                </div>
            </div>
        </div>

        <!-- 2. MASALAH YANG DISELESAIKAN -->
        <div class="row g-4 align-items-center mb-5 pb-4 border-bottom">
            <div class="col-lg-6 order-lg-2">
                <div class="d-inline-flex align-items-center gap-2 px-3 py-1 bg-danger-subtle text-danger rounded-pill small font-monospace mb-3">
                    <i class="bi bi-shield-x"></i> 02. Masalah yang Diselesaikan
                </div>
                <h2 class="h3 fw-bold text-dark mb-3">Mengeliminasi Jebakan "Trial & Error"</h2>
                <p class="text-secondary">
                    Dalam pengelolaan jaringan skala laboratorium sekolah, perkantoran, atau jaringan rumahan, insiden konektivitas seringkali ditangani dengan asumsi keliru. Hal ini mengakibatkan pemborosan waktu dan potensi gangguan yang lebih luas.
                </p>
                <div class="vstack gap-3">
                    <div class="d-flex align-items-start gap-3 p-3 rounded-3 bg-light border">
                        <div class="nt-icon-box nt-icon-primary flex-shrink-0" style="width: 40px; height: 40px; font-size: 1.1rem;">
                            <i class="bi bi-clock-history"></i>
                        </div>
                        <div>
                            <h6 class="fw-bold text-dark mb-1">Downtime Berkepanjangan</h6>
                            <small class="text-secondary">Waktu terbuang untuk memeriksa komponen yang sebenarnya bekerja normal (misal memeriksa kabel fisik padahal terjadi kegagalan server DNS).</small>
                        </div>
                    </div>
                    <div class="d-flex align-items-start gap-3 p-3 rounded-3 bg-light border">
                        <div class="nt-icon-box nt-icon-warning flex-shrink-0" style="width: 40px; height: 40px; font-size: 1.1rem;">
                            <i class="bi bi-exclamation-triangle"></i>
                        </div>
                        <div>
                            <h6 class="fw-bold text-dark mb-1">Kerusakan Konfigurasi Lanjutan</h6>
                            <small class="text-secondary">Mengubah setting IP static, firewall, atau reset modem secara sembarangan seringkali justru menciptakan masalah baru.</small>
                        </div>
                    </div>
                    <div class="d-flex align-items-start gap-3 p-3 rounded-3 bg-light border">
                        <div class="nt-icon-box nt-icon-success flex-shrink-0" style="width: 40px; height: 40px; font-size: 1.1rem;">
                            <i class="bi bi-chat-left-quote"></i>
                        </div>
                        <div>
                            <h6 class="fw-bold text-dark mb-1">Keluhan Pengguna yang Tidak Spesifik</h6>
                            <small class="text-secondary">Keluhan seperti "internet mati" atau "laptop lemot" diubah oleh sistem menjadi parameter teknis yang dapat diuji.</small>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-6 order-lg-1">
                <div class="nt-card p-4 bg-white border text-center">
                    <div class="p-4 bg-light rounded-3 mb-3">
                        <span class="badge bg-secondary mb-2">Sebelum NetworkTrouble</span>
                        <div class="text-danger fw-bold fs-5 mb-1"><i class="bi bi-x-circle me-1"></i> Metode Tebak-Tebakan</div>
                        <p class="text-muted small mb-0">Cabut colok kabel &bull; Restart router sembarangan &bull; Ubah IP asal-asalan &bull; Frustrasi berjam-jam</p>
                    </div>
                    <div class="py-2"><i class="bi bi-arrow-down fs-4 text-primary"></i></div>
                    <div class="p-4 bg-primary-subtle rounded-3 border border-primary-subtle">
                        <span class="badge bg-primary mb-2">Bersama NetworkTrouble</span>
                        <div class="text-primary fw-bold fs-5 mb-1"><i class="bi bi-check-circle-fill me-1"></i> Investigasi Terstruktur</div>
                        <p class="text-dark small mb-0">Deteksi Gejala &bull; Pemetaan OSI Layer &bull; Verifikasi Baris Perintah &bull; Solusi Tepat Sasaran</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- 3. MANFAAT BAGI MAHASISWA & TEKNISI PEMULA -->
        <div class="row g-4 align-items-center mb-5 pb-4 border-bottom">
            <div class="col-lg-6">
                <div class="d-inline-flex align-items-center gap-2 px-3 py-1 bg-success-subtle text-success rounded-pill small font-monospace mb-3">
                    <i class="bi bi-mortarboard"></i> 03. Manfaat bagi Pembelajar
                </div>
                <h2 class="h3 fw-bold text-dark mb-3">Akselerator Kompetensi Mahasiswa & Teknisi Pemula</h2>
                <p class="text-secondary">
                    Bagi mahasiswa jurusan Teknik Informatika, Sistem Informasi, Teknik Komputer dan Jaringan (TKJ), serta staf helpdesk pemula, NetworkTrouble bertindak sebagai mentor digital interaktif.
                </p>
                <div class="row g-3">
                    <div class="col-12">
                        <div class="nt-card p-3 border">
                            <h6 class="fw-bold text-dark mb-1"><i class="bi bi-lightbulb text-warning me-2"></i>Korelasi Teori & Kenyataan Lapangan</h6>
                            <p class="text-secondary small mb-0">Memahami kapan sebuah materi kuliah (seperti subnetting, DNS resolver, atau ARP frame) secara riil memicu kegagalan di dunia nyata.</p>
                        </div>
                    </div>
                    <div class="col-12">
                        <div class="nt-card p-3 border">
                            <h6 class="fw-bold text-dark mb-1"><i class="bi bi-terminal text-primary me-2"></i>Penguasaan Command Line Interface (CLI)</h6>
                            <p class="text-secondary small mb-0">Setiap rekomendasi dilengkapi perintah riil seperti <code>ipconfig /release</code>, <code>nslookup</code>, <code>tracert</code>, atau <code>netstat -ano</code> lengkap dengan arti keluarannya.</p>
                        </div>
                    </div>
                    <div class="col-12">
                        <div class="nt-card p-3 border">
                            <h6 class="fw-bold text-dark mb-1"><i class="bi bi-award text-success me-2"></i>Kesiapan Sertifikasi Industri</h6>
                            <p class="text-secondary small mb-0">Pola pikir diagnostik bertingkat yang diajarkan sejalan dengan standar kurikulum sertifikasi internasional (Cisco CCNA, CompTIA Network+, MikroTik MTCNA).</p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="nt-card p-4 p-md-5 bg-dark text-white border-0 shadow-sm">
                    <div class="font-monospace text-warning small mb-2"><i class="bi bi-terminal-fill me-1"></i> Interactive Troubleshooting Practice</div>
                    <h5 class="fw-bold mb-3">Belajar Membaca Kode Status Jaringan</h5>
                    <div class="p-3 bg-black rounded font-monospace small text-light mb-3" style="line-height: 1.6;">
                        <span class="text-secondary">C:\&gt; ping 192.168.1.1</span><br>
                        <span class="text-danger">Request timed out.</span><br>
                        <span class="text-secondary">C:\&gt; ipconfig</span><br>
                        <span class="text-warning">IPv4 Address: 169.254.120.44</span><br>
                        <span class="text-info">&gt;&gt; Analisis NetworkTrouble:</span><br>
                        <span class="text-success">[Deteksi]: Masalah Layer 3 (APIPA / DHCP Lease Failure). Kabel fisik terhubung namun router tidak memberi respon DHCP.</span>
                    </div>
                    <p class="text-secondary small mb-0">
                        Platform membantu menerjemahkan status terminal di atas menjadi rencana aksi perbaikan tanpa rasa panik.
                    </p>
                </div>
            </div>
        </div>

        <!-- 4. PENJELASAN SISTEM KLASIFIKASI JARINGAN -->
        <div class="row g-4 align-items-center mb-5 pb-4 border-bottom">
            <div class="col-lg-6 order-lg-2">
                <div class="d-inline-flex align-items-center gap-2 px-3 py-1 bg-info-subtle text-primary rounded-pill small font-monospace mb-3">
                    <i class="bi bi-cpu"></i> 04. Sistem Klasifikasi Jaringan
                </div>
                <h2 class="h3 fw-bold text-dark mb-3">Arsitektur Inferensi Berbobot (Rule-Based Engine)</h2>
                <p class="text-secondary">
                    NetworkTrouble menerapkan mesin inferensi hibrida yang memadukan aturan logika berbobot (weighted rule scoring) dengan pencocokan kata kunci gejala teknis.
                </p>
                <div class="vstack gap-3 small">
                    <div class="p-3 bg-light rounded-3 border">
                        <span class="badge bg-primary font-monospace mb-1">Tahap 1: Ekstraksi Gejala</span>
                        <p class="text-secondary mb-0">Pengguna memilih media koneksi (LAN / WiFi) dan memilih kombinasi gejala spesifik yang sedang dialami dari daftar inventaris gejala terstruktur.</p>
                    </div>
                    <div class="p-3 bg-light rounded-3 border">
                        <span class="badge bg-dark font-monospace mb-1">Tahap 2: Akumulasi Skor Bobot</span>
                        <p class="text-secondary mb-0">Setiap gejala memiliki bobot prioritas (weight 10 s.d 40). Aturan klasifikasi (classification rules) mengevaluasi kombinasi gejala untuk menghitung skor confidence tingkat akurasi.</p>
                    </div>
                    <div class="p-3 bg-light rounded-3 border">
                        <span class="badge bg-success font-monospace mb-1">Tahap 3: AI-Assisted Keyword Fallback</span>
                        <p class="text-secondary mb-0">Jika keluhan dimasukkan lewat teks deskripsi bebas, sistem menjalankan tokenizer kata kunci (keyword extraction) yang dicocokkan dengan database simtoma teknis.</p>
                    </div>
                </div>
            </div>
            <div class="col-lg-6 order-lg-1">
                <div class="nt-card p-4 bg-white border">
                    <h5 class="fw-bold text-dark mb-3"><i class="bi bi-sliders text-primary me-2"></i>Komponen Mesin Inferensi</h5>
                    <div class="table-responsive">
                        <table class="table table-sm table-borderless align-middle small mb-0">
                            <tbody>
                                <tr class="border-bottom">
                                    <td class="fw-bold text-secondary" style="width: 40%;">Metode Utama</td>
                                    <td>Deterministic Weighted Rules</td>
                                </tr>
                                <tr class="border-bottom">
                                    <td class="fw-bold text-secondary">Basis Aturan</td>
                                    <td>20+ Aturan Logika Relasional MySQL</td>
                                </tr>
                                <tr class="border-bottom">
                                    <td class="fw-bold text-secondary">Kamus Kata Kunci</td>
                                    <td>35+ Keyword Jaringan Multibahasa</td>
                                </tr>
                                <tr class="border-bottom">
                                    <td class="fw-bold text-secondary">Penilaian Hasil</td>
                                    <td>Confidence Score (0% - 100%) & Severity Level</td>
                                </tr>
                                <tr>
                                    <td class="fw-bold text-secondary">Fallback Kasus</td>
                                    <td>Tersimpan di Unresolved Cases untuk Review NOC</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- 5. PEMETAAN OSI LAYER -->
        <div class="mb-5 pb-4 border-bottom">
            <div class="text-center max-w-700 mx-auto mb-4">
                <div class="d-inline-flex align-items-center gap-2 px-3 py-1 bg-warning-subtle text-dark rounded-pill small font-monospace mb-2">
                    <i class="bi bi-layers"></i> 05. Pemetaan OSI Layer
                </div>
                <h2 class="h3 fw-bold text-dark mb-2">9 Kategori Kerusakan dalam Model Lapisan OSI</h2>
                <p class="text-secondary small">
                    Seluruh anomali dikelompokkan berdasarkan lapisan arsitektur <em>Open Systems Interconnection</em> agar teknisi dapat melakukan isolasi masalah secara sistematis (Bottom-Up Approach).
                </p>
            </div>

            <div class="row g-3">
                <div class="col-md-4">
                    <div class="nt-card p-3 h-100 border">
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <span class="badge bg-primary">Layer 1</span>
                            <i class="bi bi-ethernet text-primary fs-5"></i>
                        </div>
                        <h6 class="fw-bold text-dark mb-1">Physical Layer Issue</h6>
                        <p class="text-secondary small mb-0">Kabel LAN putus/rusak, konektor RJ45 longgar, port switch mati, atau router mengalami panas berlebih (overheating).</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="nt-card p-3 h-100 border">
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <span class="badge bg-primary">Layer 2</span>
                            <i class="bi bi-diagram-2 text-primary fs-5"></i>
                        </div>
                        <h6 class="fw-bold text-dark mb-1">Data Link Layer Issue</h6>
                        <p class="text-secondary small mb-0">Switch loop, port speed/duplex negotiation mismatch (terkunci 10 Mbps), VLAN tagging error, atau framing error.</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="nt-card p-3 h-100 border">
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <span class="badge bg-primary">Layer 3</span>
                            <i class="bi bi-diagram-3 text-primary fs-5"></i>
                        </div>
                        <h6 class="fw-bold text-dark mb-1">Network Layer Issue</h6>
                        <p class="text-secondary small mb-0">Kegagalan sewa DHCP (IP APIPA 169.254.x.x), konflik IP statis antar perangkat, atau default gateway unreachable.</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="nt-card p-3 h-100 border">
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <span class="badge bg-primary">Layer 4</span>
                            <i class="bi bi-shield-check text-primary fs-5"></i>
                        </div>
                        <h6 class="fw-bold text-dark mb-1">Transport Layer Issue</h6>
                        <p class="text-secondary small mb-0">Pemblokiran port TCP/UDP oleh firewall lokal, TCP handshake timeout, atau packet connection refused pada layanan tertentu.</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="nt-card p-3 h-100 border">
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <span class="badge bg-primary">Layer 7</span>
                            <i class="bi bi-globe2 text-primary fs-5"></i>
                        </div>
                        <h6 class="fw-bold text-dark mb-1">Application Layer Issue</h6>
                        <p class="text-secondary small mb-0">DNS resolver gagal menerjemahkan nama host (NXDOMAIN), SSL certificate expired, kesalahan konfigurasi proxy, atau server web down.</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="nt-card p-3 h-100 border">
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <span class="badge bg-info text-dark">Wireless / PHY</span>
                            <i class="bi bi-wifi text-info fs-5"></i>
                        </div>
                        <h6 class="fw-bold text-dark mb-1">Wireless Issue</h6>
                        <p class="text-secondary small mb-0">Sinyal WiFi lemah (low RSSI), SSID tidak muncul, interferensi kanal frekuensi 2.4/5GHz, atau disconnect loop berulang.</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="nt-card p-3 h-100 border">
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <span class="badge bg-warning text-dark">Cross-Layer</span>
                            <i class="bi bi-speedometer2 text-warning fs-5"></i>
                        </div>
                        <h6 class="fw-bold text-dark mb-1">Performance Issue</h6>
                        <p class="text-secondary small mb-0">Jaringan lambat, packet loss tinggi, lonjakan jitter saat streaming/gaming, atau bottleneck utilisasi bandwidth berlebih.</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="nt-card p-3 h-100 border">
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <span class="badge bg-danger">Security</span>
                            <i class="bi bi-shield-exclamation text-danger fs-5"></i>
                        </div>
                        <h6 class="fw-bold text-dark mb-1">Security Issue</h6>
                        <p class="text-secondary small mb-0">Indikasi serangan ARP spoofing / man-in-the-middle, DHCP rogue liar, DNS hijacking, atau traffic flood anomali.</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="nt-card p-3 h-100 border">
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <span class="badge bg-secondary">Diagnostic</span>
                            <i class="bi bi-question-circle text-secondary fs-5"></i>
                        </div>
                        <h6 class="fw-bold text-dark mb-1">Unknown Network Issue</h6>
                        <p class="text-secondary small mb-0">Gejala kompleks lintas perangkat yang membutuhkan isolasi bertingkat dan inspeksi log paket Wireshark secara manual.</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- 6. TROUBLESHOOTING BERBASIS PENGETAHUAN -->
        <div class="row g-4 align-items-center mb-5">
            <div class="col-lg-6">
                <div class="d-inline-flex align-items-center gap-2 px-3 py-1 bg-indigo-subtle text-primary rounded-pill small font-monospace mb-3">
                    <i class="bi bi-journal-bookmark"></i> 06. Troubleshooting Berbasis Pengetahuan
                </div>
                <h2 class="h3 fw-bold text-dark mb-3">Dari Diagnosis Menuju Solusi Aksi Nyata</h2>
                <p class="text-secondary">
                    Mengetahui bahwa jaringan mengalami masalah DNS atau APIPA baru separuh perjalanan. Nilai sesungguhnya dari <strong>NetworkTrouble</strong> terletak pada penyediaan panduan resolusi berbasis pengetahuan (Knowledge-Based Troubleshooting).
                </p>
                <div class="vstack gap-3 small">
                    <div class="d-flex align-items-start gap-3">
                        <div class="nt-step-badge">1</div>
                        <div>
                            <strong>Perintah Baris Perintah Terverifikasi:</strong>
                            <p class="text-secondary mb-0">Menyertakan perintah CLI siap salin untuk platform Windows dan Linux lengkap dengan parameter yang dibutuhkan.</p>
                        </div>
                    </div>
                    <div class="d-flex align-items-start gap-3">
                        <div class="nt-step-badge">2</div>
                        <div>
                            <strong>Kuesioner Verifikasi Hasil (Verification Check):</strong>
                            <p class="text-secondary mb-0">Setiap langkah menyertakan pertanyaan konfirmasi (contoh: "Apakah lampu port LAN menyala?") untuk memastikan solusi berhasil sebelum lanjut.</p>
                        </div>
                    </div>
                    <div class="d-flex align-items-start gap-3">
                        <div class="nt-step-badge">3</div>
                        <div>
                            <strong>Pusat Artikel Teori Mendalam:</strong>
                            <p class="text-secondary mb-0">Terhubung langsung ke modul Knowledge Base sehingga pengguna dapat mempelajari latar belakang ilmiah di balik setiap kendala.</p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="nt-card p-4 p-md-5 bg-light border text-center">
                    <h5 class="fw-bold text-dark mb-2">Ingin Menguji Sistem Ini?</h5>
                    <p class="text-secondary small mb-4">
                        Jelajahi alur diagnosis sekarang atau pelajari artikel-artikel protokol jaringan di pusat pengetahuan kami.
                    </p>
                    <div class="d-flex flex-column flex-sm-row justify-content-center gap-3">
                        <a href="<?= base_url('diagnose.php'); ?>" class="btn btn-nt-primary px-4">
                            <i class="bi bi-play-circle-fill me-1"></i> Mulai Diagnosis
                        </a>
                        <a href="<?= base_url('knowledge.php'); ?>" class="btn btn-nt-outline px-4">
                            <i class="bi bi-book me-1"></i> Buka Knowledge Base
                        </a>
                    </div>
                </div>
            </div>
        </div>

    </div>
</section>

<?php
require_once __DIR__ . '/includes/footer.php';
?>
