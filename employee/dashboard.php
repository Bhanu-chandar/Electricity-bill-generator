<?php

require_once __DIR__ . '/../includes/auth.php';
requireEmployee();

$db = getDB();

$stmt = $db->prepare("SELECT COUNT(*) as count FROM readings WHERE recorded_by = ?");
$stmt->execute([$_SESSION['user_id']]);
$totalReadings = $stmt->fetch()['count'];

$stmt = $db->prepare("SELECT COUNT(*) as count FROM readings WHERE recorded_by = ? AND DATE(reading_date) = CURDATE()");
$stmt->execute([$_SESSION['user_id']]);
$todayReadings = $stmt->fetch()['count'];

$stmt = $db->prepare("
    SELECT r.*, c.name, c.service_number, c.category
    FROM readings r
    JOIN consumers c ON r.consumer_id = c.id
    WHERE r.recorded_by = ?
    ORDER BY r.created_at DESC
    LIMIT 10
");
$stmt->execute([$_SESSION['user_id']]);
$recentReadings = $stmt->fetchAll();

$stmt = $db->query("SELECT COUNT(*) as count FROM consumers WHERE is_active = 1");
$totalConsumers = $stmt->fetch()['count'];

$flash = getFlashMessage();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Employee Dashboard - Electricity Bill Generator</title>
    <link rel="stylesheet" href="/css/style.css">
</head>
<body>
    <div class="page-wrapper">
        <aside class="sidebar">
            <div class="sidebar-header">
                <h2><span class="logo-icon">⚡</span> EBG Employee</h2>
            </div>
            
            <div class="sidebar-user">
                <div class="user-name"><?php echo sanitize($_SESSION['user_name']); ?></div>
                <div class="user-role"><?php echo ucfirst($_SESSION['user_role']); ?></div>
            </div>
            
            <ul class="sidebar-nav">
                <li><a href="/employee/dashboard.php" class="active"><span class="nav-icon">📊</span> Dashboard</a></li>
                <li><a href="/employee/add_reading.php"><span class="nav-icon">📝</span> Add Reading</a></li>
                <li><a href="/logout.php"><span class="nav-icon">🚪</span> Logout</a></li>
            </ul>
        </aside>
        
        <main class="main-content">
            <div class="page-header">
                <h1 class="page-title">Dashboard</h1>
                <span class="text-muted">Welcome, <?php echo sanitize($_SESSION['user_name']); ?>!</span>
            </div>
            
            <?php if ($flash): ?>
                <div class="alert alert-<?php echo $flash['type']; ?>">
                    <?php echo $flash['message']; ?>
                </div>
            <?php endif; ?>
            
            <div class="stats-grid">
                <div class="stat-card">
                    <span class="stat-icon">📝</span>
                    <div class="stat-value"><?php echo number_format($totalReadings); ?></div>
                    <div class="stat-label">Total Readings</div>
                </div>
                
                <div class="stat-card success">
                    <span class="stat-icon">📅</span>
                    <div class="stat-value"><?php echo number_format($todayReadings); ?></div>
                    <div class="stat-label">Today's Readings</div>
                </div>
                
                <div class="stat-card warning">
                    <span class="stat-icon">👥</span>
                    <div class="stat-value"><?php echo number_format($totalConsumers); ?></div>
                    <div class="stat-label">Total Consumers</div>
                </div>
            </div>
            
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Quick Actions</h3>
                </div>
                <a href="/employee/add_reading.php" class="btn btn-primary btn-lg">
                    <span>📝</span> Record New Meter Reading
                </a>
            </div>
            
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Your Recent Readings</h3>
                </div>
                
                <?php if (empty($recentReadings)): ?>
                    <div class="empty-state">
                        <div class="icon">📝</div>
                        <h3>No Readings Yet</h3>
                        <p>You haven't recorded any meter readings yet</p>
                        <a href="/employee/add_reading.php" class="btn btn-primary">Record First Reading</a>
                    </div>
                <?php else: ?>
                    <div class="table-wrapper">
                        <table>
                            <thead>
                                <tr>
                                    <th>Service No.</th>
                                    <th>Consumer</th>
                                    <th>Category</th>
                                    <th>Reading</th>
                                    <th>Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recentReadings as $reading): ?>
                                    <tr>
                                        <td><code><?php echo sanitize($reading['service_number']); ?></code></td>
                                        <td><?php echo sanitize($reading['name']); ?></td>
                                        <td>
                                            <span class="badge <?php echo getCategoryBadge($reading['category']); ?>">
                                                <?php echo ucfirst($reading['category']); ?>
                                            </span>
                                        </td>
                                        <td><strong><?php echo number_format($reading['reading_value']); ?></strong> units</td>
                                        <td><?php echo formatDate($reading['reading_date']); ?></td>
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
