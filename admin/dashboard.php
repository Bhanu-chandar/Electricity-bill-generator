<?php

require_once __DIR__ . '/../includes/auth.php';
requireAdmin();

$db = getDB();

$stats = [];

$stmt = $db->query("SELECT COUNT(*) as count FROM consumers WHERE is_active = 1");
$stats['consumers'] = $stmt->fetch()['count'];

$stmt = $db->query("SELECT COUNT(*) as count FROM users WHERE role = 'employee' AND is_active = 1");
$stats['employees'] = $stmt->fetch()['count'];

$stmt = $db->query("SELECT COUNT(*) as count FROM bills");
$stats['bills'] = $stmt->fetch()['count'];

$stmt = $db->query("SELECT COUNT(*) as count FROM bills WHERE is_paid = 0");
$stats['unpaid'] = $stmt->fetch()['count'];

$stmt = $db->query("SELECT COALESCE(SUM(grand_total), 0) as total FROM bills WHERE is_paid = 1");
$stats['revenue'] = $stmt->fetch()['total'];

$stmt = $db->query("SELECT COALESCE(SUM(grand_total), 0) as total FROM bills WHERE is_paid = 0");
$stats['pending'] = $stmt->fetch()['total'];

$stmt = $db->query("
    SELECT b.*, c.name 
    FROM bills b 
    JOIN consumers c ON b.consumer_id = c.id 
    ORDER BY b.generated_at DESC 
    LIMIT 10
");
$recentBills = $stmt->fetchAll();

$stmt = $db->query("
    SELECT category, COUNT(*) as count 
    FROM consumers 
    WHERE is_active = 1 
    GROUP BY category
");
$categories = $stmt->fetchAll();

$flash = getFlashMessage();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Electricity Bill Generator</title>
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
                <li><a href="/admin/dashboard.php" class="active"><span class="nav-icon">📊</span> Dashboard</a></li>
                <li><a href="/admin/add_user.php"><span class="nav-icon">👤</span> Add Consumer</a></li>
                <li><a href="/admin/add_employee.php"><span class="nav-icon">👷</span> Add Employee</a></li>
                <li><a href="/admin/manage_bills.php"><span class="nav-icon">📄</span> Manage Bills</a></li>
                <li><a href="/admin/view_all.php"><span class="nav-icon">📋</span> View All Data</a></li>
                <li><a href="/logout.php"><span class="nav-icon">🚪</span> Logout</a></li>
            </ul>
        </aside>
        
        <main class="main-content">
            <div class="page-header">
                <h1 class="page-title">Dashboard</h1>
                <span class="text-muted">Welcome back, <?php echo sanitize($_SESSION['user_name']); ?>!</span>
            </div>
            
            <?php if ($flash): ?>
                <div class="alert alert-<?php echo $flash['type']; ?>">
                    <?php echo $flash['message']; ?>
                </div>
            <?php endif; ?>
            
            <div class="stats-grid">
                <div class="stat-card">
                    <span class="stat-icon">👥</span>
                    <div class="stat-value"><?php echo number_format($stats['consumers']); ?></div>
                    <div class="stat-label">Total Consumers</div>
                </div>
                
                <div class="stat-card success">
                    <span class="stat-icon">👷</span>
                    <div class="stat-value"><?php echo number_format($stats['employees']); ?></div>
                    <div class="stat-label">Employees</div>
                </div>
                
                <div class="stat-card warning">
                    <span class="stat-icon">📄</span>
                    <div class="stat-value"><?php echo number_format($stats['bills']); ?></div>
                    <div class="stat-label">Total Bills</div>
                </div>
                
                <div class="stat-card danger">
                    <span class="stat-icon">⏳</span>
                    <div class="stat-value"><?php echo number_format($stats['unpaid']); ?></div>
                    <div class="stat-label">Unpaid Bills</div>
                </div>
                
                <div class="stat-card success">
                    <span class="stat-icon">💰</span>
                    <div class="stat-value"><?php echo formatCurrency($stats['revenue']); ?></div>
                    <div class="stat-label">Total Revenue</div>
                </div>
                
                <div class="stat-card purple">
                    <span class="stat-icon">📊</span>
                    <div class="stat-value"><?php echo formatCurrency($stats['pending']); ?></div>
                    <div class="stat-label">Pending Amount</div>
                </div>
            </div>
            
            <div class="form-row">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Consumer Categories</h3>
                    </div>
                    <?php if (empty($categories)): ?>
                        <div class="empty-state">
                            <p>No consumers registered yet</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($categories as $cat): ?>
                            <div class="bill-row">
                                <span class="label">
                                    <span class="badge <?php echo getCategoryBadge($cat['category']); ?>">
                                        <?php echo getCategoryName($cat['category']); ?>
                                    </span>
                                </span>
                                <span class="value"><?php echo $cat['count']; ?> consumers</span>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
                
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Quick Actions</h3>
                    </div>
                    <div class="btn-group" style="flex-direction: column;">
                        <a href="/admin/add_user.php" class="btn btn-primary">
                            <span>👤</span> Add New Consumer
                        </a>
                        <a href="/admin/add_employee.php" class="btn btn-success">
                            <span>👷</span> Add New Employee
                        </a>
                        <a href="/admin/manage_bills.php" class="btn btn-warning">
                            <span>📄</span> Manage Bills
                        </a>
                    </div>
                </div>
            </div>
            
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Recent Bills</h3>
                    <a href="/admin/manage_bills.php" class="btn btn-sm btn-secondary">View All</a>
                </div>
                
                <?php if (empty($recentBills)): ?>
                    <div class="empty-state">
                        <div class="icon">📄</div>
                        <h3>No Bills Yet</h3>
                        <p>Bills will appear here once employees record meter readings</p>
                    </div>
                <?php else: ?>
                    <div class="table-wrapper">
                        <table>
                            <thead>
                                <tr>
                                    <th>Bill No.</th>
                                    <th>Consumer</th>
                                    <th>Service No.</th>
                                    <th>Amount</th>
                                    <th>Status</th>
                                    <th>Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recentBills as $bill): ?>
                                    <tr>
                                        <td><strong><?php echo sanitize($bill['bill_number']); ?></strong></td>
                                        <td><?php echo sanitize($bill['name']); ?></td>
                                        <td><code><?php echo sanitize($bill['service_number']); ?></code></td>
                                        <td><?php echo formatCurrency($bill['grand_total']); ?></td>
                                        <td>
                                            <?php if ($bill['is_paid']): ?>
                                                <span class="badge badge-success">✓ Paid</span>
                                            <?php else: ?>
                                                <span class="badge badge-danger">✗ Unpaid</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo formatDate($bill['generated_at']); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </main>
    </div>
</body>
</html>
