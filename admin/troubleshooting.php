<?php
/**
 * NetworkTrouble - Manage Troubleshooting Steps & Network Commands
 */
$adminTitle = 'Kelola Panduan Troubleshooting & CLI';
require_once __DIR__ . '/includes/admin_header.php';
require_role(['super_admin', 'technical_admin']);

$db = get_db_connection();
$errors = [];

// Handle Delete Step
if (isset($_GET['delete_step']) && is_numeric($_GET['delete_step'])) {
    $stepId = (int)$_GET['delete_step'];
    if ($db) {
        $stmt = $db->prepare("DELETE FROM troubleshooting_steps WHERE id = ?");
        $stmt->execute([$stepId]);
        log_activity($currentAdmin['id'] ?? null, 'delete_troubleshooting_step', 'troubleshooting_steps', $stepId);
        set_flash('success', 'Langkah troubleshooting berhasil dihapus.');
        header('Location: ' . base_url('admin/troubleshooting.php'));
        exit;
    }
}

// Handle Delete Command
if (isset($_GET['delete_cmd']) && is_numeric($_GET['delete_cmd'])) {
    $cmdId = (int)$_GET['delete_cmd'];
    if ($db) {
        $stmt = $db->prepare("DELETE FROM network_commands WHERE id = ?");
        $stmt->execute([$cmdId]);
        log_activity($currentAdmin['id'] ?? null, 'delete_network_command', 'network_commands', $cmdId);
        set_flash('success', 'Perintah jaringan berhasil dihapus.');
        header('Location: ' . base_url('admin/troubleshooting.php?tab=commands'));
        exit;
    }
}

