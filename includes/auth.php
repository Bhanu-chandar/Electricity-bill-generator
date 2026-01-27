<?php

session_start();

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/functions.php';

function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

function getCurrentUser() {
    if (!isLoggedIn()) {
        return null;
    }
    
    $db = getDB();
    $stmt = $db->prepare("SELECT * FROM users WHERE id = ? AND is_active = 1");
    $stmt->execute([$_SESSION['user_id']]);
    return $stmt->fetch();
}


function getUserRole() {
    return $_SESSION['user_role'] ?? null;
}

function hasRole($role) {
    return getUserRole() === $role;
}

function requireLogin() {
    if (!isLoggedIn()) {
        setFlashMessage('danger', 'Please login to continue');
        header('Location: /index.php');
        exit;
    }
}

function requireRole($role) {
    requireLogin();
    
    if (!hasRole($role)) {
        setFlashMessage('danger', 'Access denied. Insufficient permissions.');
        
        $userRole = getUserRole();
        switch ($userRole) {
            case 'admin':
                header('Location: /admin/dashboard.php');
                break;
            case 'employee':
                header('Location: /employee/dashboard.php');
                break;
            case 'user':
                header('Location: /user/dashboard.php');
                break;
            default:
                header('Location: /index.php');
        }
        exit;
    }
}

function requireAdmin() {
    requireRole('admin');
}

function requireEmployee() {
    requireLogin();
    
    $role = getUserRole();
    if ($role !== 'employee' && $role !== 'admin') {
        setFlashMessage('danger', 'Access denied. Insufficient permissions.');
        header('Location: /user/dashboard.php');
        exit;
    }
}

function authenticate($username, $password) {
    $db = getDB();
    
    $stmt = $db->prepare("SELECT * FROM users WHERE username = ? AND is_active = 1");
    $stmt->execute([$username]);
    $user = $stmt->fetch();
    
    if (!$user) {
        return ['success' => false, 'message' => 'Invalid username or password'];
    }
    
    if (!password_verify($password, $user['password'])) {
        return ['success' => false, 'message' => 'Invalid username or password'];
    }
    
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['user_role'] = $user['role'];
    $_SESSION['user_name'] = $user['name'];
    $_SESSION['username'] = $user['username'];
    
    return ['success' => true, 'user' => $user];
}

function logout() {
    $_SESSION = [];
    
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    
    session_destroy();
}

function createUser($username, $password, $role, $name, $email = '', $phone = '') {
    $db = getDB();
    $stmt = $db->prepare("SELECT id FROM users WHERE username = ?");
    $stmt->execute([$username]);
    if ($stmt->fetch()) {
        return ['success' => false, 'message' => 'Username already exists'];
    }
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    
    $formattedName = formatName($name);
    
    try {
        $stmt = $db->prepare("
            INSERT INTO users (username, password, role, name, email, phone)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([$username, $hashedPassword, $role, $formattedName, $email, $phone]);
        
        return ['success' => true, 'user_id' => $db->lastInsertId()];
    } catch (Exception $e) {
        return ['success' => false, 'message' => 'Failed to create user: ' . $e->getMessage()];
    }
}

function redirectToDashboard() {
    $role = getUserRole();
    
    switch ($role) {
        case 'admin':
            header('Location: /admin/dashboard.php');
            break;
        case 'employee':
            header('Location: /employee/dashboard.php');
            break;
        case 'user':
            header('Location: /user/dashboard.php');
            break;
        default:
            header('Location: /index.php');
    }
    exit;
}

function getBaseUrl() {
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'];
    return $protocol . '://' . $host;
}
?>
