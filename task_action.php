<?php
require_once __DIR__ . '/bootstrap.php';
requireLogin();

$action  = $_REQUEST['action'] ?? '';
$user_id = $_SESSION['user_id'];

if ($action === 'add') {
    requireAdmin();
    $title    = trim($_POST['title'] ?? '');
    $desc     = trim($_POST['description'] ?? '');
    $deadline = $_POST['deadline'] ?? null;
    $ids      = array_filter(array_map('intval', (array)($_POST['assigned_user_ids'] ?? [])));
    if (!empty($_POST['assigned_user_id'])) $ids[] = intval($_POST['assigned_user_id']);
    
    // Jika tidak ada user dipilih, berikan ke semua user
    if (empty($ids)) {
        $allUsers = $pdo->query("SELECT id FROM users WHERE role_id=(SELECT id FROM roles WHERE name='user')");
        $ids = array_values(array_column($allUsers->fetchAll(PDO::FETCH_ASSOC), 'id'));
        if (empty($ids)) back('Tidak ada user dalam sistem.', 'danger');
    }
    
    if (!$title || empty($ids) || !$deadline) back('Judul dan tenggat wajib diisi.', 'danger');
    $open_id = $pdo->query("SELECT id FROM task_statuses WHERE code='open'")->fetchColumn();
    $attach  = null;
    if (!empty($_FILES['attachment']['name'])) {
        $attach = upload($_FILES['attachment']);
        if (!$attach) back('File tidak diizinkan atau terlalu besar (maks 5MB).', 'danger');
    }
    $stmt = $pdo->prepare("INSERT INTO tasks (user_id,created_by,title,description,status_id,deadline,attachment) VALUES (?,?,?,?,?,?,?)");
    $n = 0;
    foreach (array_unique($ids) as $aid) {
        $r = $pdo->prepare("SELECT u.id FROM users u JOIN roles r ON u.role_id=r.id WHERE u.id=? AND r.name='user'");
        $r->execute([$aid]);
        if ($r->fetch() && $stmt->execute([$aid, $user_id, $title, $desc, $open_id, $deadline, $attach])) $n++;
    }
    back($n ? "Tugas ditambahkan ke $n user." : 'Gagal. Pastikan user valid.', $n ? 'success' : 'danger');
}

if ($action === 'delete_admin') {
    requireAdmin();
    $id = intval($_GET['id'] ?? 0);
    if (!$id) back('ID tidak valid.', 'danger');
    $t = $pdo->prepare("SELECT attachment,completion_attachment FROM tasks WHERE id=?");
    $t->execute([$id]);
    if ($row = $t->fetch()) { delFile($row['attachment']); delFile($row['completion_attachment']); }
    $pdo->prepare("DELETE FROM tasks WHERE id=?")->execute([$id]);
    back('Tugas dihapus.', 'success');
}

if ($action === 'edit_admin') {
    requireAdmin();
    $id       = intval($_POST['edit_id'] ?? 0);
    $title    = trim($_POST['edit_title'] ?? '');
    $desc     = trim($_POST['edit_description'] ?? '');
    $deadline = $_POST['edit_deadline'] ?? null;
    $status   = trim($_POST['edit_status'] ?? '');
    if (!$id || !$title || !$deadline) back('ID, judul, dan tenggat wajib diisi.', 'danger');
    $stmt = $pdo->prepare("SELECT id FROM tasks WHERE id=?");
    $stmt->execute([$id]);
    if (!$stmt->fetch()) back('Tugas tidak ditemukan.', 'danger');
    
    // Get status_id if status code provided
    $status_id = null;
    if ($status) {
        $s = $pdo->prepare("SELECT id FROM task_statuses WHERE code=?");
        $s->execute([$status]);
        $status_id = $s->fetchColumn();
        if (!$status_id) back('Status tidak valid.', 'danger');
    }
    
    // Update with status if provided, otherwise just title/description/deadline
    if ($status_id) {
        $update = $pdo->prepare("UPDATE tasks SET title=?, description=?, deadline=?, status_id=? WHERE id=?");
        if ($update->execute([$title, $desc, $deadline, $status_id, $id])) {
            back('Tugas diperbarui.', 'success');
        } else {
            back('Gagal memperbarui tugas.', 'danger');
        }
    } else {
        $update = $pdo->prepare("UPDATE tasks SET title=?, description=?, deadline=? WHERE id=?");
        if ($update->execute([$title, $desc, $deadline, $id])) {
            back('Tugas diperbarui.', 'success');
        } else {
            back('Gagal memperbarui tugas.', 'danger');
        }
    }
}

if ($action === 'bulk_delete_tasks') {
    requireAdmin();
    $ids = array_filter(array_map('intval', explode(',', $_POST['ids'] ?? '')));
    if (!$ids) back('Tidak ada tugas dipilih.', 'warning');
    $ph = implode(',', array_fill(0, count($ids), '?'));
    $rows = $pdo->prepare("SELECT attachment,completion_attachment FROM tasks WHERE id IN ($ph)");
    $rows->execute($ids);
    foreach ($rows->fetchAll() as $r) { delFile($r['attachment']); delFile($r['completion_attachment']); }
    $pdo->prepare("DELETE FROM tasks WHERE id IN ($ph)")->execute($ids);
    back(count($ids).' tugas dihapus.', 'success');
}

if ($action === 'delete_user') {
    requireAdmin();
    $uid = intval($_GET['uid'] ?? 0);
    if (!$uid) back('User tidak valid.', 'danger');
    $pdo->prepare("DELETE FROM tasks WHERE user_id=?")->execute([$uid]);
    $pdo->prepare("DELETE FROM users WHERE id=? AND role_id=(SELECT id FROM roles WHERE name='user')")->execute([$uid]);
    back('User dihapus.', 'success');
}

