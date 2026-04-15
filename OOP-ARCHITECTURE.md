# TaskHub OOP Refactor - Dokumentasi Struktur

## 📋 Ringkasan Perubahan

Project TaskHub telah direfactor menjadi **Full OOP** dengan struktur yang proper dan maintainable. Struktur lama (procedural) masih tersedia, dan struktur OOP baru dapat digunakan secara paralel atau mengganti yang lama.

---

## 🏗️ Struktur Folder OOP

```
taskhub/
├── app/
│   ├── Models/              # Data models
│   │   ├── BaseModel.php      # Base class untuk semua model
│   │   ├── User.php           # User model
│   │   ├── Task.php           # Task model
│   │   ├── Role.php           # Role model
│   │   └── TaskStatus.php     # TaskStatus model
│   │
│   ├── Repositories/        # Database abstraction layer
│   │   ├── BaseRepository.php    # Base repository
│   │   ├── UserRepository.php    # User database operations
│   │   ├── TaskRepository.php    # Task database operations
│   │   ├── RoleRepository.php    # Role database operations
│   │   └── TaskStatusRepository.php
│   │
│   ├── Services/            # Business logic layer
│   │   ├── AuthService.php      # Login, register, logout logic
│   │   └── TaskService.php      # Task CRUD logic
│   │
│   ├── Controllers/         # Request handlers
│   │   ├── BaseController.php   # Base controller
│   │   ├── AuthController.php   # Login/Register/Logout
│   │   └── TaskController.php   # Task operations
│   │
│   └── bootstrap-oop.php    # OOP initialization & autoloader
│
├── views/                   # Presentation layer (HTML only)
│   ├── auth/
│   │   ├── login.php
│   │   └── register.php
│   ├── admin/
│   └── user/
│
├── index-oop.php           # Entry point: Login (OOP)
├── register-oop.php        # Entry point: Register (OOP)
├── logout-oop.php          # Entry point: Logout (OOP)
├── task_action-oop.php     # Entry point: Task actions (OOP)
│
├── index.php               # Original procedural files (masih ada)
├── register.php
├── logout.php
├── task_action.php
└── bootstrap.php
```

---

## 🎯 Arsitektur MVC-like OOP

### 1. **Models Layer** (app/Models/)
**Tujuan**: Represent data dan business rules

```php
// Contoh: User Model
class User extends BaseModel {
    protected $username;
    protected $password;
    protected $roleId;
    
    public function hashPassword(): self { ... }
    public function verifyPassword(string $pwd): bool { ... }
    public static function fromArray(array $data): self { ... }
}
```

**Karakteristik**:
- ✅ Encapsulation (private/protected properties)
- ✅ Getter/Setter methods
- ✅ Business logic (hash, verify, isOverdue)
- ✅ Factory method (fromArray)
- ✅ Inheritance dari BaseModel

---

### 2. **Repositories Layer** (app/Repositories/)
**Tujuan**: Database abstraction - pisahkan database logic dari business logic

```php
// Contoh: UserRepository
class UserRepository extends BaseRepository {
    protected string $table = 'users';
    protected string $modelClass = User::class;
    
    public function findByUsername(string $username): ?User { ... }
    public function create(User $user): int { ... }
    public function update(User $user): bool { ... }
}
```

**Karakteristik**:
- ✅ CRUD operations (Create, Read, Update, Delete)
- ✅ Query builder methods
- ✅ Return model objects (bukan array)
- ✅ Separation of concerns

**Keuntungan Repository Pattern**:
- ✅ Mudah test (mock repository)
- ✅ Mudah ganti database engine
- ✅ Centralized query logic

---

### 3. **Services Layer** (app/Services/)
**Tujuan**: Business logic - sisi mana transaction, validation, orchestration

```php
// Contoh: AuthService
class AuthService {
    public function login(string $username, string $password): ?array { ... }
    public function register(string $username, string $password, ...): ?int { ... }
    public static function requireLogin(): void { ... }
    public static function logout(): void { ... }
}
```

**Karakteristik**:
- ✅ Menggunakan repositories untuk data access
- ✅ Validation logic
- ✅ Business rules enforcement
- ✅ Error handling

**Keuntungan Service Layer**:
- ✅ Reusable business logic
- ✅ Testable (no dependencies dengan web layer)
- ✅ Easy to maintain

---

### 4. **Controllers Layer** (app/Controllers/)
**Tujuan**: Handle requests dan responses

```php
// Contoh: AuthController
class AuthController extends BaseController {
    public function login(): void { ... }
    public function register(): void { ... }
    public function loginForm(): void { ... }
}
```

