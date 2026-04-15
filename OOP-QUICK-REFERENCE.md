# TaskHub OOP - Quick Reference Guide

Quick reference untuk developers yang ingin menggunakan atau extend OOP version.

---

## 🎯 Entry Points

| URL | Purpose | File |
|-----|---------|------|
| `/index-oop.php` | Login page | Uses AuthController |
| `/register-oop.php` | Register page | Uses AuthController |
| `/logout-oop.php` | Logout process | Uses AuthService |
| `/task_action-oop.php` | Task CRUD operations | Uses TaskController |

---

## 📦 How to Use Services

### AuthService
```php
require_once 'app/bootstrap-oop.php';

$authService = app('auth');

// Login
$result = $authService->login('username', 'password');
if ($result) {
    $user = $result['user'];     // User model
    $role = $result['role'];     // Role model
}

// Register
try {
    $userId = $authService->register('newuser', 'password', 'password');
} catch (InvalidArgumentException $e) {
    echo $e->getMessage();
}

// Check login
if (AuthService::isLoggedIn()) { }

// Check role
if (AuthService::isAdmin()) { }
if (AuthService::isUser()) { }

// Require login
AuthService::requireLogin();    // Redirect if not logged in
AuthService::requireAdmin();    // Redirect if not admin
AuthService::requireUser();     // Redirect if not user

// Logout
AuthService::logout();
```

### TaskService
```php
$taskService = app('task');

// Create task
$taskId = $taskService->createTask(
    'Task Title',
    'Description',
    $userId,
    $createdBy,
    '2026-12-31'  // deadline
);

// Update task
$taskService->updateTask(
    $taskId,
    'New Title',
    'New Description',
    $userId,
    'in_progress',  // status code
    '2026-12-31'
);

// Delete task
$taskService->deleteTask($taskId);

// Delete multiple
$taskService->deleteMultipleTasks([1, 2, 3]);

// Get tasks for admin
$tasks = $taskService->getAdminTasks(
    $search = null,
    $statusFilter = null
);

// Get tasks for user
$tasks = $taskService->getUserTasks(
    $userId,
    $search = null,
    $statusFilter = null
);
// Note: Returns Task models only, overdue tasks are filtered out

// Get statistics
$stats = $taskService->getStatistics();
// Returns: ['total_open' => 5, 'total_progress' => 3, 'total_completed' => 2, 'total_tasks' => 10]
```

---

## 🏗️ How to Create Controller

```php
namespace App\Controllers;

class NewController extends BaseController {
    private $service;
    
    public function __construct($service) {
        $this->service = $service;
    }
    
    public function someAction(): void {
        // Get request data
        $username = $this->post('username');
        $search = $this->query('search');
        
        // Use service
        try {
            $result = $this->service->doSomething($username);
            
            // Set data untuk view
            $this->setData('result', $result);
            
            // Render view
            $this->render('folder/viewname');
            
        } catch (Exception $e) {
            // Redirect dengan error
            $this->redirect('page.php', [
                'message' => $e->getMessage(),
                'type' => 'error'
            ]);
        }
    }
}
```

---

## 🗂️ How to Create Model

```php
namespace App\Models;

class YourModel extends BaseModel {
    protected $propertyName;
    protected $anotherProperty;
    
    public function __construct($propertyName = null, $anotherProperty = null, $id = null) {
        $this->propertyName = $propertyName;
        $this->anotherProperty = $anotherProperty;
        $this->id = $id;
    }
    
    // Getter
    public function getPropertyName(): string {
        return $this->propertyName;
    }
    
    // Setter (return $this for method chaining)
    public function setPropertyName(string $value): self {
        $this->propertyName = $value;
        return $this;
    }
    
    // Business logic
    public function doSomething(): bool {
        return strlen($this->propertyName) > 3;
    }
    
    // Factory
    public static function fromArray(array $data): self {
        $model = new self(
            $data['property_name'] ?? null,
            $data['another_property'] ?? null,
            $data['id'] ?? null
        );
        
        if (isset($data['created_at'])) {
            $model->setCreatedAt($data['created_at']);
        }
        
        return $model;
    }
}
```

---

## 💾 How to Create Repository

```php
namespace App\Repositories;

use App\Models\YourModel;
use PDO;

class YourRepository extends BaseRepository {
    protected string $table = 'table_name';
    protected string $modelClass = YourModel::class;
    
    // Find by custom field
    public function findByField(string $field, $value): ?YourModel {
        $stmt = $this->pdo->prepare("SELECT * FROM {$this->table} WHERE $field = ?");
        $stmt->execute([$value]);
        $data = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $data ? YourModel::fromArray($data) : null;
    }
    
    // Create
    public function create(YourModel $model): int {
        $stmt = $this->pdo->prepare(
            "INSERT INTO {$this->table} (field1, field2) VALUES (?, ?)"
        );
        
        $stmt->execute([
            $model->getField1(),
            $model->getField2()
        ]);
        
        return $this->pdo->lastInsertId();
    }
    
    // Update
    public function update(YourModel $model): bool {
        $stmt = $this->pdo->prepare(
            "UPDATE {$this->table} SET field1 = ?, field2 = ? WHERE id = ?"
        );
        
        return $stmt->execute([
            $model->getField1(),
            $model->getField2(),
            $model->getId()
        ]);
    }
}
```

