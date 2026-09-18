<?php
/**
 * NetworkTrouble - Media & Slider Manager
 * Manage uploaded assets and configure the homepage image slider carousel.
 */
$adminTitle = 'Media & Slider Manager';
require_once __DIR__ . '/includes/admin_header.php';
require_role(['super_admin', 'content_admin']);

$db = get_db_connection();
$errors = [];
$uploadDir = __DIR__ . '/../uploads/';

// Ensure upload directory exists
if (!is_dir($uploadDir)) {
    @mkdir($uploadDir, 0755, true);
}

// ----------------------------------------------------
// 1. Handle Slider Actions (Add, Edit, Toggle, Delete)
// ----------------------------------------------------

// Add to Slider from Media Library
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_slider') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Token CSRF tidak valid.';
    } else {
        $mediaId = (int)($_POST['media_id'] ?? 0);
        $title = sanitize($_POST['title'] ?? '');
        $caption = sanitize($_POST['caption'] ?? '');
        $buttonText = sanitize($_POST['button_text'] ?? 'Mulai Diagnosis');
        $buttonUrl = sanitize($_POST['button_url'] ?? 'diagnose.php');
        $order = (int)($_POST['display_order'] ?? 0);
        $isActive = isset($_POST['is_active']) ? 1 : 0;

        if ($mediaId > 0 && $db) {
            try {
                $stmt = $db->prepare("
                    INSERT INTO slider_items (media_id, title, caption, button_text, button_url, display_order, is_active, created_at)
                    VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
                ");
                $stmt->execute([$mediaId, $title, $caption, $buttonText, $buttonUrl, $order, $isActive]);

                log_activity($currentAdmin['id'] ?? null, 'add_slider_item', 'slider_items', $db->lastInsertId());
                set_flash('success', 'Slide berhasil ditambahkan ke carousel homepage!');
                header('Location: ' . base_url('admin/media.php?tab=sliders'));
                exit;
            } catch (PDOException $e) {
                $errors[] = 'Gagal menambahkan slide: ' . $e->getMessage();
            }
        } else {
            $errors[] = 'Pilih media gambar yang valid untuk dijadikan slider.';
        }
    }
}

// Edit Existing Slider Item
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'edit_slider') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Token CSRF tidak valid.';
    } else {
        $sliderId = (int)($_POST['slider_id'] ?? 0);
        $title = sanitize($_POST['title'] ?? '');
        $caption = sanitize($_POST['caption'] ?? '');
        $buttonText = sanitize($_POST['button_text'] ?? '');
        $buttonUrl = sanitize($_POST['button_url'] ?? '');
        $order = (int)($_POST['display_order'] ?? 0);
        $isActive = isset($_POST['is_active']) ? 1 : 0;

        if ($sliderId > 0 && $db) {
            try {
                $stmt = $db->prepare("
                    UPDATE slider_items
                    SET title = ?, caption = ?, button_text = ?, button_url = ?, display_order = ?, is_active = ?, updated_at = NOW()
                    WHERE id = ?
                ");
                $stmt->execute([$title, $caption, $buttonText, $buttonUrl, $order, $isActive, $sliderId]);

                log_activity($currentAdmin['id'] ?? null, 'update_slider_item', 'slider_items', $sliderId);
                set_flash('success', 'Pengaturan slide berhasil diperbarui!');
                header('Location: ' . base_url('admin/media.php?tab=sliders'));
                exit;
            } catch (PDOException $e) {
                $errors[] = 'Gagal memperbarui slide: ' . $e->getMessage();
            }
        }
    }
}

// Toggle Slider Active/Inactive
if (isset($_GET['toggle_slider']) && is_numeric($_GET['toggle_slider'])) {
    $sliderId = (int)$_GET['toggle_slider'];
    if ($db) {
        try {
            $stmt = $db->prepare("UPDATE slider_items SET is_active = IF(is_active = 1, 0, 1), updated_at = NOW() WHERE id = ?");
            $stmt->execute([$sliderId]);
            set_flash('success', 'Status keaktifan slide berhasil diubah.');
            header('Location: ' . base_url('admin/media.php?tab=sliders'));
            exit;
        } catch (PDOException $e) {}
    }
}

