<?php
require_once __DIR__ . '/bootstrap.php';
if (isset($_SESSION['user_id'])) { header("Location: dashboard.php"); exit(); }

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $username = trim($_POST['username'] ?? '');
  $password = $_POST['password'] ?? '';
  if ($username === '' || $password === '') {
    $error = 'empty';
  } else {
    $stmt = $pdo->prepare("SELECT u.id, u.username, u.password, r.name AS role FROM users u JOIN roles r ON u.role_id=r.id WHERE u.username=?");
    $stmt->execute([$username]);
    $user = $stmt->fetch();
    if ($user && password_verify($password, $user['password'])) {
      $_SESSION['user_id'] = $user['id'];
      $_SESSION['username']= $user['username'];
      $_SESSION['role']    = $user['role'];
      header("Location: dashboard.php"); exit();
    } else { $error = 'wrong'; }
  }
}
?>
<!DOCTYPE html>
<html lang="id" data-theme="light">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Login — TaskHub</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
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
      <p>Platform manajemen tugas tim modern</p>
    </div>

    <p class="auth-divider">Masuk ke akun</p>

    <form method="POST" novalidate>
      <div class="mb-3">
        <label class="form-label">Username</label>
        <input type="text" name="username" class="form-control <?= $error?'is-invalid':'' ?>"
               value="<?= htmlspecialchars($_POST['username'] ?? '') ?>"
               placeholder="Masukkan username" required autocomplete="username">
      </div>
      <div class="mb-4">
        <label class="form-label">Password</label>
        <div style="position:relative;">
          <input type="password" name="password" id="passwordInput" class="form-control <?= $error?'is-invalid':'' ?>"
                 placeholder="Masukkan password" required autocomplete="current-password" style="padding-right:42px;">
          <button type="button" onclick="togglePwd()" style="position:absolute;right:10px;top:50%;transform:translateY(-50%);background:none;border:none;cursor:pointer;color:var(--text-muted);font-size:.85rem;padding:4px;">👁</button>
        </div>
      </div>
      <button
          type="submit"
          class="btn btn-primary w-100 d-flex justify-content-center align-items-center"
          style="padding:12px;font-size:.95rem;">
          Masuk ke Dashboard
        </button>
    </form>

    <p class="auth-footer">Belum punya akun? <a href="register.php">Daftar sekarang</a></p>
  </div>
</div>

<!-- Error Modal -->
<div class="modal fade" id="errorModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header modal-header-danger">
        <div class="modal-icon-wrap">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2.5" stroke-linecap="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
        </div>
        <h5 class="modal-title modal-title-white ms-2">Login Gagal</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body text-center py-4">
        <?php if($error==='wrong'): ?>
        <p style="font-weight:700;font-size:.95rem;margin-bottom:4px;">Username atau password salah.</p>
        <p style="color:var(--text-muted);font-size:.85rem;margin:0;">Periksa kembali kredensial kamu dan coba lagi.</p>
        <?php elseif($error==='empty'): ?>
        <p style="font-weight:700;font-size:.95rem;margin-bottom:4px;">Form belum lengkap.</p>
        <p style="color:var(--text-muted);font-size:.85rem;margin:0;">Username dan password wajib diisi.</p>
        <?php endif; ?>
      </div>
      <div class="modal-footer justify-content-center">
        <button type="button" class="btn btn-primary px-5" data-bs-dismiss="modal">Coba Lagi</button>
      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="js/app.js"></script>
<?php if($error): ?>
<script>document.addEventListener('DOMContentLoaded',()=>new bootstrap.Modal(document.getElementById('errorModal')).show());</script>
<?php endif; ?>
<script>
function togglePwd() {
  const inp = document.getElementById('passwordInput');
  inp.type = inp.type === 'password' ? 'text' : 'password';
}
</script>
</body>
</html>
