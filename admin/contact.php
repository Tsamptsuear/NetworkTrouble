<?php
/**
 * NetworkTrouble - Manage Contact Information
 */
$adminTitle = 'Kelola Informasi Kontak';
require_once __DIR__ . '/includes/admin_header.php';
require_role(['super_admin', 'content_admin']);

$db = get_db_connection();
$errors = [];

// Handle update contact settings
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_contact'])) {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Token CSRF tidak valid.';
    } else {
        $contactName  = sanitize($_POST['contact_name'] ?? '');
        $phone        = sanitize($_POST['phone'] ?? '');
        $email        = sanitize($_POST['email'] ?? '');
        $whatsapp     = preg_replace('/[^0-9]/', '', $_POST['whatsapp'] ?? '');
        $workingHours = sanitize($_POST['working_hours'] ?? '');
        $address      = sanitize($_POST['address'] ?? '');

        if ($db) {
            try {
                // Ensure at least 1 record exists in contact_settings
                $count = (int)$db->query("SELECT COUNT(*) FROM contact_settings")->fetchColumn();
                if ($count === 0) {
                    $stmt = $db->prepare("
                        INSERT INTO contact_settings (contact_name, phone, email, whatsapp, working_hours, address)
                        VALUES (?, ?, ?, ?, ?, ?)
                    ");
                    $stmt->execute([$contactName, $phone, $email, $whatsapp, $workingHours, $address]);
                } else {
                    $stmt = $db->prepare("
                        UPDATE contact_settings
                        SET contact_name = ?, phone = ?, email = ?, whatsapp = ?, working_hours = ?, address = ?, updated_at = NOW()
                        LIMIT 1
                    ");
                    $stmt->execute([$contactName, $phone, $email, $whatsapp, $workingHours, $address]);
                }

                // Sync to site_settings as fallback for legacy readers
                $stmtSite = $db->prepare("
                    INSERT INTO site_settings (setting_key, setting_value, setting_type)
                    VALUES (?, ?, 'text')
                    ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value), updated_at = NOW()
                ");
                $stmtSite->execute(['contact_email', $email]);
                $stmtSite->execute(['contact_phone', $phone]);

                log_activity($currentAdmin['id'] ?? null, 'update_contact_info', 'contact_settings', 1);
                set_flash('success', 'Informasi kontak berhasil diperbarui!');
                header('Location: ' . base_url('admin/contact.php'));
                exit;
            } catch (PDOException $e) {
                $errors[] = 'Gagal menyimpan kontak: ' . $e->getMessage();
            }
        }
    }
}

// Fetch current contact settings
$contact = null;
if ($db) {
    try {
        $stmt = $db->query("SELECT * FROM contact_settings LIMIT 1");
        $contact = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {}
}

$contactName  = normalize_cms_text($contact['contact_name'] ?? 'IT Support & Network NOC');
$phone        = normalize_cms_text($contact['phone'] ?? '+62 812-3456-7890');
$email        = normalize_cms_text($contact['email'] ?? 'support@networktrouble.local');
$whatsapp     = normalize_cms_text($contact['whatsapp'] ?? '6281234567890');
$workingHours = normalize_cms_text($contact['working_hours'] ?? 'Senin - Jumat: 08:00 - 17:00 WIB');
$address      = normalize_cms_text($contact['address'] ?? "Gedung Lab Komputer & Jaringan, Lantai 2\nJl. Teknologi Informasi No. 42\nIndonesia");
?>

<div class="admin-layout-wrapper">
    <?php include __DIR__ . '/includes/admin_sidebar.php'; ?>

    <div class="admin-main">
        <?php include __DIR__ . '/includes/admin_topbar.php'; ?>

        <div class="admin-content">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
                <div>
                    <h1 class="h3 fw-bold text-dark mb-1">Informasi Kontak & Saluran Dukungan</h1>
                    <p class="text-secondary mb-0">Atur nomor hotline, WhatsApp Helpdesk, dan lokasi lab untuk halaman kontak publik.</p>
                </div>
                <a href="<?= base_url('contact.php'); ?>" target="_blank" class="btn btn-outline-secondary btn-sm rounded-pill px-3">
                    <i class="bi bi-box-arrow-up-right me-1"></i> Buka Halaman Kontak
                </a>
            </div>

            <?php
            $flash = get_flash();
            if ($flash):
            ?>
            <div class="alert alert-<?= $flash['type'] === 'error' ? 'danger' : $flash['type']; ?> alert-dismissible fade show shadow-sm" role="alert">
                <?= htmlspecialchars($flash['message']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php endif; ?>

            <?php if (!empty($errors)): ?>
            <div class="alert alert-danger shadow-sm">
                <ul class="mb-0 ps-3">
                    <?php foreach ($errors as $err): ?>
                        <li><?= htmlspecialchars($err); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <?php endif; ?>

            <div class="row g-4">
                <!-- Form Column -->
                <div class="col-lg-8">
                    <div class="admin-card">
                        <div class="admin-card-header">
                            <h6 class="fw-bold text-dark mb-0"><i class="bi bi-headset me-2 text-primary"></i>Rincian Kontak Helpdesk</h6>
                        </div>
                        <div class="admin-card-body">
                            <form method="POST" action="">
                                <input type="hidden" name="csrf_token" value="<?= generate_csrf_token(); ?>">
                                <input type="hidden" name="save_contact" value="1">

                                <div class="mb-3">
                                    <label class="form-label fw-semibold">Nama Divisi / Layanan</label>
                                    <input type="text" name="contact_name" class="form-control" value="<?= htmlspecialchars($contactName); ?>" required>
                                </div>

                                <div class="row g-3 mb-3">
                                    <div class="col-md-6">
                                        <label class="form-label fw-semibold">Nomor Telepon / Hotline</label>
                                        <div class="input-group">
                                            <span class="input-group-text"><i class="bi bi-telephone"></i></span>
                                            <input type="text" name="phone" class="form-control" value="<?= htmlspecialchars($phone); ?>" required>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-semibold">Nomor WhatsApp (Angka saja)</label>
                                        <div class="input-group">
                                            <span class="input-group-text"><i class="bi bi-whatsapp text-success"></i></span>
                                            <input type="text" name="whatsapp" class="form-control" value="<?= htmlspecialchars($whatsapp); ?>" placeholder="6281234567890" required>
                                        </div>
                                        <div class="form-text small">Awali dengan kode negara tanpa simbol +, contoh: 6281234567890.</div>
                                    </div>
                                </div>

                                <div class="row g-3 mb-3">
                                    <div class="col-md-6">
                                        <label class="form-label fw-semibold">Alamat Email Dukungan</label>
                                        <div class="input-group">
                                            <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                                            <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($email); ?>" required>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-semibold">Jam Operasional Layanan</label>
                                        <div class="input-group">
                                            <span class="input-group-text"><i class="bi bi-clock"></i></span>
                                            <input type="text" name="working_hours" class="form-control" value="<?= htmlspecialchars($workingHours); ?>" required>
                                        </div>
                                    </div>
                                </div>

                                <div class="mb-4">
                                    <label class="form-label fw-semibold">Alamat Fisik / Lokasi Lab Komputer</label>
                                    <textarea name="address" class="form-control" rows="3"><?= htmlspecialchars($address); ?></textarea>
                                </div>

                                <div class="d-flex justify-content-end">
                                    <button type="submit" class="btn btn-primary fw-semibold px-4">
                                        <i class="bi bi-save me-1"></i> Simpan Kontak
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Preview Column -->
                <div class="col-lg-4">
                    <div class="admin-card">
                        <div class="admin-card-header">
                            <h6 class="fw-bold text-dark mb-0"><i class="bi bi-eye me-2 text-primary"></i>Pratinjau Kartu Kontak</h6>
                        </div>
                        <div class="admin-card-body">
                            <div class="card border-0 bg-light p-3 rounded-3 mb-3">
                                <h6 class="fw-bold text-dark mb-1"><?= htmlspecialchars($contactName); ?></h6>
                                <p class="small text-secondary mb-3"><?= htmlspecialchars($workingHours); ?></p>

                                <div class="d-flex align-items-center gap-2 mb-2 text-dark small">
                                    <i class="bi bi-telephone text-primary"></i>
                                    <span><?= htmlspecialchars($phone); ?></span>
                                </div>
                                <div class="d-flex align-items-center gap-2 mb-2 text-dark small">
                                    <i class="bi bi-envelope text-primary"></i>
                                    <span><?= htmlspecialchars($email); ?></span>
                                </div>
                                <div class="d-flex align-items-start gap-2 text-dark small mb-3">
                                    <i class="bi bi-geo-alt text-primary mt-1"></i>
                                    <span><?= nl2br(htmlspecialchars($address)); ?></span>
                                </div>

                                <a href="https://wa.me/<?= htmlspecialchars($whatsapp); ?>?text=Halo%20Admin%20NetworkTrouble" target="_blank" class="btn btn-success btn-sm w-100 fw-semibold">
                                    <i class="bi bi-whatsapp me-1"></i> Hubungi WhatsApp
                                </a>
                            </div>
                            <small class="text-muted d-block text-center">Data di atas otomatis ditampilkan pada halaman `contact.php` publik.</small>
                        </div>
                    </div>
                </div>
            </div>

<?php require_once __DIR__ . '/includes/admin_footer.php'; ?>
