<?php

namespace App\Services;

use App\Models\User;
use App\Models\Role;
use App\Repositories\UserRepository;
use App\Repositories\RoleRepository;

/**
 * AuthService - Business logic untuk autentikasi
 */
class AuthService
{
    private UserRepository $userRepository;
    private RoleRepository $roleRepository;

    public function __construct(UserRepository $userRepository, RoleRepository $roleRepository)
    {
        $this->userRepository = $userRepository;
        $this->roleRepository = $roleRepository;
    }

    /**
     * Authenticate user dengan username dan password
     * 
     * @return array|null ['user' => User, 'role' => Role] atau null jika gagal
     */
    public function login(string $username, string $password): ?array
    {
        if (empty($username) || empty($password)) {
            return null;
        }

        $user = $this->userRepository->findByUsername($username);

        if (!$user || !$user->verifyPassword($password)) {
            return null;
        }

        $role = $this->roleRepository->findById($user->getRoleId());

        return [
            'user' => $user,
            'role' => $role
        ];
    }

    /**
     * Register user baru
     */
    public function register(string $username, string $password, string $passwordConfirm): ?int
    {
        // Validasi input
        if (strlen($username) < 3) {
            throw new \InvalidArgumentException('Username minimal 3 karakter');
        }

        if (strlen($password) < 4) {
            throw new \InvalidArgumentException('Password minimal 4 karakter');
        }

        if ($password !== $passwordConfirm) {
            throw new \InvalidArgumentException('Password tidak cocok');
        }

        // Cek username sudah ada
        if ($this->userRepository->usernameExists($username)) {
            throw new \InvalidArgumentException('Username sudah terdaftar');
        }

        // Buat user baru
        $user = new User($username, $password);
        $user->hashPassword();

        // Set role 'user' (bukan admin)
        $roleId = $this->roleRepository->getIdByName('user');
        if (!$roleId) {
            throw new \RuntimeException('Role user tidak ditemukan di database');
        }

        $user->setRoleId($roleId);

        // Simpan ke database
        return $this->userRepository->create($user);
    }

    /**
     * Check apakah user sudah login
     */
    public static function isLoggedIn(): bool
    {
        return !empty($_SESSION['user_id'] ?? null);
    }

    /**
     * Get current user dari session
     */
    public static function getCurrentUser(): ?array
    {
        if (!self::isLoggedIn()) {
            return null;
        }

        return [
            'user_id' => $_SESSION['user_id'],
            'username' => $_SESSION['username'],
            'role' => $_SESSION['role'],
            'role_id' => $_SESSION['role_id'] ?? null
        ];
    }

    /**
     * Check apakah user adalah admin
     */
    public static function isAdmin(): bool
    {
        return $_SESSION['role'] === 'admin';
    }

    /**
     * Check apakah user adalah user biasa
     */
    public static function isUser(): bool
    {
        return $_SESSION['role'] === 'user';
    }

    /**
     * Require login - redirect jika tidak login
     */
    public static function requireLogin(): void
    {
        if (!self::isLoggedIn()) {
            header('Location: ../index.php?message=Login+Required&type=error');
            exit();
        }
    }

    /**
     * Require admin - redirect jika user bukan admin
     */
    public static function requireAdmin(): void
    {
        self::requireLogin();
        if (!self::isAdmin()) {
            header('Location: ../index.php?message=Access+Denied&type=error');
            exit();
        }
    }

    /**
     * Require user role - redirect jika user bukan regular user
     */
    public static function requireUser(): void
    {
        self::requireLogin();
        if (!self::isUser()) {
            header('Location: ../index.php?message=Access+Denied&type=error');
            exit();
        }
    }

    /**
     * Logout user
     */
    public static function logout(): void
    {
        session_unset();
        session_destroy();
        header('Location: ../index.php?message=Logout+Berhasil&type=success');
        exit();
    }
}
