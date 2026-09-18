<?php
/**
 * NetworkTrouble - Admin User Management & Role-Based Access Control (RBAC)
 */
$adminTitle = 'Kelola Pengguna Admin';
require_once __DIR__ . '/includes/admin_header.php';
require_role(['super_admin']);

$db = get_db_connection();
$errors = [];

// Handle Delete Admin
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $delId = (int)$_GET['delete'];
    if ($delId === (int)$currentAdmin['id']) {
        set_flash('error', 'Anda tidak dapat menghapus akun Anda sendiri saat sedang login.');
    } elseif ($db) {
        $stmt = $db->prepare("DELETE FROM admins WHERE id = ?");
        $stmt->execute([$delId]);
        log_activity($currentAdmin['id'], 'delete_admin', 'admins', $delId);
        set_flash('success', 'Akun administrator berhasil dihapus.');
    }
    header('Location: ' . base_url('admin/admins.php'));
    exit;
}

// Handle Add / Edit Admin
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_admin'])) {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Token CSRF tidak valid.';
    } else {
        $adminId  = (int)($_POST['admin_id'] ?? 0);
        $name     = sanitize($_POST['name'] ?? '');
        $username = sanitize($_POST['username'] ?? '');
        $email    = filter_var($_POST['email'] ?? '', FILTER_VALIDATE_EMAIL);
        $role     = in_array($_POST['role'] ?? '', ['super_admin', 'content_admin', 'technical_admin']) ? $_POST['role'] : 'technical_admin';
        $status   = in_array($_POST['status'] ?? '', ['active', 'inactive']) ? $_POST['status'] : 'active';
        $password = $_POST['password'] ?? '';

        if (empty($name) || empty($username) || !$email) {
            $errors[] = 'Nama lengkap, username, dan email valid wajib diisi.';
        } elseif ($adminId === 0 && empty($password)) {
            $errors[] = 'Password wajib diisi untuk administrator baru.';
        } elseif ($db) {
            try {
                if ($adminId > 0) {
                    // Update
                    if (!empty($password)) {
                        $hash = password_hash($password, PASSWORD_BCRYPT);
                        $stmt = $db->prepare("UPDATE admins SET name = ?, username = ?, email = ?, password = ?, role = ?, status = ? WHERE id = ?");
                        $stmt->execute([$name, $username, $email, $hash, $role, $status, $adminId]);
                    } else {
                        $stmt = $db->prepare("UPDATE admins SET name = ?, username = ?, email = ?, role = ?, status = ? WHERE id = ?");
                        $stmt->execute([$name, $username, $email, $role, $status, $adminId]);
                    }
                    log_activity($currentAdmin['id'], 'update_admin', 'admins', $adminId);
                    set_flash('success', 'Data administrator berhasil diperbarui.');
                } else {
                    // Insert
                    $hash = password_hash($password, PASSWORD_BCRYPT);
                    $stmt = $db->prepare("INSERT INTO admins (name, username, email, password, role, status, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())");
                    $stmt->execute([$name, $username, $email, $hash, $role, $status]);
                    log_activity($currentAdmin['id'], 'create_admin', 'admins', $db->lastInsertId());
                    set_flash('success', 'Administrator baru berhasil ditambahkan.');
                }
                header('Location: ' . base_url('admin/admins.php'));
                exit;
            } catch (PDOException $e) {
                $errors[] = 'Username atau Email sudah terdaftar.';
            }
        }
    }
}

