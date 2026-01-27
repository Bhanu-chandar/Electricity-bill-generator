<?php

require_once __DIR__ . '/../includes/auth.php';
requireAdmin();

$db = getDB();

$tab = $_GET['tab'] ?? 'consumers';

$stmt = $db->query("SELECT * FROM consumers ORDER BY created_at DESC");
$consumers = $stmt->fetchAll();

$stmt = $db->query("SELECT * FROM users WHERE role = 'employee' ORDER BY created_at DESC");
$employees = $stmt->fetchAll();

$stmt = $db->query("SELECT * FROM users WHERE role = 'user' ORDER BY created_at DESC");
$users = $stmt->fetchAll();

$flash = getFlashMessage();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View All Data - Electricity Bill Generator</title>
    <link rel="stylesheet" href="/css/style.css">
    <style>
        .tabs {
            display: flex;
            gap: 0.5rem;
            margin-bottom: 1.5rem;
            border-bottom: 1px solid var(--border-color);
            padding-bottom: 1rem;
        }
        .tab-btn {
            padding: 0.75rem 1.5rem;
            background: var(--bg-tertiary);
            border: 1px solid var(--border-color);
            border-radius: var(--radius) var(--radius) 0 0;
            color: var(--text-secondary);
            cursor: pointer;
            transition: var(--transition);
            text-decoration: none;
        }
        .tab-btn:hover {
            background: var(--bg-secondary);
            color: var(--text-primary);
            text-decoration: none;
        }
        .tab-btn.active {
            background: var(--accent-primary);
            color: #fff;
            border-color: var(--accent-primary);
        }
        .tab-content {
            display: none;
        }
        .tab-content.active {
            display: block;
        }
    </style>
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
                <li><a href="/admin/add_employee.php"><span class="nav-icon">👷</span> Add Employee</a></li>
                <li><a href="/admin/manage_bills.php"><span class="nav-icon">📄</span> Manage Bills</a></li>
                <li><a href="/admin/view_all.php" class="active"><span class="nav-icon">📋</span> View All Data</a></li>
                <li><a href="/logout.php"><span class="nav-icon">🚪</span> Logout</a></li>
            </ul>
        </aside>
        
        <main class="main-content">
            <div class="page-header">
                <h1 class="page-title">View All Data</h1>
            </div>
            
            <?php if ($flash): ?>
                <div class="alert alert-<?php echo $flash['type']; ?>">
                    <?php echo $flash['message']; ?>
                </div>
            <?php endif; ?>
            
            <div class="tabs">
                <a href="?tab=consumers" class="tab-btn <?php echo $tab === 'consumers' ? 'active' : ''; ?>">
                    👥 Consumers (<?php echo count($consumers); ?>)
                </a>
                <a href="?tab=employees" class="tab-btn <?php echo $tab === 'employees' ? 'active' : ''; ?>">
                    👷 Employees (<?php echo count($employees); ?>)
                </a>
                <a href="?tab=users" class="tab-btn <?php echo $tab === 'users' ? 'active' : ''; ?>">
                    🔐 User Accounts (<?php echo count($users); ?>)
                </a>
            </div>
            
            <div class="tab-content <?php echo $tab === 'consumers' ? 'active' : ''; ?>" id="consumers">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">All Consumers</h3>
                        <a href="/admin/add_user.php" class="btn btn-primary btn-sm">+ Add Consumer</a>
                    </div>
                    
                    <?php if (empty($consumers)): ?>
                        <div class="empty-state">
                            <div class="icon">👥</div>
                            <h3>No Consumers</h3>
                            <p>No consumers have been registered yet</p>
                            <a href="/admin/add_user.php" class="btn btn-primary">Add First Consumer</a>
                        </div>
                    <?php else: ?>
                        <div class="table-wrapper">
                            <table>
                                <thead>
                                    <tr>
                                        <th>Service No.</th>
                                        <th>Name</th>
                                        <th>Category</th>
                                        <th>Phone</th>
                                        <th>Address</th>
                                        <th>Meter No.</th>
                                        <th>Connected</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($consumers as $consumer): ?>
                                        <tr>
                                            <td><code><strong><?php echo sanitize($consumer['service_number']); ?></strong></code></td>
                                            <td><?php echo sanitize($consumer['name']); ?></td>
                                            <td>
                                                <span class="badge <?php echo getCategoryBadge($consumer['category']); ?>">
                                                    <?php echo ucfirst($consumer['category']); ?>
                                                </span>
                                            </td>
                                            <td><?php echo sanitize($consumer['phone']); ?></td>
                                            <td>
                                                <?php echo sanitize($consumer['city']); ?> - <?php echo sanitize($consumer['pincode']); ?>
                                            </td>
                                            <td><?php echo sanitize($consumer['meter_number']); ?></td>
                                            <td><?php echo formatDate($consumer['connection_date']); ?></td>
                                            <td>
                                                <?php if ($consumer['is_active']): ?>
                                                    <span class="badge badge-success">Active</span>
                                                <?php else: ?>
                                                    <span class="badge badge-danger">Inactive</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="tab-content <?php echo $tab === 'employees' ? 'active' : ''; ?>" id="employees">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">All Employees</h3>
                        <a href="/admin/add_employee.php" class="btn btn-primary btn-sm">+ Add Employee</a>
                    </div>
                    
                    <?php if (empty($employees)): ?>
                        <div class="empty-state">
                            <div class="icon">👷</div>
                            <h3>No Employees</h3>
                            <p>No employees have been registered yet</p>
                            <a href="/admin/add_employee.php" class="btn btn-primary">Add First Employee</a>
                        </div>
                    <?php else: ?>
                        <div class="table-wrapper">
                            <table>
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Name</th>
                                        <th>Username</th>
                                        <th>Email</th>
                                        <th>Phone</th>
                                        <th>Joined</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($employees as $emp): ?>
                                        <tr>
                                            <td><?php echo $emp['id']; ?></td>
                                            <td><strong><?php echo sanitize($emp['name']); ?></strong></td>
                                            <td><code><?php echo sanitize($emp['username']); ?></code></td>
                                            <td><?php echo sanitize($emp['email'] ?: '-'); ?></td>
                                            <td><?php echo sanitize($emp['phone'] ?: '-'); ?></td>
                                            <td><?php echo formatDate($emp['created_at']); ?></td>
                                            <td>
                                                <?php if ($emp['is_active']): ?>
                                                    <span class="badge badge-success">Active</span>
                                                <?php else: ?>
                                                    <span class="badge badge-danger">Inactive</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="tab-content <?php echo $tab === 'users' ? 'active' : ''; ?>" id="users">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Consumer Login Accounts</h3>
                    </div>
                    
                    <?php if (empty($users)): ?>
                        <div class="empty-state">
                            <div class="icon">🔐</div>
                            <h3>No User Accounts</h3>
                            <p>No consumers have login accounts yet</p>
                        </div>
                    <?php else: ?>
                        <div class="table-wrapper">
                            <table>
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Name</th>
                                        <th>Username</th>
                                        <th>Email</th>
                                        <th>Phone</th>
                                        <th>Created</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($users as $user): ?>
                                        <tr>
                                            <td><?php echo $user['id']; ?></td>
                                            <td><strong><?php echo sanitize($user['name']); ?></strong></td>
                                            <td><code><?php echo sanitize($user['username']); ?></code></td>
                                            <td><?php echo sanitize($user['email'] ?: '-'); ?></td>
                                            <td><?php echo sanitize($user['phone'] ?: '-'); ?></td>
                                            <td><?php echo formatDate($user['created_at']); ?></td>
                                            <td>
                                                <?php if ($user['is_active']): ?>
                                                    <span class="badge badge-success">Active</span>
                                                <?php else: ?>
                                                    <span class="badge badge-danger">Inactive</span>
                                                <?php endif; ?>
                                            </td>
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
