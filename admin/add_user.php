<?php

require_once __DIR__ . '/../includes/auth.php';
requireAdmin();

$db = getDB();
$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'] ?? '';
    $address = $_POST['address'] ?? '';
    $city = $_POST['city'] ?? '';
    $pincode = $_POST['pincode'] ?? '';
    $phone = $_POST['phone'] ?? '';
    $email = $_POST['email'] ?? '';
    $category = $_POST['category'] ?? '';
    $meterNumber = $_POST['meter_number'] ?? '';
    $connectionDate = $_POST['connection_date'] ?? '';
    $createLogin = isset($_POST['create_login']);
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    
    $nameValidation = validateName($name);
    if (!$nameValidation['valid']) {
        $errors['name'] = $nameValidation['message'];
    }
    
    if (empty($address)) {
        $errors['address'] = 'Address is required';
    }
    
    if (empty($city)) {
        $errors['city'] = 'City is required';
    }
    
    $pincodeValidation = validatePincode($pincode);
    if (!$pincodeValidation['valid']) {
        $errors['pincode'] = $pincodeValidation['message'];
    }
    
    $phoneValidation = validatePhone($phone);
    if (!$phoneValidation['valid']) {
        $errors['phone'] = $phoneValidation['message'];
    }
    
    $emailValidation = validateEmail($email);
    if (!$emailValidation['valid']) {
        $errors['email'] = $emailValidation['message'];
    }
    
    if (!in_array($category, ['household', 'commercial', 'industry'])) {
        $errors['category'] = 'Please select a valid category';
    }
    
    if (empty($meterNumber)) {
        $errors['meter_number'] = 'Meter number is required';
    }
    
    if (empty($connectionDate)) {
        $errors['connection_date'] = 'Connection date is required';
    }
    
    if ($createLogin) {
        if (empty($username)) {
            $errors['username'] = 'Username is required';
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
    }
    
    if (empty($errors)) {
        $db->beginTransaction();
        
        try {
            $userId = null;
            
            if ($createLogin) {
                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                $formattedName = formatName($name);
                
                $stmt = $db->prepare("
                    INSERT INTO users (username, password, role, name, email, phone)
                    VALUES (?, ?, 'user', ?, ?, ?)
                ");
                $stmt->execute([
                    $username, 
                    $hashedPassword, 
                    $formattedName, 
                    $emailValidation['value'], 
                    $phoneValidation['value']
                ]);
                $userId = $db->lastInsertId();
            }
            
            $serviceNumber = generateServiceNumber($category);
            
            $stmt = $db->prepare("
                INSERT INTO consumers (
                    user_id, service_number, name, address, city, pincode, 
                    phone, email, category, meter_number, connection_date
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $userId,
                $serviceNumber,
                formatName($name),
                sanitize($address),
                sanitize($city),
                $pincodeValidation['value'],
                $phoneValidation['value'],
                $emailValidation['value'],
                $category,
                sanitize($meterNumber),
                $connectionDate
            ]);
            
            $db->commit();
            
            $success = "Consumer registered successfully! Service Number: <strong>$serviceNumber</strong>";
            
            $_POST = [];
            
        } catch (Exception $e) {
            $db->rollBack();
            $errors['general'] = 'Failed to register consumer: ' . $e->getMessage();
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
    <title>Add Consumer - Electricity Bill Generator</title>
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
                <li><a href="/admin/add_user.php" class="active"><span class="nav-icon">👤</span> Add Consumer</a></li>
                <li><a href="/admin/add_employee.php"><span class="nav-icon">👷</span> Add Employee</a></li>
                <li><a href="/admin/manage_bills.php"><span class="nav-icon">📄</span> Manage Bills</a></li>
                <li><a href="/admin/view_all.php"><span class="nav-icon">📋</span> View All Data</a></li>
                <li><a href="/logout.php"><span class="nav-icon">🚪</span> Logout</a></li>
            </ul>
        </aside>
        
        <main class="main-content">
            <div class="page-header">
                <h1 class="page-title">Add New Consumer</h1>
                <a href="/admin/view_all.php" class="btn btn-secondary">View All Consumers</a>
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
            
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Consumer Registration Form</h3>
                </div>
                
                <form method="POST" action="" id="consumerForm">
                    <h4 class="mb-2">Personal Information</h4>
                    
                    <div class="form-row">
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
                                   title="Only letters, spaces, dots, and hyphens allowed (max 32 characters)"
                                   required>
                            <?php if (isset($errors['name'])): ?>
                                <div class="invalid-feedback"><?php echo $errors['name']; ?></div>
                            <?php endif; ?>
                            <div class="form-text">Max 32 characters. Names will be auto-formatted (e.g., "n. naveen" → "N. Naveen")</div>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label" for="phone">
                                Phone Number <span class="required">*</span>
                            </label>
                            <input type="tel" 
                                   id="phone" 
                                   name="phone" 
                                   class="form-control <?php echo isset($errors['phone']) ? 'is-invalid' : ''; ?>"
                                   value="<?php echo sanitize($_POST['phone'] ?? ''); ?>"
                                   pattern="[6-9][0-9]{9}"
                                   maxlength="10"
                                   title="10-digit phone number starting with 6-9"
                                   required>
                            <?php if (isset($errors['phone'])): ?>
                                <div class="invalid-feedback"><?php echo $errors['phone']; ?></div>
                            <?php endif; ?>
                            <div class="form-text">10-digit mobile number</div>
                        </div>
                    </div>
                    
                    <div class="form-row">
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
                            <label class="form-label" for="category">
                                Connection Category <span class="required">*</span>
                            </label>
                            <select id="category" name="category" class="form-control <?php echo isset($errors['category']) ? 'is-invalid' : ''; ?>" required>
                                <option value="">-- Select Category --</option>
                                <option value="household" <?php echo (($_POST['category'] ?? '') === 'household') ? 'selected' : ''; ?>>Household (Domestic)</option>
                                <option value="commercial" <?php echo (($_POST['category'] ?? '') === 'commercial') ? 'selected' : ''; ?>>Commercial</option>
                                <option value="industry" <?php echo (($_POST['category'] ?? '') === 'industry') ? 'selected' : ''; ?>>Industrial</option>
                            </select>
                            <?php if (isset($errors['category'])): ?>
                                <div class="invalid-feedback"><?php echo $errors['category']; ?></div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <h4 class="mt-3 mb-2">Address Details</h4>
                    
                    <div class="form-group">
                        <label class="form-label" for="address">
                            Full Address <span class="required">*</span>
                        </label>
                        <textarea id="address" 
                                  name="address" 
                                  class="form-control <?php echo isset($errors['address']) ? 'is-invalid' : ''; ?>"
                                  rows="3"
                                  required><?php echo sanitize($_POST['address'] ?? ''); ?></textarea>
                        <?php if (isset($errors['address'])): ?>
                            <div class="invalid-feedback"><?php echo $errors['address']; ?></div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label" for="city">
                                City <span class="required">*</span>
                            </label>
                            <input type="text" 
                                   id="city" 
                                   name="city" 
                                   class="form-control <?php echo isset($errors['city']) ? 'is-invalid' : ''; ?>"
                                   value="<?php echo sanitize($_POST['city'] ?? ''); ?>"
                                   required>
                            <?php if (isset($errors['city'])): ?>
                                <div class="invalid-feedback"><?php echo $errors['city']; ?></div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label" for="pincode">
                                Pincode <span class="required">*</span>
                            </label>
                            <input type="text" 
                                   id="pincode" 
                                   name="pincode" 
                                   class="form-control <?php echo isset($errors['pincode']) ? 'is-invalid' : ''; ?>"
                                   value="<?php echo sanitize($_POST['pincode'] ?? ''); ?>"
                                   pattern="[1-9][0-9]{5}"
                                   maxlength="6"
                                   title="6-digit pincode"
                                   required>
                            <?php if (isset($errors['pincode'])): ?>
                                <div class="invalid-feedback"><?php echo $errors['pincode']; ?></div>
                            <?php endif; ?>
                            <div class="form-text">6-digit pincode</div>
                        </div>
                    </div>
                    
                    <h4 class="mt-3 mb-2">Connection Details</h4>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label" for="meter_number">
                                Meter Number <span class="required">*</span>
                            </label>
                            <input type="text" 
                                   id="meter_number" 
                                   name="meter_number" 
                                   class="form-control <?php echo isset($errors['meter_number']) ? 'is-invalid' : ''; ?>"
                                   value="<?php echo sanitize($_POST['meter_number'] ?? ''); ?>"
                                   required>
                            <?php if (isset($errors['meter_number'])): ?>
                                <div class="invalid-feedback"><?php echo $errors['meter_number']; ?></div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label" for="connection_date">
                                Connection Date <span class="required">*</span>
                            </label>
                            <input type="date" 
                                   id="connection_date" 
                                   name="connection_date" 
                                   class="form-control <?php echo isset($errors['connection_date']) ? 'is-invalid' : ''; ?>"
                                   value="<?php echo sanitize($_POST['connection_date'] ?? date('Y-m-d')); ?>"
                                   max="<?php echo date('Y-m-d'); ?>"
                                   required>
                            <?php if (isset($errors['connection_date'])): ?>
                                <div class="invalid-feedback"><?php echo $errors['connection_date']; ?></div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <h4 class="mt-3 mb-2">Login Credentials (Optional)</h4>
                    
                    <div class="form-group">
                        <label class="form-label">
                            <input type="checkbox" 
                                   name="create_login" 
                                   id="create_login"
                                   <?php echo isset($_POST['create_login']) ? 'checked' : ''; ?>
                                   onchange="toggleLoginFields()">
                            Create login account for consumer
                        </label>
                        <div class="form-text">If enabled, the consumer can view their bills by logging in</div>
                    </div>
                    
                    <div id="loginFields" class="<?php echo isset($_POST['create_login']) ? '' : 'hidden'; ?>">
                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label" for="username">Username</label>
                                <input type="text" 
                                       id="username" 
                                       name="username" 
                                       class="form-control <?php echo isset($errors['username']) ? 'is-invalid' : ''; ?>"
                                       value="<?php echo sanitize($_POST['username'] ?? ''); ?>">
                                <?php if (isset($errors['username'])): ?>
                                    <div class="invalid-feedback"><?php echo $errors['username']; ?></div>
                                <?php endif; ?>
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label" for="password">Password</label>
                                <input type="password" 
                                       id="password" 
                                       name="password" 
                                       class="form-control <?php echo isset($errors['password']) ? 'is-invalid' : ''; ?>"
                                       minlength="6">
                                <?php if (isset($errors['password'])): ?>
                                    <div class="invalid-feedback"><?php echo $errors['password']; ?></div>
                                <?php endif; ?>
                                <div class="form-text">Minimum 6 characters</div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mt-3">
                        <button type="submit" class="btn btn-primary btn-lg">
                            Register Consumer
                        </button>
                        <button type="reset" class="btn btn-secondary btn-lg">
                            Clear Form
                        </button>
                    </div>
                </form>
            </div>
        </main>
    </div>
    
    <script>
        function toggleLoginFields() {
            const checkbox = document.getElementById('create_login');
            const loginFields = document.getElementById('loginFields');
            
            if (checkbox.checked) {
                loginFields.classList.remove('hidden');
            } else {
                loginFields.classList.add('hidden');
            }
        }
        
        document.getElementById('name').addEventListener('input', function(e) {
        });
    </script>
</body>
</html>