// Fetch all admins
$adminList = [];
if ($db) {
    try {
        $adminList = $db->query("SELECT * FROM admins ORDER BY id ASC")->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {}
}
?>

<div class="admin-layout-wrapper">
    <?php include __DIR__ . '/includes/admin_sidebar.php'; ?>

    <div class="admin-main">
        <?php include __DIR__ . '/includes/admin_topbar.php'; ?>

        <div class="admin-content">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
                <div>
                    <h1 class="h3 fw-bold text-dark mb-1">Manajemen Pengguna Admin & Hak Akses (RBAC)</h1>
                    <p class="text-secondary mb-0">Kelola akun administrator dengan pembagian peran: Super Admin, Content Admin, dan Technical Admin.</p>
                </div>
                <button type="button" class="btn btn-primary btn-sm rounded-pill px-3" data-bs-toggle="modal" data-bs-target="#adminModal" onclick="openCreateAdminModal()">
                    <i class="bi bi-person-plus me-1"></i> Tambah Admin Baru
                </button>
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

            <!-- Table Card -->
            <div class="admin-card">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Administrator</th>
                                <th>Username</th>
                                <th>Email</th>
                                <th>Peran (RBAC)</th>
                                <th>Status</th>
                                <th>Login Terakhir</th>
                                <th class="text-end">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($adminList)): ?>
                                <?php foreach ($adminList as $a): ?>
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center gap-2">
                                            <span class="avatar-circle"><?= strtoupper(substr($a['name'], 0, 1)); ?></span>
                                            <div>
                                                <div class="fw-bold text-dark"><?= htmlspecialchars($a['name']); ?></div>
                                                <?php if ($a['id'] === $currentAdmin['id']): ?>
                                                    <span class="badge bg-primary-subtle text-primary" style="font-size: 0.7rem;">Anda (Sedang Login)</span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </td>
                                    <td><code class="text-dark"><?= htmlspecialchars($a['username']); ?></code></td>
                                    <td><small class="text-secondary"><?= htmlspecialchars($a['email']); ?></small></td>
                                    <td>
                                        <?php
                                        $rBadge = match($a['role']) {
                                            'super_admin' => 'bg-danger-subtle text-danger border border-danger-subtle',
                                            'content_admin' => 'bg-info-subtle text-info border border-info-subtle',
                                            default => 'bg-primary-subtle text-primary border border-primary-subtle'
                                        };
                                        $rName = match($a['role']) {
                                            'super_admin' => 'Super Admin',
                                            'content_admin' => 'Content Admin',
                                            default => 'Technical Admin'
                                        };
                                        ?>
                                        <span class="badge <?= $rBadge; ?>"><?= $rName; ?></span>
                                    </td>
                                    <td>
                                        <span class="badge bg-<?= $a['status'] === 'active' ? 'success' : 'secondary'; ?>-subtle text-<?= $a['status'] === 'active' ? 'success' : 'secondary'; ?>">
                                            <?= ucfirst($a['status']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <small class="text-muted"><?= $a['last_login'] ? date('d M Y, H:i', strtotime($a['last_login'])) : 'Belum pernah'; ?></small>
                                    </td>
                                    <td class="text-end">
                                        <button type="button" class="btn btn-outline-secondary btn-xs py-1 px-2"
                                                onclick='openEditAdminModal(<?= json_encode($a); ?>)'>
                                            <i class="bi bi-pencil me-1"></i> Edit
                                        </button>
                                        <?php if ($a['id'] !== $currentAdmin['id']): ?>
                                        <a href="<?= base_url('admin/admins.php?delete=' . $a['id']); ?>" class="btn btn-outline-danger btn-xs py-1 px-2 ms-1" onclick="return confirm('Hapus admin ini?')">
                                            <i class="bi bi-trash"></i>
                                        </a>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="7" class="text-center py-4 text-muted">Tidak ada admin terdaftar.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Modal Add / Edit Admin -->
            <div class="modal fade" id="adminModal" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                        <form method="POST" action="">
                            <input type="hidden" name="csrf_token" value="<?= generate_csrf_token(); ?>">
                            <input type="hidden" name="save_admin" value="1">
                            <input type="hidden" name="admin_id" id="modalAdminId" value="0">

                            <div class="modal-header">
                                <h5 class="modal-title fw-bold" id="modalAdminTitle">Tambah Admin Baru</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <div class="mb-3">
                                    <label class="form-label fw-semibold">Nama Lengkap</label>
                                    <input type="text" name="name" id="modalAdminName" class="form-control" required>
                                </div>
                                <div class="row g-3 mb-3">
                                    <div class="col-md-6">
                                        <label class="form-label fw-semibold">Username</label>
                                        <input type="text" name="username" id="modalAdminUsername" class="form-control" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-semibold">Alamat Email</label>
                                        <input type="email" name="email" id="modalAdminEmail" class="form-control" required>
                                    </div>
                                </div>
                                <div class="row g-3 mb-3">
                                    <div class="col-md-6">
                                        <label class="form-label fw-semibold">Peran (Role RBAC)</label>
                                        <select name="role" id="modalAdminRole" class="form-select">
                                            <option value="technical_admin">Technical Admin (Engine, Rules, Reports)</option>
                                            <option value="content_admin">Content Admin (CMS, Media, Settings)</option>
                                            <option value="super_admin">Super Admin (Akses Penuh)</option>
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-semibold">Status Akun</label>
                                        <select name="status" id="modalAdminStatus" class="form-select">
                                            <option value="active">Active</option>
                                            <option value="inactive">Inactive</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label fw-semibold">Password</label>
                                    <input type="password" name="password" id="modalAdminPass" class="form-control" placeholder="Kosongkan jika tidak ingin mengubah password">
                                    <div class="form-text small" id="passHelp">Minimal 6 karakter kombinasi huruf dan angka.</div>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                                <button type="submit" class="btn btn-primary fw-semibold px-4">Simpan Admin</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <script>
            function openCreateAdminModal() {
                document.getElementById('modalAdminTitle').textContent = 'Tambah Admin Baru';
                document.getElementById('modalAdminId').value = '0';
                document.getElementById('modalAdminName').value = '';
                document.getElementById('modalAdminUsername').value = '';
                document.getElementById('modalAdminEmail').value = '';
                document.getElementById('modalAdminRole').value = 'technical_admin';
                document.getElementById('modalAdminStatus').value = 'active';
                document.getElementById('modalAdminPass').required = true;
                document.getElementById('passHelp').textContent = 'Wajib diisi untuk akun baru.';
            }

            function openEditAdminModal(a) {
                document.getElementById('modalAdminTitle').textContent = 'Edit Data Administrator';
                document.getElementById('modalAdminId').value = a.id;
                document.getElementById('modalAdminName').value = a.name || '';
                document.getElementById('modalAdminUsername').value = a.username || '';
                document.getElementById('modalAdminEmail').value = a.email || '';
                document.getElementById('modalAdminRole').value = a.role || 'technical_admin';
                document.getElementById('modalAdminStatus').value = a.status || 'active';
                document.getElementById('modalAdminPass').required = false;
                document.getElementById('passHelp').textContent = 'Kosongkan jika tidak ingin mengganti password lama.';

                const modal = new bootstrap.Modal(document.getElementById('adminModal'));
                modal.show();
            }
            </script>

<?php require_once __DIR__ . '/includes/admin_footer.php'; ?>
