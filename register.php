<?php
require_once __DIR__ . '/bootstrap.php';
if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit();
}
$error   = '';
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm  = $_POST['confirm_password'] ?? '';
    if ($username === '' || $password === '') {
        $error = 'Username dan password wajib diisi.';
    } elseif (strlen($username) < 3) {
        $error = 'Username minimal 3 karakter.';
    } elseif (strlen($password) < 4) {
        $error = 'Password minimal 4 karakter.';
    } elseif ($password !== $confirm) {
        $error = 'Konfirmasi password tidak cocok.';
    } else {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
        $stmt->execute([$username]);
        if ($stmt->fetch()) {
            $error = 'Username sudah digunakan. Pilih username lain.';
        } else {
            $stmt = $pdo->prepare("SELECT id FROM roles WHERE name = 'user'");
            $stmt->execute();
            $role_id = $stmt->fetchColumn();
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO users (username, password, role_id) VALUES (?, ?, ?)");
            if ($stmt->execute([$username, $hashed_password, $role_id])) {
                $success = true;
            } else {
                $error = 'Pendaftaran gagal. Coba lagi.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar — TaskHub</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="style.css" rel="stylesheet">
    <link href="style.css" rel="stylesheet">
</head>
<body>
<div class="auth-page-bg">
    <div class="auth-card">
        <div class="auth-logo-wrap">
            <div class="auth-logo-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="9 11 12 14 22 4"/>
                    <path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/>
                </svg>
            </div>
            <h1>TaskHub</h1>
            <p>Buat akun gratis sekarang</p>
        </div>

        <p class="auth-divider">Buat akun baru</p>

        <form method="POST" novalidate>
            <div class="mb-3">
                <label class="form-label">Username</label>
                <input type="text" name="username" class="form-control <?php echo $error ? 'is-invalid' : ''; ?>"
                       value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>"
                       placeholder="Minimal 3 karakter" required autocomplete="username">
            </div>
            <div class="mb-3">
                <label class="form-label">Password</label>
                <input type="password" name="password" class="form-control <?php echo $error ? 'is-invalid' : ''; ?>"
                       placeholder="Minimal 4 karakter" required autocomplete="new-password">
            </div>
            <div class="mb-3">
                <label class="form-label">Konfirmasi Password</label>
                <input type="password" name="confirm_password" class="form-control <?php echo $error ? 'is-invalid' : ''; ?>"
                       placeholder="Ulangi password" required autocomplete="new-password">
            </div>
            <button type="submit" class="btn btn-primary w-100 mt-1">Daftar Sekarang</button>
        </form>

        <p class="auth-footer">Sudah punya akun? <a href="index.php">Login di sini</a></p>
    </div>
</div>

<!-- Modal: Registrasi Berhasil -->
<div class="modal fade" id="successModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header modal-header-success">
                <div class="modal-icon-wrap">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <polyline points="20 6 9 17 4 12"/>
                    </svg>
                </div>
                <h5 class="modal-title ms-2" style="color:#fff;">Akun Berhasil Dibuat!</h5>
            </div>
            <div class="modal-body text-center py-4">
                <p class="mb-1 fw-semibold" style="font-size:1rem;">
                    Selamat, <strong><?php echo htmlspecialchars($_POST['username'] ?? ''); ?></strong>!
                </p>
                <p class="text-muted" style="font-size:.875rem;">Akun kamu sudah siap. Silakan login untuk mulai menggunakan TaskHub.</p>
            </div>
            <div class="modal-footer justify-content-center">
                <a href="index.php" class="btn btn-primary px-5">Login Sekarang</a>
            </div>
        </div>
    </div>
</div>

<!-- Modal: Registrasi Gagal -->
<div class="modal fade" id="errorModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header modal-header-danger">
                <div class="modal-icon-wrap">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2.5" stroke-linecap="round">
                        <circle cx="12" cy="12" r="10"/>
                        <line x1="12" y1="8" x2="12" y2="12"/>
                        <line x1="12" y1="16" x2="12.01" y2="16"/>
                    </svg>
                </div>
                <h5 class="modal-title ms-2" style="color:#fff;">Pendaftaran Gagal</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body text-center py-4">
                <p class="mb-1 fw-semibold" style="font-size:1rem;"><?php echo htmlspecialchars($error); ?></p>
                <p class="text-muted" style="font-size:.875rem;">Periksa kembali data yang kamu masukkan.</p>
            </div>
            <div class="modal-footer justify-content-center">
                <button type="button" class="btn btn-primary px-5" data-bs-dismiss="modal">Coba Lagi</button>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="js/app.js"></script>
<?php if ($success): ?>
<script>
    document.addEventListener('DOMContentLoaded', () => {
        new bootstrap.Modal(document.getElementById('successModal')).show();
    });
</script>
<?php elseif ($error): ?>
<script>
    document.addEventListener('DOMContentLoaded', () => {
        new bootstrap.Modal(document.getElementById('errorModal')).show();
    });
</script>
<?php endif; ?>
</body>
</html>
