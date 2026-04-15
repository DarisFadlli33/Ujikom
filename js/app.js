/* TaskHub — app.js */

// Terapkan tema tersimpan sebelum DOM render
(function () {
  document.documentElement.setAttribute('data-theme', localStorage.getItem('th-theme') || 'light');
})();

function getBase() {
  return (location.pathname.includes('/admin/') || location.pathname.includes('/user/'))
    ? '../task_action.php' : 'task_action.php';
}

function formSubmit(url, action, data) {
  const f = document.createElement('form');
  f.method = 'POST'; f.action = url;
  const add = (n, v) => { const i = document.createElement('input'); i.type = 'hidden'; i.name = n; i.value = v; f.appendChild(i); };
  add('action', action);
  Object.entries(data).forEach(([k, v]) => add(k, v));
  document.body.appendChild(f); f.submit();
}

function confirmDelete(title, desc, onOk) {
  const id = 'delModal';
  if (!document.getElementById(id)) {
    document.body.insertAdjacentHTML('beforeend', `
      <div class="modal fade" id="${id}" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
          <div class="modal-content">
            <div class="modal-header modal-header-danger">
              <h5 class="modal-title modal-title-white">Konfirmasi Hapus</h5>
              <button class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body text-center py-4">
              <p id="delMsg" style="font-weight:700;font-size:.95rem;margin-bottom:6px;"></p>
              <p id="delDesc" style="color:var(--text-muted);font-size:.85rem;margin:0;"></p>
            </div>
            <div class="modal-footer justify-content-center gap-2">
              <button class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
              <button class="btn btn-danger px-4" id="delOk">Hapus</button>
            </div>
          </div>
        </div>
      </div>`);
  }
  document.getElementById('delMsg').textContent = title;
  document.getElementById('delDesc').textContent = desc;
  const btn = document.getElementById('delOk');
  const newBtn = btn.cloneNode(true);
  btn.replaceWith(newBtn);
  const m = new bootstrap.Modal(document.getElementById(id));
  newBtn.addEventListener('click', () => { m.hide(); onOk(); });
  m.show();
}

function renderAttach(path, containerId) {
  const el = document.getElementById(containerId);
  if (!el) return;
  if (!path) { el.innerHTML = '<span style="color:var(--text-muted);font-size:.85rem;">Tidak ada lampiran.</span>'; return; }
  const ext = path.split('.').pop().toLowerCase();
  const fname = path.split('/').pop();
  const base = getBase();
  const dl = `${base}?action=download&file=${encodeURIComponent(path)}`;
  const view = dl + '&view=1';
  const icons = { pdf: '📄', doc: '📝', docx: '📝', xls: '📊', xlsx: '📊' };
  const icon = icons[ext] || '📎';
  const dlBtn = `<a href="${dl}" class="attach-download-btn" download>
    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round">
      <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/>
    </svg>Download</a>`;

  if (['jpg', 'jpeg', 'png', 'gif', 'webp'].includes(ext)) {
    el.innerHTML = `<div class="modal-attach-preview">
      <img src="${view}" class="modal-attach-image" style="cursor:pointer;" onclick="window.open('${view}','_blank')" alt="${fname}">
      <div class="modal-attach-footer">
        <div><div class="modal-attach-name">${fname}</div><div class="modal-attach-type">Klik untuk perbesar</div></div>
        ${dlBtn}
      </div></div>`;
  } else if (ext === 'pdf') {
    el.innerHTML = `<div class="modal-attach-preview">
      <div style="display:flex;align-items:center;gap:10px;padding:12px;background:var(--accent-light);">
        <span style="font-size:1.5rem;">📄</span>
        <div style="flex:1"><div class="modal-attach-name">${fname}</div><div class="modal-attach-type">PDF</div></div>
        ${dlBtn}
      </div>
      <iframe src="${view}" style="width:100%;height:460px;border:none;display:block;"></iframe>
    </div>`;
  } else if (['doc', 'docx', 'xls', 'xlsx'].includes(ext)) {
    const root = location.origin + location.pathname.replace(/\/[^/]*$/, '/');
    const gdoc = `https://docs.google.com/viewer?url=${encodeURIComponent(root + (location.pathname.includes('/admin/') || location.pathname.includes('/user/') ? '../' : '') + path)}&embedded=true`;
    el.innerHTML = `<div class="modal-attach-preview">
      <div style="display:flex;align-items:center;gap:10px;padding:12px;background:var(--accent-light);">
        <span style="font-size:1.5rem;">${icon}</span>
        <div style="flex:1"><div class="modal-attach-name">${fname}</div><div class="modal-attach-type">${ext.toUpperCase()} via Google Docs</div></div>
        ${dlBtn}
      </div>
      <iframe src="${gdoc}" style="width:100%;height:460px;border:none;display:block;"></iframe>
    </div>`;
  } else {
    el.innerHTML = `<div class="modal-attach-preview">
      <div class="modal-attach-footer" style="padding:14px;">
        <div style="display:flex;align-items:center;gap:10px;">
          <span style="font-size:1.5rem;">${icon}</span>
          <div><div class="modal-attach-name">${fname}</div><div class="modal-attach-type">${ext.toUpperCase()}</div></div>
        </div>
        ${dlBtn}
      </div></div>`;
  }
}

