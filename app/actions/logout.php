<?php
require_once __DIR__ . '/../core/init.php';

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