**Karakteristik**:
- ✅ Menggunakan services untuk business logic
- ✅ Return views (render template)
- ✅ Redirect dengan data
- ✅ Error handling

---

### 5. **Views Layer** (views/)
**Tujuan**: Presentation only - HTML dan output

```html
<!-- views/auth/login.php -->
<form method="POST">
    <input name="username" required>
    <input name="password" type="password" required>
    <button type="submit">Login</button>
</form>

<?php if ($error): ?>
    <div class="alert alert-danger"><?= $error ?></div>
<?php endif; ?>
```

---

## 🔄 Data Flow: OOP Architecture

```
┌────────────────┐
│  User Request  │
│  (Browser)     │
└────────┬───────┘
         │
         ↓
┌─────────────────────────────────────┐
│  Entry Point (index-oop.php)        │
│  ├─ Require bootstrap-oop.php       │
│  └─ Create & use Controller         │
└────────┬────────────────────────────┘
         │
         ↓
┌─────────────────────────────────────┐
│  Controller (AuthController)        │
│  ├─ Get request data                │
│  ├─ Call Service method             │
│  ├─ Handle result                   │
│  └─ Render view atau redirect       │
└─────────┬──────────────────────────┘
         │
         ↓
┌──────────────────────────────────────┐
│  Service (AuthService)              │
│  ├─ Validate input                  │
│  ├─ Call Repository method          │
│  ├─ Apply business logic            │
│  └─ Return result                   │
└────────┬──────────────────────────┘
         │
         ↓
┌────────────────────────────────────────┐
│  Repository (UserRepository)          │
│  ├─ Build query                       │
│  ├─ Execute query                     │
│  ├─ Convert to Model objects          │
│  └─ Return to Service                 │
└────────┬─────────────────────────────┘
         │
         ↓
┌────────────────────────────────────────┐
│  Database (MySQL)                     │
│  └─ Return data rows                  │
└──────────────────────────────────────┘
```

---

## 💾 Comparison: Procedural vs OOP

### Procedural (Lama)
```php
// index.php
$stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
$stmt->execute([$username]);
$user = $stmt->fetch();

if ($user && password_verify($password, $user['password'])) {
    $_SESSION['user_id'] = $user['id'];
}
```

**Masalah**:
- ❌ Logic tercampur di entry point
- ❌ Sulit test
- ❌ Perubahan database → semua file affected
- ❌ Code duplication

### OOP (Baru)
```php
// index-oop.php
$authService = app('auth');
$controller = new AuthController($authService);
$controller->login();

// AuthController.php
$result = $this->authService->login($username, $password);
if ($result) {
    $_SESSION['user_id'] = $result['user']->getId();
}

// AuthService.php
public function login($username, $password): ?array {
    $user = $this->userRepository->findByUsername($username);
    if ($user && $user->verifyPassword($password)) {
        return ['user' => $user, 'role' => $role];
    }
    return null;
}

// UserRepository.php
public function findByUsername($username): ?User {
    $stmt = $this->pdo->prepare(...);
    $data = $stmt->fetch();
    return $data ? User::fromArray($data) : null;
}
```

**Keuntungan**:
- ✅ Separation of concerns
- ✅ Mudah test (inject service)
- ✅ Reusable (service bisa dipakai di multiple controllers)
- ✅ Maintainable (perubahan logic di service saja)
- ✅ Scalable (structure siap untuk project besar)

---

## 🚀 Cara Menggunakan OOP Version

### 1. Menggunakan Entry Point OOP

Ganti URL dari:
```
http://localhost/taskhub/index.php          → Login (Procedural)
http://localhost/taskhub/register.php        → Register (Procedural)
http://localhost/taskhub/task_action.php     → Task actions (Procedural)
```

Ke:
```
http://localhost/taskhub/index-oop.php       → Login (OOP)
http://localhost/taskhub/register-oop.php    → Register (OOP)
http://localhost/taskhub/task_action-oop.php → Task actions (OOP)
```

### 2. Atau Rename Files

Jika ingin sepenuhnya ganti (backup yang lama dulu):
```bash
# Backup files lama
mv index.php index-procedural.php
mv register.php register-procedural.php
mv task_action.php task_action-procedural.php
mv logout.php logout-procedural.php

# Pindahkan OOP versions
mv index-oop.php index.php
mv register-oop.php register.php
mv task_action-oop.php task_action.php
mv logout-oop.php logout.php
```

### 3. Update Frontend Links

