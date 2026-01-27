<?php

require_once __DIR__ . '/../includes/auth.php';
requireAdmin();

$db = getDB();

updateOverdueFines();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['toggle_payment'])) {
    $billId = (int)$_POST['bill_id'];
    $newStatus = (int)$_POST['new_status'];
    
    try {
        if ($newStatus == 1) {
            $stmt = $db->prepare("
                UPDATE bills SET is_paid = 1, paid_date = CURDATE(), paid_marked_by = ?
                WHERE id = ?
            ");
            $stmt->execute([$_SESSION['user_id'], $billId]);
            setFlashMessage('success', 'Bill marked as PAID');
        } else {
            $stmt = $db->prepare("
                UPDATE bills SET is_paid = 0, paid_date = NULL, paid_marked_by = NULL
                WHERE id = ?
            ");
            $stmt->execute([$billId]);
            setFlashMessage('success', 'Bill marked as UNPAID');
        }
    } catch (Exception $e) {
        setFlashMessage('danger', 'Failed to update payment status');
    }
    
    header('Location: /admin/manage_bills.php');
    exit;
}

$filter = $_GET['filter'] ?? 'all';
$search = $_GET['search'] ?? '';

$whereConditions = [];
$params = [];

if ($filter === 'paid') {
    $whereConditions[] = 'b.is_paid = 1';
} elseif ($filter === 'unpaid') {
    $whereConditions[] = 'b.is_paid = 0';
} elseif ($filter === 'overdue') {
    $whereConditions[] = 'b.is_paid = 0 AND b.due_date_without_fine < CURDATE()';
}

if (!empty($search)) {
    $whereConditions[] = '(b.bill_number LIKE ? OR b.service_number LIKE ? OR c.name LIKE ?)';
    $searchParam = "%$search%";
    $params = array_merge($params, [$searchParam, $searchParam, $searchParam]);
}

$whereClause = '';
if (!empty($whereConditions)) {
    $whereClause = 'WHERE ' . implode(' AND ', $whereConditions);
}

$sql = "
    SELECT b.*, c.name, c.phone, c.address, c.city
    FROM bills b
    JOIN consumers c ON b.consumer_id = c.id
    $whereClause
    ORDER BY b.generated_at DESC
";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$bills = $stmt->fetchAll();

$flash = getFlashMessage();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Bills - Electricity Bill Generator</title>
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
                <li><a href="/admin/add_employee.php"><span class="nav-icon">👷</span> Add Employee</a></li>
                <li><a href="/admin/manage_bills.php" class="active"><span class="nav-icon">📄</span> Manage Bills</a></li>
                <li><a href="/admin/view_all.php"><span class="nav-icon">📋</span> View All Data</a></li>
                <li><a href="/logout.php"><span class="nav-icon">🚪</span> Logout</a></li>
            </ul>
        </aside>
        
        <main class="main-content">
            <div class="page-header">
                <h1 class="page-title">Manage Bills</h1>
                <span class="badge badge-primary"><?php echo count($bills); ?> Bills Found</span>
            </div>
            
            <?php if ($flash): ?>
                <div class="alert alert-<?php echo $flash['type']; ?>">
                    <?php echo $flash['message']; ?>
                </div>
            <?php endif; ?>
            
            <div class="card">
                <form method="GET" action="" class="d-flex gap-2" style="flex-wrap: wrap;">
                    <div class="form-group" style="flex: 1; min-width: 200px; margin-bottom: 0;">
                        <input type="text" 
                               name="search" 
                               class="form-control" 
                               placeholder="Search by Bill No, Service No, or Name"
                               value="<?php echo sanitize($search); ?>">
                    </div>
                    
                    <div class="form-group" style="width: 200px; margin-bottom: 0;">
                        <select name="filter" class="form-control" onchange="this.form.submit()">
                            <option value="all" <?php echo $filter === 'all' ? 'selected' : ''; ?>>All Bills</option>
                            <option value="paid" <?php echo $filter === 'paid' ? 'selected' : ''; ?>>Paid Only</option>
                            <option value="unpaid" <?php echo $filter === 'unpaid' ? 'selected' : ''; ?>>Unpaid Only</option>
                            <option value="overdue" <?php echo $filter === 'overdue' ? 'selected' : ''; ?>>Overdue</option>
                        </select>
                    </div>
                    
                    <button type="submit" class="btn btn-primary">Search</button>
                    <a href="/admin/manage_bills.php" class="btn btn-secondary">Reset</a>
                </form>
            </div>
            
            <div class="card">
                <?php if (empty($bills)): ?>
                    <div class="empty-state">
                        <div class="icon">📄</div>
                        <h3>No Bills Found</h3>
                        <p>No bills match your search criteria</p>
                    </div>
                <?php else: ?>
                    <div class="table-wrapper">
                        <table>
                            <thead>
                                <tr>
                                    <th>Bill No.</th>
                                    <th>Consumer</th>
                                    <th>Service No.</th>
                                    <th>Category</th>
                                    <th>Period</th>
                                    <th>Units</th>
                                    <th>Amount</th>
                                    <th>Fine</th>
                                    <th>Total</th>
                                    <th>Due Date</th>
                                    <th>Status</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($bills as $bill): ?>
                                    <?php 
                                        $isOverdue = !$bill['is_paid'] && strtotime($bill['due_date_without_fine']) < time();
                                    ?>
                                    <tr class="<?php echo $isOverdue ? 'text-danger' : ''; ?>">
                                        <td><strong><?php echo sanitize($bill['bill_number']); ?></strong></td>
                                        <td>
                                            <?php echo sanitize($bill['name']); ?>
                                            <br><small class="text-muted"><?php echo sanitize($bill['phone']); ?></small>
                                        </td>
                                        <td><code><?php echo sanitize($bill['service_number']); ?></code></td>
                                        <td>
                                            <span class="badge <?php echo getCategoryBadge($bill['category']); ?>">
                                                <?php echo ucfirst($bill['category']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php echo formatDate($bill['billing_start_date']); ?>
                                            <br><small>to <?php echo formatDate($bill['billing_end_date']); ?></small>
                                        </td>
                                        <td><?php echo number_format($bill['units_consumed']); ?></td>
                                        <td><?php echo formatCurrency($bill['total_amount']); ?></td>
                                        <td>
                                            <?php if ($bill['fine_amount'] > 0): ?>
                                                <span class="text-danger"><?php echo formatCurrency($bill['fine_amount']); ?></span>
                                            <?php else: ?>
                                                -
                                            <?php endif; ?>
                                        </td>
                                        <td><strong><?php echo formatCurrency($bill['grand_total']); ?></strong></td>
                                        <td>
                                            <?php echo formatDate($bill['due_date_without_fine']); ?>
                                            <?php if ($isOverdue): ?>
                                                <br><small class="text-danger">OVERDUE</small>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($bill['is_paid']): ?>
                                                <span class="status-paid" title="Paid">✓</span>
                                            <?php else: ?>
                                                <span class="status-unpaid" title="Unpaid">✗</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <form method="POST" action="" style="display: inline;">
                                                <input type="hidden" name="toggle_payment" value="1">
                                                <input type="hidden" name="bill_id" value="<?php echo $bill['id']; ?>">
                                                <?php if ($bill['is_paid']): ?>
                                                    <input type="hidden" name="new_status" value="0">
                                                    <button type="submit" class="btn btn-sm btn-danger" 
                                                            onclick="return confirm('Mark this bill as UNPAID?')">
                                                        Mark Unpaid
                                                    </button>
                                                <?php else: ?>
                                                    <input type="hidden" name="new_status" value="1">
                                                    <button type="submit" class="btn btn-sm btn-success">
                                                        Mark Paid
                                                    </button>
                                                <?php endif; ?>
                                            </form>
                                            <a href="/admin/view_bill.php?id=<?php echo $bill['id']; ?>" 
                                               class="btn btn-sm btn-secondary">View</a>
                                        </td>
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