// Delete Slider Item
if (isset($_GET['delete_slider']) && is_numeric($_GET['delete_slider'])) {
    $sliderId = (int)$_GET['delete_slider'];
    if ($db) {
        try {
            $stmt = $db->prepare("DELETE FROM slider_items WHERE id = ?");
            $stmt->execute([$sliderId]);
            log_activity($currentAdmin['id'] ?? null, 'delete_slider_item', 'slider_items', $sliderId);
            set_flash('success', 'Item slider berhasil dihapus dari carousel homepage.');
            header('Location: ' . base_url('admin/media.php?tab=sliders'));
            exit;
        } catch (PDOException $e) {}
    }
}

// ----------------------------------------------------
// 2. Handle Media Asset Upload & Delete
// ----------------------------------------------------

// Handle Delete Media Asset
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $mediaId = (int)$_GET['delete'];
    if ($db) {
        $stmt = $db->prepare("SELECT file_path, file_name FROM media_assets WHERE id = ?");
        $stmt->execute([$mediaId]);
        $asset = $stmt->fetch();

        if ($asset) {
            $filePath = __DIR__ . '/../' . ltrim($asset['file_path'], '/\\');
            if (file_exists($filePath)) {
                @unlink($filePath);
            }
            $delStmt = $db->prepare("DELETE FROM media_assets WHERE id = ?");
            $delStmt->execute([$mediaId]);

            log_activity($currentAdmin['id'] ?? null, 'delete_media', 'media_assets', $mediaId);
            set_flash('success', 'File media dan asosiasi slidernya berhasil dihapus.');
            header('Location: ' . base_url('admin/media.php?tab=media'));
            exit;
        }
    }
}

