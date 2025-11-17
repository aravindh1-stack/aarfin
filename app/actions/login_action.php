<?php
require_once __DIR__ . '/../core/init.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . URL_ROOT . '/?page=login');
    exit();
}

$username = trim($_POST['username'] ?? '');
$password = $_POST['password'] ?? '';

if ($username === '' || $password === '') {
    $_SESSION['login_error'] = 'Please enter both username and password.';
    header('Location: ' . URL_ROOT . '/?page=login');
    exit();
}

try {
    $stmt = $pdo->prepare('SELECT id, username, password_hash FROM users WHERE username = ? LIMIT 1');
    $stmt->execute([$username]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && password_verify($password, $user['password_hash'])) {
        $_SESSION['user_id'] = (int)$user['id'];
        $_SESSION['username'] = $user['username'];
        header('Location: ' . URL_ROOT . '/?page=dashboard');
        exit();
    } else {
        $_SESSION['login_error'] = 'Invalid username or password.';
        header('Location: ' . URL_ROOT . '/?page=login');
        exit();
    }
} catch (Exception $e) {
    $_SESSION['login_error'] = 'Login failed. Please try again.';
    header('Location: ' . URL_ROOT . '/?page=login');
    exit();
}
