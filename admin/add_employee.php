<?php

require_once __DIR__ . '/../includes/auth.php';
requireAdmin();

$db = getDB();
$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'] ?? '';
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    $email = $_POST['email'] ?? '';
    $phone = $_POST['phone'] ?? '';
    
    $nameValidation = validateName($name);
    if (!$nameValidation['valid']) {
        $errors['name'] = $nameValidation['message'];
    }
    
    if (empty($username) || strlen($username) < 3) {
        $errors['username'] = 'Username must be at least 3 characters';
    } else {
        $stmt = $db->prepare("SELECT id FROM users WHERE username = ?");
        $stmt->execute([$username]);
        if ($stmt->fetch()) {
            $errors['username'] = 'Username already exists';
        }
    }
    
    if (empty($password) || strlen($password) < 6) {
        $errors['password'] = 'Password must be at least 6 characters';
    }
    
    $emailValidation = validateEmail($email);
    if (!$emailValidation['valid']) {
        $errors['email'] = $emailValidation['message'];
    }
    
    if (!empty($phone)) {
        $phoneValidation = validatePhone($phone);
        if (!$phoneValidation['valid']) {
            $errors['phone'] = $phoneValidation['message'];
        }
    }
    
    if (empty($errors)) {
        try {
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            $formattedName = formatName($name);
            
            $stmt = $db->prepare("
                INSERT INTO users (username, password, role, name, email, phone)
                VALUES (?, ?, 'employee', ?, ?, ?)
            ");
            $stmt->execute([
                $username,
                $hashedPassword,
                $formattedName,
                $emailValidation['value'],
                isset($phoneValidation) ? $phoneValidation['value'] : ''
            ]);
            
            $success = "Employee <strong>" . sanitize($formattedName) . "</strong> has been registered successfully!";
            $_POST = [];
            
        } catch (Exception $e) {
            $errors['general'] = 'Failed to create employee: ' . $e->getMessage();
        }
    }
}

$stmt = $db->query("SELECT * FROM users WHERE role = 'employee' ORDER BY created_at DESC");
$employees = $stmt->fetchAll();

$flash = getFlashMessage();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Employee - Electricity Bill Generator</title>
    <link rel="stylesheet" href="/css/style.css">
