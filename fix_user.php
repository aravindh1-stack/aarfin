<?php
// Load your database connection
// (Adjust this path to point to your actual init.php file)
require_once __DIR__ . '/core/init.php'; 

$username = 'admin@aarfin';
$password = 'aarfin@jp@2025@jp';

// 1. Create the secure hash
$secure_hash = password_hash($password, PASSWORD_DEFAULT);

// 2. Update the database
try {
    $stmt = $pdo->prepare("UPDATE users SET password_hash = ? WHERE username = ?");
    $stmt->execute([$secure_hash, $username]);
    
    echo "<h1>Success!</h1>";
    echo "<p>Password for <b>$username</b> has been updated securely.</p>";
    echo "<p>Generated Hash: $secure_hash</p>";
    echo "<p><a href='index.php?page=login'>Go to Login</a></p>";
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>