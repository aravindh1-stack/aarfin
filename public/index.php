<?php
// You can comment these lines out later for production
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// This is the single entry point. It loads the core application files.
require_once __DIR__ . '/../app/core/init.php';

// Simple URL Router to determine which page to load
$page = $_GET['page'] ?? 'dashboard';

// A list of pages that are regular views with HTML templates
$viewPages = ['login', 'dashboard', 'members', 'payments', 'expenses', 'reports', 'settings'];

// A list of special action pages (like PDF generation) that don't have HTML templates
$actionPages = ['generate_report', 'login_action', 'logout'];

// Public (non-auth) pages
$publicPages = ['login'];
$publicActions = ['login_action'];

// --- Remember-me auto login ---
if (empty($_SESSION['user_id']) && !empty($_COOKIE['remember_token'])) {
    try {
        $token = $_COOKIE['remember_token'];
        $stmt = $pdo->prepare("SELECT id, username FROM users WHERE remember_token = ? AND (remember_expires IS NULL OR remember_expires > NOW()) LIMIT 1");
        $stmt->execute([$token]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($user) {
            $_SESSION['user_id'] = (int)$user['id'];
            $_SESSION['username'] = $user['username'];
        }
    } catch (Exception $e) {
        // Fail silently if remember-me lookup fails
    }
}

// --- Auth guard ---
$isPublic = in_array($page, $publicPages) || in_array($page, $publicActions);

// If not logged in and page is protected, redirect to login
if (empty($_SESSION['user_id']) && !$isPublic) {
    header('Location: ' . URL_ROOT . '/?page=login');
    exit();
}

// If already logged in and trying to access login page, go to dashboard
if (!empty($_SESSION['user_id']) && $page === 'login') {
    header('Location: ' . URL_ROOT . '/?page=dashboard');
    exit();
}


// --- Routing Logic ---

// First, check if the requested page is a standard view
if (in_array($page, $viewPages) && file_exists(APP_ROOT . '/views/' . $page . '.php')) {
    $fileToInclude = APP_ROOT . '/views/' . $page . '.php';
} 
// If not, check if it is a special action page
else if (in_array($page, $actionPages) && file_exists(APP_ROOT . '/actions/' . $page . '.php')) {
    $fileToInclude = APP_ROOT . '/actions/' . $page . '.php';
} 
// If no route is matched at all, fall back to the dashboard
else {
    http_response_code(404); // Not Found
    $fileToInclude = APP_ROOT . '/views/dashboard.php';
}

// Finally, load the chosen file.
require_once $fileToInclude;