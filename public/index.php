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