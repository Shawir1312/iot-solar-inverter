<?php
/**
 * Sistem Autentikasi Admin Sederhana & Aman
 */

require_once __DIR__ . '/config.php';

function auth_init() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
}

function isLoggedIn(): bool {
    auth_init();
    return isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true;
}

function getCurrentUser(): ?string {
    auth_init();
    return $_SESSION['admin_user'] ?? null;
}

function attemptLogin(string $user, string $pass): bool {
    auth_init();
    if ($user === ADMIN_USER && $pass === ADMIN_PASSWORD) {
        $_SESSION['admin_logged_in'] = true;
        $_SESSION['admin_user'] = $user;
        $_SESSION['login_time'] = time();
        return true;
    }
    return false;
}

function logout(): void {
    auth_init();
    $_SESSION = [];
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    session_destroy();
}

function checkAccess(): void {
    if (defined('REQUIRE_LOGIN_TO_VIEW') && REQUIRE_LOGIN_TO_VIEW) {
        if (!isLoggedIn()) {
            $redirect = urlencode($_SERVER['REQUEST_URI'] ?? 'index.php');
            header("Location: login.php?redirect={$redirect}");
            exit;
        }
    }
}