Jika masih menggunakan file lama di HTML/JS, perbarui:
```html
<!-- Sebelum -->
<a href="logout.php">Logout</a>
<a href="register.php">Daftar</a>

<!-- Sesudah (jika rename) -->
<!-- Tetap sama jika sudah rename ✅ -->

<!-- Atau jika keep both parallel -->
<a href="logout-oop.php">Logout (OOP)</a>
<a href="register-oop.php">Daftar (OOP)</a>
```

---

## 🧪 Contoh Penggunaan

### Create User dengan OOP
```php
// Procedural (lama)
$username = 'daris';
$password = 'qwerty123';
$hashedPassword = password_hash($password, PASSWORD_DEFAULT);
$stmt = $pdo->prepare("INSERT INTO users (username, password, role_id) VALUES (?, ?, ?)");
$stmt->execute([$username, $hashedPassword, 2]);

// OOP (baru)
$authService = app('auth');
$userId = $authService->register('daris', 'qwerty123', 'qwerty123');
// ✅ Semua validation, hashing, dan database logic di service
```

### Get User Tasks dengan OOP
```php
// Procedural (lama)
$userId = $_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT t.*, s.code AS status_code FROM tasks t JOIN task_statuses s ON t.status_id = s.id WHERE t.user_id = ?");
$stmt->execute([$userId]);
$tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);

// OOP (baru)
$taskService = app('task');
$tasks = $taskService->getUserTasks($_SESSION['user_id']);
// ✅ Return Task model objects dengan methods seperti isOverdue()
```

---

## 📚 OOP Concepts yang Digunakan

### 1. Inheritance
```php
class User extends BaseModel { }
class UserRepository extends BaseRepository { }
class AuthController extends BaseController { }
```

### 2. Encapsulation
```php
protected $username;  // ← Private, tidak bisa diakses dari luar
public function setUsername($name) { }  // ← Controlled access
```

### 3. Dependency Injection
```php
class AuthService {
    public function __construct(UserRepository $repo) {
        $this->userRepository = $repo;  // Inject dependency
    }
}
```

### 4. Polymorphism
```php
class BaseRepository {
    public function findById($id) { }
}

class UserRepository extends BaseRepository {
    public function findByUsername($username) { }  // Override
}
```

### 5. Factory Pattern
```php
User::fromArray($data);  // Create object dari array
```

### 6. Service Locator
```php
$service = app('auth');  // Get service instance
$repo = app('users');     // Get repository instance
```

---

## ✅ Next Steps

### Untuk Lengkapi OOP Refactor:
1. **Buat view files untuk admin/dashboard dan user/dashboard**
   - Gunakan TaskService untuk get data
   - Separate HTML dari logic

2. **Buat DashboardController**
   - Handle admin dashboard logic
   - Handle user dashboard logic

3. **Buat Exception classes**
   - `InvalidArgumentException` untuk validation errors
   - `ApplicationException` untuk business logic errors

4. **Buat Unit Tests**
   - Test models (hash, verify)
   - Test repositories (queries)
   - Test services (business logic)
   - Test controllers (requests)

5. **Buat Configuration class**
   - Database config
   - App config
   - Constants

6. **Buat Middleware system** (optional)
   - Authentication middleware
   - Authorization middleware
   - Logging middleware

---

## 🔒 Security Features (sama dengan procedural)

✅ **Password Hashing**: bcrypt via `password_hash()` dan `password_verify()`
✅ **Prepared Statements**: Semua queries menggunakan prepared statements
✅ **Session Management**: PHP sessions untuk authentication
✅ **Input Validation**: Validation di Service layer
✅ **Role-based Access Control**: Admin vs User roles

---

## 📊 Comparison Table

| Aspek | Procedural | OOP |
|-------|-----------|-----|
| Reusability | Low (code duplication) | High (classes & inheritance) |
| Testability | Hard (tightly coupled) | Easy (dependency injection) |
| Maintainability | Difficult | Easy |
| Scalability | Limited | Excellent |
| Code Organization | Mixed | Separated |
| Learning Curve | Easy | Moderate |
| Enterprise Ready | No | Yes |
| Testing Framework | N/A | PHPUnit, etc |

---

## 🎓 Kesimpulan

Project TaskHub telah berhasil direfactor dari **procedural** menjadi **full OOP** dengan:
- ✅ Model layer (data representation)
- ✅ Repository layer (data access)
- ✅ Service layer (business logic)
- ✅ Controller layer (request handling)
- ✅ View layer (presentation)

Struktur ini mengikuti **best practices** dan siap untuk scaling, testing, dan maintenance jangka panjang.

---

**Tim Development**
April 15, 2026