// Handle File Upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['upload_media'])) {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Token CSRF tidak valid.';
    } elseif (!isset($_FILES['media_file']) || $_FILES['media_file']['error'] !== UPLOAD_ERR_OK) {
        $errors[] = 'Silakan pilih file gambar yang valid untuk diunggah.';
    } else {
        $file = $_FILES['media_file'];
        $altText = sanitize($_POST['alt_text'] ?? '');
        $allowedExtensions = ['jpg', 'jpeg', 'png', 'webp', 'gif', 'svg'];
        $allowedMimes = ['image/jpeg', 'image/png', 'image/webp', 'image/gif', 'image/svg+xml'];

        $fileExt = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $fileMime = mime_content_type($file['tmp_name']);
        $maxSize = 5 * 1024 * 1024; // 5MB

        if (!in_array($fileExt, $allowedExtensions) || !in_array($fileMime, $allowedMimes)) {
            $errors[] = 'Format file tidak didukung. Hanya gambar JPG, PNG, WEBP, GIF, dan SVG yang diizinkan.';
        } elseif ($file['size'] > $maxSize) {
            $errors[] = 'Ukuran file terlalu besar (Maksimal 5MB).';
        } else {
            $safeBase = preg_replace('/[^a-zA-Z0-9_\-]/', '_', pathinfo($file['name'], PATHINFO_FILENAME));
            $newFileName = 'asset_' . time() . '_' . substr(bin2hex(random_bytes(4)), 0, 8) . '.' . $fileExt;
            $destination = $uploadDir . $newFileName;

            if (move_uploaded_file($file['tmp_name'], $destination)) {
                $relativePath = 'uploads/' . $newFileName;
                if ($db) {
                    $ins = $db->prepare("
                        INSERT INTO media_assets (file_name, file_path, file_type, file_size, alt_text, uploaded_by, created_at)
                        VALUES (?, ?, ?, ?, ?, ?, NOW())
                    ");
                    $ins->execute([
                        $file['name'],
                        $relativePath,
                        $fileMime,
                        $file['size'],
                        $altText ?: pathinfo($file['name'], PATHINFO_FILENAME),
                        $currentAdmin['id'] ?? null
                    ]);
                    $newMediaId = $db->lastInsertId();

                    log_activity($currentAdmin['id'] ?? null, 'upload_media', 'media_assets', $newMediaId);

                    // Optional: automatically add as slider if checkbox checked
                    if (isset($_POST['make_slider'])) {
                        $sTitle = sanitize($_POST['slider_title'] ?? pathinfo($file['name'], PATHINFO_FILENAME));
                        $sCaption = sanitize($_POST['slider_caption'] ?? '');
                        $sBtnText = sanitize($_POST['slider_btn_text'] ?? 'Mulai Diagnosis');
                        $sBtnUrl = sanitize($_POST['slider_btn_url'] ?? 'diagnose.php');

                        $stmtSlider = $db->prepare("
                            INSERT INTO slider_items (media_id, title, caption, button_text, button_url, display_order, is_active)
                            VALUES (?, ?, ?, ?, ?, 0, 1)
                        ");
                        $stmtSlider->execute([$newMediaId, $sTitle, $sCaption, $sBtnText, $sBtnUrl]);
                    }
                }

                set_flash('success', 'File gambar berhasil diunggah' . (isset($_POST['make_slider']) ? ' dan dijadikan slider!' : '!'));
                header('Location: ' . base_url('admin/media.php?tab=' . (isset($_POST['make_slider']) ? 'sliders' : 'media')));
                exit;
            } else {
                $errors[] = 'Gagal memindahkan file ke direktori uploads.';
            }
        }
    }
}

// ----------------------------------------------------
// 3. Fetch Data for Views
// ----------------------------------------------------
$activeTab = $_GET['tab'] ?? 'sliders';

// Fetch Sliders
$sliderList = [];
if ($db) {
    try {
        $stmt = $db->query("
            SELECT s.*, ma.file_path, ma.file_name, ma.alt_text, ma.file_size
            FROM slider_items s
            JOIN media_assets ma ON s.media_id = ma.id
            ORDER BY s.display_order ASC, s.id ASC
        ");
        $sliderList = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($sliderList as &$sl) {
            foreach ($sl as $k => $v) {
                if (is_string($v)) {
                    $sl[$k] = normalize_cms_text($v);
                }
            }
        }
        unset($sl);
    } catch (PDOException $e) {}
}

// Fetch Media Assets
$mediaList = [];
if ($db) {
    try {
        $stmt = $db->query("SELECT * FROM media_assets ORDER BY created_at DESC");
        $mediaList = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {}
}
?>

<div class="admin-layout-wrapper">
    <?php include __DIR__ . '/includes/admin_sidebar.php'; ?>

    <div class="admin-main">
        <?php include __DIR__ . '/includes/admin_topbar.php'; ?>

        <div class="admin-content">
            <!-- Header & Action Buttons -->
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
                <div>
                    <h1 class="h3 fw-bold text-dark mb-1">Media & Slider Manager</h1>
                    <p class="text-secondary mb-0">Kelola gambar promosi banner slider homepage dan perpustakaan aset media.</p>
                </div>
                <div class="d-flex align-items-center gap-2 flex-wrap">
                    <button type="button" class="btn btn-outline-primary btn-sm rounded-pill px-3" data-bs-toggle="modal" data-bs-target="#previewSliderModal">
                        <i class="bi bi-eye-fill me-1"></i> Preview Slider
                    </button>
                    <button type="button" class="btn btn-primary btn-sm rounded-pill px-3" data-bs-toggle="modal" data-bs-target="#uploadModal">
                        <i class="bi bi-cloud-arrow-up me-1"></i> Unggah Gambar Baru
                    </button>
                </div>
            </div>

            <?php
            $flash = get_flash();
            if ($flash):
            ?>
            <div class="alert alert-<?= $flash['type'] === 'error' ? 'danger' : $flash['type']; ?> alert-dismissible fade show shadow-sm" role="alert">
                <i class="bi bi-check-circle-fill me-2"></i><?= htmlspecialchars($flash['message']); ?>
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

            <!-- Navigation Tabs -->
            <ul class="nav nav-pills mb-4 gap-2 border-bottom pb-3">
                <li class="nav-item">
                    <a class="nav-link <?= $activeTab === 'sliders' ? 'active fw-semibold' : 'bg-light text-dark'; ?>" href="?tab=sliders">
                        <i class="bi bi-sliders me-1"></i> Homepage Slider (<?= count($sliderList); ?>)
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= $activeTab === 'media' ? 'active fw-semibold' : 'bg-light text-dark'; ?>" href="?tab=media">
                        <i class="bi bi-images me-1"></i> Perpustakaan Media (<?= count($mediaList); ?>)
                    </a>
                </li>
            </ul>

            <!-- TAB 1: SLIDER MANAGER -->
            <?php if ($activeTab === 'sliders'): ?>
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="fw-bold text-dark mb-0">Daftar Banner Slider Aktif</h6>
                        <small class="text-muted">Urutan tampilan ditentukan oleh angka 'Urutan' (angka lebih kecil tampil lebih dulu).</small>
                    </div>
                    <button type="button" class="btn btn-sm btn-outline-success" data-bs-toggle="modal" data-bs-target="#addSliderFromMediaModal">
                        <i class="bi bi-plus-circle me-1"></i> Tambah Slide dari Media
                    </button>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th style="width: 70px;">Urutan</th>
                                <th style="width: 140px;">Gambar</th>
                                <th>Judul & Teks Slide</th>
                                <th>Tombol CTA</th>
                                <th style="width: 110px;">Status</th>
                                <th style="width: 130px;" class="text-end">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($sliderList)): ?>
                                <?php foreach ($sliderList as $s): ?>
                                <tr>
                                    <td>
                                        <span class="badge bg-secondary font-monospace fs-6 px-2 py-1"><?= $s['display_order']; ?></span>
                                    </td>
                                    <td>
                                        <div class="ratio ratio-16x9 rounded border overflow-hidden" style="width: 120px;">
                                            <img src="<?= base_url(htmlspecialchars($s['file_path'])); ?>" alt="<?= htmlspecialchars($s['alt_text'] ?? ''); ?>" class="object-fit-cover w-100 h-100">
                                        </div>
                                    </td>
                                    <td>
                                        <div class="fw-bold text-dark"><?= htmlspecialchars($s['title'] ?: '(Tanpa Judul)'); ?></div>
                                        <div class="text-muted small text-truncate" style="max-width: 320px;">
                                            <?= htmlspecialchars($s['caption'] ?: '-'); ?>
                                        </div>
                                        <div class="text-muted small font-monospace mt-1" style="font-size: 0.72rem;">
                                            <i class="bi bi-file-earmark-image me-1"></i><?= htmlspecialchars($s['file_name']); ?>
                                        </div>
                                    </td>
                                    <td>
                                        <?php if (!empty($s['button_text'])): ?>
                                            <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-2 py-1">
                                                <?= htmlspecialchars($s['button_text']); ?>
                                            </span>
                                            <div class="text-muted small font-monospace mt-1" style="font-size: 0.72rem;">
                                                <?= htmlspecialchars($s['button_url']); ?>
                                            </div>
                                        <?php else: ?>
                                            <span class="text-muted small">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <a href="?toggle_slider=<?= $s['id']; ?>" class="badge text-decoration-none <?= $s['is_active'] ? 'bg-success text-white' : 'bg-secondary text-white'; ?>" title="Klik untuk mengubah status">
                                            <?= $s['is_active'] ? '<i class="bi bi-check-circle me-1"></i>Aktif' : '<i class="bi bi-dash-circle me-1"></i>Nonaktif'; ?>
                                        </a>
                                    </td>
                                    <td class="text-end">
                                        <button type="button" class="btn btn-outline-primary btn-sm btn-edit-slider" 
                                            data-id="<?= $s['id']; ?>"
                                            data-title="<?= htmlspecialchars($s['title'] ?? ''); ?>"
                                            data-caption="<?= htmlspecialchars($s['caption'] ?? ''); ?>"
                                            data-btn-text="<?= htmlspecialchars($s['button_text'] ?? ''); ?>"
                                            data-btn-url="<?= htmlspecialchars($s['button_url'] ?? ''); ?>"
                                            data-order="<?= $s['display_order']; ?>"
                                            data-active="<?= $s['is_active']; ?>"
                                            data-img="<?= base_url(htmlspecialchars($s['file_path'])); ?>">
                                            <i class="bi bi-pencil-square"></i>
                                        </button>
                                        <a href="?delete_slider=<?= $s['id']; ?>" class="btn btn-outline-danger btn-sm" onclick="return confirm('Hapus slide ini dari carousel homepage?')" title="Hapus Slide">
                                            <i class="bi bi-trash"></i>
                                        </a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6" class="text-center py-5">
                                        <i class="bi bi-images text-muted fs-1 mb-2 d-block"></i>
                                        <h6 class="fw-bold text-dark">Belum Ada Slide Carousel Terdaftar</h6>
                                        <p class="text-secondary small mb-3">Tambahkan gambar dari perpustakaan media untuk menampilkan banner carousel di homepage.</p>
                                        <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#addSliderFromMediaModal">
                                            <i class="bi bi-plus-circle me-1"></i> Buat Slide Pertama
                                        </button>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php endif; ?>

            <!-- TAB 2: MEDIA LIBRARY -->
            <?php if ($activeTab === 'media'): ?>
            <div class="row g-4">
                <?php if (!empty($mediaList)): ?>
                    <?php foreach ($mediaList as $m): ?>
                    <div class="col-6 col-md-4 col-xl-3">
                        <div class="admin-card h-100 overflow-hidden d-flex flex-column">
                            <div class="ratio ratio-16x9 bg-light border-bottom position-relative">
                                <img src="<?= base_url(htmlspecialchars($m['file_path'])); ?>" alt="<?= htmlspecialchars($m['alt_text'] ?? ''); ?>" class="object-fit-cover w-100 h-100" loading="lazy">
                            </div>
                            <div class="p-3 d-flex flex-column justify-content-between flex-grow-1">
                                <div>
                                    <div class="fw-semibold text-truncate small text-dark mb-1" title="<?= htmlspecialchars($m['file_name']); ?>">
                                        <?= htmlspecialchars($m['file_name']); ?>
                                    </div>
                                    <div class="text-muted" style="font-size: 0.75rem;">
                                        <span><?= round(($m['file_size'] ?? 0) / 1024, 1); ?> KB</span> &bull; 
                                        <span><?= date('d M Y', strtotime($m['created_at'])); ?></span>
                                    </div>
                                </div>
                                <div class="d-flex flex-column gap-2 mt-3 pt-2 border-top">
                                    <button type="button" class="btn btn-outline-success btn-xs py-1 px-2 btn-quick-make-slider" data-id="<?= $m['id']; ?>" data-name="<?= htmlspecialchars($m['file_name']); ?>" data-img="<?= base_url(htmlspecialchars($m['file_path'])); ?>">
                                        <i class="bi bi-sliders me-1"></i> Jadikan Slider
                                    </button>
                                    <div class="d-flex justify-content-between align-items-center">
                                        <button type="button" class="btn btn-outline-secondary btn-xs py-1 px-2 copy-btn" data-url="<?= base_url(htmlspecialchars($m['file_path'])); ?>">
                                            <i class="bi bi-clipboard me-1"></i> Salin URL
                                        </button>
                                        <a href="<?= base_url('admin/media.php?delete=' . $m['id']); ?>" class="btn btn-outline-danger btn-xs py-1 px-2" onclick="return confirm('Hapus gambar ini beserta slide yang menggunakannya?')">
                                            <i class="bi bi-trash"></i>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="col-12">
                        <div class="admin-card text-center py-5">
                            <i class="bi bi-images text-muted fs-1 mb-3"></i>
                            <h5 class="text-dark fw-bold">Belum Ada Gambar Tersimpan</h5>
                            <p class="text-secondary">Gunakan tombol unggah di atas untuk menambahkan aset gambar baru.</p>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
            <?php endif; ?>

            <!-- ============================================== -->
            <!-- MODALS -->
            <!-- ============================================== -->

            <!-- 1. LIVE SLIDER PREVIEW MODAL -->
            <div class="modal fade" id="previewSliderModal" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-xl modal-dialog-centered">
                    <div class="modal-content border-0 shadow-lg">
                        <div class="modal-header bg-dark text-white">
                            <h5 class="modal-title fw-bold"><i class="bi bi-eye-fill me-2 text-primary"></i>Live Preview Homepage Carousel</h5>
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body p-0 bg-light">
                            <?php if (!empty($sliderList)): ?>
                            <div id="modalPreviewCarousel" class="carousel slide" data-bs-ride="carousel">
                                <div class="carousel-indicators">
                                    <?php foreach ($sliderList as $idx => $slide): ?>
                                    <button type="button" data-bs-target="#modalPreviewCarousel" data-bs-slide-to="<?= $idx; ?>" class="<?= $idx === 0 ? 'active' : ''; ?>" aria-current="<?= $idx === 0 ? 'true' : 'false'; ?>"></button>
                                    <?php endforeach; ?>
                                </div>
                                <div class="carousel-inner">
                                    <?php foreach ($sliderList as $idx => $slide): ?>
                                    <div class="carousel-item <?= $idx === 0 ? 'active' : ''; ?>">
                                        <div class="position-relative" style="height: 380px;">
                                            <img src="<?= base_url(htmlspecialchars($slide['file_path'])); ?>" class="d-block w-100 h-100 object-fit-cover" alt="<?= htmlspecialchars($slide['title'] ?? ''); ?>">
                                            <div class="position-absolute top-0 start-0 w-100 h-100" style="background: linear-gradient(180deg, rgba(15,23,42,0.2) 0%, rgba(15,23,42,0.85) 100%);"></div>
                                            <div class="carousel-caption text-start px-4" style="bottom: 2rem; z-index: 5;">
                                                <h3 class="fw-bold text-white mb-2"><?= htmlspecialchars($slide['title']); ?></h3>
                                                <p class="text-light opacity-75 mb-3" style="max-width: 600px;"><?= htmlspecialchars($slide['caption']); ?></p>
                                                <?php if (!empty($slide['button_text'])): ?>
                                                <span class="btn btn-primary btn-sm px-3 shadow">
                                                    <?= htmlspecialchars($slide['button_text']); ?> <i class="bi bi-arrow-right ms-1"></i>
                                                </span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                                <button class="carousel-control-prev" type="button" data-bs-target="#modalPreviewCarousel" data-bs-slide="prev">
                                    <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                                    <span class="visually-hidden">Previous</span>
                                </button>
                                <button class="carousel-control-next" type="button" data-bs-target="#modalPreviewCarousel" data-bs-slide="next">
                                    <span class="carousel-control-next-icon" aria-hidden="true"></span>
                                    <span class="visually-hidden">Next</span>
                                </button>
                            </div>
                            <?php else: ?>
                            <div class="p-5 text-center text-muted">
                                Belum ada slide aktif untuk ditampilkan.
                            </div>
                            <?php endif; ?>
                        </div>
                        <div class="modal-footer bg-white border-top">
                            <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Tutup Pratinjau</button>
                            <a href="<?= base_url(); ?>" target="_blank" class="btn btn-primary btn-sm">
                                <i class="bi bi-box-arrow-up-right me-1"></i> Buka Homepage
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 2. UPLOAD MODAL -->
            <div class="modal fade" id="uploadModal" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                        <form method="POST" action="" enctype="multipart/form-data">
                            <input type="hidden" name="csrf_token" value="<?= generate_csrf_token(); ?>">
                            <input type="hidden" name="upload_media" value="1">

                            <div class="modal-header">
                                <h5 class="modal-title fw-bold"><i class="bi bi-cloud-arrow-up me-2 text-primary"></i>Unggah Media Gambar</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <div class="mb-3">
                                    <label class="form-label fw-semibold">Pilih File (JPG, PNG, WEBP, SVG)</label>
                                    <input type="file" name="media_file" class="form-control" accept="image/*" required>
                                    <div class="form-text small">Maksimal ukuran file 5 MB. Gunakan rasio 16:9 untuk hasil banner optimal.</div>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label fw-semibold">Deskripsi / Alt Text</label>
                                    <input type="text" name="alt_text" class="form-control" placeholder="Contoh: Infrastruktur Jaringan NOC Campus">
                                </div>

                                <div class="card p-3 bg-light border">
                                    <div class="form-check form-switch mb-2">
                                        <input class="form-check-input" type="checkbox" role="switch" id="make_slider_check" name="make_slider" value="1">
                                        <label class="form-check-label fw-semibold" for="make_slider_check">Langsung Jadikan Slider Homepage</label>
                                    </div>
                                    <div id="slider_extra_fields" style="display: none;">
                                        <div class="mb-2">
                                            <label class="form-label small fw-semibold">Judul Slide</label>
                                            <input type="text" name="slider_title" class="form-control form-control-sm" placeholder="Contoh: Solusi Jaringan Cerdas">
                                        </div>
                                        <div class="mb-2">
                                            <label class="form-label small fw-semibold">Caption / Subtitle</label>
                                            <textarea name="slider_caption" class="form-control form-control-sm" rows="2" placeholder="Deskripsi singkat slide..."></textarea>
                                        </div>
                                        <div class="row g-2">
                                            <div class="col-6">
                                                <label class="form-label small fw-semibold">Teks Tombol</label>
                                                <input type="text" name="slider_btn_text" class="form-control form-control-sm" value="Mulai Diagnosis">
                                            </div>
                                            <div class="col-6">
                                                <label class="form-label small fw-semibold">URL Tombol</label>
                                                <input type="text" name="slider_btn_url" class="form-control form-control-sm" value="diagnose.php">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                                <button type="submit" class="btn btn-primary fw-semibold px-4">Unggah Sekarang</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- 3. ADD SLIDER FROM MEDIA MODAL -->
            <div class="modal fade" id="addSliderFromMediaModal" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                        <form method="POST" action="">
                            <input type="hidden" name="csrf_token" value="<?= generate_csrf_token(); ?>">
                            <input type="hidden" name="action" value="add_slider">

                            <div class="modal-header">
                                <h5 class="modal-title fw-bold"><i class="bi bi-sliders me-2 text-primary"></i>Tambah Slider dari Media Library</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <div class="mb-3">
                                    <label class="form-label fw-semibold">Pilih Gambar dari Media Library *</label>
                                    <select name="media_id" id="add_slider_media_select" class="form-select" required>
                                        <option value="">-- Pilih Salah Satu Gambar --</option>
                                        <?php foreach ($mediaList as $m): ?>
                                        <option value="<?= $m['id']; ?>"><?= htmlspecialchars($m['file_name']); ?> (<?= round(($m['file_size'] ?? 0) / 1024, 1); ?> KB)</option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label fw-semibold">Judul Banner Slider</label>
                                    <input type="text" name="title" id="add_slider_title" class="form-control" placeholder="Contoh: Diagnosis Gangguan Jaringan Cerdas">
                                </div>
                                <div class="mb-3">
                                    <label class="form-label fw-semibold">Subtitle / Caption Banner</label>
                                    <textarea name="caption" class="form-control" rows="2" placeholder="Penjelasan singkat banner..."></textarea>
                                </div>
                                <div class="row g-2 mb-3">
                                    <div class="col-6">
                                        <label class="form-label fw-semibold small">Label Tombol</label>
                                        <input type="text" name="button_text" class="form-control form-control-sm" value="Mulai Diagnosis">
                                    </div>
                                    <div class="col-6">
                                        <label class="form-label fw-semibold small">URL Tombol</label>
                                        <input type="text" name="button_url" class="form-control form-control-sm" value="diagnose.php">
                                    </div>
                                </div>
                                <div class="row g-2 align-items-center">
                                    <div class="col-6">
                                        <label class="form-label fw-semibold small">Urutan Tampilan</label>
                                        <input type="number" name="display_order" class="form-control form-control-sm" value="<?= count($sliderList) + 1; ?>">
                                    </div>
                                    <div class="col-6 pt-3">
                                        <div class="form-check form-switch">
                                            <input class="form-check-input" type="checkbox" role="switch" name="is_active" value="1" checked id="add_is_active">
                                            <label class="form-check-label fw-semibold small" for="add_is_active">Aktif Tampil</label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                                <button type="submit" class="btn btn-primary fw-semibold px-4">Simpan Slide</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- 4. EDIT SLIDER MODAL -->
            <div class="modal fade" id="editSliderModal" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                        <form method="POST" action="">
                            <input type="hidden" name="csrf_token" value="<?= generate_csrf_token(); ?>">
                            <input type="hidden" name="action" value="edit_slider">
                            <input type="hidden" name="slider_id" id="edit_slider_id">

                            <div class="modal-header">
                                <h5 class="modal-title fw-bold"><i class="bi bi-pencil-square me-2 text-primary"></i>Edit Pengaturan Slide</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <div class="mb-3 text-center">
                                    <img id="edit_slider_img_preview" src="" class="rounded border w-100 object-fit-cover" style="max-height: 180px;">
                                </div>
                                <div class="mb-3">
                                    <label class="form-label fw-semibold">Judul Banner Slide</label>
                                    <input type="text" name="title" id="edit_slider_title" class="form-control">
                                </div>
                                <div class="mb-3">
                                    <label class="form-label fw-semibold">Subtitle / Caption</label>
                                    <textarea name="caption" id="edit_slider_caption" class="form-control" rows="2"></textarea>
                                </div>
                                <div class="row g-2 mb-3">
                                    <div class="col-6">
                                        <label class="form-label fw-semibold small">Label Tombol</label>
                                        <input type="text" name="button_text" id="edit_slider_btn_text" class="form-control form-control-sm">
                                    </div>
                                    <div class="col-6">
                                        <label class="form-label fw-semibold small">URL Tombol</label>
                                        <input type="text" name="button_url" id="edit_slider_btn_url" class="form-control form-control-sm">
                                    </div>
                                </div>
                                <div class="row g-2 align-items-center">
                                    <div class="col-6">
                                        <label class="form-label fw-semibold small">Urutan Tampilan</label>
                                        <input type="number" name="display_order" id="edit_slider_order" class="form-control form-control-sm">
                                    </div>
                                    <div class="col-6 pt-3">
                                        <div class="form-check form-switch">
                                            <input class="form-check-input" type="checkbox" role="switch" name="is_active" value="1" id="edit_slider_is_active">
                                            <label class="form-check-label fw-semibold small" for="edit_slider_is_active">Aktif Tampil</label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                                <button type="submit" class="btn btn-primary fw-semibold px-4">Simpan Perubahan</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Page Scripts -->
            <script>
            document.addEventListener('DOMContentLoaded', function() {
                // Copy URL button
                document.querySelectorAll('.copy-btn').forEach(btn => {
                    btn.addEventListener('click', function() {
                        const url = this.getAttribute('data-url');
                        navigator.clipboard.writeText(url).then(() => {
                            const original = this.innerHTML;
                            this.innerHTML = '<i class="bi bi-check2 text-success"></i> Disalin!';
                            setTimeout(() => { this.innerHTML = original; }, 2000);
                        });
                    });
                });

                // Toggle extra slider inputs on upload modal
                const makeSliderCheck = document.getElementById('make_slider_check');
                const sliderExtra = document.getElementById('slider_extra_fields');
                if (makeSliderCheck && sliderExtra) {
                    makeSliderCheck.addEventListener('change', function() {
                        sliderExtra.style.display = this.checked ? 'block' : 'none';
                    });
                }

                // Quick Make Slider from media card
                document.querySelectorAll('.btn-quick-make-slider').forEach(btn => {
                    btn.addEventListener('click', function() {
                        const mediaId = this.getAttribute('data-id');
                        const mediaName = this.getAttribute('data-name');
                        const selectEl = document.getElementById('add_slider_media_select');
                        const titleEl = document.getElementById('add_slider_title');
                        if (selectEl) selectEl.value = mediaId;
                        if (titleEl) titleEl.value = mediaName.replace(/\.[^/.]+$/, "");

                        const modal = new bootstrap.Modal(document.getElementById('addSliderFromMediaModal'));
                        modal.show();
                    });
                });

                // Edit Slider Modal Population
                document.querySelectorAll('.btn-edit-slider').forEach(btn => {
                    btn.addEventListener('click', function() {
                        document.getElementById('edit_slider_id').value = this.getAttribute('data-id');
                        document.getElementById('edit_slider_title').value = this.getAttribute('data-title');
                        document.getElementById('edit_slider_caption').value = this.getAttribute('data-caption');
                        document.getElementById('edit_slider_btn_text').value = this.getAttribute('data-btn-text');
                        document.getElementById('edit_slider_btn_url').value = this.getAttribute('data-btn-url');
                        document.getElementById('edit_slider_order').value = this.getAttribute('data-order');
                        document.getElementById('edit_slider_is_active').checked = this.getAttribute('data-active') === '1';
                        document.getElementById('edit_slider_img_preview').src = this.getAttribute('data-img');

                        const modal = new bootstrap.Modal(document.getElementById('editSliderModal'));
                        modal.show();
                    });
                });
            });
            </script>

<?php require_once __DIR__ . '/includes/admin_footer.php'; ?>