</head>
<body>
    <div class="page-wrapper">
        <aside class="sidebar">
            <div class="sidebar-header">
                <h2><span class="logo-icon">⚡</span> EBG Admin</h2>
            </div>
            
            <div class="sidebar-user">
                <div class="user-name"><?php echo sanitize($_SESSION['user_name']); ?></div>
                <div class="user-role"><?php echo ucfirst($_SESSION['user_role']); ?></div>
            </div>
            
            <ul class="sidebar-nav">
                <li><a href="/admin/dashboard.php"><span class="nav-icon">📊</span> Dashboard</a></li>
                <li><a href="/admin/add_user.php"><span class="nav-icon">👤</span> Add Consumer</a></li>
                <li><a href="/admin/add_employee.php" class="active"><span class="nav-icon">👷</span> Add Employee</a></li>
                <li><a href="/admin/manage_bills.php"><span class="nav-icon">📄</span> Manage Bills</a></li>
                <li><a href="/admin/view_all.php"><span class="nav-icon">📋</span> View All Data</a></li>
                <li><a href="/logout.php"><span class="nav-icon">🚪</span> Logout</a></li>
            </ul>
        </aside>
        
        <main class="main-content">
            <div class="page-header">
                <h1 class="page-title">Manage Employees</h1>
            </div>
            
            <?php if ($flash): ?>
                <div class="alert alert-<?php echo $flash['type']; ?>">
                    <?php echo $flash['message']; ?>
                </div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert alert-success">
                    <?php echo $success; ?>
                </div>
            <?php endif; ?>
            
            <?php if (isset($errors['general'])): ?>
                <div class="alert alert-danger">
                    <?php echo $errors['general']; ?>
                </div>
            <?php endif; ?>
            
            <div class="form-row">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Add New Employee</h3>
                    </div>
                    
                    <form method="POST" action="">
                        <div class="form-group">
                            <label class="form-label" for="name">
                                Full Name <span class="required">*</span>
                            </label>
                            <input type="text" 
                                   id="name" 
                                   name="name" 
                                   class="form-control <?php echo isset($errors['name']) ? 'is-invalid' : ''; ?>"
                                   value="<?php echo sanitize($_POST['name'] ?? ''); ?>"
                                   maxlength="32"
                                   pattern="^[a-zA-Z\s.\-]+$"
                                   required>
                            <?php if (isset($errors['name'])): ?>
                                <div class="invalid-feedback"><?php echo $errors['name']; ?></div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label" for="username">
                                Username <span class="required">*</span>
                            </label>
                            <input type="text" 
                                   id="username" 
                                   name="username" 
                                   class="form-control <?php echo isset($errors['username']) ? 'is-invalid' : ''; ?>"
                                   value="<?php echo sanitize($_POST['username'] ?? ''); ?>"
                                   minlength="3"
                                   required>
                            <?php if (isset($errors['username'])): ?>
                                <div class="invalid-feedback"><?php echo $errors['username']; ?></div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label" for="password">
                                Password <span class="required">*</span>
                            </label>
                            <input type="password" 
                                   id="password" 
                                   name="password" 
                                   class="form-control <?php echo isset($errors['password']) ? 'is-invalid' : ''; ?>"
                                   minlength="6"
                                   required>
                            <?php if (isset($errors['password'])): ?>
                                <div class="invalid-feedback"><?php echo $errors['password']; ?></div>
                            <?php endif; ?>
                            <div class="form-text">Minimum 6 characters</div>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label" for="email">Email</label>
                            <input type="email" 
                                   id="email" 
                                   name="email" 
                                   class="form-control <?php echo isset($errors['email']) ? 'is-invalid' : ''; ?>"
                                   value="<?php echo sanitize($_POST['email'] ?? ''); ?>">
                            <?php if (isset($errors['email'])): ?>
                                <div class="invalid-feedback"><?php echo $errors['email']; ?></div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label" for="phone">Phone</label>
                            <input type="tel" 
                                   id="phone" 
                                   name="phone" 
                                   class="form-control <?php echo isset($errors['phone']) ? 'is-invalid' : ''; ?>"
                                   value="<?php echo sanitize($_POST['phone'] ?? ''); ?>"
                                   maxlength="10">
                            <?php if (isset($errors['phone'])): ?>
                                <div class="invalid-feedback"><?php echo $errors['phone']; ?></div>
                            <?php endif; ?>
                        </div>
                        
                        <button type="submit" class="btn btn-primary btn-block">
                            Add Employee
                        </button>
                    </form>
                </div>
                
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Current Employees</h3>
                        <span class="badge badge-primary"><?php echo count($employees); ?> Total</span>
                    </div>
                    
                    <?php if (empty($employees)): ?>
                        <div class="empty-state">
                            <div class="icon">👷</div>
                            <h3>No Employees</h3>
                            <p>Add your first employee using the form</p>
                        </div>
                    <?php else: ?>
                        <div class="table-wrapper">
                            <table>
                                <thead>
                                    <tr>
                                        <th>Name</th>
                                        <th>Username</th>
                                        <th>Status</th>
                                        <th>Joined</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($employees as $emp): ?>
                                        <tr>
                                            <td><strong><?php echo sanitize($emp['name']); ?></strong></td>
                                            <td><code><?php echo sanitize($emp['username']); ?></code></td>
                                            <td>
                                                <?php if ($emp['is_active']): ?>
                                                    <span class="badge badge-success">Active</span>
                                                <?php else: ?>
                                                    <span class="badge badge-danger">Inactive</span>
                                                <?php endif; ?>
                                            </td>
                                            <td><?php echo formatDate($emp['created_at']); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>
</body>
</html>