if ($action === 'bulk_delete_users') {
    requireAdmin();
    $ids = array_filter(array_map('intval', explode(',', $_POST['ids'] ?? '')));
    if (!$ids) back('Tidak ada user dipilih.', 'warning');
    $ph = implode(',', array_fill(0, count($ids), '?'));
    $pdo->prepare("DELETE FROM tasks WHERE user_id IN ($ph)")->execute($ids);
    $pdo->prepare("DELETE FROM users WHERE id IN ($ph) AND role_id=(SELECT id FROM roles WHERE name='user')")->execute($ids);
    back(count($ids).' user dihapus.', 'success');
}

if ($action === 'add_admin') {
    requireAdmin();
    $uname = trim($_POST['admin_username'] ?? '');
    $email = trim($_POST['admin_email'] ?? '');
    $pass  = $_POST['admin_password'] ?? '';
    if (!$uname || !$pass) back('Username dan password wajib diisi.', 'danger');
    if (strlen($uname) < 3) back('Username minimal 3 karakter.', 'danger');
    if (strlen($pass) < 4) back('Password minimal 4 karakter.', 'danger');
    $ck = $pdo->prepare("SELECT id FROM users WHERE username=?"); $ck->execute([$uname]);
    if ($ck->fetch()) back('Username sudah dipakai.', 'danger');
    $role_id = $pdo->query("SELECT id FROM roles WHERE name='admin'")->fetchColumn();
    $stmt = $pdo->prepare("INSERT INTO users (username,email,password,role_id) VALUES (?,?,?,?)");
    $stmt->execute([$uname, $email, password_hash($pass, PASSWORD_DEFAULT), $role_id]);
    back('Admin baru ditambahkan.', 'success');
}

if ($action === 'status') {
    requireUser();
    $id  = intval($_POST['id'] ?? $_GET['id'] ?? 0);
    $new = trim($_POST['status'] ?? $_GET['status'] ?? '');
    if (!$id || !in_array($new, ['open','in_progress','done'])) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'msg' => 'Parameter tidak valid.']);
        exit;
    }
    $ck = $pdo->prepare("SELECT id FROM tasks WHERE id=? AND user_id=?"); $ck->execute([$id, $user_id]);
    if (!$ck->fetch()) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'msg' => 'Tugas tidak ditemukan.']);
        exit;
    }
    $sid = $pdo->prepare("SELECT id FROM task_statuses WHERE code=?"); $sid->execute([$new]);
    $sid = $sid->fetchColumn();
    if ($new === 'done') {
        if (empty($_FILES['completion_attachment']['name'])) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'msg' => 'Lampiran penyelesaian wajib diunggah.']);
            exit;
        }
        $ca = upload($_FILES['completion_attachment']);
        if (!$ca) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'msg' => 'File tidak diizinkan atau terlalu besar.']);
            exit;
        }
        $pdo->prepare("UPDATE tasks SET status_id=?,completion_attachment=? WHERE id=? AND user_id=?")->execute([$sid,$ca,$id,$user_id]);
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'msg' => 'Tugas selesai!']);
        exit;
    }
    $pdo->prepare("UPDATE tasks SET status_id=? WHERE id=? AND user_id=?")->execute([$sid,$id,$user_id]);
    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'msg' => 'Status diperbarui.']);
    exit;
}

if ($action === 'download') {
    requireLogin();
    $file = ltrim($_GET['file'] ?? '', '/');
    if (!$file) backRole('File tidak ditemukan.', 'danger');
    $full = realpath(__DIR__.'/'.$file);
    $dir  = realpath(__DIR__.'/attachments/');
    if (!$full || !$dir || strpos($full, $dir) !== 0) backRole('Akses ditolak.', 'danger');
    $ext  = strtolower(pathinfo($full, PATHINFO_EXTENSION));
    $mime = ['jpg'=>'image/jpeg','jpeg'=>'image/jpeg','png'=>'image/png','gif'=>'image/gif',
             'webp'=>'image/webp','pdf'=>'application/pdf','doc'=>'application/msword',
             'docx'=>'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
             'xls'=>'application/vnd.ms-excel',
             'xlsx'=>'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'][$ext] ?? 'application/octet-stream';
    $disp = (in_array($ext, ['jpg','jpeg','png','gif','webp','pdf']) && isset($_GET['view'])) ? 'inline' : 'attachment';
    header("Content-Type: $mime");
    header('Content-Disposition: '.$disp.'; filename="'.basename($full).'"');
    header('Content-Length: '.filesize($full));
    readfile($full);
    exit;
}

backRole('Aksi tidak dikenali.', 'danger');

function back(string $msg, string $type = 'info'): void {
    header("Location: admin/dashboard.php?message=".urlencode($msg)."&type=$type"); exit;
}
function backUser(string $msg, string $type = 'info'): void {
    header("Location: user/dashboard.php?message=".urlencode($msg)."&type=$type"); exit;
}
function backRole(string $msg, string $type = 'info'): void {
    ($_SESSION['role'] ?? '') === 'admin' ? back($msg, $type) : backUser($msg, $type);
}
function delFile(?string $path): void {
    if (!$path) return;
    @unlink(__DIR__.'/'.ltrim($path, '/'));
    @unlink($path);
}
function upload(array $file): ?string {
    $allowed = ['pdf','doc','docx','xls','xlsx','jpg','jpeg','png','gif'];
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, $allowed) || $file['size'] > 5*1024*1024) return null;
    $dir = __DIR__.'/attachments/';
    if (!is_dir($dir)) mkdir($dir, 0755, true);
    $name = uniqid('att_', true).'.'.$ext;
    return move_uploaded_file($file['tmp_name'], $dir.$name) ? 'attachments/'.$name : null;
}