---

## 🔧 How to Create Service

```php
namespace App\Services;

use App\Repositories\YourRepository;

class YourService {
    private YourRepository $repository;
    
    public function __construct(YourRepository $repository) {
        $this->repository = $repository;
    }
    
    public function doSomething(string $field): ?array {
        // Validation
        if (strlen($field) < 3) {
            throw new InvalidArgumentException('Field minimal 3 karakter');
        }
        
        // Business logic
        $result = $this->repository->findByField('column', $field);
        
        if (!$result) {
            throw new RuntimeException('Data tidak ditemukan');
        }
        
        // Return result
        return [
            'id' => $result->getId(),
            'field' => $result->getField()
        ];
    }
}
```

---

## 📝 How to Create View

Views adalah pure HTML, tidak ada logic complex.

```html
<!-- views/folder/viewname.php -->
<!DOCTYPE html>
<html>
<head>
    <title><?= $title ?? 'Title' ?></title>
</head>
<body>
    <?php if ($error): ?>
        <div class="alert alert-danger">
            <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>
    
    <?php if ($success): ?>
        <div class="alert alert-success">
            <?= htmlspecialchars($success) ?>
        </div>
    <?php endif; ?>
    
    <form method="POST">
        <input name="field" value="<?= htmlspecialchars($data['field'] ?? '') ?>">
        <button type="submit">Submit</button>
    </form>
</body>
</html>
```

Kemudian render dari controller:
```php
$this->setData('error', $errorMessage);
$this->setData('data', ['field' => 'value']);
$this->render('folder/viewname');
```

---

## 🚀 How to Register Service in bootstrap-oop.php

Jika membuat service baru, tambahkan ke function `app()`:

```php
// Di app/bootstrap-oop.php, dalam function app($class)

case 'your_service':
    $repo = new \App\Repositories\YourRepository($pdo);
    $instances[$class] = new \App\Services\YourService($repo);
    break;
```

Kemudian gunakan:
```php
$service = app('your_service');
```

---

## 🧪 How to Test OOP Code

Keuntungan OOP adalah mudah di-test:

```php
// Test di unit test
use App\Services\AuthService;
use App\Repositories\UserRepository;

class AuthServiceTest {
    public function testLogin() {
        // Mock repository
        $mockRepo = $this->createMock(UserRepository::class);
        $mockRepo->method('findByUsername')
                ->willReturn($user);
        
        // Create service dengan mock
        $service = new AuthService($mockRepo);
        
        // Test
        $result = $service->login('user', 'pass');
        
        // Assert
        $this->assertNotNull($result);
    }
}
```

---

## 🔍 How to Debug

### Check Session
```php
var_dump($_SESSION);
// Should show: ['user_id' => 1, 'username' => 'daris', 'role' => 'admin']
```

### Check Service Result
```php
$result = $authService->login('user', 'pass');
var_dump($result);  // [user => User object, role => Role object]
```

### Check Model Data
```php
$user = $result['user'];
echo $user->getId();        // 1
echo $user->getUsername();  // 'daris'
echo $user->getRoleId();    // 1
```

### Check Query
```php
// Add debug di repository
echo "Query: SELECT * FROM users WHERE username = ?";
echo "Params: ['daris']";
```

---

## ⚡ Performance Tips

1. **Cache service instances** - `app('service')` hanya create 1x (lazy loading)
2. **Use pagination** - TaskService sudah support pagination
3. **Index database columns** - `user_id`, `status_id` harus indexed
4. **Limit queries** - Per-status limit sudah diimplementasi di admin dashboard

---

## 🚨 Common Issues & Solutions

| Issue | Solution |
|-------|----------|
| "Service not found" | Register di function `app()` di bootstrap-oop.php |
| "Fatal error: Class not found" | Check namespace dan import statement |
| "Database error" | Check PDO connection di bootstrap-oop.php |
| "Empty $_SESSION" | Call `session_start()` di bootstrap |
| "View not rendering" | Check folder path di `views/` directory |
| "Redirect not working" | Call `exit()` setelah `header()` |

---

## 📚 File Structure Reference

Ketika membuat file baru, gunakan struktur ini:

```
app/
├── Models/YourModel.php
├── Repositories/YourRepository.php
├── Services/YourService.php
└── Controllers/YourController.php

views/
└── folder/viewname.php
```

---

## 💡 Best Practices

✅ **Do:**
- ✓ Use dependency injection
- ✓ Validate input di Service layer
- ✓ Return model objects dari Repository
- ✓ Use prepared statements
- ✓ Keep views simple (no complex logic)
- ✓ Use getter/setter methods
- ✓ Document public methods with PHPDoc

❌ **Don't:**
- ✗ Directly access $pdo dari Controller
- ✗ Put SQL di Controller atau View
- ✗ Direct array access untuk complex logic
- ✗ Skip validation
- ✗ Mix presentation dengan business logic
- ✗ Use global variables

---

## 🔗 Related Files

- **OOP-ARCHITECTURE.md** - Comprehensive architecture guide
- **OOP-REFACTOR-SUMMARY.md** - Summary of changes
- **DOKUMENTASI.md** - Procedural version documentation
- **app/bootstrap-oop.php** - OOP initialization

---

**Last Updated**: April 15, 2026
**Version**: 2.0 OOP Edition
