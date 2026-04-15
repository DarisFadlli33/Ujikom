<?php

namespace App\Controllers;

use App\Services\AuthService;
use App\Repositories\UserRepository;
use App\Repositories\RoleRepository;

/**
 * AuthController - Handle autentikasi (login, register, logout)
 */
class AuthController extends BaseController
{
    private AuthService $authService;

    public function __construct(AuthService $authService)
    {
        $this->authService = $authService;
    }

    /**
     * Tampilkan login form
     */
    public function loginForm(): void
    {
        $this->render('auth/login');
    }

    /**
     * Handle login POST
     */
    public function login(): void
    {
        if (!$this->isPost()) {
            $this->loginForm();
            return;
        }

        $username = trim($this->post('username', ''));
        $password = trim($this->post('password', ''));

        try {
            $result = $this->authService->login($username, $password);

            if (!$result) {
                $this->setData('error', 'Username atau password salah');
                $this->render('auth/login');
                return;
            }

            $user = $result['user'];
            $role = $result['role'];

            // Set session
            $_SESSION['user_id'] = $user->getId();
            $_SESSION['username'] = $user->getUsername();
            $_SESSION['role'] = $role->getName();
            $_SESSION['role_id'] = $role->getId();

            // Redirect ke dashboard
            $this->redirect('dashboard.php');
        } catch (\Exception $e) {
            $this->setData('error', 'Login gagal: ' . $e->getMessage());
            $this->render('auth/login');
        }
    }

    /**
     * Tampilkan register form
     */
    public function registerForm(): void
    {
        $this->render('auth/register');
    }

    /**
     * Handle register POST
     */
    public function register(): void
    {
        if (!$this->isPost()) {
            $this->registerForm();
            return;
        }

        $username = trim($this->post('username', ''));
        $password = trim($this->post('password', ''));
        $passwordConfirm = trim($this->post('password_confirm', ''));

        try {
            $this->authService->register($username, $password, $passwordConfirm);

            $this->setData('success', 'Registrasi berhasil! Silakan login.');
            $this->render('auth/register');
        } catch (\InvalidArgumentException $e) {
            $this->setData('error', $e->getMessage());
            $this->render('auth/register');
        } catch (\Exception $e) {
            $this->setData('error', 'Registrasi gagal: ' . $e->getMessage());
            $this->render('auth/register');
        }
    }

    /**
     * Handle logout
     */
    public function logout(): void
    {
        AuthService::logout();
    }
}
