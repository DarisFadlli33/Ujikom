<?php

namespace App\Controllers;

/**
 * BaseController - Base class untuk semua controller
 */
abstract class BaseController
{
    protected array $data = [];

    /**
     * Set data untuk view
     */
    protected function setData(string $key, $value): void
    {
        $this->data[$key] = $value;
    }

    /**
     * Render view
     */
    protected function render(string $view): void
    {
        extract($this->data);
        require __DIR__ . "/../../views/$view.php";
    }

    /**
     * Redirect dengan query string
     */
    protected function redirect(string $url, array $params = []): void
    {
        if (!empty($params)) {
            $queryString = http_build_query($params);
            $url .= '?' . $queryString;
        }
        header("Location: $url");
        exit();
    }

    /**
     * Get request data (POST/GET)
     */
    protected function getRequest(string $key, $default = null)
    {
        return $_REQUEST[$key] ?? $default;
    }

    /**
     * Get POST data
     */
    protected function post(string $key, $default = null)
    {
        return $_POST[$key] ?? $default;
    }

    /**
     * Get GET data
     */
    protected function query(string $key, $default = null)
    {
        return $_GET[$key] ?? $default;
    }

    /**
     * Check if request is POST
     */
    protected function isPost(): bool
    {
        return $_SERVER['REQUEST_METHOD'] === 'POST';
    }

    /**
     * Check if request is GET
     */
    protected function isGet(): bool
    {
        return $_SERVER['REQUEST_METHOD'] === 'GET';
    }

    /**
     * Set flash message
     */
    protected function setFlashMessage(string $message, string $type = 'info'): void
    {
        $_SESSION['flash_message'] = $message;
        $_SESSION['flash_type'] = $type;
    }

    /**
     * Get and clear flash message
     */
    protected function getFlashMessage(): ?array
    {
        if (isset($_SESSION['flash_message'])) {
            $message = $_SESSION['flash_message'];
            $type = $_SESSION['flash_type'] ?? 'info';
            unset($_SESSION['flash_message']);
            unset($_SESSION['flash_type']);
            return ['message' => $message, 'type' => $type];
        }
        return null;
    }
}
