<?php
/**
 * NetworkTrouble - Admin Login Portal
 */
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

// If already logged in, redirect to admin index
if (is_admin_logged_in()) {
    header('Location: ' . base_url('admin/index.php'));
    exit;
}

$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrf = $_POST['csrf_token'] ?? '';
    if (!verify_csrf($csrf)) {
        $error = 'Token keamanan CSRF tidak valid. Silakan coba kembali.';
    } else {
        $username = trim($_POST['username'] ?? '');
        $password = trim($_POST['password'] ?? '');

        if (empty($username) || empty($password)) {
            $error = 'Harap isi username dan password.';
        } else {
            $loginRes = login_admin($username, $password);
            if ($loginRes['success']) {
                set_flash('success', 'Selamat datang kembali di Dashboard Administrator NetworkTrouble.');
                header('Location: ' . base_url('admin/index.php'));
                exit;
            } else {
                $error = $loginRes['message'];
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Administrator | NetworkTrouble</title>
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Poppins:wght@600;700&display=swap" rel="stylesheet">
    <!-- Bootstrap 5 & Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="<?= base_url('assets/css/style.css'); ?>">
</head>
<body class="bg-light d-flex align-items-center justify-content-center min-vh-100 py-5">

<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-5 col-lg-4">
            
            <div class="text-center mb-4">
                <a href="<?= base_url(); ?>" class="text-decoration-none d-inline-flex align-items-center gap-2 mb-2">
                    <span class="nt-icon-box nt-icon-primary" style="width: 44px; height: 44px;">
                        <i class="bi bi-hdd-network-fill fs-4"></i>
                    </span>
                    <span class="fw-bold fs-4 text-dark font-monospace">Network<span class="text-primary">Trouble</span></span>
                </a>
                <h5 class="fw-bold text-dark">Portal Administrator</h5>
                <p class="text-secondary small">Masuk untuk mengelola basis pengetahuan, aturan inferensi, dan laporan gangguan.</p>
            </div>

            <div class="nt-card p-4 p-md-5 shadow-sm bg-white">
                <?php if ($error): ?>
                    <div class="alert alert-danger py-2 small d-flex align-items-center gap-2 mb-3" role="alert">
                        <i class="bi bi-exclamation-triangle-fill flex-shrink-0"></i>
                        <div><?= htmlspecialchars($error); ?></div>
                    </div>
                <?php endif; ?>

                <?php
                $flash = get_flash();
                if ($flash):
                ?>
                    <div class="alert alert-<?= htmlspecialchars($flash['type']); ?> py-2 small mb-3">
                        <?= htmlspecialchars($flash['message']); ?>
                    </div>
                <?php endif; ?>

                <form action="<?= base_url('admin/login.php'); ?>" method="POST">
                    <?= csrf_field(); ?>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-dark">Username atau Email</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light text-secondary"><i class="bi bi-person"></i></span>
                            <input type="text" name="username" class="form-control" placeholder="admin" required autofocus value="<?= htmlspecialchars($_POST['username'] ?? ''); ?>">
                        </div>
                    </div>

                    <div class="mb-4">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <label class="form-label small fw-semibold text-dark mb-0">Password</label>
                        </div>
                        <div class="input-group">
                            <span class="input-group-text bg-light text-secondary"><i class="bi bi-lock"></i></span>
                            <input type="password" name="password" id="loginPassword" class="form-control" placeholder="••••••••" required>
                            <button type="button" class="btn btn-outline-secondary" onclick="togglePassVisibility()">
                                <i class="bi bi-eye" id="passToggleIcon"></i>
                            </button>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-nt-primary w-100 py-2 shadow-sm mb-3">
                        <i class="bi bi-box-arrow-in-right me-1"></i> Masuk ke Dashboard
                    </button>

                    <div class="p-3 bg-light rounded-2 border text-center small text-secondary">
                        <div class="fw-semibold text-dark mb-1"><i class="bi bi-key-fill text-warning me-1"></i>Demo Super Admin:</div>
                        <code>Username: admin</code><br>
                        <code>Password: Admin@12345</code>
                    </div>
                </form>
            </div>

            <div class="text-center mt-4 small text-secondary">
                <a href="<?= base_url(); ?>" class="text-secondary text-decoration-none">
                    <i class="bi bi-arrow-left me-1"></i> Kembali ke Halaman Utama
                </a>
            </div>

        </div>
    </div>
</div>

<script>
function togglePassVisibility() {
    const input = document.getElementById('loginPassword');
    const icon = document.getElementById('passToggleIcon');
    if (input.type === 'password') {
        input.type = 'text';
        icon.classList.replace('bi-eye', 'bi-eye-slash');
    } else {
        input.type = 'password';
        icon.classList.replace('bi-eye-slash', 'bi-eye');
    }
}
</script>

</body>
</html>
