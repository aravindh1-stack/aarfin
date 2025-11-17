<?php
// CONFIGURATION
$host = 'localhost';
$dbname = 'paycircle'; // CHECK: Is this your actual DB name?
$user = 'root';
$pass = ''; // Default for XAMPP is empty. If you have a password, put it here.

try {
    // 1. Connect directly to Database (Bypassing init.php)
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // 2. The User and New Password
    $username = 'admin@aarfin'; 
    $plain_password = 'aarfin@jp@2025@jp';

    // 3. Generate the Secure Hash
    $new_hash = password_hash($plain_password, PASSWORD_DEFAULT);

    // 4. Check if user exists first
    $check = $pdo->prepare("SELECT id FROM users WHERE username = ?");
    $check->execute([$username]);
    
    if ($check->rowCount() > 0) {
        // 5. Update the password
        $update = $pdo->prepare("UPDATE users SET password_hash = ? WHERE username = ?");
        $update->execute([$new_hash, $username]);
        
        echo "<div style='font-family:sans-serif; padding:20px; background:#d1fae5; border:1px solid #34d399; color:#065f46;'>";
        echo "<h1>✅ Success!</h1>";
        echo "<p>Password for <b>$username</b> has been updated to a secure hash.</p>";
        echo "<p><b>Hash generated:</b> " . substr($new_hash, 0, 20) . "...</p>";
        echo "<br><a href='index.php?page=login'>Click here to Login</a>";
        echo "</div>";
    } else {
        echo "<div style='font-family:sans-serif; padding:20px; background:#fee2e2; border:1px solid #f87171; color:#991b1b;'>";
        echo "<h1>❌ Error: User Not Found</h1>";
        echo "<p>The user <b>$username</b> does not exist in the 'users' table.</p>";
        echo "<p>Please check the spelling or insert the user first.</p>";
        echo "</div>";
    }

} catch (PDOException $e) {
    echo "<h1>Database Connection Failed</h1>";
    echo "Error: " . $e->getMessage();
    echo "<br><br><b>Tip:</b> Check the \$dbname at the top of this script.";
}
?>