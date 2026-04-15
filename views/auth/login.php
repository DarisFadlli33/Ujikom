<?php
// Login View
$error = $error ?? '';
$message = $_GET['message'] ?? '';
$msgType = $_GET['type'] ?? '';
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
        <input type="text" name="username" class="form-control <?= $error ? 'is-invalid' : '' ?>"
               placeholder="Masukkan username" required autocomplete="username">
      </div>
      <div class="mb-4">
        <label class="form-label">Password</label>
        <input type="password" name="password" class="form-control <?= $error ? 'is-invalid' : '' ?>"
               placeholder="Masukkan password" required autocomplete="current-password">
      </div>
      <?php if ($error): ?>
        <div class="alert alert-danger small mb-3">
          <?= htmlspecialchars($error) ?>
        </div>
      <?php endif; ?>
      <button type="submit" class="btn btn-primary w-100">
        Masuk ke Dashboard
      </button>
    </form>

    <p class="auth-footer">Belum punya akun? <a href="register.php">Daftar sekarang</a></p>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
