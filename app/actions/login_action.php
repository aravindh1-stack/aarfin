<?php
require_once __DIR__ . '/../core/init.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . URL_ROOT . '/?page=login');
    exit();
}

$username = trim($_POST['username'] ?? '');
$password = $_POST['password'] ?? '';
 $remember = !empty($_POST['remember']);

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

        // Handle remember-me: store token in DB and cookie
        if ($remember) {
            $token = bin2hex(random_bytes(32));
            $expires = date('Y-m-d H:i:s', strtotime('+30 days'));

            $upd = $pdo->prepare('UPDATE users SET remember_token = ?, remember_expires = ? WHERE id = ?');
            $upd->execute([$token, $expires, $user['id']]);

            setcookie('remember_token', $token, [
                'expires' => time() + 60 * 60 * 24 * 30,
                'path' => '/',
                'secure' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on',
                'httponly' => true,
                'samesite' => 'Lax',
            ]);
        } else {
            // If not remembering, clear any existing token and cookie
            $upd = $pdo->prepare('UPDATE users SET remember_token = NULL, remember_expires = NULL WHERE id = ?');
            $upd->execute([$user['id']]);
            if (!empty($_COOKIE['remember_token'])) {
                setcookie('remember_token', '', time() - 3600, '/');
            }
        }

        header('Location: ' . URL_ROOT . '/?page=dashboard');
        exit();
    } else {
        $_SESSION['login_error'] = 'Invalid username or password.';
        if (!empty($_COOKIE['remember_token'])) {
            setcookie('remember_token', '', time() - 3600, '/');
        }
        header('Location: ' . URL_ROOT . '/?page=login');
        exit();
    }
} catch (Exception $e) {
    $_SESSION['login_error'] = 'Login failed. Please try again.';
    header('Location: ' . URL_ROOT . '/?page=login');
    exit();
}
