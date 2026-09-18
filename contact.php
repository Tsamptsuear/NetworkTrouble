<?php
/**
 * NetworkTrouble - Contact & IT Support
 */
require_once __DIR__ . '/config/app.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

$pageTitle = "Kontak IT Support & Laboratorium";
$pageDesc = "Hubungi tim Network Operations Center (NOC) atau kirimkan tiket bantuan jaringan komputer.";

$contact = get_contact_info();
$db = get_db();
$successMsg = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $senderName = sanitize($_POST['name'] ?? '');
    $senderEmail = sanitize($_POST['email'] ?? '');
    $location = sanitize($_POST['location'] ?? '');
    $problemDesc = sanitize($_POST['message'] ?? '');

    if (!empty($senderName) && !empty($problemDesc)) {
        if ($db) {
            try {
                $fullDesc = "Dari: $senderName ($senderEmail) | Lokasi: $location\nKeluhan: $problemDesc";
                $stmt = $db->prepare("INSERT INTO unresolved_cases (user_description, predicted_category, case_status, created_at) VALUES (?, 'Inquiry via Contact Form', 'open', NOW())");
                $stmt->execute([$fullDesc]);
            } catch (Exception $e) {}
        }
        $successMsg = "Pesan Anda telah berhasil dikirim ke antrean tim IT Support NOC. Kami akan meninjau laporan Anda secepatnya.";
    }
}

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';
?>

<div class="py-5 bg-light border-bottom">
    <div class="container">
        <div class="row justify-content-center text-center">
            <div class="col-lg-8">
                <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-3 py-1 mb-2 rounded-pill font-monospace">
                    <i class="bi bi-headset me-1"></i> Bantuan & Eskalasi
                </span>
                <h1 class="h2 fw-bold text-dark mb-2">Kontak Tim IT Support NOC</h1>
                <p class="text-secondary mb-0">
                    Membutuhkan bantuan teknis di lokasi atau masalah jaringan belum terpecahkan melalui wizard mandiri? Hubungi kami langsung.
                </p>
            </div>
        </div>
    </div>
</div>

<div class="py-5">
    <div class="container">
        <div class="row g-5">
            <!-- Left Column: Support Form -->
            <div class="col-lg-7">
                <div class="nt-card p-4 p-md-5">
                    <h4 class="fw-bold text-dark mb-3">Kirim Laporan Gangguan Jaringan</h4>
                    <p class="text-secondary small mb-4">
                        Isi formulir di bawah ini untuk meneruskan keluhan jaringan komputer Anda langsung ke dashboard teknisi.
                    </p>

                    <?php if ($successMsg): ?>
                        <div class="alert alert-success d-flex align-items-center gap-2 mb-4" role="alert">
                            <i class="bi bi-check-circle-fill fs-5"></i>
                            <div><?= htmlspecialchars($successMsg); ?></div>
                        </div>
                    <?php endif; ?>

                    <form action="<?= base_url('contact.php'); ?>" method="POST">
                        <?= csrf_field(); ?>
                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label class="form-label small fw-semibold text-dark">Nama Lengkap *</label>
                                <input type="text" name="name" class="form-control" required placeholder="Contoh: Budi Santoso">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-semibold text-dark">Alamat Email *</label>
                                <input type="email" name="email" class="form-control" required placeholder="budi@campus.ac.id">
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label small fw-semibold text-dark">Lokasi / Ruang Laboratorium *</label>
                            <input type="text" name="location" class="form-control" placeholder="Contoh: Gedung B Lantai 2, Lab Komputer 3, Meja 12">
                        </div>

                        <div class="mb-4">
                            <label class="form-label small fw-semibold text-dark">Deskripsi Masalah & Gejala *</label>
                            <textarea name="message" class="form-control" rows="5" required placeholder="Jelaskan apa yang terjadi (apakah lampu indikator mati, website apa yang tidak bisa dibuka, apakah perangkat lain juga bermasalah)..."></textarea>
                        </div>

                        <button type="submit" class="btn btn-nt-primary px-4">
                            <i class="bi bi-send me-1"></i> Kirim Laporan ke NOC
                        </button>
                    </form>
                </div>
            </div>

            <!-- Right Column: Direct Info Cards -->
            <div class="col-lg-5">
                <div class="nt-card p-4 mb-4">
                    <h5 class="fw-bold text-dark mb-3">Informasi Operasional</h5>
                    <div class="d-flex flex-column gap-3 small">
                        <div class="d-flex align-items-start gap-3">
                            <div class="nt-icon-box nt-icon-primary flex-shrink-0" style="width: 40px; height: 40px; font-size: 1.1rem;">
                                <i class="bi bi-geo-alt-fill"></i>
                            </div>
                            <div>
                                <span class="fw-bold text-dark d-block">Alamat Lokasi NOC:</span>
                                <span class="text-secondary"><?= e($contact['address']); ?></span>
                            </div>
                        </div>

                        <div class="d-flex align-items-start gap-3">
                            <div class="nt-icon-box nt-icon-primary flex-shrink-0" style="width: 40px; height: 40px; font-size: 1.1rem;">
                                <i class="bi bi-clock-fill"></i>
                            </div>
                            <div>
                                <span class="fw-bold text-dark d-block">Jam Pelayanan:</span>
                                <span class="text-secondary"><?= e($contact['working_hours']); ?></span>
                            </div>
                        </div>

                        <div class="d-flex align-items-start gap-3">
                            <div class="nt-icon-box nt-icon-primary flex-shrink-0" style="width: 40px; height: 40px; font-size: 1.1rem;">
                                <i class="bi bi-telephone-fill"></i>
                            </div>
                            <div>
                                <span class="fw-bold text-dark d-block">Telepon Kantor:</span>
                                <span class="text-secondary"><?= e($contact['phone']); ?></span>
                            </div>
                        </div>

                        <div class="d-flex align-items-start gap-3">
                            <div class="nt-icon-box nt-icon-primary flex-shrink-0" style="width: 40px; height: 40px; font-size: 1.1rem;">
                                <i class="bi bi-envelope-fill"></i>
                            </div>
                            <div>
                                <span class="fw-bold text-dark d-block">Email Resmi:</span>
                                <span class="text-secondary"><?= e($contact['email']); ?></span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- WhatsApp Quick Action -->
                <div class="nt-card p-4 border-success-subtle bg-white">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <i class="bi bi-whatsapp text-success fs-4"></i>
                        <h6 class="fw-bold text-dark mb-0">Direct Chat WhatsApp</h6>
                    </div>
                    <p class="text-secondary small mb-3">
                        Untuk penanganan insiden darurat (misal: satu laboratorium tidak bisa ujian online), hubungi teknisi piket secara langsung:
                    </p>
                    <a href="https://wa.me/<?= preg_replace('/[^0-9]/', '', $contact['whatsapp']); ?>?text=Halo%20IT%20Support%20NOC,%20saya%20membutuhkan%20bantuan%20segera%20terkait%20jaringan." target="_blank" class="btn btn-success w-100">
                        <i class="bi bi-whatsapp me-1"></i> Buka WhatsApp Sekarang
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
require_once __DIR__ . '/includes/footer.php';
?>
