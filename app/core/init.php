<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'config.php';

// --- FINAL, MORE RELIABLE LANGUAGE SETUP ---

// 1. Check if the user is changing the language.
if (isset($_GET['lang'])) {
    $_SESSION['lang'] = ($_GET['lang'] == 'ta') ? 'ta' : 'en';
    
    // 2. FIX: Force the session to save its data to the server immediately.
    session_write_close();

    // 3. Get the current URL without the '?lang=...' part.
    $url = strtok($_SERVER['REQUEST_URI'], '?');
    
    // 4. FIX: Redirect to the clean, absolute URL.
    header("Location: " . URL_ROOT . $url);
    exit();
}

// 5. If no language is set in the session, default to English.
if (!isset($_SESSION['lang'])) {
    $_SESSION['lang'] = 'en';
}

// 6. Load the correct language file based on the session.
$lang_file = APP_ROOT . '/lang/' . $_SESSION['lang'] . '.php';
if (file_exists($lang_file)) {
    $lang = require_once $lang_file;
} else {
    $lang = require_once APP_ROOT . '/lang/en.php';
}

// 7. Create the helper function.
function trans($key) {
    global $lang;
    return $lang[$key] ?? str_replace('_', ' ', ucfirst($key));
}
// --- END OF LANGUAGE SETUP ---


// --- Database Connection & Other Functions ---
try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false
    ]);
    $pdo->exec("SET NAMES 'utf8mb4'");
} catch (PDOException $e) {
    die("Database Connection failed.");
}

// ... (Other helper functions remain the same)
function formatCurrency($amount) { return '₹' . number_format((float)$amount, 0); }
function formatDate($date) { return $date ? date('d-m-Y', strtotime($date)) : '-'; }
function add_log($pdo, $message) {
    try {
        $stmt = $pdo->prepare("INSERT INTO activity_log (log_message) VALUES (?)");
        $stmt->execute([$message]);
    } catch (PDOException $e) {
        error_log("Failed to add log: " . $e->getMessage());
    }
}