window.renderModalAttach = renderAttach;
window.confirmDelete = confirmDelete;

window.viewAttach = function (path) {
  renderAttach(path, 'attachModalBody');
  new bootstrap.Modal(document.getElementById('attachModal')).show();
};

window.deleteUser = function (uid, uname, tasks) {
  confirmDelete(
    `Hapus user "${uname}"?`,
    `${tasks} tugas akan ikut dihapus. Tidak bisa dibatalkan.`,
    () => { location.href = getBase() + '?action=delete_user&uid=' + uid; }
  );
};

window.assignToUser = function (uid, uname) {
  document.querySelectorAll('.user-assign-cb').forEach(cb => cb.checked = cb.value == uid);
  const lbl = document.getElementById('assignCountLabel');
  if (lbl) lbl.textContent = `1 user dipilih (${uname})`;
  openAddTask();
};

document.addEventListener('DOMContentLoaded', () => {

  // Ikon tema
  const updateIcon = t => { const i = document.getElementById('themeIcon'); if (i) i.textContent = t === 'dark' ? '☀️' : '🌙'; };
  updateIcon(document.documentElement.getAttribute('data-theme'));
  document.getElementById('themeToggle')?.addEventListener('click', () => {
    const next = document.documentElement.getAttribute('data-theme') === 'dark' ? 'light' : 'dark';
    document.documentElement.setAttribute('data-theme', next);
    localStorage.setItem('th-theme', next);
    updateIcon(next);
  });

  // Toast
  window.showAlert = function (msg, type = 'info') {
    const c = document.getElementById('alertContainer'); if (!c) return;
    const id = 'a' + Date.now();
    const cls = { success: 'alert-custom-success', danger: 'alert-custom-danger', warning: 'alert-custom-warning', info: 'alert-custom-info' }[type] || 'alert-custom-info';
    const ico = { success: '✓', danger: '✕', warning: '⚠', info: 'ℹ' }[type] || 'ℹ';
    c.insertAdjacentHTML('beforeend', `<div id="${id}" class="alert-custom ${cls}" style="margin-bottom:8px;pointer-events:auto;">
      <span>${ico}</span><span style="flex:1;">${msg}</span>
      <button onclick="this.parentElement.remove()" style="margin-left:auto;background:rgba(255,255,255,.25);border:none;cursor:pointer;padding:2px 7px;border-radius:6px;color:inherit;">✕</button>
    </div>`);
    setTimeout(() => { const e = document.getElementById(id); if (e) { e.style.transition = 'opacity .4s'; e.style.opacity = '0'; setTimeout(() => e.remove(), 400); } }, 4200);
  };

  // Logout modal
  document.getElementById('logoutBtn')?.addEventListener('click', e => {
    e.preventDefault();
    new bootstrap.Modal(document.getElementById('logoutModal')).show();
  });

  // Panel tambah tugas
  const panel = document.getElementById('addTaskPanel');
  window.openAddTask = () => { panel?.classList.add('active'); document.body.style.overflow = 'hidden'; };
  window.closeAddTask = () => { panel?.classList.remove('active'); document.body.style.overflow = ''; };
  document.querySelectorAll('[data-open-add-task]').forEach(el => el.addEventListener('click', openAddTask));
  document.querySelectorAll('[data-close-add-task]').forEach(el => el.addEventListener('click', closeAddTask));
  document.getElementById('addTaskOverlay')?.addEventListener('click', closeAddTask);
  document.addEventListener('keydown', e => { if (e.key === 'Escape') closeAddTask(); });

  // Preview file sebelum upload
  document.querySelectorAll('input[type="file"][data-preview]').forEach(inp => {
    const area = document.getElementById(inp.dataset.preview); if (!area) return;
    inp.addEventListener('change', () => {
      const file = inp.files[0];
      if (!file) { area.classList.remove('visible'); return; }
      const ext = file.name.split('.').pop().toLowerCase();
      const size = file.size < 1048576 ? (file.size / 1024).toFixed(1) + ' KB' : (file.size / 1048576).toFixed(1) + ' MB';
      const ico = { pdf: '📄', doc: '📝', docx: '📝', xls: '📊', xlsx: '📊' }[ext] || '📎';
      const iid = inp.id, pid = inp.dataset.preview;
      const rmBtn = `<button type="button" class="file-preview-remove" onclick="clearFile('${iid}','${pid}')">✕</button>`;
      if (['jpg', 'jpeg', 'png', 'gif', 'webp'].includes(ext)) {
        const r = new FileReader();
        r.onload = ev => {
          area.innerHTML = `<img src="${ev.target.result}" class="file-preview-image">
            <div class="file-preview-info"><div class="file-preview-name">${file.name}</div><div class="file-preview-size">${size} · Gambar</div></div>${rmBtn}`;
          area.classList.add('visible');
        };
        r.readAsDataURL(file);
      } else {
        area.innerHTML = `<div class="file-preview-icon" style="background:var(--accent-light);">${ico}</div>
          <div class="file-preview-info"><div class="file-preview-name">${file.name}</div><div class="file-preview-size">${size}</div></div>${rmBtn}`;
        area.classList.add('visible');
      }
    });
  });
  window.clearFile = (iid, pid) => {
    const i = document.getElementById(iid), a = document.getElementById(pid);
    if (i) i.value = ''; if (a) { a.innerHTML = ''; a.classList.remove('visible'); }
  };

  // Bulk checkbox — tugas
  const tmCb = document.getElementById('masterTaskCheckbox');
  const tBar = document.getElementById('taskBulkActionBar');
  const tCnt = document.getElementById('taskBulkCount');
  const getTCbs = () => document.querySelectorAll('.task-row-cb');
  function syncTasks() {
    const all = getTCbs(), checked = [...all].filter(c => c.checked);
    tBar?.classList.toggle('visible', checked.length > 0);
    if (tCnt) tCnt.textContent = checked.length + ' tugas dipilih';
    if (tmCb) { tmCb.indeterminate = checked.length > 0 && checked.length < all.length; tmCb.checked = all.length > 0 && checked.length === all.length; }
    all.forEach(cb => cb.closest('tr')?.classList.toggle('row-selected', cb.checked));
  }
  tmCb?.addEventListener('change', () => { getTCbs().forEach(cb => cb.checked = tmCb.checked); syncTasks(); });
  document.querySelectorAll('.task-row-cb').forEach(cb => cb.addEventListener('change', syncTasks));

  document.getElementById('taskBulkDeleteBtn')?.addEventListener('click', () => {
    const ids = [...document.querySelectorAll('.task-row-cb:checked')].map(c => c.value);
    if (!ids.length) return;
    confirmDelete(`Hapus ${ids.length} tugas?`, 'Tugas dan lampiran akan dihapus permanen.',
      () => formSubmit(getBase(), 'bulk_delete_tasks', { ids: ids.join(',') }));
  });

  // Bulk checkbox — user
  const mCb = document.getElementById('masterCheckbox');
  const uBar = document.getElementById('bulkActionBar');
  const uCnt = document.getElementById('bulkCount');
  const getUCbs = () => document.querySelectorAll('.row-cb');
  function syncUsers() {
    const all = getUCbs(), checked = [...all].filter(c => c.checked);
    uBar?.classList.toggle('visible', checked.length > 0);
    if (uCnt) uCnt.textContent = checked.length + ' dipilih';
    if (mCb) { mCb.indeterminate = checked.length > 0 && checked.length < all.length; mCb.checked = all.length > 0 && checked.length === all.length; }
    all.forEach(cb => cb.closest('tr')?.classList.toggle('row-selected', cb.checked));
  }
  mCb?.addEventListener('change', () => { getUCbs().forEach(cb => cb.checked = mCb.checked); syncUsers(); });
  document.querySelectorAll('.row-cb').forEach(cb => cb.addEventListener('change', syncUsers));

  document.getElementById('bulkDeleteBtn')?.addEventListener('click', () => {
    const ids = [...document.querySelectorAll('.row-cb:checked')].map(c => c.value);
    if (!ids.length) return;
    confirmDelete(`Hapus ${ids.length} user?`, 'Semua tugas mereka ikut dihapus.',
      () => formSubmit(getBase(), 'bulk_delete_users', { ids: ids.join(',') }));
  });

  document.getElementById('bulkAssignBtn')?.addEventListener('click', () => {
    const ids = [...document.querySelectorAll('.row-cb:checked')].map(c => c.value);
    if (!ids.length) return;
    const h = document.getElementById('bulkUserIds'); if (h) h.value = ids.join(',');
    document.querySelectorAll('.user-assign-cb').forEach(cb => cb.checked = ids.includes(cb.value));
    const cnt = ids.length, lbl = document.getElementById('assignCountLabel');
    if (lbl) lbl.textContent = `${cnt} user dipilih`;
    openAddTask();
  });

  // Pilih semua user di form tambah tugas
  const selAll = document.getElementById('selectAllUsers');
  const getACbs = () => document.querySelectorAll('.user-assign-cb');
  function updateCount() {
    const n = [...getACbs()].filter(c => c.checked).length;
    const lbl = document.getElementById('assignCountLabel');
    if (lbl) lbl.textContent = n ? `${n} user dipilih` : 'Pilih user yang dituju';
  }
  selAll?.addEventListener('change', () => { getACbs().forEach(cb => cb.checked = selAll.checked); updateCount(); });
  document.querySelectorAll('.user-assign-cb').forEach(cb => cb.addEventListener('change', () => {
    const all = getACbs();
    if (selAll) { selAll.indeterminate = [...all].some(c => c.checked) && [...all].some(c => !c.checked); selAll.checked = [...all].every(c => c.checked); }
    updateCount();
  }));

  // Animasi kartu masuk
  document.querySelectorAll('.task-card, .stat-card').forEach((el, i) => {
    el.style.cssText += 'opacity:0;transform:translateY(14px)';
    setTimeout(() => { el.style.transition = 'opacity .35s ease,transform .35s ease'; el.style.opacity = '1'; el.style.transform = ''; }, 50 + i * 45);
  });
});
