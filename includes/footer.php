<?php
/**
 * NetworkTrouble - Public Site Footer
 */
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/functions.php';
$contact = get_contact_info();
?>
</main>

<footer class="nt-footer">
    <div class="container">
        <div class="row g-4">
            <!-- Brand & Description -->
            <div class="col-lg-4 col-md-6">
                <div class="d-flex align-items-center gap-2 mb-3">
                    <span class="nt-icon-box bg-primary text-white" style="width: 36px; height: 36px; font-size: 1.1rem;">
                        <i class="bi bi-hdd-network"></i>
                    </span>
                    <span class="fw-bold fs-5 text-white">Network<span class="text-primary-light">Trouble</span></span>
                </div>
                <p class="text-secondary small mb-3">
                    <?= e(get_setting('footer_about', 'Sistem diagnosis gangguan jaringan komputer berbasis pemetaan OSI Model, rule-based inference engine, dan AI-assisted analysis.')); ?>
                </p>
                <div class="d-flex gap-2 text-white">
                    <a href="https://wa.me/<?= preg_replace('/[^0-9]/', '', $contact['whatsapp']); ?>" target="_blank" class="btn btn-sm btn-outline-success text-white border-success">
                        <i class="bi bi-whatsapp me-1 text-success"></i> Hubungi NOC Support
                    </a>
                </div>
            </div>

            <!-- Quick Links -->
            <div class="col-lg-2 col-md-6">
                <h5>Eksplorasi</h5>
                <ul class="list-unstyled small d-flex flex-column gap-2">
                    <li><a href="<?= base_url(); ?>"><i class="bi bi-chevron-right me-1"></i> Beranda</a></li>
                    <li><a href="<?= base_url('diagnose.php'); ?>"><i class="bi bi-chevron-right me-1"></i> Mulai Diagnosis</a></li>
                    <li><a href="<?= base_url('how-it-works.php'); ?>"><i class="bi bi-chevron-right me-1"></i> Cara Kerja OSI</a></li>
                    <li><a href="<?= base_url('knowledge.php'); ?>"><i class="bi bi-chevron-right me-1"></i> Pusat Pengetahuan</a></li>
                    <li><a href="<?= base_url('troubleshooting.php'); ?>"><i class="bi bi-chevron-right me-1"></i> Panduan Langkah</a></li>
                </ul>
            </div>

            <!-- Categories -->
            <div class="col-lg-3 col-md-6">
                <h5>Lapisan Masalah</h5>
                <ul class="list-unstyled small d-flex flex-column gap-2">
                    <li><a href="<?= base_url('troubleshooting.php#physical'); ?>"><i class="bi bi-ethernet me-1 text-info"></i> Physical Layer (Kabel/Port)</a></li>
                    <li><a href="<?= base_url('troubleshooting.php#network'); ?>"><i class="bi bi-diagram-3 me-1 text-info"></i> Network Layer (IP/DHCP)</a></li>
                    <li><a href="<?= base_url('troubleshooting.php#application'); ?>"><i class="bi bi-globe2 me-1 text-info"></i> Application Layer (DNS/Web)</a></li>
                    <li><a href="<?= base_url('troubleshooting.php#wireless'); ?>"><i class="bi bi-wifi me-1 text-info"></i> Wireless Layer (WiFi/SSID)</a></li>
                    <li><a href="<?= base_url('troubleshooting.php#performance'); ?>"><i class="bi bi-speedometer2 me-1 text-info"></i> QoS & Latensi (Ping/RTO)</a></li>
                </ul>
            </div>

            <!-- Contact NOC -->
            <div class="col-lg-3 col-md-6">
                <h5>Kontak Laboratorium IT</h5>
                <ul class="list-unstyled small d-flex flex-column gap-2">
                    <li class="d-flex align-items-start gap-2">
                        <i class="bi bi-geo-alt-fill text-primary-light mt-1"></i>
                        <span><?= e($contact['address']); ?></span>
                    </li>
                    <li class="d-flex align-items-center gap-2">
                        <i class="bi bi-telephone-fill text-primary-light"></i>
                        <span><?= e($contact['phone']); ?></span>
                    </li>
                    <li class="d-flex align-items-center gap-2">
                        <i class="bi bi-envelope-fill text-primary-light"></i>
                        <span><?= e($contact['email']); ?></span>
                    </li>
                    <li class="d-flex align-items-center gap-2">
                        <i class="bi bi-clock-fill text-primary-light"></i>
                        <span><?= e($contact['working_hours']); ?></span>
                    </li>
                </ul>
            </div>
        </div>

        <div class="nt-footer-bottom d-flex flex-column flex-md-row align-items-center justify-content-between gap-2">
            <div>
                <?= e(get_setting('footer_copyright', '© 2026 NetworkTrouble. All rights reserved.')); ?>
            </div>
            <div class="d-flex gap-3">
                <a href="<?= base_url('about.php'); ?>">Tentang Aplikasi</a>
                <span class="text-secondary">•</span>
                <a href="<?= base_url('contact.php'); ?>">Bantuan IT</a>
                <span class="text-secondary">•</span>
                <a href="<?= base_url('admin/login.php'); ?>"><i class="bi bi-lock me-1"></i>Admin Portal</a>
            </div>
        </div>
    </div>
</footer>

<!-- Bootstrap 5 Bundle with Popper JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<!-- Custom JS -->
<script src="<?= base_url('assets/js/main.js'); ?>"></script>
</body>
</html>
