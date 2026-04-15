<?php
if (!function_exists('requireAdmin')) {
  require_once __DIR__ . '/../bootstrap.php';
}
requireAdmin();

$user_id  = $_SESSION['user_id'];
$username = $_SESSION['username'];
$message  = $_GET['message'] ?? '';
$msg_type = $_GET['type']    ?? 'info';
$total_users  = $pdo->query("SELECT COUNT(*) FROM users WHERE role_id=(SELECT id FROM roles WHERE name='user')")->fetchColumn();
$total_admins = $pdo->query("SELECT COUNT(*) FROM users WHERE role_id=(SELECT id FROM roles WHERE name='admin')")->fetchColumn();
$total_tasks  = $pdo->query("SELECT COUNT(*) FROM tasks")->fetchColumn();
$stat_open    = $pdo->query("SELECT COUNT(*) FROM tasks t JOIN task_statuses s ON t.status_id=s.id WHERE s.code='open'")->fetchColumn();
$stat_progress = $pdo->query("SELECT COUNT(*) FROM tasks t JOIN task_statuses s ON t.status_id=s.id WHERE s.code='in_progress'")->fetchColumn();
$stat_done    = $pdo->query("SELECT COUNT(*) FROM tasks t JOIN task_statuses s ON t.status_id=s.id WHERE s.code='done'")->fetchColumn();
$search        = trim($_GET['search'] ?? '');
$status_filter = trim($_GET['status_filter'] ?? '');
$page          = max(1, intval($_GET['page'] ?? 1));
$limit         = 12;
$offset        = ($page - 1) * $limit;
$status_limit  = 3; // Max 3 items per status on each page

$search = trim($_GET['search'] ?? '');
$status_filter = trim($_GET['status'] ?? '');

// Build search and filter clauses
$search_suffix = '';
$params_list = [];
if ($search !== '') {
  $search_suffix = " AND (t.title LIKE ? OR t.description LIKE ? OR u.username LIKE ?)";
  $params_list = ["%$search%", "%$search%", "%$search%"];
}
if ($status_filter !== '') {
  $search_suffix .= " AND s.code = ?";
  array_push($params_list, $status_filter);
}

// Get tasks with per-status limit using UNION
$sql = "(SELECT t.*, u.username AS owner, s.code AS status_code, s.label AS status_label, t.completion_attachment FROM tasks t JOIN users u ON t.user_id=u.id JOIN task_statuses s ON t.status_id=s.id WHERE s.code='open' $search_suffix ORDER BY t.created_at DESC LIMIT $status_limit)
UNION ALL
(SELECT t.*, u.username AS owner, s.code AS status_code, s.label AS status_label, t.completion_attachment FROM tasks t JOIN users u ON t.user_id=u.id JOIN task_statuses s ON t.status_id=s.id WHERE s.code='in_progress' $search_suffix ORDER BY t.created_at DESC LIMIT $status_limit)
UNION ALL
(SELECT t.*, u.username AS owner, s.code AS status_code, s.label AS status_label, t.completion_attachment FROM tasks t JOIN users u ON t.user_id=u.id JOIN task_statuses s ON t.status_id=s.id WHERE s.code='done' $search_suffix ORDER BY t.created_at DESC LIMIT $status_limit)
ORDER BY created_at DESC LIMIT $limit OFFSET $offset";

// Build params for UNION query (same search/filter params repeated 3 times)
$params = array_merge($params_list, $params_list, $params_list);

$taskStmt = $pdo->prepare($sql);
$taskStmt->execute($params);
$tasks = $taskStmt->fetchAll();

// Count total for pagination
$count_sql = "SELECT COUNT(*) FROM (
(SELECT t.id FROM tasks t JOIN users u ON t.user_id=u.id JOIN task_statuses s ON t.status_id=s.id WHERE s.code='open' $search_suffix LIMIT $status_limit)
UNION ALL
(SELECT t.id FROM tasks t JOIN users u ON t.user_id=u.id JOIN task_statuses s ON t.status_id=s.id WHERE s.code='in_progress' $search_suffix LIMIT $status_limit)
UNION ALL
(SELECT t.id FROM tasks t JOIN users u ON t.user_id=u.id JOIN task_statuses s ON t.status_id=s.id WHERE s.code='done' $search_suffix LIMIT $status_limit)
) as counted";

$countStmt = $pdo->prepare($count_sql);
$countStmt->execute($params);
$total_items = $countStmt->fetchColumn();
$total_pages = ceil($total_items / $limit);
$uPage  = max(1, intval($_GET['upage'] ?? 1));
$uLimit = 10;
$uOff   = ($uPage - 1) * $uLimit;
$uSearch = trim($_GET['usearch'] ?? '');

$uWhere = ["r.name='user'"];
$uParams = [];
if ($uSearch !== '') {
  $uWhere[] = "u.username LIKE ?";
  $uParams[] = "%$uSearch%";
}
$uWhereSQL = 'WHERE ' . implode(' AND ', $uWhere);

$uTotal = $pdo->prepare("SELECT COUNT(*) FROM users u JOIN roles r ON u.role_id=r.id $uWhereSQL");
$uTotal->execute($uParams);
$u_total_pages = ceil($uTotal->fetchColumn() / $uLimit);

