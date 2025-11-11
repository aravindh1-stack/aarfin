<?php
// Force error reporting ON for this file, so we can see any warnings
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Start the session
session_start();

echo "<h1>Session Test</h1>";
echo "<strong>Status:</strong> Script is running.<br>";
echo "<strong>Session ID:</strong> " . session_id() . "<br>";

// Initialize or increment the counter
if (!isset($_SESSION['counter'])) {
    $_SESSION['counter'] = 1;
    echo "<strong>Action:</strong> Initializing counter to 1.<br>";
} else {
    $_SESSION['counter'] = $_SESSION['counter'] + 1;
    echo "<strong>Action:</strong> Incrementing counter.<br>";
}

echo "<hr>";
// Display the result
echo "<h2>You have visited this page " . ($_SESSION['counter'] ?? 'UNKNOWN') . " times.</h2>";

echo "<hr>";
echo "<strong>Current Session Data:</strong><pre>";
print_r($_SESSION);
echo "</pre>";
?>