// Handle Save Step
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_step'])) {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Token CSRF tidak valid.';
    } else {
        $stepId       = (int)($_POST['step_id'] ?? 0);
        $categoryId   = (int)($_POST['category_id'] ?? 0);
        $stepNumber   = (int)($_POST['step_number'] ?? 1);
        $title        = sanitize($_POST['title'] ?? '');
        $description  = $_POST['description'] ?? '';
        $command      = sanitize($_POST['command'] ?? '');
        $platform     = in_array($_POST['platform'] ?? '', ['windows', 'linux', 'both', 'gui']) ? $_POST['platform'] : 'both';
        $verification = sanitize($_POST['verification_question'] ?? '');

        if (empty($title) || $categoryId <= 0) {
            $errors[] = 'Judul langkah dan kategori masalah wajib diisi.';
        } elseif ($db) {
            try {
                if ($stepId > 0) {
                    $stmt = $db->prepare("
                        UPDATE troubleshooting_steps
                        SET category_id = ?, step_number = ?, title = ?, description = ?, command = ?, platform = ?, verification_question = ?
                        WHERE id = ?
                    ");
                    $stmt->execute([$categoryId, $stepNumber, $title, $description, $command, $platform, $verification, $stepId]);
                    log_activity($currentAdmin['id'] ?? null, 'update_troubleshooting_step', 'troubleshooting_steps', $stepId);
                    set_flash('success', 'Langkah berhasil diperbarui.');
                } else {
                    $stmt = $db->prepare("
                        INSERT INTO troubleshooting_steps (category_id, step_number, title, description, command, platform, verification_question, created_at)
                        VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
                    ");
                    $stmt->execute([$categoryId, $stepNumber, $title, $description, $command, $platform, $verification]);
                    log_activity($currentAdmin['id'] ?? null, 'create_troubleshooting_step', 'troubleshooting_steps', $db->lastInsertId());
                    set_flash('success', 'Langkah troubleshooting baru berhasil ditambahkan.');
                }
                header('Location: ' . base_url('admin/troubleshooting.php'));
                exit;
            } catch (PDOException $e) {
                $errors[] = 'Database error: ' . $e->getMessage();
            }
        }
    }
}

// Handle Save Command
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_command'])) {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Token CSRF tidak valid.';
    } else {
        $cmdId       = (int)($_POST['command_id'] ?? 0);
        $cmdName     = sanitize($_POST['cmd_name'] ?? '');
        $command     = sanitize($_POST['cmd_string'] ?? '');
        $platform    = in_array($_POST['cmd_platform'] ?? '', ['windows', 'linux', 'both']) ? $_POST['cmd_platform'] : 'windows';
        $description = $_POST['cmd_desc'] ?? '';
        $sample      = $_POST['cmd_sample'] ?? '';

        if (empty($cmdName) || empty($command)) {
            $errors[] = 'Nama perintah dan sintaks CLI wajib diisi.';
        } elseif ($db) {
            try {
                if ($cmdId > 0) {
                    $stmt = $db->prepare("
                        UPDATE network_commands
                        SET name = ?, command = ?, platform = ?, description = ?, output_sample = ?
                        WHERE id = ?
                    ");
                    $stmt->execute([$cmdName, $command, $platform, $description, $sample, $cmdId]);
                    log_activity($currentAdmin['id'] ?? null, 'update_network_command', 'network_commands', $cmdId);
                    set_flash('success', 'Perintah CLI berhasil diperbarui.');
                } else {
                    $stmt = $db->prepare("
                        INSERT INTO network_commands (name, command, platform, description, output_sample, created_at)
                        VALUES (?, ?, ?, ?, ?, NOW())
                    ");
                    $stmt->execute([$cmdName, $command, $platform, $description, $sample]);
                    log_activity($currentAdmin['id'] ?? null, 'create_network_command', 'network_commands', $db->lastInsertId());
                    set_flash('success', 'Perintah CLI baru berhasil ditambahkan.');
                }
                header('Location: ' . base_url('admin/troubleshooting.php?tab=commands'));
                exit;
            } catch (PDOException $e) {
                $errors[] = 'Database error: ' . $e->getMessage();
            }
        }
    }
}

// Fetch categories
$categories = [];
if ($db) {
    try {
        $categories = $db->query("SELECT id, name FROM network_categories ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {}
}

// Fetch Steps
$steps = [];
if ($db) {
    try {
        $steps = $db->query("
            SELECT ts.*, c.name AS category_name
            FROM troubleshooting_steps ts
            LEFT JOIN network_categories c ON ts.category_id = c.id
            ORDER BY ts.category_id ASC, ts.step_number ASC
        ")->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {}
}

// Fetch Commands
$commands = [];
if ($db) {
    try {
        $commands = $db->query("SELECT * FROM network_commands ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {}
}

$activeTab = $_GET['tab'] ?? 'steps';
?>

<div class="admin-layout-wrapper">
    <?php include __DIR__ . '/includes/admin_sidebar.php'; ?>

    <div class="admin-main">
        <?php include __DIR__ . '/includes/admin_topbar.php'; ?>

        <div class="admin-content">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
                <div>
                    <h1 class="h3 fw-bold text-dark mb-1">Manajemen Panduan Troubleshooting & CLI</h1>
                    <p class="text-secondary mb-0">Atur instruksi perbaikan bertahap (step-by-step) dan sintaks terminal diagnostik.</p>
                </div>
                <div>
                    <?php if ($activeTab === 'steps'): ?>
                    <button type="button" class="btn btn-primary btn-sm rounded-pill px-3" data-bs-toggle="modal" data-bs-target="#stepModal" onclick="openCreateStepModal()">
                        <i class="bi bi-plus-circle me-1"></i> Tambah Langkah Solusi
                    </button>
                    <?php else: ?>
                    <button type="button" class="btn btn-primary btn-sm rounded-pill px-3" data-bs-toggle="modal" data-bs-target="#commandModal" onclick="openCreateCommandModal()">
                        <i class="bi bi-plus-circle me-1"></i> Tambah Perintah CLI
                    </button>
                    <?php endif; ?>
                </div>
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

            <!-- Navigation Tabs -->
            <ul class="nav nav-pills mb-4">
                <li class="nav-item">
                    <a class="nav-link <?= $activeTab === 'steps' ? 'active' : ''; ?>" href="<?= base_url('admin/troubleshooting.php?tab=steps'); ?>">
                        <i class="bi bi-signpost-2 me-1"></i> Langkah Troubleshooting (<?= count($steps); ?>)
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= $activeTab === 'commands' ? 'active' : ''; ?>" href="<?= base_url('admin/troubleshooting.php?tab=commands'); ?>">
                        <i class="bi bi-terminal me-1"></i> Katalog Perintah Jaringan CLI (<?= count($commands); ?>)
                    </a>
                </li>
            </ul>

            <?php if ($activeTab === 'steps'): ?>
                <!-- Steps Table -->
                <div class="admin-card">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Kategori</th>
                                    <th>No</th>
                                    <th>Judul Langkah</th>
                                    <th>Perintah Terkait</th>
                                    <th>Platform</th>
                                    <th class="text-end">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($steps)): ?>
                                    <?php foreach ($steps as $st): ?>
                                    <tr>
                                        <td>
                                            <span class="badge bg-primary-subtle text-primary">
                                                <?= htmlspecialchars($st['category_name'] ?? 'General'); ?>
                                            </span>
                                        </td>
                                        <td><span class="badge bg-light text-dark border">#<?= $st['step_number']; ?></span></td>
                                        <td>
                                            <div class="fw-bold text-dark"><?= htmlspecialchars($st['title']); ?></div>
                                            <small class="text-muted"><?= htmlspecialchars(mb_strimwidth($st['description'] ?? '', 0, 70, '...')); ?></small>
                                        </td>
                                        <td>
                                            <?php if (!empty($st['command'])): ?>
                                                <code class="text-dark bg-light px-2 py-1 rounded border"><?= htmlspecialchars($st['command']); ?></code>
                                            <?php else: ?>
                                                <span class="text-muted small">-</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span class="badge bg-secondary-subtle text-secondary"><?= ucfirst($st['platform']); ?></span>
                                        </td>
                                        <td class="text-end">
                                            <button type="button" class="btn btn-outline-secondary btn-xs py-1 px-2"
                                                    onclick='openEditStepModal(<?= json_encode($st); ?>)'>
                                                <i class="bi bi-pencil me-1"></i> Edit
                                            </button>
                                            <a href="<?= base_url('admin/troubleshooting.php?delete_step=' . $st['id']); ?>" class="btn btn-outline-danger btn-xs py-1 px-2 ms-1" onclick="return confirm('Hapus langkah ini?')">
                                                <i class="bi bi-trash"></i>
                                            </a>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="6" class="text-center py-4 text-muted">Belum ada langkah tersimpan.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            <?php else: ?>
                <!-- Commands Table -->
                <div class="admin-card">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Nama Alat / Perintah</th>
                                    <th>Sintaks Perintah</th>
                                    <th>Platform</th>
                                    <th>Kegunaan</th>
                                    <th class="text-end">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($commands)): ?>
                                    <?php foreach ($commands as $cmd): ?>
                                    <tr>
                                        <td><div class="fw-bold text-dark"><?= htmlspecialchars($cmd['name']); ?></div></td>
                                        <td><code class="fw-bold text-primary"><?= htmlspecialchars($cmd['command']); ?></code></td>
                                        <td><span class="badge bg-secondary-subtle text-secondary"><?= ucfirst($cmd['platform']); ?></span></td>
                                        <td><small class="text-secondary"><?= htmlspecialchars($cmd['description']); ?></small></td>
                                        <td class="text-end">
                                            <button type="button" class="btn btn-outline-secondary btn-xs py-1 px-2"
                                                    onclick='openEditCommandModal(<?= json_encode($cmd); ?>)'>
                                                <i class="bi bi-pencil me-1"></i> Edit
                                            </button>
                                            <a href="<?= base_url('admin/troubleshooting.php?tab=commands&delete_cmd=' . $cmd['id']); ?>" class="btn btn-outline-danger btn-xs py-1 px-2 ms-1" onclick="return confirm('Hapus perintah ini?')">
                                                <i class="bi bi-trash"></i>
                                            </a>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="5" class="text-center py-4 text-muted">Belum ada perintah CLI terdaftar.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Modal Step -->
            <div class="modal fade" id="stepModal" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered modal-lg">
                    <div class="modal-content">
                        <form method="POST" action="">
                            <input type="hidden" name="csrf_token" value="<?= generate_csrf_token(); ?>">
                            <input type="hidden" name="save_step" value="1">
                            <input type="hidden" name="step_id" id="modalStepId" value="0">

                            <div class="modal-header">
                                <h5 class="modal-title fw-bold" id="modalStepTitle">Tambah Langkah Troubleshooting</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <div class="row g-3 mb-3">
                                    <div class="col-md-6">
                                        <label class="form-label fw-semibold">Kategori Masalah</label>
                                        <select name="category_id" id="modalStepCat" class="form-select" required>
                                            <option value="">-- Pilih Kategori --</option>
                                            <?php foreach ($categories as $cat): ?>
                                                <option value="<?= $cat['id']; ?>"><?= htmlspecialchars($cat['name']); ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label fw-semibold">Nomor Urut</label>
                                        <input type="number" name="step_number" id="modalStepNum" class="form-control" min="1" value="1" required>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label fw-semibold">Platform</label>
                                        <select name="platform" id="modalStepPlatform" class="form-select">
                                            <option value="both">Both (Semua)</option>
                                            <option value="windows">Windows</option>
                                            <option value="linux">Linux</option>
                                            <option value="gui">GUI / Router</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label fw-semibold">Judul Langkah Solusi</label>
                                    <input type="text" name="title" id="modalStepName" class="form-control" placeholder="Contoh: Periksa Lampu Indikator Link Port NIC" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label fw-semibold">Perintah CLI (Opsional)</label>
                                    <input type="text" name="command" id="modalStepCmd" class="form-control font-monospace" placeholder="ipconfig /flushdns">
                                </div>
                                <div class="mb-3">
                                    <label class="form-label fw-semibold">Instruksi & Penjelasan Rinci</label>
                                    <textarea name="description" id="modalStepDesc" class="form-control" rows="3" required></textarea>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label fw-semibold">Pertanyaan Verifikasi (Apakah berhasil?)</label>
                                    <input type="text" name="verification_question" id="modalStepVerif" class="form-control" placeholder="Apakah indikator port sudah menyala warna hijau?">
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                                <button type="submit" class="btn btn-primary fw-semibold px-4">Simpan Langkah</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Modal Command -->
            <div class="modal fade" id="commandModal" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                        <form method="POST" action="">
                            <input type="hidden" name="csrf_token" value="<?= generate_csrf_token(); ?>">
                            <input type="hidden" name="save_command" value="1">
                            <input type="hidden" name="command_id" id="modalCmdId" value="0">

                            <div class="modal-header">
                                <h5 class="modal-title fw-bold" id="modalCmdTitle">Tambah Perintah Jaringan CLI</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <div class="mb-3">
                                    <label class="form-label fw-semibold">Nama Perintah / Tool</label>
                                    <input type="text" name="cmd_name" id="modalCmdName" class="form-control" placeholder="Contoh: Ping Gateway" required>
                                </div>
                                <div class="row g-3 mb-3">
                                    <div class="col-md-7">
                                        <label class="form-label fw-semibold">Sintaks Terminal</label>
                                        <input type="text" name="cmd_string" id="modalCmdString" class="form-control font-monospace" placeholder="ping 192.168.1.1" required>
                                    </div>
                                    <div class="col-md-5">
                                        <label class="form-label fw-semibold">Platform</label>
                                        <select name="cmd_platform" id="modalCmdPlatform" class="form-select">
                                            <option value="windows">Windows</option>
                                            <option value="linux">Linux</option>
                                            <option value="both">Both</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label fw-semibold">Fungsi / Deskripsi</label>
                                    <textarea name="cmd_desc" id="modalCmdDesc" class="form-control" rows="2" placeholder="Menguji respon ICMP ke gateway..."></textarea>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label fw-semibold">Contoh Output Terminal (Opsional)</label>
                                    <textarea name="cmd_sample" id="modalCmdSample" class="form-control font-monospace" rows="3" placeholder="Reply from 192.168.1.1: bytes=32 time=1ms TTL=64"></textarea>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                                <button type="submit" class="btn btn-primary fw-semibold px-4">Simpan Perintah</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <script>
            function openCreateStepModal() {
                document.getElementById('modalStepTitle').textContent = 'Tambah Langkah Troubleshooting';
                document.getElementById('modalStepId').value = '0';
                document.getElementById('modalStepCat').value = '';
                document.getElementById('modalStepNum').value = '1';
                document.getElementById('modalStepPlatform').value = 'both';
                document.getElementById('modalStepName').value = '';
                document.getElementById('modalStepCmd').value = '';
                document.getElementById('modalStepDesc').value = '';
                document.getElementById('modalStepVerif').value = '';
            }

            function openEditStepModal(s) {
                document.getElementById('modalStepTitle').textContent = 'Edit Langkah';
                document.getElementById('modalStepId').value = s.id;
                document.getElementById('modalStepCat').value = s.category_id || '';
                document.getElementById('modalStepNum').value = s.step_number || '1';
                document.getElementById('modalStepPlatform').value = s.platform || 'both';
                document.getElementById('modalStepName').value = s.title || '';
                document.getElementById('modalStepCmd').value = s.command || '';
                document.getElementById('modalStepDesc').value = s.description || '';
                document.getElementById('modalStepVerif').value = s.verification_question || '';

                const modal = new bootstrap.Modal(document.getElementById('stepModal'));
                modal.show();
            }

            function openCreateCommandModal() {
                document.getElementById('modalCmdTitle').textContent = 'Tambah Perintah Jaringan CLI';
                document.getElementById('modalCmdId').value = '0';
                document.getElementById('modalCmdName').value = '';
                document.getElementById('modalCmdString').value = '';
                document.getElementById('modalCmdPlatform').value = 'windows';
                document.getElementById('modalCmdDesc').value = '';
                document.getElementById('modalCmdSample').value = '';
            }

            function openEditCommandModal(c) {
                document.getElementById('modalCmdTitle').textContent = 'Edit Perintah CLI';
                document.getElementById('modalCmdId').value = c.id;
                document.getElementById('modalCmdName').value = c.name || '';
                document.getElementById('modalCmdString').value = c.command || '';
                document.getElementById('modalCmdPlatform').value = c.platform || 'windows';
                document.getElementById('modalCmdDesc').value = c.description || '';
                document.getElementById('modalCmdSample').value = c.output_sample || '';

                const modal = new bootstrap.Modal(document.getElementById('commandModal'));
                modal.show();
            }
            </script>

<?php require_once __DIR__ . '/includes/admin_footer.php'; ?>