$uStmt = $pdo->prepare("SELECT u.id, u.username, u.email, u.created_at,
  (SELECT COUNT(*) FROM tasks WHERE user_id=u.id) AS task_count,
  (SELECT COUNT(*) FROM tasks t2 JOIN task_statuses s2 ON t2.status_id=s2.id WHERE t2.user_id=u.id AND s2.code='done') AS done_count
  FROM users u JOIN roles r ON u.role_id=r.id $uWhereSQL
  ORDER BY u.created_at DESC LIMIT $uLimit OFFSET $uOff");
$uStmt->execute($uParams);
$users = $uStmt->fetchAll();
$allUsers = $pdo->query("SELECT u.id, u.username FROM users u JOIN roles r ON u.role_id=r.id WHERE r.name='user' ORDER BY u.username")->fetchAll();

$statuses = $pdo->query("SELECT * FROM task_statuses ORDER BY id")->fetchAll();
?>
<!DOCTYPE html>
<html lang="id" data-theme="light">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin Dashboard — TaskHub</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="../style.css" rel="stylesheet">
</head>

<body>

  <!-- NAVBAR -->
  <nav class="navbar navbar-expand-lg">
    <div class="container" style="max-width:1300px;">
      <a class="navbar-brand" href="dashboard.php">
        <div class="brand-icon">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2.5" stroke-linecap="round">
            <polyline points="9 11 12 14 22 4" />
            <path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11" />
          </svg>
        </div>
        TaskHub
      </a>
      <div class="navbar-nav ms-auto d-flex flex-row align-items-center gap-2">
        <button id="themeToggle" class="theme-toggle-btn" title="Toggle Dark Mode">
          <span id="themeIcon">🌙</span>
        </button>
        <span class="navbar-text">
          <span class="role-badge role-admin">Admin</span>
          <span class="navbar-username"><?= htmlspecialchars($username) ?></span>
        </span>
        <a href="#" id="logoutBtn" class="btn btn-outline-light btn-sm">Logout</a>
      </div>
    </div>
  </nav>

  <!-- Alert Container -->
  <div id="alertContainer" class="position-fixed top-0 start-50 translate-middle-x" style="z-index:1060;margin-top:72px;width:min(520px,92%);pointer-events:none;"></div>
  <?php if ($message): ?><script>
      document.addEventListener('DOMContentLoaded', () => showAlert(<?= json_encode($message) ?>, <?= json_encode($msg_type) ?>));
    </script><?php endif; ?>

  <!-- FLOATING ADD TASK PANEL -->
  <div class="add-task-panel" id="addTaskPanel">
    <div class="add-task-overlay" id="addTaskOverlay"></div>
    <div class="add-task-card" style="max-width:700px;">
      <div class="add-task-card-header">
        <div class="add-task-card-title">
          <div class="title-icon">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2.5" stroke-linecap="round">
              <line x1="12" y1="5" x2="12" y2="19" />
              <line x1="5" y1="12" x2="19" y2="12" />
            </svg>
          </div>
          Tambah Tugas Baru
        </div>
        <button class="btn-close-panel" data-close-add-task>✕</button>
      </div>

      <form method="POST" action="../task_action.php" enctype="multipart/form-data">
        <input type="hidden" name="action" value="add">
        <input type="hidden" name="bulk_user_ids" id="bulkUserIds" value="">
        <div class="row g-3">
          <div class="col-12">
            <label class="form-label">Judul Tugas <span style="color:var(--danger)">*</span></label>
            <input type="text" name="title" class="form-control" required placeholder="Masukkan judul tugas…">
          </div>
          <div class="col-12">
            <label class="form-label">Deskripsi</label>
            <textarea name="description" class="form-control" rows="3" placeholder="Deskripsi tugas (opsional)…"></textarea>
          </div>
          <div class="col-md-5">
            <label class="form-label">Tenggat Waktu <span style="color:var(--danger)">*</span></label>
            <input type="date" name="deadline" class="form-control" required min="<?= date('Y-m-d') ?>">
          </div>
          <div class="col-md-7">
            <label class="form-label">Lampiran <span style="color:var(--text-muted);font-weight:400;">(opsional, maks 5MB)</span></label>
            <input type="file" name="attachment" id="attachInput" class="form-control"
              accept=".pdf,.doc,.docx,.xls,.xlsx,.jpg,.jpeg,.png" data-preview="attachPreview">
            <div class="file-preview-area" id="attachPreview"></div>
          </div>
          <div class="col-12">
            <label class="form-label">
              Tugas Khusus <span style="color:var(--text-muted);font-weight:400;">(opsional - jika kosong diberikan ke semua user)</span>
              <span id="assignCountLabel" style="font-weight:500;color:var(--text-muted);margin-left:8px;font-size:.78rem;">Cari dan pilih user spesifik</span>
            </label>
            <div style="position:relative;">
              <input type="text" id="userSearch" class="form-control" placeholder="Cari user..." autocomplete="off">
              <div id="userSuggestions" style="position:absolute;top:100%;left:0;right:0;border:1px solid var(--border);border-radius:6px;max-height:200px;overflow-y:auto;display:none;z-index:1000;margin-top:4px;box-shadow:0 4px 12px rgba(0,0,0,0.1);">
              </div>
            </div>
            <div id="selectedUsersContainer" style="margin-top:12px;display:flex;flex-wrap:wrap;gap:8px;">
            </div>
          </div>
          <div class="col-12 d-flex gap-2 pt-1">
            <button type="submit" class="btn btn-primary">
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round">
                <polyline points="20 6 9 17 4 12" />
              </svg>
              Simpan Tugas
            </button>
            <button type="button" class="btn btn-outline-secondary" data-close-add-task>Batal</button>
          </div>
        </div>
      </form>
    </div>
  </div>

  <!-- MAIN -->
  <div class="main-wrapper">

    <!-- Page Header -->
    <div class="page-header">
      <div class="page-title-group">
        <div class="page-title">Dashboard Admin</div>
        <div class="page-subtitle">Kelola tugas, pengguna, dan statistik sistem</div>
      </div>
      <button class="fab-add-task" data-open-add-task>
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round">
          <line x1="12" y1="5" x2="12" y2="19" />
          <line x1="5" y1="12" x2="19" y2="12" />
        </svg>
        Tambah Tugas
      </button>
    </div>

    <!-- Stats -->
    <div class="stats-grid">
      <div class="stat-card stat-card--users">
        <div style="font-size:1.6rem;margin-bottom:10px;">👥</div>
        <div class="stat-value"><?= $total_users ?></div>
        <div class="stat-label">Total User</div>
      </div>
      <div class="stat-card stat-card--admins">
        <div style="font-size:1.6rem;margin-bottom:10px;">🛡️</div>
        <div class="stat-value"><?= $total_admins ?></div>
        <div class="stat-label">Total Admin</div>
      </div>
      <div class="stat-card stat-card--total">
        <div style="font-size:1.6rem;margin-bottom:10px;">📋</div>
        <div class="stat-value"><?= $total_tasks ?></div>
        <div class="stat-label">Total Tugas</div>
      </div>
      <div class="stat-card stat-card--done">
        <div style="font-size:1.6rem;margin-bottom:10px;">✅</div>
        <div class="stat-value"><?= $stat_done ?></div>
        <div class="stat-label">Selesai</div>
      </div>
    </div>

    <!-- Status Recap -->
    <div class="status-recap-bar mb-4">
      <div class="recap-item recap-open"><span class="recap-dot"></span>Open<strong style="margin-left:4px;"><?= $stat_open ?></strong></div>
      <div class="recap-divider"></div>
      <div class="recap-item recap-progress"><span class="recap-dot"></span>In Progress<strong style="margin-left:4px;"><?= $stat_progress ?></strong></div>
      <div class="recap-divider"></div>
      <div class="recap-item recap-done"><span class="recap-dot"></span>Done<strong style="margin-left:4px;"><?= $stat_done ?></strong></div>
    </div>

    <!-- Task Filter -->
    <div class="section-heading">Semua Tugas</div>
    <div class="filter-bar mb-3">
      <form method="GET" class="d-flex gap-2 w-100 flex-wrap align-items-end">
        <div class="filter-group">
          <label class="form-label">Cari</label>
          <input type="text" name="search" class="form-control" value="<?= htmlspecialchars($search) ?>" placeholder="Judul, deskripsi, username…">
        </div>
        <div class="filter-group" style="max-width:180px;">
          <label class="form-label">Status</label>
          <select name="status_filter" class="form-control">
            <option value="">Semua Status</option>
            <?php foreach ($statuses as $s): ?>
              <option value="<?= $s['code'] ?>" <?= $status_filter === $s['code'] ? 'selected' : '' ?>><?= htmlspecialchars($s['label']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="filter-actions">
          <button type="submit" class="btn btn-primary">Terapkan</button>
          <?php if ($search || $status_filter): ?>
            <a href="dashboard.php" class="btn btn-outline-secondary">Reset</a>
          <?php endif; ?>
        </div>
      </form>
    </div>

    <!-- Bulk Action Bar for Tasks -->
    <div class="bulk-action-bar" id="taskBulkActionBar">
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2.5" stroke-linecap="round">
        <polyline points="9 11 12 14 22 4" />
        <path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11" />
      </svg>

      <span class="bulk-count" id="taskBulkCount">0 tugas dipilih</span>
      <span class="bulk-spacer"></span>

      <button class="bulk-btn bulk-btn--danger" id="taskBulkDeleteBtn">
        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round">
          <polyline points="3 6 5 6 21 6" />
          <path d="M19 6l-1 14H6L5 6" />
          <path d="M10 11v6" />
          <path d="M14 11v6" />
        </svg>
        Hapus Terpilih
      </button>
    </div>

    <!-- Task Table -->
    <?php if (count($tasks) > 0): ?>
      <div class="task-table-wrap mb-4">
        <table class="task-table">
          <thead>
            <tr>
              <th class="th-checkbox">
                <input type="checkbox" id="masterTaskCheckbox" style="width:16px;height:16px;accent-color:var(--accent);cursor:pointer;">
              <th>Judul</th>
              <th>User</th>
              <th>Status</th>
              <th>Tenggat</th>
              <th>Lampiran</th>
              <th>Aksi</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($tasks as $task):
              $now      = new DateTime();
              $deadline = $task['deadline'] ? new DateTime($task['deadline']) : null;
              $overdue  = $deadline && $deadline < $now && $task['status_code'] !== 'done';
            ?>
              <tr>
              <td><input type="checkbox" class="task-row-cb" value="<?= $task['id'] ?>"></td>
              <td>
                <div class="task-title-cell"><?= htmlspecialchars($task['title']) ?></div>
                <?php if ($task['description']): ?>
                  <div class="task-desc-cell"><?= htmlspecialchars(mb_substr($task['description'], 0, 60)) ?><?= mb_strlen($task['description']) > 60 ? '…' : '' ?></div>
                <?php endif; ?>
              </td>
              <td>
                <span class="user-chip">
                  <span class="user-chip-avatar"><?= strtoupper(substr($task['owner'], 0, 1)) ?></span>
                  <?= htmlspecialchars($task['owner']) ?>
                </span>
              </td>
              <td><span class="status-pill status-<?= $task['status_code'] ?>"><?= htmlspecialchars($task['status_label']) ?></span></td>
              <td>
                <?php if ($task['deadline']): ?>
                  <span <?= $overdue ? "style='color:var(--danger);font-weight:700;'" : '' ?>><?= date('d M Y', strtotime($task['deadline'])) ?></span>
                  <?php if ($overdue): ?><span class="badge-overdue">Terlambat</span><?php endif; ?>
                <?php else: ?><span style="color:var(--text-light)">—</span><?php endif; ?>
              </td>
              <td>
                <?php if ($task['attachment']): ?>
                  <button class="tbtn tbtn-info" onclick='viewAttach(<?= json_encode($task["attachment"]) ?>)'>
                    <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
                      <path d="M21.44 11.05l-9.19 9.19a6 6 0 0 1-8.49-8.49l9.19-9.19a4 4 0 0 1 5.66 5.66l-9.2 9.19a2 2 0 0 1-2.83-2.83l8.49-8.48" />
                    </svg>
                    Lihat
                  </button>
                <?php else: ?><span style="color:var(--text-light)">—</span><?php endif; ?>
              </td>
              <td>
                <div class="action-btns">
                  <button class="tbtn tbtn-info view-btn"
                    data-id="<?= $task['id'] ?>"
                    data-title="<?= htmlspecialchars($task['title'], ENT_QUOTES) ?>"
                    data-desc="<?= htmlspecialchars($task['description'] ?? '', ENT_QUOTES) ?>"
                    data-status="<?= htmlspecialchars($task['status_label'], ENT_QUOTES) ?>"
                    data-status-code="<?= htmlspecialchars($task['status_code'], ENT_QUOTES) ?>"
                    data-deadline="<?= $task['deadline'] ? date('d M Y', strtotime($task['deadline'])) : '-' ?>"
                    data-deadline-raw="<?= htmlspecialchars($task['deadline'] ?? '', ENT_QUOTES) ?>"
                    data-owner="<?= htmlspecialchars($task['owner'], ENT_QUOTES) ?>"
                    data-attach="<?= htmlspecialchars($task['attachment'] ?? '', ENT_QUOTES) ?>"
                    data-completion-attach="<?= htmlspecialchars($task['completion_attachment'] ?? '', ENT_QUOTES) ?>">Detail</button>
                  <button class="tbtn tbtn-info edit-btn"
                    data-id="<?= $task['id'] ?>"
                    data-title="<?= htmlspecialchars($task['title'], ENT_QUOTES) ?>"
                    data-desc="<?= htmlspecialchars($task['description'] ?? '', ENT_QUOTES) ?>"
                    data-deadline="<?= htmlspecialchars($task['deadline'] ?? '', ENT_QUOTES) ?>">Edit</button>
                  <button class="tbtn tbtn-del"
                    onclick="confirmDelete('Hapus tugas ini?','Lampiran juga akan dihapus.',()=>location.href='../task_action.php?action=delete_admin&id=<?= $task['id'] ?>')">Hapus</button>
                </div>
                <div class="action-menu-mobile">
                  <button class="menu-dots-btn" onclick="toggleMenu(event)">⋮</button>
                  <div class="menu-dots-dropdown" data-task-id="<?= $task['id'] ?>" data-task-title="<?= htmlspecialchars($task['title'], ENT_QUOTES) ?>" data-task-desc="<?= htmlspecialchars($task['description'] ?? '', ENT_QUOTES) ?>" data-task-deadline="<?= htmlspecialchars($task['deadline'] ?? '', ENT_QUOTES) ?>" data-task-status-code="<?= htmlspecialchars($task['status_code'], ENT_QUOTES) ?>" data-task-status-label="<?= htmlspecialchars($task['status_label'], ENT_QUOTES) ?>" data-task-owner="<?= htmlspecialchars($task['owner'], ENT_QUOTES) ?>" data-task-attach="<?= htmlspecialchars($task['attachment'] ?? '', ENT_QUOTES) ?>" data-task-completion-attach="<?= htmlspecialchars($task['completion_attachment'] ?? '', ENT_QUOTES) ?>">
                    <button onclick="viewDetailFromMenu(this)">Detail</button>
                    <button onclick="editFromMenu(this)">Edit</button>
                    <button class="delete-option" onclick="confirmDelete('Hapus tugas ini?','Lampiran juga akan dihapus.',()=>location.href='../task_action.php?action=delete_admin&id=<?= $task['id'] ?>')">Hapus</button>
                  </div>
                </div>
              </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      <?php if ($total_pages > 1): ?>
        <nav class="mb-4">
          <ul class="pagination justify-content-center">
            <?php $base = '?search=' . urlencode($search) . '&status_filter=' . urlencode($status_filter) . '&page=';
            for ($i = 1; $i <= $total_pages; $i++): ?>
              <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                <a class="page-link" href="<?= $base . $i . '&upage=' . $uPage ?>"><?= $i ?></a>
              </li>
            <?php endfor; ?>
          </ul>
        </nav>
      <?php endif; ?>
    <?php else: ?>
      <div class="empty-state mb-4">
        <div class="empty-icon">📋</div>
        <p>Belum ada tugas<?= $search || $status_filter ? ' yang cocok' : '' ?>.</p>
      </div>
    <?php endif; ?>

    <!-- ── ADMIN MANAGEMENT ── -->
    <div class="section-heading mt-5 d-flex align-items-center justify-content-between">
      <span>Manajemen Admin</span>
      <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addAdminModal">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round">
          <line x1="12" y1="5" x2="12" y2="19" />
          <line x1="5" y1="12" x2="19" y2="12" />
        </svg>
        Tambah Admin
      </button>
    </div>

    <div class="task-table-wrap mb-4">
      <table class="task-table">
        <thead>
          <tr>
            <th>Username</th>
            <th>Email</th>
            <th>Bergabung</th>
          </tr>
        </thead>
        <tbody>
          <?php
          $adminStmt = $pdo->query("SELECT u.id, u.username, u.email, u.created_at 
                                FROM users u 
                                JOIN roles r ON u.role_id = r.id 
                                WHERE r.name = 'admin' 
                                ORDER BY u.created_at DESC");
          $admins = $adminStmt->fetchAll();
          foreach ($admins as $admin):
          ?>
            <tr>
              <td>
                <span class="user-chip">
                  <span class="user-chip-avatar" style="background:var(--danger);">
                    <?= strtoupper(substr($admin['username'], 0, 1)) ?>
                  </span>
                  <?= htmlspecialchars($admin['username']) ?>
                </span>
              </td>
              <td style="color:var(--text-muted);font-size:.85rem;">
                <?= htmlspecialchars($admin['email'] ?: '-') ?>
              </td>
              <td style="color:var(--text-muted);font-size:.82rem;">
                <?= date('d M Y', strtotime($admin['created_at'])) ?>
              </td>
            </tr>
          <?php endforeach; ?>

          <?php if (empty($admins)): ?>
            <tr>
              <td colspan="3" style="text-align:center;padding:32px;color:var(--text-muted);">
                Tidak ada admin.
              </td>
            </tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>

    <!-- ── USER MANAGEMENT ── -->
    <div class="section-heading mt-2">Manajemen User</div>

    <!-- User Search -->
    <div class="filter-bar mb-3">
      <form method="GET" class="d-flex gap-2 w-100 flex-wrap align-items-end">
        <?php /* preserve task filters */ ?>
        <input type="hidden" name="search" value="<?= htmlspecialchars($search) ?>">
        <input type="hidden" name="status_filter" value="<?= htmlspecialchars($status_filter) ?>">
        <input type="hidden" name="page" value="<?= $page ?>">
        <div class="filter-group">
          <label class="form-label">Cari User</label>
          <input type="text" name="usearch" class="form-control" value="<?= htmlspecialchars($uSearch) ?>" placeholder="Username…">
        </div>
        <div class="filter-actions">
          <button type="submit" class="btn btn-primary">Cari</button>
          <?php if ($uSearch): ?><a href="dashboard.php" class="btn btn-outline-secondary">Reset</a><?php endif; ?>
        </div>
      </form>
    </div>

    <!-- Bulk Action Bar -->
    <div class="bulk-action-bar" id="bulkActionBar">
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2.5" stroke-linecap="round">
        <polyline points="9 11 12 14 22 4" />
        <path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11" />
      </svg>
      <span class="bulk-count" id="bulkCount">0 dipilih</span>
      <button class="bulk-btn" id="bulkAssignBtn">
        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round">
          <line x1="12" y1="5" x2="12" y2="19" />
          <line x1="5" y1="12" x2="19" y2="12" />
        </svg>
        Tambah Tugas
      </button>
      <span class="bulk-spacer"></span>
      <button class="bulk-btn bulk-btn--danger" id="bulkDeleteBtn">
        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round">
          <polyline points="3 6 5 6 21 6" />
          <path d="M19 6l-1 14H6L5 6" />
          <path d="M10 11v6" />
          <path d="M14 11v6" />
        </svg>
        Hapus Terpilih
      </button>
    </div>

    <!-- User Table -->
    <div class="task-table-wrap mb-4">
      <table class="task-table">
        <thead>
          <tr>
            <th class="th-checkbox">
              <input type="checkbox" id="masterCheckbox" style="width:16px;height:16px;accent-color:var(--accent);cursor:pointer;">
            </th>
            <th>Username</th>
            <th>Tugas</th>
            <th>Selesai</th>
            <th>Bergabung</th>
            <th>Aksi</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($users as $u): ?>
            <tr>
              <td><input type="checkbox" class="row-cb" value="<?= $u['id'] ?>"></td>
              <td>
                <span class="user-chip">
                  <span class="user-chip-avatar"><?= strtoupper(substr($u['username'], 0, 1)) ?></span>
                  <?= htmlspecialchars($u['username']) ?>
                </span>
              </td>
              <td><span style="font-weight:700;"><?= $u['task_count'] ?></span> tugas</td>
              <td>
                <?php
                $pct = $u['task_count'] > 0 ? round($u['done_count'] / $u['task_count'] * 100) : 0;
                ?>
                <div style="display:flex;align-items:center;gap:8px;">
                  <div style="width:60px;height:6px;background:var(--border);border-radius:99px;overflow:hidden;">
                    <div style="width:<?= $pct ?>%;height:100%;background:var(--success);border-radius:99px;"></div>
                  </div>
                  <span style="font-size:.75rem;color:var(--text-muted);"><?= $u['done_count'] ?></span>
                </div>
              </td>
              <td style="color:var(--text-muted);font-size:.82rem;"><?= date('d M Y', strtotime($u['created_at'])) ?></td>
              <td>
                <div class="action-btns">
                  <button class="tbtn tbtn-info" onclick="assignToUser(<?= $u['id'] ?>, '<?= htmlspecialchars($u['username'], ENT_QUOTES) ?>')">
                    + Tugas
                  </button>
                  <button class="tbtn tbtn-del" onclick="deleteUser(<?= $u['id'] ?>, '<?= htmlspecialchars($u['username'], ENT_QUOTES) ?>', <?= $u['task_count'] ?>, <?= $u['done_count'] ?>)">Hapus</button>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
          <?php if (empty($users)): ?>
            <tr>
              <td colspan="6" style="text-align:center;padding:32px;color:var(--text-muted);">Tidak ada user ditemukan.</td>
            </tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>

    <!-- User Pagination -->
    <?php if ($u_total_pages > 1): ?>
      <nav class="mb-4">
        <ul class="pagination justify-content-center">
          <?php $uBase = '?search=' . urlencode($search) . '&status_filter=' . urlencode($status_filter) . '&page=' . $page . '&usearch=' . urlencode($uSearch) . '&upage=';
          for ($i = 1; $i <= $u_total_pages; $i++): ?>
            <li class="page-item <?= $i === $uPage ? 'active' : '' ?>">
              <a class="page-link" href="<?= $uBase . $i ?>"><?= $i ?></a>
            </li>
          <?php endfor; ?>
        </ul>
      </nav>
    <?php endif; ?>
  </div><!-- /main-wrapper -->

  <!-- MODAL DETAIL TUGAS -->
  <div class="modal fade" id="adminDetailModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-xl" style="max-width:860px;">
      <div class="modal-content">
        <div class="modal-header modal-header-accent">
          <div class="modal-icon-wrap">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2.5" stroke-linecap="round">
              <polyline points="9 11 12 14 22 4" />
              <path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11" />
            </svg>
          </div>
          <h5 class="modal-title modal-title-white ms-2">Detail Tugas</h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <div class="row g-3">
            <div class="col-12">
              <div class="detail-label">Judul</div>
              <div class="detail-value" id="ad-title" style="font-size:1rem;font-weight:700;"></div>
            </div>
            <div class="col-md-6">
              <div class="detail-label">User</div>
              <div class="detail-value" id="ad-owner"></div>
            </div>
            <div class="col-md-6">
              <div class="detail-label">Status</div>
              <div id="ad-status"></div>
            </div>
            <div class="col-md-6">
              <div class="detail-label">Tenggat</div>
              <div class="detail-value" id="ad-deadline"></div>
            </div>
            <div class="col-12">
              <div class="detail-label">Deskripsi</div>
              <div class="detail-value" id="ad-desc" style="white-space:pre-wrap;color:var(--text-muted);"></div>
            </div>
            <div class="col-12">
              <div class="detail-label">Lampiran</div>
              <div id="ad-attach-container"></div>
            </div>
            <div class="col-12" id="ad-completion-section" style="display:none;">
              <div class="detail-label">Lampiran Penyelesaian</div>
              <div id="ad-completion-container"></div>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button class="btn btn-outline-secondary" data-bs-dismiss="modal">Tutup</button>
        </div>
      </div>
    </div>
  </div>

  <!-- MODAL ATTACH VIEWER -->
  <div class="modal fade" id="attachModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
      <div class="modal-content">
        <div class="modal-header modal-header-accent">
          <div class="modal-icon-wrap">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2.5" stroke-linecap="round">
              <path d="M21.44 11.05l-9.19 9.19a6 6 0 0 1-8.49-8.49l9.19-9.19a4 4 0 0 1 5.66 5.66l-9.2 9.19a2 2 0 0 1-2.83-2.83l8.49-8.48"/>
            </svg>
          </div>
          <h5 class="modal-title modal-title-white ms-2">Lihat Lampiran</h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body p-3" id="attachModalBody"></div>
        <div class="modal-footer"><button class="btn btn-outline-secondary" data-bs-dismiss="modal">Tutup</button></div>
      </div>
    </div>
  </div>

  <!-- MODAL LOGOUT -->
  <div class="modal fade" id="logoutModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">Konfirmasi Logout</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">Apakah kamu yakin ingin keluar dari TaskHub?</div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
          <a href="../logout.php" class="btn btn-danger">Logout</a>
        </div>
      </div>
    </div>
  </div>

  <!-- MODAL ADD ADMIN -->
  <div class="modal fade" id="addAdminModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content">

        <div class="modal-header modal-header-accent">
          <div class="modal-icon-wrap">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2.5" stroke-linecap="round">
              <path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2" />
              <circle cx="8.5" cy="7" r="4" />
              <line x1="20" y1="8" x2="20" y2="14" />
              <line x1="23" y1="11" x2="17" y2="11" />
            </svg>
          </div>

          <h5 class="modal-title modal-title-white ms-2">Tambah Admin Baru</h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
        </div>

        <form method="POST" action="../task_action.php">
          <input type="hidden" name="action" value="add_admin">

          <div class="modal-body">
            <div class="row g-3">

              <div class="col-12">
                <label class="form-label">
                  Username <span style="color:var(--danger)">*</span>
                </label>
                <input type="text" name="admin_username" class="form-control" required placeholder="Minimal 3 karakter" minlength="3">
              </div>

              <div class="col-12">
                <label class="form-label">
                  Email <span style="color:var(--text-muted);font-weight:400;">(opsional)</span>
                </label>
                <input type="email" name="admin_email" class="form-control" placeholder="email@example.com">
              </div>

              <div class="col-12">
                <label class="form-label">
                  Password <span style="color:var(--danger)">*</span>
                </label>
                <input type="password" name="admin_password" class="form-control" required placeholder="Minimal 4 karakter" minlength="4">
              </div>

            </div>
          </div>

          <div class="modal-footer">
            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
            <button type="submit" class="btn btn-primary px-4">Tambah Admin</button>
          </div>
        </form>

      </div>
    </div>
  </div>

  <!-- MODAL EDIT TASK -->
  <div class="modal fade" id="editTaskModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
      <div class="modal-content">
        <div class="modal-header modal-header-accent">
          <div class="modal-icon-wrap">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2.5" stroke-linecap="round">
              <path d="M12 20h9" />
              <path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19H4v-3L16.5 3.5z" />
            </svg>
          </div>
          <h5 class="modal-title modal-title-white ms-2">Edit Tugas</h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
        </div>
        <form method="POST" action="../task_action.php" id="editTaskForm">
          <input type="hidden" name="action" value="edit_admin">
          <input type="hidden" name="edit_id" id="editTaskId">
          <div class="modal-body">
            <div class="row g-3">
              <div class="col-12">
                <label class="form-label">Judul Tugas <span style="color:var(--danger)">*</span></label>
                <input type="text" name="edit_title" id="editTaskTitle" class="form-control" required>
              </div>
              <div class="col-12">
                <label class="form-label">Deskripsi</label>
                <textarea name="edit_description" id="editTaskDesc" class="form-control" rows="3"></textarea>
              </div>
              <div class="col-md-6">
                <label class="form-label">Tenggat Waktu <span style="color:var(--danger)">*</span></label>
                <input type="date" name="edit_deadline" id="editTaskDeadline" class="form-control" required>
              </div>
              <div class="col-md-6">
                <label class="form-label">Status</label>
                <select name="edit_status" id="editTaskStatus" class="form-control" onchange="handleStatusChange(this)">
                  <option value="">-- Pilih Status --</option>
                  <option value="open">Open</option>
                  <option value="in_progress">In Progress</option>
                  <option value="done">Done</option>
                </select>
              </div>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
            <button type="submit" class="btn btn-primary px-4">Simpan Perubahan</button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  <script src="../js/app.js"></script>
  <script>
    // User search autocomplete - multiple selection
    const allUsers = <?= json_encode($allUsers) ?>;
    const userSearchInput = document.getElementById('userSearch');
    const suggestionBox = document.getElementById('userSuggestions');
    const selectedUsersContainer = document.getElementById('selectedUsersContainer');
    const selectedUsers = {}; // Store { userId: username }

    // Get computed CSS variable values for light/dark mode support
    function getCSSVariable(varName) {
      return getComputedStyle(document.documentElement).getPropertyValue(varName).trim();
    }

    if (userSearchInput) {
      userSearchInput.addEventListener('input', (e) => {
        const query = e.target.value.toLowerCase().trim();
        
        if (query.length === 0) {
          suggestionBox.style.display = 'none';
          return;
        }
        
        const filtered = allUsers.filter(u => 
          u.username.toLowerCase().includes(query) && !selectedUsers.hasOwnProperty(u.id)
        );
        
        if (filtered.length === 0) {
          suggestionBox.innerHTML = '<div style="padding:12px;color:var(--text-muted);text-align:center;">Tidak ada user ditemukan</div>';
          suggestionBox.style.display = 'block';
          return;
        }
        
        const bgSecondary = getCSSVariable('--bg-secondary');
        const bgTertiary = getCSSVariable('--bg-tertiary');
        
        // Apply background to container
        suggestionBox.style.background = bgSecondary;
        suggestionBox.style.opacity = '1';
        
        suggestionBox.innerHTML = filtered.map(u => `
          <div onclick="selectUser(${u.id}, '${u.username}')" style="padding:10px 12px;border-bottom:1px solid var(--border);cursor:pointer;transition:background 0.2s;background:${bgSecondary} !important;opacity:1 !important;" onmouseover="this.style.background='${bgTertiary}'" onmouseout="this.style.background='${bgSecondary}'">
            <div style="font-weight:500;">${u.username}</div>
          </div>
        `).join('');
        suggestionBox.style.display = 'block';
      });

      document.addEventListener('click', (e) => {
        if (!e.target.closest('[id="userSearch"], #userSuggestions')) {
          suggestionBox.style.display = 'none';
        }
      });
    }

    function selectUser(userId, username) {
      if (selectedUsers.hasOwnProperty(userId)) return; // Avoid duplicates
      
      selectedUsers[userId] = username;
      userSearchInput.value = '';
      updateChips();
      suggestionBox.style.display = 'none';
    }

    function removeUser(userId) {
      delete selectedUsers[userId];
      updateChips();
    }

    function updateChips() {
      const colors = ['#3b82f6', '#10b981', '#f59e0b', '#ef4444', '#8b5cf6', '#ec4899', '#14b8a6'];
      let colorIndex = 0;
      
      selectedUsersContainer.innerHTML = Object.entries(selectedUsers).map(([userId, username]) => {
        const bgColor = colors[colorIndex % colors.length];
        const chip = `
          <span style="display:inline-flex;align-items:center;gap:6px;background:${bgColor} !important;opacity:1 !important;color:white !important;padding:8px 14px;border-radius:20px;font-size:.85rem;font-weight:500;box-shadow:0 2px 8px rgba(0,0,0,0.15);border:none;">
            <span style="display:inline-block;">${username}</span>
            <button type="button" onclick="removeUser(${userId})" style="background:none !important;border:none !important;color:white !important;cursor:pointer;padding:0;font-weight:bold;display:inline-flex;align-items:center;justify-content:center;">✕</button>
          </span>
          <input type="hidden" name="assigned_user_ids[]" value="${userId}">
        `;
        colorIndex++;
        return chip;
      }).join('');
    }
  </script>
  <script>
    // Mobile menu toggle
    function toggleMenu(e) {
      e.stopPropagation();
      const btn = e.target.closest('.menu-dots-btn');
      const dropdown = btn.nextElementSibling;
      if (dropdown) {
        dropdown.classList.toggle('show');
        document.addEventListener('click', closeDropdown, { once: true });
      }
    }

    function closeDropdown(e) {
      document.querySelectorAll('.menu-dots-dropdown.show').forEach(dd => {
        if (!dd.contains(e.target)) dd.classList.remove('show');
      });
    }

    function viewDetailFromMenu(btn) {
      const dd = btn.closest('.menu-dots-dropdown');
      const d = dd.dataset;
      document.getElementById('ad-title').textContent    = d.taskTitle;
      document.getElementById('ad-desc').textContent     = d.taskDesc || 'Tidak ada deskripsi.';
      document.getElementById('ad-status').innerHTML     = `<span class="status-pill status-${d.taskStatusCode}">${d.taskStatusLabel}</span>`;
      document.getElementById('ad-deadline').textContent = d.taskDeadline || '—';
      document.getElementById('ad-owner').textContent    = d.taskOwner;
      renderModalAttach(d.taskAttach || '', 'ad-attach-container');
      const sec = document.getElementById('ad-completion-section');
      sec.style.display = d.taskCompletionAttach ? 'block' : 'none';
      if (d.taskCompletionAttach) renderModalAttach(d.taskCompletionAttach, 'ad-completion-container');
      new bootstrap.Modal(document.getElementById('adminDetailModal')).show();
    }

    function editFromMenu(btn) {
      const dd = btn.closest('.menu-dots-dropdown');
      const d = dd.dataset;
      document.getElementById('editTaskId').value = d.taskId;
      document.getElementById('editTaskTitle').value = d.taskTitle;
      document.getElementById('editTaskDesc').value = d.taskDesc || '';
      document.getElementById('editTaskDeadline').value = d.taskDeadline || '';
      new bootstrap.Modal(document.getElementById('editTaskModal')).show();
    }

    document.querySelectorAll('.view-btn').forEach(btn => {
      btn.addEventListener('click', () => {
        document.getElementById('ad-title').textContent    = btn.dataset.title;
        document.getElementById('ad-desc').textContent     = btn.dataset.desc || 'Tidak ada deskripsi.';
        document.getElementById('ad-status').innerHTML     = `<span class="status-pill status-${btn.dataset.statusCode}">${btn.dataset.status}</span>`;
        document.getElementById('ad-deadline').textContent = btn.dataset.deadline || '—';
        document.getElementById('ad-owner').textContent    = btn.dataset.owner;
        renderModalAttach(btn.dataset.attach, 'ad-attach-container');
        const sec = document.getElementById('ad-completion-section');
        const ca  = btn.dataset.completionAttach;
        sec.style.display = ca ? 'block' : 'none';
        if (ca) renderModalAttach(ca, 'ad-completion-container');
        new bootstrap.Modal(document.getElementById('adminDetailModal')).show();
      });
    });

    document.querySelectorAll('.edit-btn').forEach(btn => {
      btn.addEventListener('click', () => {
        document.getElementById('editTaskId').value = btn.dataset.id;
        document.getElementById('editTaskTitle').value = btn.dataset.title;
        document.getElementById('editTaskDesc').value = btn.dataset.desc || '';
        document.getElementById('editTaskDeadline').value = btn.dataset.deadline || '';
        document.getElementById('editTaskStatus').value = btn.dataset.statusCode || '';
        new bootstrap.Modal(document.getElementById('editTaskModal')).show();
      });
    });

    document.getElementById('editTaskForm')?.addEventListener('submit', e => {
      e.preventDefault();
      const formData = new FormData(e.target);
      const taskTitle = document.getElementById('editTaskTitle').value;
      const newStatus = document.getElementById('editTaskStatus').value;
      const taskId = document.getElementById('editTaskId').value;
      
      // If status changed, show confirmation
      if (newStatus) {
        const confirmed = confirm(`Ubah status tugas "${taskTitle}"?`);
        if (!confirmed) return;
      }
      
      fetch('../task_action.php', { method: 'POST', body: formData })
        .then(() => {
          const modal = bootstrap.Modal.getInstance(document.getElementById('editTaskModal'));
          modal?.hide();
          showAlert('Tugas diperbarui! ✓', 'success');
          setTimeout(() => location.reload(), 1500);
        })
        .catch(() => showAlert('Gagal memperbarui tugas.', 'danger'));
    });

    // Status change confirmation popup
    let lastEditTaskId = null;
    let lastEditTaskTitle = null;

    function handleStatusChange(selectElement) {
      // Store the new status but don't submit yet - wait for form submission
      const newStatus = selectElement.value;
      if (!newStatus) return;
      
      const confirmed = confirm(`Ubah status tugas? Perubahan akan disimpan saat Anda klik "Simpan Perubahan".`);
      if (!confirmed) {
        selectElement.value = ''; // Reset dropdown if user cancels
      }
    }

    function confirmStatusChange(taskId, newStatus, taskTitle) {
      const statusLabels = { 'open': 'Open', 'in_progress': 'In Progress', 'done': 'Done' };
      showAlert(`Status tugas "${taskTitle}" akan diubah menjadi ${statusLabels[newStatus]}. Lanjutkan?`, 'info');
      setTimeout(() => {
        const confirmed = confirm(`Ubah status ke ${statusLabels[newStatus]}?`);
        if (confirmed) {
          const form = document.createElement('form');
          form.method = 'POST';
          form.action = '../task_action.php';
          form.innerHTML = `
            <input type="hidden" name="action" value="update_task_status">
            <input type="hidden" name="task_id" value="${taskId}">
            <input type="hidden" name="status" value="${newStatus}">
          `;
          document.body.appendChild(form);
          form.submit();
        }
      }, 500);
    }
  </script>
</body>

</html>