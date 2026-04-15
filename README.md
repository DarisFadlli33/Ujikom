# TaskHub - Dokumentasi Aplikasi

## 📋 Daftar Isi
1. [Gambaran Umum](#gambaran-umum)
2. [Struktur Database](#struktur-database)
3. [Penjelasan File dan Kode](#penjelasan-file-dan-kode)
4. [Alur Autentikasi](#alur-autentikasi)
5. [Alur Manajemen Task](#alur-manajemen-task)
6. [Frontend & JavaScript](#frontend--javascript)

---

## 🎯 Gambaran Umum

**TaskHub** adalah aplikasi manajemen tugas (task management) berbasis web menggunakan:
- **Backend**: PHP 7.4+
- **Database**: MySQL dengan PDO (PHP Data Objects)
- **Frontend**: HTML5, CSS3 dengan Bootstrap 5.3.0
- **Authentication**: Session-based dengan password hashing bcrypt
- **Fitur**: Role-based access control (Admin & User), task management dengan status tracking

---

## 💾 Struktur Database

### Tabel: `users`
```sql
CREATE TABLE users (
  id INT PRIMARY KEY AUTO_INCREMENT,
  username VARCHAR(255) UNIQUE NOT NULL,
  password VARCHAR(255) NOT NULL,      -- Hashed dengan PASSWORD_DEFAULT (bcrypt)
  role_id INT FOREIGN KEY,              -- Referensi ke tabel roles
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```
**Penjelasan**: Menyimpan data pengguna aplikasi. Password disimpan terenkripsi menggunakan `password_hash()`.

### Tabel: `roles`
```sql
CREATE TABLE roles (
  id INT PRIMARY KEY AUTO_INCREMENT,
  name VARCHAR(50) UNIQUE NOT NULL,     -- 'user' atau 'admin'
  description VARCHAR(255)
);
```
**Penjelasan**: Menyimpan tipe role. Admin memiliki akses penuh ke semua task, User hanya melihat task miliknya.

### Tabel: `tasks`
```sql
CREATE TABLE tasks (
  id INT PRIMARY KEY AUTO_INCREMENT,
  title VARCHAR(255) NOT NULL,
  description TEXT,
  user_id INT FOREIGN KEY,              -- Pemilik task (user yang ditugasi)
  created_by INT FOREIGN KEY,           -- Admin yang membuat task
  status_id INT FOREIGN KEY,            -- Referensi ke task_statuses
  deadline DATETIME,
  completion_attachment VARCHAR(255),   -- File bukti penyelesaian
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
```
**Penjelasan**: Menyimpan data task. Admin membuat task dan menugaskan ke user tertentu.

### Tabel: `task_statuses`
```sql
CREATE TABLE task_statuses (
  id INT PRIMARY KEY AUTO_INCREMENT,
  code VARCHAR(20) UNIQUE NOT NULL,     -- 'open', 'in_progress', 'done'
  label VARCHAR(50),
  description VARCHAR(255)
);
```
**Penjelasan**: Menyimpan status task yang dapat diperbarui (Open → In Progress → Done).

### Tabel: `attachments`
```sql
CREATE TABLE attachments (
  id INT PRIMARY KEY AUTO_INCREMENT,
  task_id INT FOREIGN KEY,
  file_path VARCHAR(255) NOT NULL,
  uploaded_by INT FOREIGN KEY,
  uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```
**Penjelasan**: Menyimpan file yang di-upload sebagai attachment untuk task tertentu.

---

## 📄 Penjelasan File dan Kode

### 1. **bootstrap.php** - File Inisialisasi Aplikasi
**Lokasi**: `bootstrap.php`

**Tujuan**: Mengatur konfigurasi awal aplikasi, memulai session, dan menginclude file kritis.

**Kode Utama**:
```php
<?php
session_start();  // Mulai session PHP untuk menyimpan data user
require_once __DIR__ . '/db.php';        // Include koneksi database
require_once __DIR__ . '/auth.php';      // Include fungsi autentikasi
```

**Penjelasan Baris Demi Baris**:
- `session_start()`: Memulai session PHP sehingga bisa menggunakan `$_SESSION` untuk menyimpan data login user
- `require_once`: Menginclude file hanya sekali, jika sudah diinclude tidak akan diinclude lagi
- Semua file yang membutuhkan database dan autentikasi harus meng-include file ini terlebih dahulu

---

### 2. **db.php** - Koneksi Database
**Lokasi**: `db.php`

**Tujuan**: Membuat koneksi ke database MySQL menggunakan PDO (PHP Data Objects).

**Kode Utama**:
```php
<?php
try {
    $pdo = new PDO(
        'mysql:host=localhost;dbname=taskhub;charset=utf8mb4',
        'root',                    // Username MySQL
        '',                        // Password MySQL
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,      // Throw exceptions saat error
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC  // Fetch hasil sebagai array asosiatif
        ]
    );
} catch (PDOException $e) {
    die('Database Error: ' . $e->getMessage());  // Tampilkan error jika koneksi gagal
}
?>
```

**Penjelasan Kode**:
| Bagian | Penjelasan |
|--------|-----------|
| `PDO()` | Constructor untuk membuat koneksi database |
| `mysql:host=localhost;dbname=taskhub` | Host lokal dengan database bernama `taskhub` |
| `charset=utf8mb4` | Encoding untuk mendukung karakter Unicode |
| `PDO::ATTR_ERRMODE => ERRMODE_EXCEPTION` | Mode error yang melempar exception daripada warning |
| `PDO::ATTR_DEFAULT_FETCH_MODE => FETCH_ASSOC` | Default fetch menghasilkan array asosiatif `['key' => 'value']` |
| `PDOException` | Exception khusus untuk error PDO |

**Keamanan**: Menggunakan PDO dengan prepared statements untuk mencegah SQL injection.

---

### 3. **auth.php** - Fungsi Autentikasi
**Lokasi**: `auth.php`

**Tujuan**: Menyediakan fungsi-fungsi untuk memverifikasi login dan mengecek role user.

**Kode Utama**:
```php
<?php
// Cek apakah user sudah login
function requireLogin() {
    if (empty($_SESSION['user_id'])) {
        header('Location: index.php?message=Login+Required&type=error');
        exit();
    }
}

// Cek apakah user memiliki role tertentu
function requireRole($role) {
    requireLogin();
    if ($_SESSION['role'] !== $role) {
        header('Location: index.php?message=Access+Denied&type=error');
        exit();
    }
}

// Shortcut: Cek apakah user adalah admin
function requireAdmin() {
    requireRole('admin');
}

// Shortcut: Cek apakah user adalah user biasa
function requireUser() {
    requireRole('user');
}
?>
```

**Penjelasan Fungsi**:

| Fungsi | Parameter | Penjelasan |
|--------|-----------|-----------|
| `requireLogin()` | - | Mengecek apakah `$_SESSION['user_id']` ada. Jika tidak, redirect ke login |
| `requireRole()` | `$role` | Mengecek role user (`'admin'` atau `'user'`). Jika tidak sesuai, redirect |
| `requireAdmin()` | - | Wrapper `requireRole('admin')` untuk memastikan hanya admin yang bisa akses |
| `requireUser()` | - | Wrapper `requireRole('user')` untuk memastikan hanya user biasa yang bisa akses |

**Cara Kerja**:
1. Jika user belum login → `$_SESSION['user_id']` kosong → redirect ke login
2. Jika user login tapi role salah → redirect dengan error
3. Jika semua OK → script melanjutkan eksekusi

---

### 4. **index.php** - Halaman Login
**Lokasi**: `index.php`

**Tujuan**: Menampilkan form login dan memproses autentikasi user.

**Kode Logika Login**:
```php
<?php
require_once 'bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');
    
    // Validasi input
    if (empty($username) || empty($password)) {
        $error = 'Username dan password harus diisi';
    } else {
        // Query user dari database
        $stmt = $pdo->prepare("SELECT id, username, password, role_id FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch();
        
        if ($user && password_verify($password, $user['password'])) {
            // Password benar → login berhasil
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role_id'] = $user['role_id'];
            
            // Ambil role dari database
            $roleStmt = $pdo->prepare("SELECT name FROM roles WHERE id = ?");
            $roleStmt->execute([$user['role_id']]);
            $roleData = $roleStmt->fetch();
            $_SESSION['role'] = $roleData['name'];  // 'admin' atau 'user'
            
            // Redirect ke dashboard
            header('Location: dashboard.php');
            exit();
        } else {
            // Password salah atau user tidak ada
            $error = 'Username atau password salah';
        }
    }
}
?>
```

**Alur Login Step-by-Step**:
```
User Input Username & Password
           ↓
Cek input kosong?
    ✗ Ya → Tampilkan error
    ✓ Tidak
           ↓
Query database: SELECT user WHERE username = ?
           ↓
User ditemukan?
    ✗ Tidak → Error "Username atau password salah"
    ✓ Ya
           ↓
Verifikasi password dengan password_verify()
           ↓
Password benar?
    ✗ Tidak → Error "Username atau password salah"
    ✓ Ya
           ↓
Simpan ke $_SESSION:
  - user_id
  - username
  - role
           ↓
Redirect ke dashboard.php
```

**Keamanan**:
- `password_verify()`: Membandingkan input password dengan hash yang tersimpan (bcrypt)
- `trim()`: Menghilangkan whitespace untuk validasi input
- Prepared statements: Mencegah SQL injection
- Tidak menampilkan detail error spesifik untuk user (cegah information leakage)

---

### 5. **register.php** - Halaman Registrasi
**Lokasi**: `register.php`

**Tujuan**: Menampilkan form registrasi dan membuat akun user baru.

**Kode Registrasi**:
```php
<?php
require_once 'bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $password_confirm = trim($_POST['password_confirm'] ?? '');
    
    // Validasi username: minimal 3 karakter
    if (strlen($username) < 3) {
        $error = 'Username minimal 3 karakter';
    }
    // Validasi password: minimal 4 karakter
    else if (strlen($password) < 4) {
        $error = 'Password minimal 4 karakter';
    }
    // Validasi kecocokan password
    else if ($password !== $password_confirm) {
        $error = 'Password tidak cocok';
    }
    else {
        // Cek apakah username sudah ada
        $checkStmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = ?");
        $checkStmt->execute([$username]);
        $exists = $checkStmt->fetchColumn();
        
        if ($exists > 0) {
            $error = 'Username sudah terdaftar';
        } else {
            // Hash password menggunakan bcrypt (PASSWORD_DEFAULT)
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            
            // Ambil ID role 'user' (bukan admin)
            $roleStmt = $pdo->prepare("SELECT id FROM roles WHERE name = 'user'");
            $roleStmt->execute();
            $roleId = $roleStmt->fetchColumn();
            
            // Insert user baru
            $insertStmt = $pdo->prepare(
                "INSERT INTO users (username, password, role_id) VALUES (?, ?, ?)"
            );
            $insertStmt->execute([$username, $hashedPassword, $roleId]);
            
            // Registrasi berhasil
            $success = 'Registrasi berhasil! Silakan login.';
        }
    }
}
?>
```

**Validasi Registrasi**:
| Kondisi | Aksi | Hasil |
|---------|------|-------|
| Username < 3 karakter | Tampilkan error | Registrasi gagal |
| Password < 4 karakter | Tampilkan error | Registrasi gagal |
| Password tidak cocok | Tampilkan error | Registrasi gagal |
| Username sudah terdaftar | Tampilkan error | Registrasi gagal |
| Semua valid | Hash password → Insert ke DB | Registrasi berhasil |

**Keamanan Password**:
```php
$hashedPassword = password_hash($password, PASSWORD_DEFAULT);
// PASSWORD_DEFAULT menggunakan bcrypt (algoritma hashing terkuat di PHP)
// Hasil: $2y$10$N9qo8ucoaw...  (format hash bcrypt)
```

---

### 6. **logout.php** - Logout User
**Lokasi**: `logout.php`

**Tujuan**: Menghapus session dan logout user.

**Kode**:
```php
<?php
require_once 'bootstrap.php';

// Hapus semua data session
session_unset();  // Hapus semua variabel dalam $_SESSION
session_destroy(); // Hapus file session di server

// Redirect ke halaman login
header('Location: index.php?message=Logout+Berhasil&type=success');
exit();
?>
```

**Penjelasan**:
- `session_unset()`: Menghapus semua data `$_SESSION`
- `session_destroy()`: Menghapus file session dari server
- Setelah itu, user tidak lagi login dan harus login ulang

---

### 7. **dashboard.php** - Router Berdasarkan Role
**Lokasi**: `dashboard.php`

**Tujuan**: Mengarahkan user ke dashboard yang sesuai berdasarkan role-nya.

**Kode**:
```php
<?php
require_once 'bootstrap.php';
requireLogin();  // Pastikan user sudah login

// Redirect ke dashboard sesuai role
if ($_SESSION['role'] === 'admin') {
    header('Location: admin/dashboard.php');
} else {
    header('Location: user/dashboard.php');
}
exit();
?>
```

**Alur**:
```
User akses dashboard.php
           ↓
Cek apakah user login?
    ✗ Tidak → Redirect ke login
    ✓ Ya
           ↓
Cek role user
    ├─ 'admin' → Redirect ke admin/dashboard.php
    └─ 'user'  → Redirect ke user/dashboard.php
```

---

### 8. **admin/dashboard.php** - Dashboard Admin
**Lokasi**: `admin/dashboard.php`

**Tujuan**: Menampilkan dashboard admin dengan semua task dan statistik.

**Fitur Utama**:
1. **Statistik**: Total users, admins, tasks, dan per-status count
2. **Per-Status Limit**: Tampilkan maksimal 3 task per status
3. **Search & Filter**: Cari berdasarkan title, description, username, atau status
4. **Pagination**: Pagination untuk browsing task

**Kode Utama - UNION Query untuk Per-Status Limit**:
```php
<?php
requireAdmin();

// Ambil statistik
$total_users  = $pdo->query("SELECT COUNT(*) FROM users WHERE role_id=(SELECT id FROM roles WHERE name='user')")->fetchColumn();
$total_admins = $pdo->query("SELECT COUNT(*) FROM users WHERE role_id=(SELECT id FROM roles WHERE name='admin')")->fetchColumn();
$total_tasks  = $pdo->query("SELECT COUNT(*) FROM tasks")->fetchColumn();
$stat_open    = $pdo->query("SELECT COUNT(*) FROM tasks t JOIN task_statuses s ON t.status_id=s.id WHERE s.code='open'")->fetchColumn();
$stat_progress = $pdo->query("SELECT COUNT(*) FROM tasks t JOIN task_statuses s ON t.status_id=s.id WHERE s.code='in_progress'")->fetchColumn();
$stat_done    = $pdo->query("SELECT COUNT(*) FROM tasks t JOIN task_statuses s ON t.status_id=s.id WHERE s.code='done'")->fetchColumn();

// Ambil parameter dari GET
$search        = trim($_GET['search'] ?? '');
$status_filter = trim($_GET['status_filter'] ?? '');
$page          = max(1, intval($_GET['page'] ?? 1));
$limit         = 12;
$offset        = ($page - 1) * $limit;
$status_limit  = 3;  // Max 3 items per status

// Build search suffix
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

// UNION Query - Ambil 3 task terbaru dari setiap status
$sql = "(SELECT t.*, u.username AS owner, s.code AS status_code, s.label AS status_label, t.completion_attachment 
        FROM tasks t 
        JOIN users u ON t.user_id=u.id 
        JOIN task_statuses s ON t.status_id=s.id 
        WHERE s.code='open' $search_suffix 
        ORDER BY t.created_at DESC 
        LIMIT $status_limit)
UNION ALL
(SELECT t.*, u.username AS owner, s.code AS status_code, s.label AS status_label, t.completion_attachment 
        FROM tasks t 
        JOIN users u ON t.user_id=u.id 
        JOIN task_statuses s ON t.status_id=s.id 
        WHERE s.code='in_progress' $search_suffix 
        ORDER BY t.created_at DESC 
        LIMIT $status_limit)
UNION ALL
(SELECT t.*, u.username AS owner, s.code AS status_code, s.label AS status_label, t.completion_attachment 
        FROM tasks t 
        JOIN users u ON t.user_id=u.id 
        JOIN task_statuses s ON t.status_id=s.id 
        WHERE s.code='done' $search_suffix 
        ORDER BY t.created_at DESC 
        LIMIT $status_limit)
ORDER BY created_at DESC 
LIMIT $limit 
OFFSET $offset";

// Jalankan query dengan parameter (3 kali untuk 3 status)
$params = array_merge($params_list, $params_list, $params_list);
$taskStmt = $pdo->prepare($sql);
$taskStmt->execute($params);
$tasks = $taskStmt->fetchAll();
?>
```

**Penjelasan UNION Query**:
```
┌─────────────────────┐
│ Status: OPEN (3)    │  ← Ambil max 3 task dengan status 'open'
│ - Task A            │
│ - Task B            │
│ - Task C            │
└─────────────────────┘
             ↓
        UNION ALL
             ↓
┌─────────────────────┐
│ Status: IN PROGRESS │  ← Ambil max 3 task dengan status 'in_progress'
│ - Task D            │
│ - Task E            │
│ - Task F            │
└─────────────────────┘
             ↓
        UNION ALL
             ↓
┌─────────────────────┐
│ Status: DONE (3)    │  ← Ambil max 3 task dengan status 'done'
│ - Task G            │
│ - Task H            │
│ - Task I            │
└─────────────────────┘
             ↓
    Final Result: 6-9 tasks
     (3 per status maksimum)
```

**Search & Filter**:
- Search default: Cari di title, description, atau username
- Filter status: Filter hanya 1 status tertentu
- Akses: `?search=keyword&status_filter=open`

---

### 9. **user/dashboard.php** - Dashboard User
**Lokasi**: `user/dashboard.php`

**Tujuan**: Menampilkan task yang ditugaskan ke user tertentu.

**Fitur Utama**:
1. **Task Milik User**: Hanya tampilkan task dengan `user_id = $_SESSION['user_id']`
2. **Sembunyikan Task Overdue**: Task yang sudah melewati deadline dan belum selesai disembunyikan
3. **Search & Filter**: Cari dan filter status
4. **Pagination**: 3 item per halaman

**Kode Utama**:
```php
<?php
requireUser();

$user_id  = $_SESSION['user_id'];
$username = $_SESSION['username'];

// Ambil parameter
$search        = trim($_GET['search'] ?? '');
$status_filter = trim($_GET['status_filter'] ?? '');

// Build WHERE clause
$where  = ["t.user_id = ?"];  // ← PENTING: Hanya task user ini
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

// Query task
$taskStmt = $pdo->prepare("SELECT t.*, s.code AS status_code, s.label AS status_label,
  CASE WHEN t.created_by != t.user_id THEN 'admin' ELSE 'user' END AS task_source
  FROM tasks t 
  JOIN task_statuses s ON t.status_id=s.id
  $whereSQL 
  ORDER BY t.deadline ASC, t.created_at DESC");
$taskStmt->execute($params);
$allTasks = $taskStmt->fetchAll();

// Filter task overdue (disembunyikan dari user)
$now = new DateTime();
$tasks = array_filter($allTasks, function($t) use ($now) {
    $deadline = $t['deadline'] ? new DateTime($t['deadline']) : null;
    $overdue = $deadline && $deadline < $now && $t['status_code'] !== 'done';
    return !$overdue;  // return true = tampilkan, false = sembunyikan
});
$tasks = array_values($tasks);  // Re-index array

// Pagination: 3 items per page
$page = max(1, intval($_GET['page'] ?? 1));
$limit = 3;
$offset = ($page - 1) * $limit;
$total_pages = ceil(count($tasks) / $limit);
$paginatedTasks = array_slice($tasks, $offset, $limit);
?>
```

**Penjelasan Penting**:
| Kode | Penjelasan |
|------|-----------|
| `WHERE t.user_id = ?` | Hanya ambil task milik user |
| `task_source` | Cek apakah task dibuat oleh admin (`created_by != user_id`) atau user sendiri |
| `Overdue filtering` | Sembunyikan task yang sudah melewati deadline tapi belum selesai |
| `array_filter()` | Filter array task sesuai kondisi overdue |
| `array_values()` | Re-index array agar key mulai dari 0 |

---

### 10. **task_action.php** - Handler untuk Aksi Task
**Lokasi**: `task_action.php`

**Tujuan**: Memproses action CRUD untuk task (Create, Read, Update, Delete).

**Kode Utama - Handler Aksi**:
```php
<?php
require_once 'bootstrap.php';
requireLogin();

$action = $_POST['action'] ?? '';

switch ($action) {
    // ===== ADD TASK =====
    case 'add':
        requireAdmin();  // Hanya admin bisa membuat task
        
        $title = trim($_POST['title'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $user_id = intval($_POST['user_id'] ?? 0);
        $deadline = $_POST['deadline'] ?? null;
        
        if (empty($title) || empty($user_id)) {
            die('Title dan user harus diisi');
        }
        
        // Insert task baru dengan status 'open' (status_id=1)
        $stmt = $pdo->prepare(
            "INSERT INTO tasks (title, description, user_id, created_by, status_id, deadline, created_at) 
             VALUES (?, ?, ?, ?, 1, ?, NOW())"
        );
        $stmt->execute([$title, $description, $user_id, $_SESSION['user_id'], $deadline]);
        
        header('Location: admin/dashboard.php?message=Task+ditambahkan&type=success');
        break;
    
    // ===== EDIT TASK (ADMIN) =====
    case 'edit_admin':
        requireAdmin();
        
        $task_id = intval($_POST['task_id'] ?? 0);
        $title = trim($_POST['title'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $user_id = intval($_POST['user_id'] ?? 0);
        $deadline = $_POST['deadline'] ?? null;
        $status = trim($_POST['status'] ?? 'open');
        
        if (empty($task_id) || empty($title)) {
            die('ID task dan title harus diisi');
        }
        
        // Konversi status code ke status_id
        $statusMap = ['open' => 1, 'in_progress' => 2, 'done' => 3];
        $status_id = $statusMap[$status] ?? 1;
        
        // Update task (hanya admin yang bisa edit)
        $stmt = $pdo->prepare(
            "UPDATE tasks 
             SET title = ?, description = ?, user_id = ?, status_id = ?, deadline = ?, updated_at = NOW()
             WHERE id = ?"
        );
        $stmt->execute([$title, $description, $user_id, $status_id, $deadline, $task_id]);
        
        header('Location: admin/dashboard.php?message=Task+diperbarui&type=success');
        break;
    
    // ===== DELETE TASK (ADMIN) =====
    case 'delete_admin':
        requireAdmin();
        
        $task_id = intval($_POST['task_id'] ?? 0);
        
        if (empty($task_id)) {
            die('ID task harus diisi');
        }
        
        // Hapus attachments terkait
        $deleteAttachStmt = $pdo->prepare("DELETE FROM attachments WHERE task_id = ?");
        $deleteAttachStmt->execute([$task_id]);
        
        // Hapus task
        $deleteStmt = $pdo->prepare("DELETE FROM tasks WHERE id = ?");
        $deleteStmt->execute([$task_id]);
        
        header('Location: admin/dashboard.php?message=Task+dihapus&type=success');
        break;
    
    // ===== BULK DELETE =====
    case 'bulk_delete_tasks':
        requireAdmin();
        
        $task_ids = $_POST['task_ids'] ?? [];
        
        if (empty($task_ids)) {
            die('Pilih minimal 1 task');
        }
        
        // Gunakan IN clause untuk delete multiple
        $placeholders = implode(',', array_fill(0, count($task_ids), '?'));
        
        // Delete attachments
        $deleteAttachStmt = $pdo->prepare("DELETE FROM attachments WHERE task_id IN ($placeholders)");
        $deleteAttachStmt->execute($task_ids);
        
        // Delete tasks
        $deleteStmt = $pdo->prepare("DELETE FROM tasks WHERE id IN ($placeholders)");
        $deleteStmt->execute($task_ids);
        
        header('Location: admin/dashboard.php?message=Task+dihapus&type=success');
        break;
    
    default:
        die('Action tidak dikenali');
}
?>
```

**Alur Setiap Action**:

**1. ADD (Membuat Task)**:
```
Admin submit form "Add Task"
        ↓
POST ke task_action.php dengan action='add'
        ↓
Validasi: title dan user_id harus ada
        ↓
INSERT ke tasks dengan:
  - title, description, deadline
  - user_id: user yang ditugasi
  - created_by: id admin
  - status_id: 1 (open)
        ↓
Redirect dengan success message
```

**2. EDIT_ADMIN (Edit Task oleh Admin)**:
```
Admin edit task
        ↓
POST ke task_action.php dengan action='edit_admin'
        ↓
Konversi status (string) ke status_id (int):
  'open' → 1
  'in_progress' → 2
  'done' → 3
        ↓
UPDATE tasks SET ... WHERE id = ?
        ↓
Redirect dengan success message
```

**3. DELETE_ADMIN (Hapus Task)**:
```
Admin klik delete
        ↓
DELETE FROM attachments (hapus file dulu)
        ↓
DELETE FROM tasks
        ↓
Redirect dengan success message
```

**4. BULK_DELETE_TASKS (Hapus Multiple)**:
```
Admin checkbox multiple tasks
        ↓
Submit dengan action='bulk_delete_tasks'
        ↓
Build IN clause: WHERE id IN (?, ?, ?)
        ↓
Delete attachments & tasks sekaligus
        ↓
Redirect dengan success message
```

---

### 11. **js/app.js** - Frontend JavaScript
**Lokasi**: `js/app.js`

**Tujuan**: Handle interaksi frontend (modal, form submission, theme toggle).

**Kode Utama**:

```javascript
/* TaskHub — app.js */

// 1. THEME MANAGEMENT
// Terapkan tema tersimpan sebelum DOM render
// (mencegah flash of unstyled content)
(function () {
    document.documentElement.setAttribute('data-theme', 
        localStorage.getItem('th-theme') || 'light'
    );
})();

// 2. GET BASE PATH
// Cari path ke task_action.php sesuai direktori
function getBase() {
    return (location.pathname.includes('/admin/') || location.pathname.includes('/user/'))
        ? '../task_action.php'      // Dari /admin/ atau /user/ → ../task_action.php
        : 'task_action.php';        // Dari root → task_action.php
}

// 3. FORM SUBMISSION
// Submit form ke server via POST (tanpa reload halaman)
function formSubmit(url, action, data) {
    const f = document.createElement('form');
    f.method = 'POST';
    f.action = url;
    
    // Tambah hidden field 'action'
    const add = (n, v) => {
        const i = document.createElement('input');
        i.type = 'hidden';
        i.name = n;
        i.value = v;
        f.appendChild(i);
    };
    
    add('action', action);
    Object.entries(data).forEach(([k, v]) => add(k, v));
    
    document.body.appendChild(f);
    f.submit();  // Submit form
}

// 4. DELETE CONFIRMATION MODAL
// Tampilkan modal konfirmasi sebelum delete
function confirmDelete(title, desc, onOk) {
    const id = 'delModal';
    
    if (!document.getElementById(id)) {
        // Buat modal jika belum ada
        document.body.insertAdjacentHTML('beforeend', `
            <div class="modal fade" id="${id}" tabindex="-1">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-header modal-header-danger">
                            <h5 class="modal-title modal-title-white">Konfirmasi Hapus</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body" id="delBody"></div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                            <button type="button" class="btn btn-danger" id="delConfirm">Hapus</button>
                        </div>
                    </div>
                </div>
            </div>
        `);
    }
    
    // Update isi modal
    document.getElementById('delBody').innerHTML = 
        `<p><strong>${title}</strong></p><p>${desc}</p>`;
    
    // Handle konfirmasi
    const confirmBtn = document.getElementById('delConfirm');
    confirmBtn.onclick = () => {
        onOk();  // Callback function
        bootstrap.Modal.getInstance(document.getElementById(id)).hide();
    };
    
    // Show modal
    new bootstrap.Modal(document.getElementById(id)).show();
}

// 5. CONTOH PENGGUNAAN: Hapus Task
// call: confirmDelete('Task: Buat Laporan', 'Yakin ingin menghapus?', () => {
//   formSubmit(getBase(), 'delete_admin', { task_id: 123 });
// });
```

**Penjelasan Fungsi JavaScript**:

| Fungsi | Tujuan | Cara Kerja |
|--------|--------|-----------|
| `(function)` IIFE | Inisialisasi theme | Baca localStorage → set `data-theme` attr |
| `getBase()` | Cari path task_action.php | Cek apakah di /admin/ atau /user/ → return path |
| `formSubmit()` | Submit form via JS | Create `<form>` → add hidden fields → submit |
| `confirmDelete()` | Modal konfirmasi delete | Create modal → set callback → show modal |

**Alur Delete dengan Konfirmasi**:
```
User klik button "Hapus"
           ↓
Call confirmDelete(title, desc, callback)
           ↓
Tampilkan modal Bootstrap
           ↓
User klik "Hapus" di modal
           ↓
Jalankan callback: formSubmit(...)
           ↓
Form submission ke task_action.php
           ↓
action='delete_admin' → hapus task
           ↓
Redirect & reload halaman
```

---

## 🔐 Alur Autentikasi

**Diagram Alur Login**:
```
┌──────────────────────────────────────────────┐
│ User akses aplikasi                          │
└──────────────────────────────────────────────┘
                    ↓
        ┌─────────────────────────┐
        │ Sudah login?            │
        │ ($_SESSION['user_id'])  │
        └────────┬────────────────┘
                 │
         Ya ────┼──── Tidak
         ↓      ↓
    Dashboard  Login Form (index.php)
         ↓      ↓
                User input username + password
                         ↓
                SELECT user FROM users WHERE username = ?
                         ↓
                User ditemukan?
                ✗ Tidak → Error "Username/password salah"
                ✓ Ya
                         ↓
                password_verify(input_pwd, db_pwd)
                         ↓
                Password benar?
                ✗ Tidak → Error "Username/password salah"
                ✓ Ya
                         ↓
        $_SESSION['user_id'] = user.id
        $_SESSION['username'] = user.username
        $_SESSION['role'] = 'admin' atau 'user'
                         ↓
        Redirect ke dashboard.php
                         ↓
    ┌─────────────────────────────┐
    │ dashboard.php cek role      │
    │ ├─ admin → admin/dashboard  │
    │ └─ user → user/dashboard    │
    └─────────────────────────────┘
```

**Session Security**:
```
Password di Database:      Password Input User:
┌──────────────────┐       ┌──────────────┐
│ $2y$10$N9qo8u... │       │ qwerty123    │
│ (bcrypt hash)    │       │ (plain text) │
└──────────────────┘       └──────────────┘
         ↓                         ↓
    Tidak bisa di-reverse        Hash dengan salt
         ↓                         ↓
                 password_verify()
                         ↓
                    Cocok? → Login OK
```

---

## 📋 Alur Manajemen Task

**Admin Perspective**:
```
Dashboard Admin
    ├─ Lihat SEMUA task (dari semua user)
    ├─ Create new task → pilih user → set deadline
    ├─ Edit task → ubah title, description, user, status, deadline
    ├─ Delete task → hapus task + attachments
    ├─ Search/Filter → by title, description, user, status
    └─ Pagination → 12 items per page dengan per-status limit 3
```

**User Perspective**:
```
Dashboard User
    ├─ Lihat HANYA task yang ditugaskan ke dia
    ├─ Tidak bisa create task (hanya admin)
    ├─ Upload completion file jika task done
    ├─ Task overdue di-hidden (jika belum selesai)
    ├─ Dapat lihat apakah task dari admin atau user sendiri
    └─ Pagination → 3 items per page
```

**Status Progression**:
```
Task Created (Admin)
         ↓
    OPEN
    (User menerima task)
         ↓
    IN_PROGRESS
    (User mengerjakan)
         ↓
    DONE
    (User submit + attachment)
```

**Database Task Relations**:
```
Task
├─ user_id (FK) → users.id (user yang ditugasi)
├─ created_by (FK) → users.id (admin yang membuat)
├─ status_id (FK) → task_statuses.id (status task)
└─ attachments (1:N) → attachments table
```

---

## 🎨 Frontend & JavaScript

### Theme System
```javascript
// Light theme (default)
document.documentElement.setAttribute('data-theme', 'light');

// Dark theme
document.documentElement.setAttribute('data-theme', 'dark');

// Simpan preference
localStorage.setItem('th-theme', 'dark');
```

### Modal Management
```javascript
// Bootstrap 5 modal
const modal = new bootstrap.Modal(element);
modal.show();
modal.hide();
```

### Form Posting
```javascript
// Membuat form hidden dan submit
formSubmit(getBase(), 'delete_admin', { task_id: 123 });

// Ini akan membuat:
// <form method="POST" action="task_action.php">
//   <input type="hidden" name="action" value="delete_admin">
//   <input type="hidden" name="task_id" value="123">
// </form>
```

---

## 🔧 Teknologi Stack

| Layer | Teknologi | Fungsi |
|-------|-----------|--------|
| **Backend** | PHP 7.4+ | Server-side logic |
| **Database** | MySQL | Data storage |
| **Database Access** | PDO | Query builder & security |
| **Frontend** | HTML5 | Markup |
| **Styling** | CSS3 + Bootstrap 5.3.0 | Layout & components |
| **Interaktivitas** | Vanilla JavaScript | DOM manipulation |
| **Authentication** | PHP Sessions + bcrypt | User login & security |

---

## 📝 Ringkasan Poin Penting untuk Ujian

✅ **Konsep Penting**:
1. **PDO + Prepared Statements** → Mencegah SQL injection
2. **password_hash() + password_verify()** → Keamanan password
3. **Session-based authentication** → User tracking
4. **Role-based access control** → Admin vs User permissions
5. **UNION query** → Per-status limit di admin dashboard
6. **Overdue filtering** → User tidak lihat task overdue
7. **Bulk operations** → Delete multiple items sekaligus

✅ **File Flow**:
```
index.php (login) 
    ↓
bootstrap.php (init)
    ↓
db.php (koneksi)
    ↓
auth.php (validasi role)
    ↓
dashboard.php (router)
    ├─ admin/dashboard.php (UNION query)
    └─ user/dashboard.php (filter overdue)
```

✅ **Form Actions**:
- `add` → INSERT task baru
- `edit_admin` → UPDATE task (admin hanya)
- `delete_admin` → DELETE task
- `bulk_delete_tasks` → DELETE multiple

✅ **Database Design**:
- Normalisasi: users → roles, tasks → task_statuses, tasks → attachments
- Foreign keys untuk relasi
- Timestamp untuk audit trail (created_at, updated_at)
