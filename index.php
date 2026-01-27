<?php

require_once __DIR__ . '/includes/auth.php';

if (isLoggedIn()) {
    redirectToDashboard();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = sanitize($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($username) || empty($password)) {
        $error = 'Please enter both username and password';
    } else {
        $result = authenticate($username, $password);
        
        if ($result['success']) {
            redirectToDashboard();
        } else {
            $error = $result['message'];
        }
    }
}

$flash = getFlashMessage();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Electricity Bill Generator</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="login-wrapper">
        <div class="login-container">
            <div class="login-card">
                <div class="login-header">
                    <div class="logo">⚡</div>
                    <h1>Electricity Bill</h1>
                    <p>Generator System</p>
                </div>
                
                <?php if ($flash): ?>
                    <div class="alert alert-<?php echo $flash['type']; ?>">
                        <?php echo $flash['message']; ?>
                    </div>
                <?php endif; ?>
                
                <?php if ($error): ?>
                    <div class="alert alert-danger">
                        <?php echo $error; ?>
                    </div>
                <?php endif; ?>
                
                <form method="POST" action="">
                    <div class="form-group">
                        <label class="form-label" for="username">Username</label>
                        <input type="text" 
                               id="username" 
                               name="username" 
                               class="form-control" 
                               placeholder="Enter your username"
                               value="<?php echo isset($_POST['username']) ? sanitize($_POST['username']) : ''; ?>"
                               required 
                               autofocus>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label" for="password">Password</label>
                        <input type="password" 
                               id="password" 
                               name="password" 
                               class="form-control" 
                               placeholder="Enter your password"
                               required>
                    </div>
                    
                    <button type="submit" class="btn btn-primary btn-block btn-lg mt-2">
                        Login
                    </button>
                </form>
                
                <div class="text-center mt-3 text-muted" style="font-size: 0.85rem;">
                    <p>Default Admin: admin / admin123</p>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
