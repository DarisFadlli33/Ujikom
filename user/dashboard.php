<?php
if (!function_exists('requireUser')) {
  require_once __DIR__ . '/../bootstrap.php';
}
requireUser();

$user_id  = $_SESSION['user_id'];
$username = $_SESSION['username'];
$message  = $_GET['message'] ?? '';
$msg_type = $_GET['type']    ?? 'info';

$search        = trim($_GET['search'] ?? '');
$status_filter = trim($_GET['status_filter'] ?? '');

$where  = ["t.user_id = ?"];
$params = [$user_id];
if ($search !== '') {
  $where[] = "(t.title LIKE ? OR t.description LIKE ?)";
  $params[] = "%$search%";
  $params[] = "%$search%";
}
if ($status_filter !== '') {
  $where[] = "s.code = ?";
  $params[] = $status_filter;
}
$whereSQL = 'WHERE ' . implode(' AND ', $where);

$taskStmt = $pdo->prepare("SELECT t.*, s.code AS status_code, s.label AS status_label,
  CASE WHEN t.created_by != t.user_id THEN 'admin' ELSE 'user' END AS task_source
  FROM tasks t JOIN task_statuses s ON t.status_id=s.id
  $whereSQL ORDER BY t.deadline ASC, t.created_at DESC");
$taskStmt->execute($params);
$allTasks = $taskStmt->fetchAll();

// Hide overdue tasks from user display
$now = new DateTime();
$tasks = array_filter($allTasks, function($t) use ($now) {
  $deadline = $t['deadline'] ? new DateTime($t['deadline']) : null;
  $overdue = $deadline && $deadline < $now && $t['status_code'] !== 'done';
  return !$overdue;
});
$tasks = array_values($tasks); // Re-index the array

// Pagination: 3 items per page
$page = max(1, intval($_GET['page'] ?? 1));
$limit = 3;
$offset = ($page - 1) * $limit;
$total_pages = ceil(count($tasks) / $limit);
$paginatedTasks = array_slice($tasks, $offset, $limit);

// Stats based on all tasks (including overdue)
$stat_open     = count(array_filter($allTasks, fn($t) => $t['status_code'] === 'open'));
$stat_progress = count(array_filter($allTasks, fn($t) => $t['status_code'] === 'in_progress'));
$stat_done     = count(array_filter($allTasks, fn($t) => $t['status_code'] === 'done'));
$total_tasks   = count($allTasks);

$statuses = $pdo->query("SELECT * FROM task_statuses ORDER BY id")->fetchAll();
?>
<!DOCTYPE html>
<html lang="id" data-theme="light">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Dashboard — TaskHub</title>
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
          <span class="role-badge role-user">User</span>
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

  <!-- MAIN -->
  <div class="main-wrapper">

    <!-- Page Header -->
    <div class="page-header">
      <div class="page-title-group">
        <div class="page-title">Halo, <?= htmlspecialchars($username) ?> 👋</div>
        <div class="page-subtitle">Berikut adalah ringkasan tugas kamu hari ini</div>
      </div>
    </div>

    <!-- Stats -->
    <div class="stats-grid">
      <div class="stat-card stat-card--open">
        <div style="font-size:1.5rem;margin-bottom:10px;">🔵</div>
        <div class="stat-value"><?= $stat_open ?></div>
        <div class="stat-label">Open</div>
      </div>
      <div class="stat-card stat-card--progress">
        <div style="font-size:1.5rem;margin-bottom:10px;">⚡</div>
        <div class="stat-value"><?= $stat_progress ?></div>
        <div class="stat-label">In Progress</div>
      </div>
      <div class="stat-card stat-card--done">
        <div style="font-size:1.5rem;margin-bottom:10px;">✅</div>
        <div class="stat-value"><?= $stat_done ?></div>
        <div class="stat-label">Selesai</div>
      </div>
      <div class="stat-card stat-card--total">
        <div style="font-size:1.5rem;margin-bottom:10px;">📋</div>
        <div class="stat-value"><?= $total_tasks ?></div>
        <div class="stat-label">Total</div>
      </div>
    </div>

    <!-- Filter -->
    <div class="filter-bar mb-4">
      <form method="GET" class="d-flex gap-2 w-100 flex-wrap align-items-end">
        <div class="filter-group">
          <label class="form-label">Cari Tugas</label>
          <input type="text" name="search" class="form-control" value="<?= htmlspecialchars($search) ?>" placeholder="Judul atau deskripsi…">
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
          <button type="submit" class="btn btn-primary">Filter</button>
          <?php if ($search || $status_filter): ?><a href="dashboard.php" class="btn btn-outline-secondary">Reset</a><?php endif; ?>
        </div>
      </form>
    </div>

    <!-- KANBAN BOARD -->
    <div class="section-heading">Papan Tugas</div>
    <div class="kanban-board">
      <?php
      $columns = [
        'open'        => ['label' => 'Open',        'class' => 'col-open'],
        'in_progress' => ['label' => 'In Progress',  'class' => 'col-progress'],
        'done'        => ['label' => 'Done',         'class' => 'col-done'],
      ];
      foreach ($columns as $code => $col):
        $colTasks = array_filter($paginatedTasks, fn($t) => $t['status_code'] === $code);
      ?>
        <div class="kanban-col <?= $col['class'] ?>">
          <div class="kanban-col-header">
            <div class="kanban-col-title">
              <span class="kanban-col-dot"></span>
              <?= $col['label'] ?>
            </div>
            <span class="col-count"><?= count($colTasks) ?></span>
          </div>
          <div class="kanban-col-body">
            <?php if (count($colTasks) > 0): ?>
              <?php foreach ($colTasks as $task):
                $now      = new DateTime();
                $deadline = $task['deadline'] ? new DateTime($task['deadline']) : null;
                $overdue  = $deadline && $deadline < $now && $task['status_code'] !== 'done';
              ?>
                <div class="task-card task-<?= $task['status_code'] ?> <?= $overdue ? 'task-overdue' : '' ?> <?= $task['task_source'] === 'admin' ? 'task-admin' : '' ?>"
                  data-id="<?= $task['id'] ?>"
                  data-title="<?= htmlspecialchars($task['title'], ENT_QUOTES) ?>"
                  data-desc="<?= htmlspecialchars($task['description'] ?? '', ENT_QUOTES) ?>"
                  data-status="<?= $task['status_code'] ?>"
                  data-status-label="<?= htmlspecialchars($task['status_label'], ENT_QUOTES) ?>"
                  data-deadline="<?= $task['deadline'] ? date('d M Y', strtotime($task['deadline'])) : '' ?>"
                  data-attach="<?= htmlspecialchars($task['attachment'] ?? '', ENT_QUOTES) ?>"
                  data-source="<?= $task['task_source'] ?>"
                  onclick="showTaskDetail(this)">
                  <div class="card-top">
                    <div class="card-title"><?= htmlspecialchars($task['title']) ?></div>
                    <div class="card-badges">
                      <?php if ($task['task_source'] === 'admin'): ?>
                        <span class="badge-admin">Admin</span>
                      <?php endif; ?>
                      <span class="status-pill status-<?= $task['status_code'] ?>"><?= htmlspecialchars($task['status_label']) ?></span>
                    </div>
                  </div>
                  <?php if ($task['description']): ?>
                    <div class="card-desc"><?= htmlspecialchars($task['description']) ?></div>
                  <?php endif; ?>
                  <?php if ($task['deadline']): ?>
                    <div class="deadline-chip <?= $overdue ? 'deadline-chip--overdue' : '' ?>">
                      <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
                        <rect x="3" y="4" width="18" height="18" rx="2" />
                        <line x1="16" y1="2" x2="16" y2="6" />
                        <line x1="8" y1="2" x2="8" y2="6" />
                        <line x1="3" y1="10" x2="21" y2="10" />
                      </svg>
                      <?= date('d M Y', strtotime($task['deadline'])) ?>
                      <?php if ($overdue): ?><span style="font-weight:800;"> · Terlambat</span><?php endif; ?>
                    </div>
                  <?php endif; ?>
                  <div class="card-actions" onclick="event.stopPropagation()">
                    <button class="tbtn tbtn-info" onclick="showTaskDetail(this.closest('.task-card'))">Detail</button>
                    <?php if ($task['status_code'] === 'open'): ?>
                      <button class="tbtn tbtn-start status-trigger"
                        data-id="<?= $task['id'] ?>" data-next="in_progress"
                        data-label="In Progress" data-title="<?= htmlspecialchars($task['title'], ENT_QUOTES) ?>">Mulai</button>
                    <?php elseif ($task['status_code'] === 'in_progress'): ?>
                      <button class="tbtn tbtn-done status-trigger"
                        data-id="<?= $task['id'] ?>" data-next="done"
                        data-label="Done" data-title="<?= htmlspecialchars($task['title'], ENT_QUOTES) ?>">Selesai</button>
                    <?php endif; ?>
                  </div>
                </div>
              <?php endforeach; ?>
            <?php else: ?>
              <div class="kanban-empty">
                <div>
                  <div class="kanban-empty-icon">🗂️</div>Tidak ada tugas
                </div>
              </div>
            <?php endif; ?>
          </div>
        </div>
      <?php endforeach; ?>
    </div>

    <!-- Pagination -->
    <?php if ($total_pages > 1): ?>
      <nav class="mb-4 mt-4">
        <ul class="pagination justify-content-center">
          <?php
            $base = 'dashboard.php?search=' . urlencode($search) . '&status_filter=' . urlencode($status_filter) . '&page=';
            for ($i = 1; $i <= $total_pages; $i++):
          ?>
            <li class="page-item <?= $i === $page ? 'active' : '' ?>">
              <a class="page-link" href="<?= $base . $i ?>"><?= $i ?></a>
            </li>
          <?php endfor; ?>
        </ul>
      </nav>
    <?php endif; ?>
  </div><!-- /main-wrapper -->

  <!-- MODAL DETAIL TUGAS (USER) -->
  <div class="modal fade" id="userDetailModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
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
          <div id="ud-source-wrap" style="margin-bottom:12px;display:none;">
            <span class="badge-admin">Dari Admin</span>
          </div>
          <div class="row g-3">
            <div class="col-12">
              <div class="detail-label">Judul</div>
              <div class="detail-value" id="ud-title" style="font-size:1rem;font-weight:700;"></div>
            </div>
            <div class="col-6">
              <div class="detail-label">Status</div>
              <div id="ud-status"></div>
            </div>
            <div class="col-6">
              <div class="detail-label">Tenggat</div>
              <div class="detail-value" id="ud-deadline"></div>
            </div>
            <div class="col-12">
              <div class="detail-label">Deskripsi</div>
              <div class="detail-value" id="ud-desc" style="white-space:pre-wrap;color:var(--text-muted);"></div>
            </div>
            <div class="col-12">
              <div class="detail-label">Lampiran</div>
              <div id="ud-attach-container"></div>
            </div>
          </div>
        </div>
        <div class="modal-footer"><button class="btn btn-outline-secondary" data-bs-dismiss="modal">Tutup</button></div>
      </div>
    </div>
  </div>

  <!-- MODAL STATUS CONFIRM  (for in_progress) -->
  <div class="modal fade" id="statusModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content">
        <div class="modal-header" id="statusModalHeader" style="border:none;">
          <div class="modal-icon-wrap" id="statusModalIcon"></div>
          <h5 class="modal-title ms-2 modal-title-white" id="statusModalTitle"></h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body text-center py-4">
          <p style="font-size:.85rem;color:var(--text-muted);margin-bottom:4px;">Tugas:</p>
          <p style="font-weight:700;font-size:.95rem;margin-bottom:6px;" id="statusModalTaskTitle"></p>
          <p style="color:var(--text-muted);font-size:.82rem;margin:0;" id="statusModalDesc"></p>
        </div>
        <div class="modal-footer justify-content-center gap-2">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
          <a href="#" id="statusModalConfirm" class="btn btn-primary px-4">Konfirmasi</a>
        </div>
      </div>
    </div>
  </div>

  <!-- MODAL COMPLETION (for done with attachment) -->
  <div class="modal fade" id="completionModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content">
        <div class="modal-header" style="background:linear-gradient(135deg,#10b981,#059669);border:none;">
          <div class="modal-icon-wrap">
            <span style="font-size:1.1rem;color:#fff;">✓</span>
          </div>
          <h5 class="modal-title ms-2 modal-title-white">Tandai Tugas Selesai</h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
        </div>

        <form method="POST" action="../task_action.php" enctype="multipart/form-data" id="completionForm">
          <input type="hidden" name="action" value="status">
          <input type="hidden" name="status" value="done">
          <input type="hidden" name="id" id="completionTaskId">

          <div class="modal-body py-4">
            <p style="font-size:.85rem;color:var(--text-muted);margin-bottom:4px;">Tugas:</p>
            <p style="font-weight:700;font-size:.95rem;margin-bottom:16px;" id="completionTaskTitle"></p>

            <div class="mb-3">
              <label class="form-label" style="font-weight:600;">
                Lampiran Penyelesaian <span style="color:var(--danger)">*</span>
              </label>

              <p style="font-size:.82rem;color:var(--text-muted);margin-bottom:8px;">
                Upload bukti penyelesaian tugas (screenshot, dokumen, dll)
              </p>

              <input type="file"
                name="completion_attachment"
                id="completionAttachment"
                class="form-control"
                accept=".pdf,.doc,.docx,.xls,.xlsx,.jpg,.jpeg,.png"
                required
                data-preview="completionPreview">

              <div class="file-preview-area" id="completionPreview"></div>
            </div>
          </div>

          <div class="modal-footer justify-content-center gap-2">
            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
            <button type="submit" class="btn btn-success px-4">Selesaikan Tugas</button>
          </div>
        </form>
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

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  <script src="../js/app.js"></script>
  <script>
    function showTaskDetail(card) {
      const d = card.dataset;
      document.getElementById('ud-title').textContent = d.title;
      document.getElementById('ud-desc').textContent = d.desc || 'Tidak ada deskripsi.';
      document.getElementById('ud-status').innerHTML = `<span class="status-pill status-${d.status}">${d.statusLabel}</span>`;
      document.getElementById('ud-deadline').textContent = d.deadline || '—';
      document.getElementById('ud-source-wrap').style.display = d.source === 'admin' ? 'block' : 'none';
      renderModalAttach(d.attach, 'ud-attach-container');
      new bootstrap.Modal(document.getElementById('userDetailModal')).show();
    }

    const statusModal = new bootstrap.Modal(document.getElementById('statusModal'));
    const completionModal = new bootstrap.Modal(document.getElementById('completionModal'));

    // Handle completion form submission with AJAX
    document.getElementById('completionForm')?.addEventListener('submit', e => {
      e.preventDefault();
      const formData = new FormData(e.target);
      fetch('../task_action.php', { method: 'POST', body: formData })
        .then(r => r.json())
        .catch(() => null)
        .then(data => {
          if (data && data.success) {
            completionModal.hide();
            showAlert('Tugas selesai! ✓', 'success');
            setTimeout(() => location.reload(), 1500);
          } else {
            showAlert(data?.msg || 'Gagal menyelesaikan tugas.', 'danger');
          }
        });
    });

    // Handle status change (in_progress)
    const updateStatusBtn = document.getElementById('statusModalConfirm');
    updateStatusBtn?.addEventListener('click', e => {
      e.preventDefault();
      const id = updateStatusBtn.getAttribute('data-task-id');
      fetch(`../task_action.php?action=status&id=${id}&status=in_progress`, { method: 'GET' })
        .then(r => r.json())
        .catch(() => null)
        .then(data => {
          if (data && data.success) {
            statusModal.hide();
            showAlert('Status diperbarui! ✓', 'success');
            setTimeout(() => location.reload(), 1500);
          } else {
            showAlert(data?.msg || 'Gagal memperbarui status.', 'danger');
          }
        });
    });

    document.querySelectorAll('.status-trigger').forEach(btn => {
      btn.addEventListener('click', e => {
        e.stopPropagation();
        if (btn.dataset.next === 'done') {
          document.getElementById('completionTaskId').value = btn.dataset.id;
          document.getElementById('completionTaskTitle').textContent = btn.dataset.title;
          completionModal.show();
        } else {
          document.getElementById('statusModalHeader').style.background = 'linear-gradient(135deg,#3b82f6,#2563eb)';
          document.getElementById('statusModalTitle').textContent = 'Mulai Mengerjakan';
          document.getElementById('statusModalIcon').innerHTML = '<span style="font-size:1.1rem;color:#fff;">▶</span>';
          document.getElementById('statusModalTaskTitle').textContent = btn.dataset.title;
          document.getElementById('statusModalDesc').textContent = 'Status akan berubah dari Open → In Progress.';
          updateStatusBtn.setAttribute('data-task-id', btn.dataset.id);
          updateStatusBtn.href = '#';
          statusModal.show();
        }
      });
    });
  </script>
</body>

</html>