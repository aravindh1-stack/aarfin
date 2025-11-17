<?php
require_once __DIR__ . '/../core/init.php';

// Clear remember-me token in DB if possible
if (!empty($_SESSION['user_id'])) {
    try {
        $stmt = $pdo->prepare('UPDATE users SET remember_token = NULL, remember_expires = NULL WHERE id = ?');
        $stmt->execute([$_SESSION['user_id']]);
    } catch (Exception $e) {
        // ignore
    }
}

// Clear remember-me cookie
if (!empty($_COOKIE['remember_token'])) {
    setcookie('remember_token', '', time() - 3600, '/');
}

// Clear all session data and destroy the session
$_SESSION = [];
if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params['path'], $params['domain'],
        $params['secure'], $params['httponly']
    );
}
session_destroy();

// Redirect back to login page
header('Location: ' . URL_ROOT . '/?page=login');
exit();
